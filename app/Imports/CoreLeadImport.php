<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\CoreLead;
use App\Models\DuplicateRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Log;

class CoreLeadImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $importId;
    protected int $userId;
    protected int $totalRows = 0;
    protected int $duplicateCount = 0;

    public function __construct(int $importId, int $userId)
    {
        $this->importId = $importId;
        $this->userId = $userId;
    }

    public function getTotalRowCount(): int
    {
        return $this->totalRows;
    }

    public function getDuplicateCount(): int
    {
        return $this->duplicateCount;
    }

    public function chunkSize(): int
    {
        return 500;
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

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();
    
        try {
            $this->totalRows += $rows->count();
            $now = now();
            $rowsArray = $rows->toArray();
    
            $insertData = [];
    
            foreach ($rowsArray as $row) {
                $insertData[] = [
                    'user_id'      => $this->userId,
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
                    'is_duplicate' => false,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
    
            DB::table('core_leads')->insert($insertData);
    
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
    
            Log::error('[CoreLeadImport] Insert failed', [
                'import_id' => $this->importId,
                'error'     => $e->getMessage(),
            ]);
                
            throw $e;
        }
    }
}
