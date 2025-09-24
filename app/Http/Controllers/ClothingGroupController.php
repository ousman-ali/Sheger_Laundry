<?php

namespace App\Http\Controllers;

use App\Models\ClothingGroup;
use App\Models\ClothItem;
use App\Models\User;
use Illuminate\Http\Request;

class ClothingGroupController extends Controller
{
    /**
     * Show all clothing groups
     */
    public function index()
    {
        $clothingGroups = ClothingGroup::with(['user','clothItems'])->paginate(10);
        return view('clothing-groups.index', compact('clothingGroups'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $clothItems = ClothItem::all();
        $users = User::all();
        return view('clothing-groups.create', compact('clothItems', 'users'));
    }

    /**
     * Store a new group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id'     => 'required|exists:users,id',
            'cloth_items' => 'required|array',
            'cloth_items.*' => 'exists:cloth_items,id',
        ]);

        // Create the group
        $group = ClothingGroup::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'user_id'     => $validated['user_id'],
        ]);

        // Assign group_id to each selected cloth item
        ClothItem::whereIn('id', $validated['cloth_items'])
            ->update(['clothing_group_id' => $group->id]);

        return redirect()->route('clothing-groups.index')
            ->with('success', 'Clothing Group created successfully.');
    }

    /**
     * Show edit form
     */
    public function edit(ClothingGroup $clothingGroup)
    {
        $clothItems = ClothItem::with('unit')->get(); // include unit for display
        $users = User::all();

        return view('clothing-groups.edit', compact('clothingGroup', 'clothItems', 'users'));
    }

    /**
     * Update group
     */
    public function update(Request $request, ClothingGroup $clothingGroup)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id'     => 'required|exists:users,id',
            'cloth_items' => 'required|array',
            'cloth_items.*' => 'exists:cloth_items,id',
        ]);

        $clothingGroup->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'user_id'     => $validated['user_id'],
        ]);

        // First remove old associations
        ClothItem::where('clothing_group_id', $clothingGroup->id)
            ->update(['clothing_group_id' => null]);

        // Then assign new ones
        ClothItem::whereIn('id', $validated['cloth_items'])
            ->update(['clothing_group_id' => $clothingGroup->id]);

        return redirect()->route('clothing-groups.index')
            ->with('success', 'Clothing Group updated successfully.');
    }

    /**
     * Delete group
     */
    public function destroy(ClothingGroup $clothingGroup)
    {
        // Detach or delete related clothing items if needed
        $clothingGroup->clothItems()->update(['clothing_group_id' => null]); 
        // or ->delete() if you want them removed completely

        $clothingGroup->delete();

        return redirect()->route('clothing-groups.index')
                        ->with('success', 'Clothing group deleted successfully.');
    }
}
