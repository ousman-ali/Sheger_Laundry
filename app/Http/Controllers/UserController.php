<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        // Protect all methods with authorization middleware
        $this->middleware('can:view_users')->only('index');
        $this->middleware('can:create_users')->only(['create', 'store']);
        $this->middleware('can:edit_users')->only(['edit', 'update']);
        $this->middleware('can:delete_users')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:name,email,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        // Sticky per_page
        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('users.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('users.per_page', 10);
        }

        $query = User::query()->with('roles');
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_users'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'users_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Email','Phone','Roles','Created At']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->name,
                        $r->email,
                        $r->phone,
                        $r->getRoleNames()->join(', '),
                        optional($r->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(function($r){
                return [
                    $r->name,
                    $r->email,
                    $r->phone,
                    $r->getRoleNames()->join(', '),
                    optional($r->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'users_'.now()->format('Ymd_His').'.xlsx',
                ['Name','Email','Phone','Roles','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(function($r){
                return [
                    'Name' => $r->name,
                    'Email' => $r->email,
                    'Phone' => $r->phone,
                    'Roles' => $r->getRoleNames()->join(', '),
                    'Created At' => optional($r->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\PdfExportService::streamSimpleTable(
                'users_'.now()->format('Ymd_His').'.pdf',
                'Users',
                ['Name','Email','Phone','Roles','Created At'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_users'), 403);
            $rows = (clone $query)->get()->map(function($r){
                return [
                    'Name' => $r->name,
                    'Email' => $r->email,
                    'Phone' => $r->phone,
                    'Roles' => $r->getRoleNames()->join(', '),
                    'Created At' => optional($r->created_at)->toDateTimeString(),
                ];
            });
            return view('exports.simple_table', [
                'title' => 'Users',
                'columns' => ['Name','Email','Phone','Roles','Created At'],
                'rows' => $rows,
            ]);
        }

        $users = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','sort','direction']), ['per_page' => $perPage]));
        return view('users.index', compact('users', 'sort', 'direction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = DB::table('roles')->pluck('name', 'id');
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        // Assign roles to the user by mapping selected IDs to names
        $roleNames = DB::table('roles')
            ->whereIn('id', (array) $request->roles)
            ->pluck('name')
            ->toArray();
        $user->syncRoles($roleNames);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Typically not needed for user management, redirect to edit
        return redirect()->route('users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Get available roles keyed by id
        // Get available roles keyed by id
        $roles = DB::table('roles')->pluck('name', 'id');
        $user->load('roles'); // Eager load roles for the user
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        // Sync roles by mapping selected IDs to names
        $roleNames = DB::table('roles')
            ->whereIn('id', (array) $request->roles)
            ->pluck('name')
            ->toArray();
        $user->syncRoles($roleNames);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}