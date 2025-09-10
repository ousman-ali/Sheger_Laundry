<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderItemService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OperatorDashboardController extends Controller
{
    public function __construct()
    {
    // Operators only for their dashboards
    $this->middleware('role:Operator');
    }

    public function index(Request $request)
    {
        $query = OrderItemService::query()->with([
            'orderItem.order.customer', 'service', 'employee', 'assignments.employee'
        ]);
    $user = Auth::user();
    $canBrowseOthers = $user && \Illuminate\Support\Facades\Gate::allows('view_all_orders');
        // If operator cannot browse all, force filter to own tasks
        if (!$canBrowseOthers) {
            $employeeId = $user?->id;
            $query->where(function($w) use ($employeeId){
                $w->where('employee_id', $employeeId)
                  ->orWhereHas('assignments', function($q) use ($employeeId){
                      $q->where('employee_id', $employeeId);
                  });
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($canBrowseOthers && ($employeeId = $request->get('employee_id'))) {
            $query->where(function($w) use ($employeeId){
                $w->where('employee_id', $employeeId)
                  ->orWhereHas('assignments', function($q) use ($employeeId){
                      $q->where('employee_id', $employeeId);
                  });
            });
        }
        if ($serviceId = $request->get('service_id')) {
            $query->where('service_id', $serviceId);
        }
        $services = $query->latest()->paginate(20)->withQueryString();
        // Employees list (used for admins/managers); operators will see the filter hidden unless permitted
        $employees = User::role(['Operator'])->orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();
        return view('operator.index', compact('services', 'employees', 'customers'));
    }

    public function my(Request $request)
    {
        $userId = Auth::id();
        $services = OrderItemService::with(['orderItem.order.customer','service','assignments.employee'])
            ->where(function($w) use ($userId){
                $w->where('employee_id', $userId)
                  ->orWhereHas('assignments', function($q) use ($userId){
                      $q->where('employee_id', $userId);
                  });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();
        return view('operator.my', compact('services'));
    }
}
