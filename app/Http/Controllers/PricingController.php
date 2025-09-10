<?php

namespace App\Http\Controllers;

use App\Models\PricingTier;
use App\Models\ClothItem;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Requests\StorePricingTierRequest;
use App\Http\Requests\UpdatePricingTierRequest;

class PricingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_pricing')->only(['index']);
        $this->middleware('permission:create_pricing')->only(['create', 'store']);
        $this->middleware('permission:edit_pricing')->only(['edit', 'update', 'bulkUpdate']);
        $this->middleware('permission:delete_pricing')->only(['destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:cloth_item_id,service_id,price,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('pricing.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('pricing.per_page', 10);
        }

        $query = PricingTier::with(['clothItem.unit', 'service']);
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->whereHas('clothItem', fn($w) => $w->where('name', 'like', "%{$q}%"))
                ->orWhereHas('service', fn($w) => $w->where('name', 'like', "%{$q}%"));
        }
        $sort = $validated['sort'] ?? 'cloth_item_id';
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_pricing'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'pricing_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Cloth Item','Unit','Service','Price']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->clothItem)->name,
                        optional(optional($r->clothItem)->unit)->name,
                        optional($r->service)->name,
                        number_format($r->price, 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r) => [
                optional($r->clothItem)->name,
                optional(optional($r->clothItem)->unit)->name,
                optional($r->service)->name,
                number_format($r->price, 2, '.', ''),
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'pricing_'.now()->format('Ymd_His').'.xlsx',
                ['Cloth Item','Unit','Service','Price'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r) => [
                'Cloth Item' => optional($r->clothItem)->name,
                'Unit' => optional(optional($r->clothItem)->unit)->name,
                'Service' => optional($r->service)->name,
                'Price' => number_format($r->price, 2, '.', ''),
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'pricing_'.now()->format('Ymd_His').'.pdf',
                'Pricing Tiers',
                ['Cloth Item','Unit','Service','Price'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_pricing'), 403);
            $rows = (clone $query)->get()->map(fn($r) => [
                'Cloth Item' => optional($r->clothItem)->name,
                'Unit' => optional(optional($r->clothItem)->unit)->name,
                'Service' => optional($r->service)->name,
                'Price' => number_format($r->price, 2, '.', ''),
            ]);
            return view('exports.simple_table', [
                'title' => 'Pricing Tiers',
                'columns' => ['Cloth Item','Unit','Service','Price'],
                'rows' => $rows,
            ]);
        }

        $pricingTiers = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','sort','direction']), ['per_page' => $perPage]));
        
        return view('pricing.index', compact('pricingTiers', 'sort', 'direction'));
    }

    public function create()
    {
        $clothItems = ClothItem::with('unit')->get();
        $services = Service::all();
        
        return view('pricing.create', compact('clothItems', 'services'));
    }

    public function store(StorePricingTierRequest $request)
    {
        PricingTier::create($request->validated());

        return redirect()->route('pricing.index')
            ->with('success', 'Pricing tier created successfully.');
    }

    public function edit(PricingTier $pricing)
    {
        // Rename to match view variable while respecting implicit binding on {pricing}
        $pricingTier = $pricing;
        $clothItems = ClothItem::with('unit')->get();
        $services = Service::all();

        return view('pricing.edit', compact('pricingTier', 'clothItems', 'services'));
    }

    public function update(UpdatePricingTierRequest $request, PricingTier $pricing)
    {
        $pricing->update($request->validated());

        return redirect()->route('pricing.index')
            ->with('success', 'Pricing tier updated successfully.');
    }

    public function destroy(PricingTier $pricing)
    {
        $deleted = $pricing->delete();
        if ($deleted) {
            return redirect()->route('pricing.index')
                ->with('success', 'Pricing tier deleted successfully.');
        }
        return redirect()->route('pricing.index')
            ->with('error', 'Failed to delete the selected pricing tier. It may have already been removed.');
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'prices' => 'required|array',
            'prices.*' => 'required|numeric|min:0',
        ]);

        foreach ($request->prices as $id => $price) {
            PricingTier::where('id', $id)->update(['price' => $price]);
        }

        return redirect()->route('pricing.index')
            ->with('success', 'Pricing tiers updated successfully.');
    }
}