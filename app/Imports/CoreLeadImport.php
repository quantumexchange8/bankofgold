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
    protected int $importId;
    protected int $totalRows = 0;
    protected int $duplicateCount = 0;
    protected array $duplicateCheckFields = ['email', 'telephone'];

    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    public function getTotalRowCount(): int
    {
        return $this->totalRows;
    }

    public function getDuplicateCount(): int
    {
        return $this->duplicateCount;
    }

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
                ->pluck($field)->filter()->unique()->flip()->all();
        }

        $duplicateSummary = [];
        $rowDuplicateMap = [];
        $fileSeen = array_fill_keys($this->duplicateCheckFields, []);

        foreach ($rowsArray as $i => $row) {
            $rowDuplicateMap[$i] = [];
            foreach ($this->duplicateCheckFields as $field) {
                $value = $row[$field] ?? null;
                if (!$value) continue;

                if (isset($existingLookup[$field][$value]) || isset($fileSeen[$field][$value])) {
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
        $this->totalRows = $rows->count();
        $userId = Auth::id();
        $now = now();
        $rowsArray = $rows->toArray();

        [$duplicateSummary, $rowDuplicateMap] = $this->detectDuplicates($rowsArray);

        $insertData = [];
        $rowIdMap = [];

        $existingDuplicates = DuplicateRecord::where('table_name', 'core_leads')
            ->where(function ($query) use ($duplicateSummary) {
                foreach ($duplicateSummary as $field => $values) {
                    $query->orWhere(function ($q) use ($field, $values) {
                        $q->where('field_name', $field)
                          ->whereIn('duplicate_value', array_keys($values));
                    });
                }
            })->get();

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
                    $record->increment('count', $count);
                    $duplicateIdMap[$field][$val] = $record->id;
                } else {
                    $newRecords[] = [
                        'table_name' => 'core_leads',
                        'field_name' => $field,
                        'duplicate_value' => $val,
                        'count' => $count,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($newRecords)) {
            DB::table('duplicate_records')->insert($newRecords);

            $inserted = DuplicateRecord::where('table_name', 'core_leads')
                ->where(function ($query) use ($newRecords) {
                    foreach ($newRecords as $record) {
                        $query->orWhere(function ($q) use ($record) {
                            $q->where('field_name', $record['field_name'])
                              ->where('duplicate_value', $record['duplicate_value']);
                        });
                    }
                })->get();

            foreach ($inserted as $record) {
                $duplicateIdMap[$record->field_name][$record->duplicate_value] = $record->id;
            }
        }

        foreach ($rowsArray as $i => $row) {
            $duplicateIds = [];

            foreach ($rowDuplicateMap[$i] as $field => $value) {
                if (isset($duplicateIdMap[$field][$value])) {
                    $duplicateIds[] = $duplicateIdMap[$field][$value];
                }
            }

            $rowIdMap[$i] = $duplicateIds;

            $insertData[] = [
                'user_id'      => $userId,
                'import_id'    => $this->importId,
                'lead_id'      => $row['lead_id'] ?? null,
                'categories'   => $row['categories'] ?? null,
                'date_added'   => $this->normalizeExcelDate($row['date_added'] ?? null),
                'referrer'     => $row['referrer'] ?? null,
                'first_name'   => $row['first_name'] ?? null,
                'surname'      => $row['surname'] ?? null,
                'email'        => $row['email'] ?? null,
                'telephone'    => $row['telephone'] ?? null,
                'country'      => $row['country'] ?? null,
                'is_duplicate' => !empty($duplicateIds),
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        $this->duplicateCount = collect($insertData)->where('is_duplicate', true)->count();

        $leadIds = [];
        foreach (array_chunk($insertData, 1000, true) as $chunk) {
            $startIndex = array_key_first($chunk);
            DB::table('core_leads')->insert($chunk);

            $inserted = CoreLead::where('user_id', $userId)
                ->where('import_id', $this->importId)
                ->orderBy('id')
                ->take(count($chunk))
                ->get();

            foreach ($inserted->values() as $offset => $lead) {
                $leadIds[$startIndex + $offset] = $lead->id;
            }
        }

        $linkInserts = [];
        foreach ($rowIdMap as $i => $duplicateIds) {
            $leadId = $leadIds[$i] ?? null;
            if (!$leadId) continue;

            foreach ($duplicateIds as $dupId) {
                $linkInserts[] = [
                    'duplicate_record_id' => $dupId,
                    'related_table'       => 'core_leads',
                    'related_record_id'   => $leadId,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            }
        }

        foreach (array_chunk($linkInserts, 1000) as $chunk) {
            DB::table('duplicate_links')->insert($chunk);
        }
    }
}
