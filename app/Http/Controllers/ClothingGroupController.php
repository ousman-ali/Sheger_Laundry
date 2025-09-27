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
    public function index(Request $request)
    {
        // Validate inputs
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:name,created_at',
            'direction' => 'nullable|in:asc,desc',
        ]);

        // Sticky per-page
        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('clothing_groups.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('clothing_groups.per_page', 10);
        }

        // Build query
        $query = ClothingGroup::with(['user','clothItems']);

        // Search filter
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%");
        }

        // Sorting
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        // Paginate with sticky params
        $clothingGroups = $query->paginate($perPage)
            ->appends(array_merge(
                $request->only(['q','sort','direction']),
                ['per_page' => $perPage]
            ));

        return view('clothing-groups.index', compact('clothingGroups', 'sort', 'direction'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        // Only fetch items that are not in any group
        $clothItems = ClothItem::whereNull('clothing_group_id')->get();
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
        $clothItems = ClothItem::with('unit')
            ->whereNull('clothing_group_id')
            ->orWhere('clothing_group_id', $clothingGroup->id)
            ->get();

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
