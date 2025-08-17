<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Router;
use App\Models\BillingPlan;
use App\Models\HotspotUser;
use App\Services\MikrotikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Voucher::with(['router', 'billingPlan']);

        // Filter by router
        if ($request->router_id) {
            $query->where('router_id', $request->router_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by batch
        if ($request->batch_id) {
            $query->where('batch_id', $request->batch_id);
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('comment', 'like', '%' . $request->search . '%');
            });
        }

        $vouchers = $query->latest()->paginate(20);
        $routers = Router::where('is_active', true)->get();
        $batches = Voucher::select('batch_id')->distinct()->whereNotNull('batch_id')->get();

        return view('vouchers.index', compact('vouchers', 'routers', 'batches'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->get();
        $billingPlans = BillingPlan::where('is_active', true)->get();

        return view('vouchers.create', compact('routers', 'billingPlans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'billing_plan_id' => 'required|exists:billing_plans,id',
            'quantity' => 'required|integer|min:1|max:500',
            'code_length' => 'required|integer|min:4|max:12',
            'password_length' => 'required|integer|min:4|max:12',
            'code_prefix' => 'nullable|string|max:5',
            'selling_price' => 'nullable|numeric|min:0',
            'comment' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        DB::beginTransaction();
        try {
            $router = Router::findOrFail($request->router_id);
            $billingPlan = BillingPlan::findOrFail($request->billing_plan_id);
            $batchId = 'VOUCHER_' . now()->format('YmdHis');
            
            $api = new MikrotikApiService($router);
            $api->connect();

            $createdVouchers = [];

            for ($i = 1; $i <= $request->quantity; $i++) {
                // Generate unique codes
                do {
                    $code = ($request->code_prefix ?? '') . strtoupper(Str::random($request->code_length));
                } while (Voucher::where('code', $code)->exists());

                $password = Str::random($request->password_length);

                // Create voucher in database
                $voucher = Voucher::create([
                    'router_id' => $request->router_id,
                    'billing_plan_id' => $request->billing_plan_id,
                    'code' => $code,
                    'password' => $password,
                    'batch_id' => $batchId,
                    'selling_price' => $request->selling_price ?? $billingPlan->price,
                    'comment' => $request->comment,
                    'expires_at' => $request->expires_at,
                ]);

                // Create corresponding hotspot user
                $hotspotUser = HotspotUser::create([
                    'router_id' => $request->router_id,
                    'billing_plan_id' => $request->billing_plan_id,
                    'username' => $code,
                    'password' => $password,
                    'profile' => 'default', // Will be set based on billing plan
                    'status' => 'active',
                    'expires_at' => $request->expires_at,
                    'comment' => "Voucher: $batchId",
                    'is_voucher' => true,
                ]);

                // Create user in MikroTik
                $routerUserData = [
                    'name' => $code,
                    'password' => $password,
                    'profile' => 'default',
                    'comment' => "Voucher: $batchId",
                    'disabled' => 'yes', // Enable only when voucher is activated
                ];

                if ($billingPlan->time_limit) {
                    $routerUserData['limit-uptime'] = $billingPlan->time_limit . 'm';
                }

                if ($billingPlan->data_limit) {
                    $routerUserData['limit-bytes-total'] = $billingPlan->data_limit;
                }

                $api->createHotspotUser($routerUserData);
                $createdVouchers[] = $voucher;
            }

            $api->disconnect();
            DB::commit();

            return redirect()->route('vouchers.index')
                ->with('success', "Berhasil membuat {$request->quantity} voucher dengan batch ID: $batchId");

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->withErrors(['error' => 'Gagal membuat voucher: ' . $e->getMessage()]);
        }
    }

    public function show(Voucher $voucher)
    {
        $voucher->load(['router', 'billingPlan', 'hotspotUser']);

        return view('vouchers.show', compact('voucher'));
    }

    public function activate(Voucher $voucher)
    {
        if ($voucher->status !== 'unused') {
            return response()->json([
                'success' => false,
                'message' => 'Voucher sudah digunakan atau tidak valid!'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Enable user in MikroTik
            $api = new MikrotikApiService($voucher->router);
            $api->connect();
            
            $api->updateHotspotUser($voucher->code, ['disabled' => 'no']);
            $api->disconnect();

            // Update voucher status
            $voucher->update([
                'status' => 'used',
                'used_at' => now(),
                'used_by_ip' => request()->ip(),
            ]);

            // Update hotspot user
            if ($voucher->hotspotUser) {
                $voucher->hotspotUser->update(['status' => 'active']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher berhasil diaktifkan!'
            ]);

        } catch (Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengaktifkan voucher: ' . $e->getMessage()
            ], 400);
        }
    }

    public function print(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:vouchers,batch_id',
        ]);

        $vouchers = Voucher::with(['billingPlan'])
            ->where('batch_id', $request->batch_id)
            ->get();

        return view('vouchers.print', compact('vouchers'));
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->status === 'used') {
            return back()->withErrors(['error' => 'Tidak dapat menghapus voucher yang sudah digunakan!']);
        }

        DB::beginTransaction();
        try {
            // Delete from MikroTik
            $api = new MikrotikApiService($voucher->router);
            $api->connect();
            $api->deleteHotspotUser($voucher->code);
            $api->disconnect();

            // Delete hotspot user
            if ($voucher->hotspotUser) {
                $voucher->hotspotUser->delete();
            }

            // Delete voucher
            $voucher->delete();

            DB::commit();

            return redirect()->route('vouchers.index')
                ->with('success', 'Voucher berhasil dihapus!');

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withErrors(['error' => 'Gagal menghapus voucher: ' . $e->getMessage()]);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:vouchers,batch_id',
        ]);

        $vouchers = Voucher::where('batch_id', $request->batch_id)
            ->where('status', 'unused')
            ->get();

        if ($vouchers->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada voucher yang dapat dihapus di batch ini!']);
        }

        DB::beginTransaction();
        try {
            foreach ($vouchers as $voucher) {
                // Delete from MikroTik
                $api = new MikrotikApiService($voucher->router);
                $api->connect();
                $api->deleteHotspotUser($voucher->code);
                $api->disconnect();

                // Delete hotspot user
                if ($voucher->hotspotUser) {
                    $voucher->hotspotUser->delete();
                }

                // Delete voucher
                $voucher->delete();
            }

            DB::commit();

            return redirect()->route('vouchers.index')
                ->with('success', "Berhasil menghapus {$vouchers->count()} voucher dari batch {$request->batch_id}!");

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withErrors(['error' => 'Gagal menghapus voucher: ' . $e->getMessage()]);
        }
    }
}