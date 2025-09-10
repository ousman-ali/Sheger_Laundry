<?php

namespace App\Http\Controllers;

use App\Models\ClothItem;
use App\Models\Unit;
use Illuminate\Http\Request;

class ClothItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_cloth_items')->only(['index']);
        $this->middleware('permission:create_cloth_items')->only(['create', 'store']);
        $this->middleware('permission:edit_cloth_items')->only(['edit', 'update']);
        $this->middleware('permission:delete_cloth_items')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Validate inputs
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:name,order_items_count,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);
        // Sticky per-page
        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('cloth_items.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('cloth_items.per_page', 10);
        }

        $query = ClothItem::with('unit')->withCount('orderItems');
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
        }
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        if ($sort === 'order_items_count') {
            $query->orderBy('order_items_count', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }
        // Exports
        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_cloth_items'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'cloth_items_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Unit','Description','Used In','Created At']);
                foreach ($rows as $item) {
                    fputcsv($out, [
                        $item->name,
                        optional($item->unit)->name,
                        $item->description,
                        $item->order_items_count,
                        optional($item->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(function ($item) {
                return [
                    $item->name,
                    optional($item->unit)->name,
                    $item->description,
                    $item->order_items_count,
                    optional($item->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'cloth_items_'.now()->format('Ymd_His').'.xlsx',
                ['Name','Unit','Description','Used In','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(function ($item) {
                return [
                    'Name' => $item->name,
                    'Unit' => optional($item->unit)->name,
                    'Description' => $item->description,
                    'Used In' => $item->order_items_count,
                    'Created At' => optional($item->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\PdfExportService::streamSimpleTable(
                'cloth_items_'.now()->format('Ymd_His').'.pdf',
                'Cloth Items',
                ['Name','Unit','Description','Used In','Created At'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_cloth_items'), 403);
            $rows = (clone $query)->get()->map(fn($item) => [
                'Name' => $item->name,
                'Unit' => optional($item->unit)->name,
                'Description' => $item->description,
                'Used In' => $item->order_items_count,
                'Created At' => optional($item->created_at)->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Cloth Items',
                'columns' => ['Name','Unit','Description','Used In','Created At'],
                'rows' => $rows,
            ]);
        }
        $clothItems = $query->paginate($perPage)
            ->appends(array_merge(
                $request->only(['q','sort','direction']),
                ['per_page' => $perPage]
            ));
        return view('cloth-items.index', compact('clothItems', 'sort', 'direction'));
    }

    public function create()
    {
        $units = Unit::all();
        return view('cloth-items.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:cloth_items',
            'unit_id' => 'required|exists:units,id',
            'description' => 'nullable|string',
        ]);

        ClothItem::create($request->all());

        return redirect()->route('cloth-items.index')
            ->with('success', 'Cloth item created successfully.');
    }

    public function edit(ClothItem $clothItem)
    {
        $units = Unit::all();
        return view('cloth-items.edit', compact('clothItem', 'units'));
    }

    public function update(Request $request, ClothItem $clothItem)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:cloth_items,name,' . $clothItem->id,
            'unit_id' => 'required|exists:units,id',
            'description' => 'nullable|string',
        ]);

        $clothItem->update($request->all());

        return redirect()->route('cloth-items.index')
            ->with('success', 'Cloth item updated successfully.');
    }

    public function destroy(ClothItem $clothItem)
    {
        // Prevent deletion if linked to any pricing tiers
        if ($clothItem->pricingTiers()->count() > 0) {
            return redirect()->route('cloth-items.index')
                ->with('error', 'Cannot delete cloth item that has associated pricing tiers.');
        }
        // Prevent deletion if linked to any order items
        if ($clothItem->orderItems()->count() > 0) {
            return redirect()->route('cloth-items.index')
                ->with('error', 'Cannot delete cloth item that has associated orders.');
        }

        // Safe to delete
        $clothItem->delete();
        return redirect()->route('cloth-items.index')
            ->with('success', 'Cloth item deleted successfully.');
    }
}