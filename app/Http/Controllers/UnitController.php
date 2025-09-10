<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUnitRequest;

class UnitController extends Controller
{
    public function __construct()
    {
    $this->middleware('role_or_permission:Admin|view_units')->only(['index']);
    $this->middleware('role_or_permission:Admin|create_units')->only(['create', 'store']);
    $this->middleware('role_or_permission:Admin|edit_units')->only(['edit', 'update']);
    $this->middleware('role_or_permission:Admin|delete_units')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:name,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);
        $perPage = (int) ($validated['per_page'] ?? $request->session()->get('units.per_page', 10));
        $request->session()->put('units.per_page', $perPage);

        $query = Unit::query()->with('parentUnit');
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where('name', 'like', "%{$q}%");
        }
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_units'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'units_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Parent Unit','Conversion Factor','Created At']);
                foreach ($rows as $u) {
                    fputcsv($out, [
                        $u->name,
                        optional($u->parentUnit)->name,
                        $u->conversion_factor,
                        optional($u->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($u) => [
                $u->name,
                optional($u->parentUnit)->name,
                $u->conversion_factor,
                optional($u->created_at)->toDateTimeString(),
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'units_'.now()->format('Ymd_His').'.xlsx',
                ['Name','Parent Unit','Conversion Factor','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($u) => [
                'Name' => $u->name,
                'Parent Unit' => optional($u->parentUnit)->name,
                'Conversion Factor' => $u->conversion_factor,
                'Created At' => optional($u->created_at)->toDateTimeString(),
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'units_'.now()->format('Ymd_His').'.pdf',
                'Units',
                ['Name','Parent Unit','Conversion Factor','Created At'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_units'), 403);
            $rows = (clone $query)->get()->map(fn($u) => [
                'Name' => $u->name,
                'Parent Unit' => optional($u->parentUnit)->name,
                'Conversion Factor' => $u->conversion_factor,
                'Created At' => optional($u->created_at)->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Units',
                'columns' => ['Name','Parent Unit','Conversion Factor','Created At'],
                'rows' => $rows,
            ]);
        }

        $units = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','sort','direction']), ['per_page' => $perPage]));
        return view('units.index', compact('units', 'sort', 'direction'));
    }

    public function create()
    {
        $units = Unit::all();
        return view('units.create', compact('units'));
    }

    public function store(StoreUnitRequest $request)
    {
        $data = $request->validated();
        if (empty($data['parent_unit_id'])) {
            $data['conversion_factor'] = null;
        }
        Unit::create($data);
        return redirect()->route('units.index')->with('success', 'Unit created successfully.');
    }

    public function edit(Unit $unit)
    {
        $units = Unit::where('id', '!=', $unit->id)->get();
        return view('units.edit', compact('unit', 'units'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:units,name,' . $unit->id,
            'parent_unit_id' => 'nullable|exists:units,id|not_in:'.$unit->id,
            'conversion_factor' => [
                'nullable','numeric','min:0',
                function ($attribute, $value, $fail) use ($request) {
                    $hasParent = (bool) $request->input('parent_unit_id');
                    if ($hasParent && ($value === null || $value === '')) {
                        $fail('Conversion Factor is required when a parent unit is selected.');
                    }
                    if (!$hasParent && ($value !== null && $value !== '')) {
                        $fail('Conversion Factor must be empty when no parent unit is selected.');
                    }
                }
            ],
        ]);
        if (empty($data['parent_unit_id'])) {
            $data['conversion_factor'] = null;
        }
        $unit->update($data);
        return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->childUnits()->exists()) {
            return redirect()->route('units.index')->with('error', 'Cannot delete a unit that has child units.');
        }
        if ($unit->clothItems()->exists()) {
            return redirect()->route('units.index')->with('error', 'Cannot delete a unit used by cloth items.');
        }
        if ($unit->inventoryItems()->exists()) {
            return redirect()->route('units.index')->with('error', 'Cannot delete a unit used by inventory items.');
        }
        if ($unit->purchaseItems()->exists() || $unit->stockTransferItems()->exists() || $unit->stockUsage()->exists()) {
            return redirect()->route('units.index')->with('error', 'Cannot delete a unit referenced by stock or purchase records.');
        }
        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
