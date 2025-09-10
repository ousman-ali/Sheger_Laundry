<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:Admin']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'active' => 'nullable|in:1,0',
            'per_page' => 'nullable|integer|in:10,25,50,100',
        ]);
        $per = (int)($validated['per_page'] ?? 25);
        $q = Bank::query()
            ->when(($validated['q'] ?? null), fn($w,$t)=>$w->where('name','like',"%{$t}%"))
            ->when(isset($validated['active']), fn($w,$a)=>$w->where('is_active', (bool)$a))
            ->orderBy('name');
        $banks = $q->paginate($per)->appends($request->query());
        return view('banks.index', compact('banks'));
    }

    public function create()
    {
        return view('banks.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'branch' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        Bank::create($data);
        return redirect()->route('banks.index')->with('success', 'Bank created.');
    }

    public function edit(Bank $bank)
    {
        return view('banks.edit', compact('bank'));
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'branch' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $bank->update($data);
        return redirect()->route('banks.index')->with('success', 'Bank updated.');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return redirect()->route('banks.index')->with('success', 'Bank deleted.');
    }
}
