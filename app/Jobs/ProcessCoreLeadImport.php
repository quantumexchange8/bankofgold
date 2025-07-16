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
            $importRecord = DataImport::findOrFail($this->importId);
            $format = $this->detectFormat($this->filePath);

            $this->cleanupImportData($this->importId);

            $import = new CoreLeadImport($this->importId, $importRecord->user_id);
            Excel::import($import, $this->filePath, null, $format);

            $this->handlePostImportDuplicateDetection();

            DataImport::where('id', $this->importId)->update([
                'total_rows'      => $import->getTotalRowCount(),
                'duplicate_count' => $import->getDuplicateCount(),
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

    protected function handlePostImportDuplicateDetection(): void
    {
        $now = now();
        $fields = ['email', 'telephone'];
    
        $duplicates = [];
        $rowLinkMap = [];
    
        foreach ($fields as $field) {
            CoreLead::where('import_id', $this->importId)
                ->whereNotNull($field)
                ->select('id', $field)
                ->chunkById(1000, function ($leadsChunk) use ($field, &$duplicates, &$rowLinkMap, $now) {
                    $values = $leadsChunk->pluck($field)->unique()->filter();
    
                    if ($values->isEmpty()) return;
    
                    $matchingLeads = CoreLead::whereIn($field, $values)
                        ->select('id', 'import_id', $field)
                        ->get()
                        ->groupBy($field);
    
                    foreach ($matchingLeads as $val => $leads) {
                        if ($leads->count() <= 1) continue;
    
                        $duplicates[$field][$val] = $leads->count();
    
                        foreach ($leads as $lead) {
                            $rowLinkMap[$lead->id][] = [
                                'field' => $field,
                                'value' => $val,
                                'from_current_import' => $lead->import_id === $this->importId,
                            ];
                        }
                    }
                });
        }
    
        $existingRecords = DuplicateRecord::where('table_name', 'core_leads')
            ->where(function ($query) use ($duplicates) {
                foreach ($duplicates as $field => $values) {
                    $query->orWhere(function ($q) use ($field, $values) {
                        $q->where('field_name', $field)
                          ->whereIn('duplicate_value', array_keys($values));
                    });
                }
            })->get();
    
        $existingMap = [];
        foreach ($existingRecords as $rec) {
            $existingMap[$rec->field_name][$rec->duplicate_value] = $rec;
        }
    
        $newRecords = [];
        $duplicateIdMap = [];
    
        foreach ($duplicates as $field => $values) {
            foreach ($values as $val => $count) {
                if (isset($existingMap[$field][$val])) {
                    DB::table('duplicate_records')->where('id', $existingMap[$field][$val]->id)->increment('count', $count);
                    $duplicateIdMap[$field][$val] = $existingMap[$field][$val]->id;
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
    
            foreach ($inserted as $rec) {
                $duplicateIdMap[$rec->field_name][$rec->duplicate_value] = $rec->id;
            }
        }
    
        $linkInserts = [];
        $duplicateLeadIds = [];
    
        foreach ($rowLinkMap as $leadId => $dups) {
            foreach ($dups as $info) {
                $dupId = $duplicateIdMap[$info['field']][$info['value']] ?? null;
                if ($dupId) {
                    $linkInserts[] = [
                        'duplicate_record_id' => $dupId,
                        'related_table'       => 'core_leads',
                        'related_record_id'   => $leadId,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
    
                    if ($info['from_current_import']) {
                        $duplicateLeadIds[] = $leadId;
                    }
                }
            }
        }
    
        if (!empty($linkInserts)) {
            DB::table('duplicate_links')->insert($linkInserts);
        }
    
        if (!empty($duplicateLeadIds)) {
            CoreLead::whereIn('id', $duplicateLeadIds)->update(['is_duplicate' => true]);
        }
    }
    
    public function failed(Throwable $exception): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    
        // Add cleanup to remove any partial insert if final failure
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
                ->where('count', '<=', 0)
                ->delete();

            CoreLead::whereIn('id', $leadIds)
                ->chunkById(500, function ($leads) {
                    foreach ($leads as $lead) {
                        $lead->delete(); // SoftDeletes respected
                    }
                });
        });
    }
}
