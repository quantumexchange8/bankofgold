<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\CoreLead;
use App\Models\DataImport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\CoreLeadExport;
use App\Imports\CoreLeadImport;
use App\Models\DuplicateRecord;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessCoreLeadImport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

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
                $validator = Validator::make($request->all(), [
                    'status' => ['required'],
                ])->setAttributeNames([
                    'status' => trans('public.status'),
                ]);
            
                $validator->validate();
        
                // Check if there are selected core_lead for export
                $selectedIds = $request->input('selected_ids', default: []);
                $status = $request->input('status');

                if (!empty($selectedIds)) {
                    $query->whereIn('id', $selectedIds);
                }
            
                if($status) {
                    $query->update(['status' => $status]);
                }

                $coreLeads = $query->get();

                return Excel::download(new CoreLeadExport($coreLeads), now() . '-lead-report.xlsx');
            }

            $result  = $query->paginate($rowsPerPage);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result ,
        ]);

    }

    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|file|mimes:csv,xls,xlsx,ods',
    //     ]);
    
    //     $file = $request->file('file');
    
    //     try {
    //         // Create the import record first
    //         $import = DataImport::create([
    //             'table_name' => 'core_leads',
    //             'file_name'  => $file->getClientOriginalName(),
    //             'user_id'    => Auth::id(),
    //         ]);
    
    //         // Pass the import ID into the import class
    //         $importer = new CoreLeadImport($import->id);
    //         Excel::import($importer, $file);
    
    //         // Update with totals
    //         $import->update([
    //             'total_rows'      => $importer->getTotalRowCount(),
    //             'duplicate_count' => $importer->getDuplicateCount(),
    //         ]);
    
    //         return back()->with('toast', [
    //             'title' => 'File uploaded successfully!',
    //             'type'  => 'success',
    //         ]);
    //     } catch (\Throwable $e) {
    //         return back()->with('toast', [
    //             'title' => 'An error occurred while uploading the file!',
    //             'type'  => 'error',
    //         ]);
    //     }
    // }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx,ods',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // 1. Store file somewhere permanent
        $filename = Str::uuid() . '.' . $extension;
        $destination = storage_path('app/temp-imports');
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }
        $fullPath = $destination . '/' . $filename;
        $file->move($destination, $filename);

        // 2. Create the import record
        $import = DataImport::create([
            'table_name' => 'core_leads',
            'file_name'  => $originalName,
            'user_id'    => Auth::id(),
            'status'     => 'processing',
        ]);

        // 3. Dispatch job
        ProcessCoreLeadImport::dispatch($import->id, $fullPath);

        // 4. Respond immediately (no waiting)
        return back()->with('toast', [
            'title' => 'File is being processed in background!',
            'type'  => 'success',
        ]);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:core_leads,id'],
            'status' => ['required'],
            // 'status' => ['required', 'in:pending,approved,rejected'],
        ])->setAttributeNames([
            'ids'    => trans('public.selected_leads'),
            'status' => trans('public.status'),
        ]);
    
        $validator->validate();
    
        CoreLead::whereIn('id', $request->ids)->update(['status' => $request->status]);
    
        return back()->with('toast', [
            'title' => 'Status updated successfully!',
            'type'  => 'success',
        ]);
    }
    
    public function deleteLead(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:core_leads,id',
        ]);

        DB::transaction(function () use ($request) {
            $leadId = $request->id;

            // Get duplicate links before deleting the lead
            $duplicateLinks = DB::table('duplicate_links')
                ->where('related_table', 'core_leads')
                ->where('related_record_id', $leadId)
                ->get();

            // Soft delete the lead
            CoreLead::where('id', $leadId)->delete();

            // Remove duplicate links
            DB::table('duplicate_links')
                ->where('related_table', 'core_leads')
                ->where('related_record_id', $leadId)
                ->delete();

            // Decrement duplicate record counts
            foreach ($duplicateLinks as $link) {
                DB::table('duplicate_records')
                    ->where('id', $link->duplicate_record_id)
                    ->decrement('count');

                // Optionally delete duplicate record if count is now 0
                DB::table('duplicate_records')
                    ->where('id', $link->duplicate_record_id)
                    ->where('count', '<=', 0)
                    ->delete();
            }
        });

        return back()->with('toast', [
            'title' => 'Lead deleted successfully.',
            'type' => 'success',
        ]);
    }

}
