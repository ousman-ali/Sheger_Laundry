<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_services')->only(['index']);
        $this->middleware('permission:create_services')->only(['create', 'store']);
        $this->middleware('permission:edit_services')->only(['edit', 'update']);
        $this->middleware('permission:delete_services')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'q' => 'nullable|string|max:255',
            'sort' => 'nullable|in:name,order_item_services_count,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        // Sticky per_page
        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('services.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('services.per_page', 10);
        }

        $query = Service::query()->withCount('orderItemServices');
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';

        if ($sort === 'order_item_services_count') {
            $query->orderBy('order_item_services_count', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        // Exports
        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_services'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'services_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Description','Used In','Created At']);
                foreach ($rows as $s) {
                    $usedIn = $s->order_item_services_count ?? $s->orderItemServices()->count();
                    fputcsv($out, [
                        $s->name,
                        $s->description,
                        $usedIn,
                        optional($s->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(function ($s) {
                return [
                    $s->name,
                    $s->description,
                    ($s->order_item_services_count ?? $s->orderItemServices()->count()),
                    optional($s->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'services_'.now()->format('Ymd_His').'.xlsx',
                ['Name','Description','Used In','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(function ($s) {
                return [
                    'Name' => $s->name,
                    'Description' => $s->description,
                    'Used In' => ($s->order_item_services_count ?? $s->orderItemServices()->count()),
                    'Created At' => optional($s->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\PdfExportService::streamSimpleTable(
                'services_'.now()->format('Ymd_His').'.pdf',
                'Services',
                ['Name','Description','Used In','Created At'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_services'), 403);
            $rows = (clone $query)->get()->map(function ($s) {
                return [
                    'Name' => $s->name,
                    'Description' => $s->description,
                    'Used In' => ($s->order_item_services_count ?? $s->orderItemServices()->count()),
                    'Created At' => optional($s->created_at)->toDateTimeString(),
                ];
            });
            return view('exports.simple_table', [
                'title' => 'Services',
                'columns' => ['Name','Description','Used In','Created At'],
                'rows' => $rows,
            ]);
        }

        $services = $query->paginate($perPage)
            ->appends(array_merge(
                $request->only(['q','sort','direction']),
                ['per_page' => $perPage]
            ));
        return view('services.index', compact('services', 'sort', 'direction'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:services',
            'description' => 'nullable|string',
        ]);

        Service::create($request->all());

        return redirect()->route('services.index')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        return view('services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'description' => 'nullable|string',
        ]);

        $service->update($request->all());

        return redirect()->route('services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        // Prevent deletion if linked to any pricing tiers
        if ($service->pricingTiers()->count() > 0) {
            return redirect()->route('services.index')
                ->with('error', 'Cannot delete service that has associated pricing tiers.');
        }
        // Prevent deletion if linked to any order item services
        if ($service->orderItemServices()->count() > 0) {
            return redirect()->route('services.index')
                ->with('error', 'Cannot delete service that has associated orders.');
        }

        // Safe to delete
        $service->delete();
        return redirect()->route('services.index')
            ->with('success', 'Service deleted successfully.');
    }
}