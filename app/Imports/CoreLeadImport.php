<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\CoreLead;
use App\Models\DuplicateRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CoreLeadImport implements ToCollection, WithHeadingRow
{
    protected array $duplicateCheckFields = ['email', 'telephone'];

    protected function normalizeExcelDate($value): ?string
    {
        if (blank($value)) return null;

        try {
            return is_numeric($value)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject($value))->toDateString()
                : Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            Log::warning('Invalid date format', ['value' => $value]);
            return null;
        }
    }

    protected function detectDuplicates(array $rowsArray): array
    {
        $valuesToCheck = [];
        foreach ($this->duplicateCheckFields as $field) {
            $valuesToCheck[$field] = collect($rowsArray)->pluck($field)->filter()->unique()->values();
        }

        $existingLookup = [];
        foreach ($this->duplicateCheckFields as $field) {
            $existingLookup[$field] = CoreLead::whereIn($field, $valuesToCheck[$field])
                ->pluck($field)
                ->filter()
                ->unique()
                ->flip()
                ->all();
        }

        $duplicateSummary = [];       // [field][value] => count
        $rowDuplicateMap = [];        // [rowIndex] => [field => value]
        $fileSeen = [];

        foreach ($this->duplicateCheckFields as $field) {
            $fileSeen[$field] = [];
        }

        foreach ($rowsArray as $i => $row) {
            $rowDuplicateMap[$i] = [];
            foreach ($this->duplicateCheckFields as $field) {
                $value = $row[$field] ?? null;
                if (!$value) continue;

                $isDup = false;

                if (isset($existingLookup[$field][$value])) $isDup = true;
                if (isset($fileSeen[$field][$value])) $isDup = true;

                if ($isDup) {
                    $duplicateSummary[$field][$value] = ($duplicateSummary[$field][$value] ?? 0) + 1;
                    $rowDuplicateMap[$i][$field] = $value;
                }

                $fileSeen[$field][$value] = true;
            }
        }

        return [$duplicateSummary, $rowDuplicateMap];
    }

    public function collection(Collection $rows): void
    {
        $userId = Auth::id();
        $now = now();
        $rowsArray = $rows->toArray();

        [$duplicateSummary, $rowDuplicateMap] = $this->detectDuplicates($rowsArray);

        $insertData = [];

        // Step 1: Bulk fetch existing DuplicateRecords
        $flatPairs = [];
        foreach ($duplicateSummary as $field => $values) {
            foreach ($values as $val => $count) {
                $flatPairs[] = ['field_name' => $field, 'duplicate_value' => $val];
            }
        }

        $existingDuplicates = DuplicateRecord::where('table_name', 'core_leads')
            ->where(function ($query) use ($duplicateSummary) {
                foreach ($duplicateSummary as $field => $values) {
                    $query->orWhere(function ($q) use ($field, $values) {
                        $q->where('field_name', $field)
                          ->whereIn('duplicate_value', array_keys($values));
                    });
                }
            })
            ->get();

        $existingMap = [];
        foreach ($existingDuplicates as $record) {
            $existingMap[$record->field_name][$record->duplicate_value] = $record;
        }

        $newRecords = [];
        $duplicateIdMap = [];

        foreach ($duplicateSummary as $field => $values) {
            foreach ($values as $val => $count) {
                if (isset($existingMap[$field][$val])) {
                    $record = $existingMap[$field][$val];
                    $record->count += $count;
                    $record->updated_at = $now;
                    $record->save();
                    $duplicateIdMap[$field][$val] = $record->id;
                } else {
                    $newRecords[] = [
                        'table_name'      => 'core_leads',
                        'field_name'      => $field,
                        'duplicate_value' => $val,
                        'count'           => $count,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
        }

        // Step 2: Bulk insert new DuplicateRecords and fetch their IDs
        if (!empty($newRecords)) {
            DuplicateRecord::insert($newRecords);

            $insertedDuplicates = DuplicateRecord::where('table_name', 'core_leads')
                ->where(function ($query) use ($newRecords) {
                    foreach ($newRecords as $record) {
                        $query->orWhere(function ($q) use ($record) {
                            $q->where('field_name', $record['field_name'])
                              ->where('duplicate_value', $record['duplicate_value']);
                        });
                    }
                })
                ->get();

            foreach ($insertedDuplicates as $record) {
                $duplicateIdMap[$record->field_name][$record->duplicate_value] = $record->id;
            }
        }

        // Step 3: Prepare core lead insert data
        foreach ($rowsArray as $i => $row) {
            $duplicateIds = [];

            foreach ($rowDuplicateMap[$i] as $field => $value) {
                if (isset($duplicateIdMap[$field][$value])) {
                    $duplicateIds[] = $duplicateIdMap[$field][$value];
                }
            }
            
            $insertData[] = [
                'user_id'      => $userId,
                'lead_id'      => $row['lead_id'] ?? null,
                'categories'   => $row['categories'] ?? null,
                'date_added'   => $this->normalizeExcelDate($row['date_added'] ?? null),
                'referrer'     => $row['referrer'] ?? null,
                'first_name'   => $row['first_name'] ?? null,
                'surname'      => $row['surname'] ?? null,
                'email'        => $row['email'] ?? null,
                'telephone'    => $row['telephone'] ?? null,
                'country'      => $row['country'] ?? null,
                'is_duplicate'  => !empty($duplicateIds),
                'duplicate_ids' => !empty($duplicateIds) ? json_encode($duplicateIds) : null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        // Step 4: Bulk insert leads
        foreach (array_chunk($insertData, 1000) as $chunk) {
            CoreLead::insert($chunk);
        }
    }
}
