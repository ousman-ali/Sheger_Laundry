<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportingService;
use App\Models\Order;

class ReceptionistDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Receptionist');
    }

    public function index(ReportingService $reportingService)
    {
        $stats = $reportingService->getDashboardStats();
        $recentOrders = Order::with('customer')->latest()->take(10)->get();

        return view('reception.index', compact('stats', 'recentOrders'));
    }
}
