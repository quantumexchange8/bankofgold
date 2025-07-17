<?php

namespace App\Http\Controllers;

use App\Models\CoreLead;
use Illuminate\Http\Request;
use App\Exports\CoreLeadExport;
use App\Models\DuplicateRecord;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DuplicateRecordController extends Controller
{
    public function getDuplicateRecords(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->input('lazyEvent'), true);
            $type = $request->input('type');
    
            $query = DuplicateRecord::query();
    
            if ($type) {
                $query->where('table_name', $type);
            }
    
            $field = $data['filters']['field']['value'] ?? null;
            if ($field) {
                $query->where('field_name', $field);
            }

            $search = $data['filters']['global']['value'] ?? null;
            if ($search) {
                $query->where('duplicate_value', 'like', "%$search%");
            }
    
            if (!empty($data['sortField']) && !empty($data['sortOrder'])) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }
    
            $rowsPerPage = $data['rows'] ?? 15;
    
            $exportMap = [
                'core_leads' => CoreLeadExport::class,
            ];
    
            if ($request->has('exportStatus') && $request->exportStatus) {
                $selectedIds = $request->input('selected_ids', []);
                $exportData = !empty($selectedIds)
                    ? $query->whereIn('id', $selectedIds)->get()
                    : $query->get();
    
                if (!isset($exportMap[$type])) {
                    return response()->json(['error' => 'Invalid export type'], 400);
                }
    
                $exportClass = $exportMap[$type];
                return Excel::download(new $exportClass($exportData), now() . "-$type-export.xlsx");
            }
    
            $result = $query->paginate($rowsPerPage);
    
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        }
    
        return response()->json(['success' => false]);
    }
    
    
    public function getRecordsByDuplicateId(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $duplicateRecord = DuplicateRecord::find($request->duplicate_id);
            if (!$duplicateRecord) {
                return response()->json(['success' => false, 'message' => 'Duplicate record not found.'], 404);
            }
    
            $table = $duplicateRecord->table_name;
            $modelMap = [
                'core_leads' => CoreLead::class,
            ];
    
            if (!isset($modelMap[$table])) {
                return response()->json(['success' => false, 'message' => "Model for table '$table' is not supported."], 400);
            }
    
            $model = $modelMap[$table];
    
            // Fetch related record IDs via duplicate_links table
            $relatedRecordIds = DB::table('duplicate_links')
                ->where('duplicate_record_id', $duplicateRecord->id)
                ->where('related_table', $table)
                ->pluck('related_record_id');
    
            $records = $model::whereIn('id', $relatedRecordIds)->get();
    
            return response()->json([
                'success' => true,
                'data' => $records,
            ]);
        }
    
        return response()->json(['success' => false]);
    }
}