<?php

namespace App\Jobs;

use Throwable;
use Carbon\Carbon;
use App\Models\CoreLead;
use App\Models\DataImport;
use App\Models\DuplicateRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\CoreLeadImport;

class ProcessCoreLeadImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $importId;
    protected string $filePath;

    protected array $groups = [
        'email' => [
            'private_email_1',
            'private_email_2',
            'company_email_1',
            'company_email_2',
            'followup_email',
        ],

        'phone' => [
            'home_telephone_1',
            'home_telephone_2',
            'mobile_telephone_1',
            'mobile_telephone_2',
            'private_fax',
            'office_phone_1',
            'office_phone_2',
            'office_fax',
            'followup_mobile',
        ],
    ];

    protected array $duplicateValues = [];

    protected array $duplicateRecordIds = []; // store affected duplicate record IDs

    // Disable Laravel's queue timeout
    protected $timeout = null;

    public function __construct(int $importId, string $filePath)
    {
        $this->importId = $importId;
        $this->filePath = $filePath;
        $this->queue = 'core_lead_imports';
    }

    public function handle(): void
    {
        // Disable PHP execution timeout (unlimited time)
        ini_set('max_execution_time', 0);  // No timeout for PHP script execution

        try {
            $this->cleanupImportData($this->importId);

            $importRecord = DataImport::findOrFail($this->importId);
            $format = $this->detectFormat($this->filePath);

            $import = new CoreLeadImport($this->importId, $importRecord->user_id);
            Excel::import($import, $this->filePath, null, $format);

            $this->handleDuplicateDetection();
            $this->markDuplicates();

            $duplicateCount = CoreLead::where('import_id', $this->importId)->where('is_duplicate', true)->count();
        
            DataImport::where('id', $this->importId)->update([
                'total_rows'      => $import->getTotalRowCount(),
                'duplicate_count' => $duplicateCount,
                'status'          => 'completed',
                'updated_at'      => now(),
            ]);

            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }

        } catch (Throwable $e) {
            Log::error("Import failed", [
                'error' => $e->getMessage(),
                'import_id' => $this->importId,
            ]);

            DataImport::where('id', $this->importId)->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    // protected function handleDuplicateDetection(): void
    // {
    //     $now = now();
    //     $fields = [
    //         'private_email_1',
    //         'private_email_2',
    //         'home_telephone_1',
    //         'home_telephone_2',
    //         'mobile_telephone_1',
    //         'mobile_telephone_2',
    //         'private_fax',
    //         'company_email_1',
    //         'company_email_2',
    //         'office_phone_1',
    //         'office_phone_2',
    //         'office_fax',
    //         'followup_email',
    //         'followup_mobile',
    //     ];
    //     $duplicateIdMap = [];
    
    //     foreach ($fields as $field) {
    //         $seen = [];
    
    //         // Step 1: Process in chunks & flush every 500 distinct values
    //         CoreLead::where('import_id', $this->importId)
    //             ->whereNotNull($field)
    //             ->select('id', $field)
    //             ->chunkById(500, function ($chunk) use (&$seen, $field, &$duplicateIdMap, $now) {
    //                 foreach ($chunk as $lead) {
    //                     $val = $lead->$field;
    //                     if ($val !== null && $val !== '') {
    //                         $seen[$val] = true;
    //                     }
    
    //                     if (count($seen) >= 500) {
    //                         $this->processDuplicateValues(array_keys($seen), $field, $duplicateIdMap, $now);
    //                         $seen = []; // clear buffer
    //                     }
    //                 }
    //             });
    
    //         // Final flush
    //         if (!empty($seen)) {
    //             $this->processDuplicateValues(array_keys($seen), $field, $duplicateIdMap, $now);
    //         }
    //     }
    // }

    protected function handleDuplicateDetection(): void
    {
        $now = now();
    
        CoreLead::where('import_id', $this->importId)
            ->chunkById(500, function ($leads) use ($now) {
                $chunkCandidates = [];
    
                // Step 1: Collect unique non-empty values per group in this chunk
                foreach ($leads as $lead) {
                    foreach ($this->groups as $groupName => $fields) {
                        foreach ($fields as $field) {
                            $value = $lead->$field;
                            if ($value !== null && $value !== '') {
                                $chunkCandidates[$groupName][$value] = true;
                            }
                        }
                    }
                }
    
                // Step 2: For each group, check counts of those values across all leads
                foreach ($chunkCandidates as $groupName => $values) {
                    $fields = $this->groups[$groupName];
                    $valueList = array_keys($values);
    
                    foreach (array_chunk($valueList, 500) as $chunk) {
                        $results = CoreLead::query()
                            ->select(DB::raw('val, COUNT(*) as total'))
                            ->from(function ($sub) use ($fields, $chunk) {
                                foreach ($fields as $f) {
                                    $sub->unionAll(
                                        CoreLead::select(DB::raw("$f as val"))
                                            ->whereIn($f, $chunk)
                                    );
                                }
                            }, 'derived')
                            ->whereNotNull('val')
                            ->groupBy('val')
                            ->get();
    
                        // Step 3: Only process actual duplicates
                        foreach ($results as $row) {
                            if ($row->total > 1) {
                                $this->processDuplicateValue($row->val, $groupName, $now, $row->total);
                            }
                        }
                    }
                }
            });
    }
                        
    // private function processDuplicateValues(array $allValues, string $field, array &$duplicateIdMap, $now): void
    // {
    //     CoreLead::whereIn($field, $allValues)
    //         ->select('id', 'import_id', $field)
    //         ->whereNotNull($field)
    //         ->chunkById(500, function ($leads) use (&$duplicateIdMap, $field, $now) {
    //             $grouped = [];
    
    //             foreach ($leads as $lead) {
    //                 $val = $lead->$field;
    //                 $grouped[$val][] = [
    //                     'id' => $lead->id,
    //                     'import_id' => $lead->import_id,
    //                 ];
    //             }
    
    //             $existingRecords = DuplicateRecord::where('table_name', 'core_leads')
    //                 ->where('field_name', $field)
    //                 ->whereIn('duplicate_value', array_keys($grouped))
    //                 ->get()
    //                 ->keyBy('duplicate_value');
    
    //             $linkInserts = [];
    
    //             foreach ($grouped as $val => $items) {
    //                 if (count($items) <= 1)
    //                     continue;
    
    //                 $currentCount = CoreLead::where($field, $val)
    //                     ->count();
    
    //                 if (!isset($duplicateIdMap[$field][$val])) {
    //                     if (isset($existingRecords[$val])) {
    //                         $existing = $existingRecords[$val];
    //                         $existing->update([
    //                             'count' => $currentCount,
    //                             'updated_at' => $now,
    //                         ]);
    //                         $duplicateIdMap[$field][$val] = $existing->id;
    //                     } else {
    //                         $newId = DB::table('duplicate_records')->insertGetId([
    //                             'table_name' => 'core_leads',
    //                             'field_name' => $field,
    //                             'duplicate_value' => $val,
    //                             'count' => $currentCount,
    //                             'created_at' => $now,
    //                             'updated_at' => $now,
    //                         ]);
    //                         $duplicateIdMap[$field][$val] = $newId;
    //                     }
    //                 }
    
    //                 $dupId = $duplicateIdMap[$field][$val];
    
    //                 // 🔹 Include *all* items in duplicate_links, including the "first" record
    //                 foreach ($items as $lead) {
    //                     $linkInserts[] = [
    //                         'duplicate_record_id' => $dupId,
    //                         'related_table' => 'core_leads',
    //                         'related_record_id' => $lead['id'],
    //                         'created_at' => $now,
    //                         'updated_at' => $now,
    //                     ];
    //                 }
    //             }
    
    //             foreach (array_chunk($linkInserts, 500) as $chunk) {
    //                 DB::table('duplicate_links')->insertOrIgnore($chunk);
    //             }
    //         });
    // }
    
    private function processDuplicateValue(string $value, string $groupName, $now, int $currentCount): void
    {
        // Skip if already processed in this run
        if (($this->duplicateValues[$groupName][$value] ?? false) === true) {
            return;
        }
    
        $fields = $this->groups[$groupName];
    
        // Create or update duplicate record
        $duplicateRecord = DuplicateRecord::firstOrCreate(
            [
                'table_name'      => 'core_leads',
                'field_name'      => $groupName,
                'duplicate_value' => $value,
            ],
            [
                'count'      => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    
        if ($duplicateRecord->count !== $currentCount) {
            $duplicateRecord->update([
                'count'      => $currentCount,
                'updated_at' => $now,
            ]);
        }
    
        $this->duplicateRecordIds[] = $duplicateRecord->id;
    
        // Bulk link all related leads in batches
        $buffer = [];
        $leadIds = CoreLead::where(function ($q) use ($fields, $value) {
            foreach ($fields as $f) {
                $q->orWhere($f, $value);
            }
        })->pluck('id');
    
        foreach ($leadIds as $leadId) {
            $buffer[] = [
                'duplicate_record_id' => $duplicateRecord->id,
                'related_table'       => 'core_leads',
                'related_record_id'   => $leadId,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
    
            if (count($buffer) >= 500) {
                DB::table('duplicate_links')->insertOrIgnore($buffer);
                $buffer = [];
            }
        }
    
        if (!empty($buffer)) {
            DB::table('duplicate_links')->insertOrIgnore($buffer);
        }
    
        // Mark this value as processed so we don’t re-run it
        $this->duplicateValues[$groupName][$value] = true;
    }
                
    // protected function markDuplicates(): void
    // {
    //     $fields = [
    //         'private_email_1',
    //         'private_email_2',
    //         'home_telephone_1',
    //         'home_telephone_2',
    //         'mobile_telephone_1',
    //         'mobile_telephone_2',
    //         'private_fax',
    //         'company_email_1',
    //         'company_email_2',
    //         'office_phone_1',
    //         'office_phone_2',
    //         'office_fax',
    //         'followup_email',
    //         'followup_mobile',
    //     ];
    
    //     foreach ($fields as $field) {
    //         // Step 1: Get all duplicate values for this field
    //         $duplicateValues = DuplicateRecord::where('table_name', 'core_leads')
    //             ->where('field_name', $field)
    //             ->pluck('duplicate_value');
    
    //         if ($duplicateValues->isEmpty()) continue;
    
    //         foreach ($duplicateValues->chunk(500) as $valueChunk) {
    //             // Step 2: Get first ID per value across ALL imports
    //             $firstIds = CoreLead::whereIn($field, $valueChunk)
    //                 ->whereNotNull($field)
    //                 ->select($field, DB::raw('MIN(id) as first_id'))
    //                 ->groupBy($field)
    //                 ->pluck('first_id', $field); // [duplicate_value => first_id]
    
    //             if ($firstIds->isEmpty()) continue;
    
    //             // Step 3: In current import, mark any record with same value but not the first one
    //             foreach ($firstIds as $value => $firstId) {
    //                 CoreLead::where($field, $value)
    //                     ->where('import_id', $this->importId) // only mark within this import
    //                     ->where('id', '<>', $firstId)         // skip the true first one
    //                     ->update(['is_duplicate' => true]);
    //             }
    //         }
    //     }
    // }
    
    
    protected function markDuplicates(): void
    {
        if (empty($this->duplicateRecordIds)) return; // nothing to process
    
        // process in chunks to avoid memory issues
        foreach (array_chunk($this->duplicateRecordIds, 500) as $chunkIds) {
            $dupRecords = DuplicateRecord::whereIn('id', $chunkIds)->get();
    
            foreach ($dupRecords as $dup) {
                // find the first lead ID for this duplicate value
                $firstLeadId = CoreLead::where(function($q) use ($dup) {
                        foreach ($this->groups[$dup->field_name] as $f) {
                            $q->orWhere($f, $dup->duplicate_value);
                        }
                    })
                    ->orderBy('id', 'asc')
                    ->value('id');
    
                if (!$firstLeadId) continue; // skip if no lead found
    
                // mark all other leads with the same value as duplicates
                CoreLead::where('import_id', $this->importId)
                    ->where(function($q) use ($dup) {
                        foreach ($this->groups[$dup->field_name] as $f) {
                            $q->orWhere($f, $dup->duplicate_value);
                        }
                    })
                    ->where('id', '<>', $firstLeadId)
                    ->update(['is_duplicate' => true]);
            }
        }
    }
            
    public function failed(Throwable $exception): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }

        $this->cleanupImportData($this->importId);
    }

    protected function detectFormat(string $filePath): string
    {
        return match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'csv'  => ExcelFormat::CSV,
            'xls'  => ExcelFormat::XLS,
            'xlsx' => ExcelFormat::XLSX,
            'ods'  => ExcelFormat::ODS,
            default => ExcelFormat::XLSX,
        };
    }

    protected function cleanupImportData(int $importId): void
    {
        DB::transaction(function () use ($importId) {
            $leadIds = CoreLead::where('import_id', $importId)->pluck('id');
            if ($leadIds->isEmpty()) return;

            $linkCounts = DB::table('duplicate_links')
                ->select('duplicate_record_id', DB::raw('count(*) as total'))
                ->where('related_table', 'core_leads')
                ->whereIn('related_record_id', $leadIds)
                ->groupBy('duplicate_record_id')
                ->get();

            foreach ($linkCounts as $link) {
                DB::table('duplicate_records')
                    ->where('id', $link->duplicate_record_id)
                    ->decrement('count', $link->total);
            }

            DB::table('duplicate_links')
                ->where('related_table', 'core_leads')
                ->whereIn('related_record_id', $leadIds)
                ->delete();

            DB::table('duplicate_records')
                ->where('count', '<=', 1)
                ->delete();

            CoreLead::whereIn('id', $leadIds)
                ->chunkById(500, function ($leads) {
                    foreach ($leads as $lead) {
                        $lead->delete();
                    }
                });
        });
    }
}
