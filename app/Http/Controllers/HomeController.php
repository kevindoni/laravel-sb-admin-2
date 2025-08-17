<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Router;
use App\Models\HotspotUser;
use App\Models\Voucher;
use App\Models\Transaction;
use App\Models\BillingPlan;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Get statistics for dashboard
        $totalRouters = Router::count();
        $activeRouters = Router::where('is_active', true)->count();
        $totalUsers = HotspotUser::count();
        $activeUsers = HotspotUser::where('status', 'active')->count();
        $totalVouchers = Voucher::count();
        $unusedVouchers = Voucher::where('status', 'unused')->count();
        $todayRevenue = Transaction::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');
        $monthlyRevenue = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Recent activities
        $recentUsers = HotspotUser::with(['router', 'billingPlan'])
            ->latest()
            ->limit(5)
            ->get();

        $recentTransactions = Transaction::with(['user', 'hotspotUser'])
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get();

        // Popular billing plans
        $popularPlans = BillingPlan::withCount('hotspotUsers')
            ->where('is_active', true)
            ->orderBy('hotspot_users_count', 'desc')
            ->limit(5)
            ->get();

        $widget = [
            'total_routers' => $totalRouters,
            'active_routers' => $activeRouters,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_vouchers' => $totalVouchers,
            'unused_vouchers' => $unusedVouchers,
            'today_revenue' => $todayRevenue,
            'monthly_revenue' => $monthlyRevenue,
        ];

        return view('home', compact(
            'widget', 
            'recentUsers', 
            'recentTransactions', 
            'popularPlans'
        ));
    }
}
