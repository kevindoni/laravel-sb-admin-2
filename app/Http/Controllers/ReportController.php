<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\HotspotUser;
use App\Models\Voucher;
use App\Models\Transaction;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now()->endOfMonth();

        // Revenue stats
        $revenueStats = [
            'total_revenue' => Transaction::where('status', 'completed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->sum('amount'),
            'voucher_sales' => Transaction::where('status', 'completed')
                ->where('type', 'voucher_purchase')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->sum('amount'),
            'topup_revenue' => Transaction::where('status', 'completed')
                ->where('type', 'topup')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->sum('amount'),
            'transaction_count' => Transaction::where('status', 'completed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
        ];

        // Usage stats
        $usageStats = [
            'total_users' => HotspotUser::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_users' => HotspotUser::where('status', 'active')->count(),
            'vouchers_generated' => Voucher::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'vouchers_used' => Voucher::where('status', 'used')
                ->whereBetween('used_at', [$dateFrom, $dateTo])
                ->count(),
        ];

        // Daily revenue chart data
        $dailyRevenue = Transaction::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Popular routers
        $popularRouters = Router::withCount(['hotspotUsers', 'vouchers'])
            ->orderBy('hotspot_users_count', 'desc')
            ->limit(5)
            ->get();

        return view('reports.index', compact(
            'revenueStats', 
            'usageStats', 
            'dailyRevenue', 
            'popularRouters',
            'dateFrom',
            'dateTo'
        ));
    }

    public function revenue(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now()->endOfMonth();

        // Revenue by type
        $revenueByType = Transaction::selectRaw('type, SUM(amount) as total, COUNT(*) as count')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('type')
            ->get();

        // Revenue by router
        $revenueByRouter = Router::withSum(['hotspotUsers.transactions' => function($query) use ($dateFrom, $dateTo) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$dateFrom, $dateTo]);
            }], 'amount')
            ->get();

        // Monthly revenue trend
        $monthlyRevenue = Transaction::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return view('reports.revenue', compact(
            'revenueByType', 
            'revenueByRouter', 
            'monthlyRevenue',
            'dateFrom',
            'dateTo'
        ));
    }

    public function usage(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now()->endOfMonth();

        // Usage by router
        $usageByRouter = Router::withCount([
                'hotspotUsers', 
                'vouchers',
                'userSessions' => function($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('started_at', [$dateFrom, $dateTo]);
                }
            ])
            ->with(['userSessions' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('started_at', [$dateFrom, $dateTo])
                      ->selectRaw('router_id, SUM(session_time) as total_time, SUM(upload_bytes + download_bytes) as total_data')
                      ->groupBy('router_id');
            }])
            ->get();

        // Top users by data usage
        $topUsersByData = UserSession::selectRaw('username, SUM(upload_bytes + download_bytes) as total_data, SUM(session_time) as total_time, COUNT(*) as session_count')
            ->whereBetween('started_at', [$dateFrom, $dateTo])
            ->groupBy('username')
            ->orderBy('total_data', 'desc')
            ->limit(10)
            ->get();

        // Top users by session time
        $topUsersByTime = UserSession::selectRaw('username, SUM(session_time) as total_time, SUM(upload_bytes + download_bytes) as total_data, COUNT(*) as session_count')
            ->whereBetween('started_at', [$dateFrom, $dateTo])
            ->groupBy('username')
            ->orderBy('total_time', 'desc')
            ->limit(10)
            ->get();

        // Hourly usage pattern
        $hourlyUsage = UserSession::selectRaw('HOUR(started_at) as hour, COUNT(*) as session_count, AVG(session_time) as avg_session_time')
            ->whereBetween('started_at', [$dateFrom, $dateTo])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return view('reports.usage', compact(
            'usageByRouter',
            'topUsersByData',
            'topUsersByTime', 
            'hourlyUsage',
            'dateFrom',
            'dateTo'
        ));
    }

    public function exportVouchers(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:vouchers,batch_id',
            'format' => 'required|in:csv,pdf'
        ]);

        $vouchers = Voucher::with('billingPlan')
            ->where('batch_id', $request->batch_id)
            ->get();

        if ($request->format === 'csv') {
            return $this->exportVouchersCSV($vouchers, $request->batch_id);
        } else {
            return $this->exportVouchersPDF($vouchers, $request->batch_id);
        }
    }

    private function exportVouchersCSV($vouchers, $batchId)
    {
        $filename = "vouchers_{$batchId}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($vouchers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Code', 'Password', 'Plan', 'Price', 'Status', 'Expires At']);

            foreach ($vouchers as $voucher) {
                fputcsv($file, [
                    $voucher->code,
                    $voucher->password,
                    $voucher->billingPlan->name,
                    $voucher->selling_price,
                    $voucher->status,
                    $voucher->expires_at ? $voucher->expires_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportVouchersPDF($vouchers, $batchId)
    {
        // For PDF export, you would use a library like DomPDF or TCPDF
        // This is a placeholder implementation
        return response()->json([
            'message' => 'PDF export feature akan ditambahkan dengan library PDF'
        ]);
    }
}