<?php

namespace App\Http\Controllers;

use App\Models\UrgencyTier;
use Illuminate\Http\Request;

class UrgencyTierController extends Controller
{
    public function __construct()
    {
        // Allow Admins or explicit permissions
        $this->middleware('role_or_permission:Admin|view_urgency_tiers')->only(['index']);
        $this->middleware('role_or_permission:Admin|create_urgency_tiers')->only(['create', 'store']);
        $this->middleware('role_or_permission:Admin|edit_urgency_tiers')->only(['edit', 'update']);
        $this->middleware('role_or_permission:Admin|delete_urgency_tiers')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:label,duration_days,multiplier,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        $perPage = (int) ($validated['per_page'] ?? $request->session()->get('urgency_tiers.per_page', 10));
        $request->session()->put('urgency_tiers.per_page', $perPage);

        $query = UrgencyTier::query();
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where('label', 'like', "%{$q}%");
        }
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        // Exports
            if (!empty($validated['export'] ?? null)) {
                abort_unless(\Illuminate\Support\Facades\Gate::allows('export_urgency_tiers'), 403);
            }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'urgency_tiers_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Label','Duration (days)','Multiplier','Created At']);
                foreach ($rows as $t) {
                    fputcsv($out, [
                        $t->label,
                        $t->duration_days,
                        number_format((float)$t->multiplier, 2),
                        optional($t->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($t) => [
                $t->label,
                $t->duration_days,
                number_format((float)$t->multiplier, 2),
                optional($t->created_at)->toDateTimeString(),
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'urgency_tiers_'.now()->format('Ymd_His').'.xlsx',
                ['Label','Duration (days)','Multiplier','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($t) => [
                'Label' => $t->label,
                'Duration (days)' => $t->duration_days,
                'Multiplier' => number_format((float)$t->multiplier, 2),
                'Created At' => optional($t->created_at)->toDateTimeString(),
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'urgency_tiers_'.now()->format('Ymd_His').'.pdf',
                'Urgency Tiers',
                ['Label','Duration (days)','Multiplier','Created At'],
                $rows
            );
        }
        if ($request->boolean('print')) {
                abort_unless(\Illuminate\Support\Facades\Gate::allows('print_urgency_tiers'), 403);
            $rows = (clone $query)->get()->map(fn($t) => [
                'Label' => $t->label,
                'Duration (days)' => $t->duration_days,
                'Multiplier' => number_format((float)$t->multiplier, 2),
                'Created At' => optional($t->created_at)->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Urgency Tiers',
                'columns' => ['Label','Duration (days)','Multiplier','Created At'],
                'rows' => $rows,
            ]);
        }

        $tiers = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','sort','direction']), ['per_page' => $perPage]));
        return view('urgency-tiers.index', compact('tiers', 'sort', 'direction'));
    }

    public function create()
    {
        return view('urgency-tiers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100|unique:urgency_tiers,label',
            'duration_days' => 'required|integer|min:0|max:365',
            'multiplier' => 'required|numeric|min:1|max:10',
        ]);
        UrgencyTier::create($data);
        return redirect()->route('urgency-tiers.index')->with('success', 'Urgency tier created successfully.');
    }

    public function edit(UrgencyTier $urgency_tier)
    {
        return view('urgency-tiers.edit', ['tier' => $urgency_tier]);
    }

    public function update(Request $request, UrgencyTier $urgency_tier)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100|unique:urgency_tiers,label,' . $urgency_tier->id,
            'duration_days' => 'required|integer|min:0|max:365',
            'multiplier' => 'required|numeric|min:1|max:10',
        ]);
        $urgency_tier->update($data);
        return redirect()->route('urgency-tiers.index')->with('success', 'Urgency tier updated successfully.');
    }

    public function destroy(UrgencyTier $urgency_tier)
    {
        if ($urgency_tier->orderItemServices()->exists()) {
            return redirect()->route('urgency-tiers.index')->with('error', 'Cannot delete a tier referenced by order item services.');
        }
        $urgency_tier->delete();
        return redirect()->route('urgency-tiers.index')->with('success', 'Urgency tier deleted successfully.');
    }
}
