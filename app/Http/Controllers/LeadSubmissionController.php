<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\CoreLead;
use Illuminate\Http\Request;
use App\Exports\CoreLeadExport;
use App\Imports\CoreLeadImport;
use App\Models\DuplicateRecord;
use Maatwebsite\Excel\Facades\Excel;

class LeadSubmissionController extends Controller
{
    public function index()
    {
        return Inertia::render('LeadSubmission/LeadSubmission');
    }

    public function getCoreLeads(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); //only() extract parameters in lazyEvent

            $type = $request->input('type');
            $query = CoreLead::query();
            
            if ($type === 'duplicate') {
                $query->where('is_duplicate', true);
            } else {
                $query->where('is_duplicate', false);
            }
            
            // Handle search functionality
            $search = $data['filters']['global']['value'];
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('lead_id', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('surname', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
                });
            }

            $startDate = $data['filters']['start_date']['value'];
            $endDate = $data['filters']['end_date']['value'];
            
            if ($startDate && $endDate) {
                $start_date = Carbon::parse($startDate)->startOfDay();
                $end_date = Carbon::parse($endDate)->endOfDay();
    
                $query->whereBetween('date_added', [$start_date, $end_date]);
            } else {
                $query->whereDate('created_at', '>=', '2020-01-01');
            }
        
            // Handle sorting
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            // Handle pagination
            $rowsPerPage = $data['rows'] ?? 15; // Default to 15 if 'rows' not provided
                    
            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                // Check if there are selected core_lead for export
                $selectedIds = $request->input('selected_ids', default: []);

                if (!empty($selectedIds)) {
                    // If selected core_lead are provided, filter by selected IDs
                    $coreLeads = $query->whereIn('id', $selectedIds)->get();
                } else {
                    // Otherwise, fetch all core_lead
                    $coreLeads = $query->get();
                }

                return Excel::download(new CoreLeadExport($coreLeads), now() . '-lead-report.xlsx');
            }

            $result  = $query->paginate($rowsPerPage);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result ,
        ]);

    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx,ods',
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        try {
            // Process the file directly into an array using the LeadsImport class
            Excel::import(new CoreLeadImport, $file);
    
            // After the import process finishes, redirect with a success message
            // return redirect()->back()->with('success', 'Leads and items uploaded successfully!');
            return redirect()->back()->with('toast', [
                'title' => 'File uploaded successfully!',
                'type' => 'success'
            ]);
    
        } catch (\Exception $e) {
            // If an error occurs during the import, return an error message
            // return redirect()->back()->with('error', 'An error occurred while uploading the leads: ' . $e->getMessage());
            return redirect()->back()->with('toast', [
                'title' => 'An error occurred while uploading the file!',
                'type' => 'error'
            ]);

        }
    }

    public function getDuplicateRecords(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); //only() extract parameters in lazyEvent

            $type = $request->input('type');
            $query = DuplicateRecord::query();
            
            if ($type) {
                $query->where('table_name', $type);
            }
            
            // Handle search functionality
            $search = $data['filters']['global']['value'];
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('duplicate_value', 'like', '%' . $search . '%');
                });
            }
        
            // Handle sorting
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            // Handle pagination
            $rowsPerPage = $data['rows'] ?? 15; // Default to 15 if 'rows' not provided
                    
            // Export class map
            $exportMap = [
                'core_leads' => CoreLeadExport::class,
            ];

            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                $selectedIds = $request->input('selected_ids', []);

                if (!empty($selectedIds)) {
                    $exportData = $query->whereIn('id', $selectedIds)->get();
                } else {
                    $exportData = $query->get();
                }

                if (!isset($exportMap[$type])) {
                    return response()->json(['error' => 'Invalid export type'], 400);
                }

                $exportClass = $exportMap[$type];
                return Excel::download(new $exportClass($exportData), now() . "-$type-export.xlsx");
            }

            $result  = $query->paginate($rowsPerPage);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result ,
        ]);
    }

    public function getRecordsByDuplicateId(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $duplicateRecord = DuplicateRecord::find($request->duplicate_id);

            if (!$duplicateRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate record not found.',
                ], 404);
            }

            // Auto-resolve Eloquent model from table_name
            $modelMap = [
                'core_leads' => CoreLead::class,
            ];

            $table = $duplicateRecord->table_name;

            if (!isset($modelMap[$table])) {
                return response()->json([
                    'success' => false,
                    'message' => "Model for table '$table' is not supported.",
                ], 400);
            }

            $model = $modelMap[$table];

            // Use JSON contains to match the duplicate_id inside duplicate_ids array
            $records = $model::whereJsonContains('duplicate_ids', $duplicateRecord->id)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

}
