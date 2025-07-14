<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DuplicateRecord;
use App\Models\CoreLead;
use App\Exports\CoreLeadExport;
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

            $search = $data['filters']['global']['value'];
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('duplicate_value', 'like', "%$search%");
                });
            }

            if ($data['sortField'] && $data['sortOrder']) {
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
            $data = json_decode($request->input('lazyEvent'), true);

            $duplicateRecord = DuplicateRecord::find($request->duplicate_id);
            if (!$duplicateRecord) {
                return response()->json(['success' => false, 'message' => 'Duplicate record not found.'], 404);
            }

            $modelMap = [
                'core_leads' => CoreLead::class,
            ];

            $table = $duplicateRecord->table_name;

            if (!isset($modelMap[$table])) {
                return response()->json(['success' => false, 'message' => "Model for table '$table' is not supported."], 400);
            }

            $model = $modelMap[$table];

            $records = $model::whereJsonContains('duplicate_ids', $duplicateRecord->id)->get();

            return response()->json([
                'success' => true,
                'data' => $records,
            ]);
        }

        return response()->json(['success' => false]);
    }
}
