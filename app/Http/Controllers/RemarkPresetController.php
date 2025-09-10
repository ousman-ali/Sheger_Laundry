<?php

namespace App\Http\Controllers;

use App\Models\RemarkPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemarkPresetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_remarks_presets');
    }

    public function index()
    {
        $presets = RemarkPreset::orderBy('sort_order')->orderBy('label')->get();
        return view('remark_presets.index', compact('presets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);
    $data['created_by'] = Auth::id();
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        RemarkPreset::create($data);
        return back()->with('success', 'Remark preset created.');
    }

    public function update(Request $request, RemarkPreset $remarkPreset)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $remarkPreset->update($data);
        return back()->with('success', 'Remark preset updated.');
    }

    public function destroy(RemarkPreset $remarkPreset)
    {
        $remarkPreset->delete();
        return back()->with('success', 'Remark preset deleted.');
    }
}
