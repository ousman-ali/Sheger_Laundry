<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:Admin']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'action' => 'nullable|string|max:100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);
        $perPage = (int)($validated['per_page'] ?? 25);

        $query = ActivityLog::with('user')
            ->when(($validated['q'] ?? null), function($q) use ($validated){
                $term = $validated['q'];
                $q->where('action','like',"%{$term}%");
            })
            ->when(($validated['user_id'] ?? null), fn($q,$id)=>$q->where('user_id',$id))
            ->when(($validated['action'] ?? null), fn($q,$a)=>$q->where('action',$a))
            ->when(($validated['from_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','<=',$d))
            ->orderByDesc('id');

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_activity_logs'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'activity_logs_'.now()->format('Ymd_His').'.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Time','User','Action','Subject','Subject ID']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->created_at)->toDateTimeString(),
                        optional($r->user)->name,
                        $r->action,
                        $r->subject_type,
                        $r->subject_id,
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                optional($r->created_at)->toDateTimeString(),
                optional($r->user)->name,
                $r->action,
                $r->subject_type,
                $r->subject_id,
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'activity_logs_'.now()->format('Ymd_His').'.xlsx',
                ['Time','User','Action','Subject','Subject ID'],
                $rows
            );
        }
    if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Time' => optional($r->created_at)->toDateTimeString(),
                'User' => optional($r->user)->name,
                'Action' => $r->action,
                'Subject' => $r->subject_type,
                'Subject ID' => $r->subject_id,
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'activity_logs_'.now()->format('Ymd_His').'.pdf',
                'Activity Logs',
                ['Time','User','Action','Subject','Subject ID'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_activity_logs'), 403);
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Time' => optional($r->created_at)->toDateTimeString(),
                'User' => optional($r->user)->name,
                'Action' => $r->action,
                'Subject' => $r->subject_type,
                'Subject ID' => $r->subject_id,
            ]);
            return view('exports.simple_table', [
                'title' => 'Activity Logs',
                'columns' => ['Time','User','Action','Subject','Subject ID'],
                'rows' => $rows,
            ]);
        }

        $logs = $query->paginate($perPage)->appends($request->query());
        return view('activity_logs.index', compact('logs'));
    }
}
