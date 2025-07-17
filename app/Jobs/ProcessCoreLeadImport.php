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
            $this->cleanupImportData($this->importId);

            $importRecord = DataImport::findOrFail($this->importId);
            $format = $this->detectFormat($this->filePath);

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
        $duplicateIdMap = [];
    
        foreach ($fields as $field) {
            $seen = [];
    
            // Gather unique values for current import
            CoreLead::where('import_id', $this->importId)
                ->whereNotNull($field)
                ->select('id', $field)
                ->chunkById(1000, function ($chunk) use (&$seen, $field) {
                    foreach ($chunk as $lead) {
                        $val = $lead->$field;
                        if ($val !== null && $val !== '') {
                            $seen[$val] = true;
                        }
                    }
                });
    
            $allValues = array_keys($seen);
            unset($seen); // free memory
    
            if (empty($allValues)) continue;
    
            // Query all core_leads with any of the seen values
            CoreLead::whereIn($field, $allValues)
                ->select('id', 'import_id', $field)
                ->whereNotNull($field)
                ->whereNull('deleted_at')
                ->chunkById(1000, function ($leads) use (
                    &$duplicateIdMap, $field, $now
                ) {
                    $grouped = [];
    
                    foreach ($leads as $lead) {
                        $val = $lead->$field;
                        $grouped[$val][] = [
                            'id' => $lead->id,
                            'import_id' => $lead->import_id,
                        ];
                    }
    
                    $linkInserts = [];
    
                    foreach ($grouped as $val => $items) {
                        if (count($items) <= 1) continue;
    
                        // Get true total count from DB
                        $currentCount = CoreLead::where($field, $val)
                            ->whereNull('deleted_at')
                            ->count();
    
                        if (!isset($duplicateIdMap[$field][$val])) {
                            $existing = DuplicateRecord::where('table_name', 'core_leads')
                                ->where('field_name', $field)
                                ->where('duplicate_value', $val)
                                ->first();
    
                            if ($existing) {
                                $existing->update([
                                    'count' => $currentCount,
                                    'updated_at' => $now,
                                ]);
                                $duplicateIdMap[$field][$val] = $existing->id;
                            } else {
                                $newId = DB::table('duplicate_records')->insertGetId([
                                    'table_name' => 'core_leads',
                                    'field_name' => $field,
                                    'duplicate_value' => $val,
                                    'count' => $currentCount,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);
                                $duplicateIdMap[$field][$val] = $newId;
                            }
                        }
    
                        $dupId = $duplicateIdMap[$field][$val];
    
                        // Find the lowest ID (first)
                        usort($items, fn($a, $b) => $a['id'] <=> $b['id']);
                        $firstId = $items[0]['id'];
                        $toMark = [];
    
                        foreach ($items as $lead) {
                            $linkInserts[] = [
                                'duplicate_record_id' => $dupId,
                                'related_table' => 'core_leads',
                                'related_record_id' => $lead['id'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
    
                            if ($lead['import_id'] == $this->importId && $lead['id'] !== $firstId) {
                                $toMark[] = $lead['id'];
                            }
                        }
    
                        foreach (array_chunk($toMark, 1000) as $chunked) {
                            CoreLead::whereIn('id', $chunked)->update(['is_duplicate' => true]);
                        }
                    }
    
                    foreach (array_chunk($linkInserts, 1000) as $chunk) {
                        DB::table('duplicate_links')->insertOrIgnore($chunk);
                    }
                });
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
                ->where('count', '<=', 0)
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
