<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\CoreLead;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CoreLeadImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected int $importId;
    protected int $userId;
    protected int $totalRows = 0;

    private array $dateColumns = [
        'date_added',
        'date_logged',
        'date_last_modified',
        'dob',
        'license_start_date',
        'license_expiry_date',
        'last_contact_date',
        'last_transaction_date',
        'tq_date',
        'verify_date',
    ];

    private array $timeColumns = [
        'verified_time',
    ];

    private array $booleanColumns = [
        'cryptocurrency_market',
        'broker_local',
        'broker_international',
        'decision_maker',
        'is_duplicate',
    ];

    public function __construct(int $importId, int $userId)
    {
        $this->importId = $importId;
        $this->userId = $userId;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function getTotalRowCount(): int
    {
        return $this->totalRows;
    }

    protected function normalizeExcelDate($value): ?string
    {
        if (blank($value)) return null;

        try {
            return is_numeric($value)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject($value))->toDateString()
                : Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    protected function normalizeExcelTime($value): ?string
    {
        if (blank($value)) return null;

        try {
            return is_numeric($value)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject($value))->toTimeString()
                : Carbon::parse($value)->toTimeString();
        } catch (\Exception) {
            return null;
        }
    }

    public function collection(Collection $rows): void
    {
        $insertData = [];
    
        foreach ($rows as $row) {
            if (method_exists($row, 'toArray')) {
                $row = $row->toArray();
            } elseif ($row instanceof \ArrayAccess || is_object($row)) {
                $row = json_decode(json_encode($row), true);
            }
    
            // Ensure all keys are valid strings
            $cleaned = [];
            foreach ($row as $key => $value) {
                if (is_scalar($key)) {
                    $cleaned[(string) $key] = $value;
                }
            }
    
            $lead = [
                'import_id' => $this->importId,
                'user_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
    
            foreach ($cleaned as $column => $value) {
                if (in_array($column, $this->dateColumns, true)) {
                    $lead[$column] = $this->normalizeExcelDate($value);
                } elseif (in_array($column, $this->timeColumns, true)) {
                    $lead[$column] = $this->normalizeExcelTime($value);
                } elseif (in_array($column, $this->booleanColumns, true)) {
                    $lead[$column] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } else {
                    $lead[$column] = $value;
                }
            }
    
            $insertData[] = $lead;
        }
    
        CoreLead::insert($insertData);
        $this->totalRows += count($insertData);
    }
}
