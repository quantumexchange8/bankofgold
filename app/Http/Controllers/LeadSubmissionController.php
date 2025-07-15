<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\CoreLead;
use App\Models\DataImport;
use Illuminate\Http\Request;
use App\Exports\CoreLeadExport;
use App\Imports\CoreLeadImport;
use App\Models\DuplicateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
    
        $file = $request->file('file');
    
        try {
            // Create the import record first
            $import = DataImport::create([
                'table_name' => 'core_leads',
                'file_name'  => $file->getClientOriginalName(),
                'user_id'    => Auth::id(),
            ]);
    
            // Pass the import ID into the import class
            $importer = new CoreLeadImport($import->id);
            Excel::import($importer, $file);
    
            // Update with totals
            $import->update([
                'total_rows'      => $importer->getTotalRowCount(),
                'duplicate_count' => $importer->getDuplicateCount(),
            ]);
    
            return back()->with('toast', [
                'title' => 'File uploaded successfully!',
                'type'  => 'success',
            ]);
        } catch (\Throwable $e) {
            return back()->with('toast', [
                'title' => 'An error occurred while uploading the file!',
                'type'  => 'error',
            ]);
        }
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
