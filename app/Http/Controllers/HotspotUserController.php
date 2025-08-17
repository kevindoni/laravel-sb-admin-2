<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Router;
use App\Models\BillingPlan;
use App\Services\MikrotikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class HotspotUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = HotspotUser::with(['router', 'billingPlan']);

        // Filter by router
        if ($request->router_id) {
            $query->where('router_id', $request->router_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('username', 'like', '%' . $request->search . '%')
                  ->orWhere('comment', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(20);
        $routers = Router::where('is_active', true)->get();

        return view('hotspot-users.index', compact('users', 'routers'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->get();
        $billingPlans = BillingPlan::where('is_active', true)->get();

        return view('hotspot-users.create', compact('routers', 'billingPlans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'billing_plan_id' => 'required|exists:billing_plans,id',
            'username' => 'required|string|max:255|unique:hotspot_users',
            'password' => 'required|string|max:255',
            'profile' => 'required|string|max:255',
            'comment' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        DB::beginTransaction();
        try {
            $router = Router::findOrFail($request->router_id);
            $billingPlan = BillingPlan::findOrFail($request->billing_plan_id);

            // Create user in database
            $hotspotUser = HotspotUser::create($request->all());

            // Create user in MikroTik
            $api = new MikrotikApiService($router);
            $api->connect();

            $routerUserData = [
                'name' => $hotspotUser->username,
                'password' => $hotspotUser->password,
                'profile' => $hotspotUser->profile,
                'comment' => $hotspotUser->comment ?? "Created by " . auth()->user()->name,
            ];

            if ($billingPlan->time_limit) {
                $routerUserData['limit-uptime'] = $billingPlan->time_limit . 'm';
            }

            if ($billingPlan->data_limit) {
                $routerUserData['limit-bytes-total'] = $billingPlan->data_limit;
            }

            $api->createHotspotUser($routerUserData);
            $api->disconnect();

            DB::commit();

            return redirect()->route('hotspot-users.index')
                ->with('success', 'User hotspot berhasil dibuat!');

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->withErrors(['error' => 'Gagal membuat user: ' . $e->getMessage()]);
        }
    }

    public function show(HotspotUser $hotspotUser)
    {
        $hotspotUser->load(['router', 'billingPlan', 'sessions' => function($query) {
            $query->latest()->limit(10);
        }]);

        // Get real-time session data
        $activeSession = null;
        try {
            $api = new MikrotikApiService($hotspotUser->router);
            $api->connect();
            
            $activeConnections = $api->getActiveConnections();
            $activeSession = collect($activeConnections)->firstWhere('user', $hotspotUser->username);
            
            $api->disconnect();
        } catch (Exception $e) {
            // Silent fail for real-time data
        }

        return view('hotspot-users.show', compact('hotspotUser', 'activeSession'));
    }

    public function edit(HotspotUser $hotspotUser)
    {
        $routers = Router::where('is_active', true)->get();
        $billingPlans = BillingPlan::where('is_active', true)->get();

        return view('hotspot-users.edit', compact('hotspotUser', 'routers', 'billingPlans'));
    }

    public function update(Request $request, HotspotUser $hotspotUser)
    {
        $request->validate([
            'billing_plan_id' => 'required|exists:billing_plans,id',
            'password' => 'required|string|max:255',
            'profile' => 'required|string|max:255',
            'status' => 'required|in:active,disabled,expired',
            'comment' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'balance' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update in MikroTik
            $api = new MikrotikApiService($hotspotUser->router);
            $api->connect();

            $routerUserData = [
                'password' => $request->password,
                'profile' => $request->profile,
                'comment' => $request->comment ?? $hotspotUser->comment,
                'disabled' => $request->status === 'disabled' ? 'yes' : 'no',
            ];

            $api->updateHotspotUser($hotspotUser->username, $routerUserData);
            $api->disconnect();

            // Update in database
            $hotspotUser->update($request->all());

            DB::commit();

            return redirect()->route('hotspot-users.index')
                ->with('success', 'User hotspot berhasil diperbarui!');

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->withErrors(['error' => 'Gagal memperbarui user: ' . $e->getMessage()]);
        }
    }

    public function destroy(HotspotUser $hotspotUser)
    {
        DB::beginTransaction();
        try {
            // Delete from MikroTik
            $api = new MikrotikApiService($hotspotUser->router);
            $api->connect();
            $api->deleteHotspotUser($hotspotUser->username);
            $api->disconnect();

            // Delete from database
            $hotspotUser->delete();

            DB::commit();

            return redirect()->route('hotspot-users.index')
                ->with('success', 'User hotspot berhasil dihapus!');

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withErrors(['error' => 'Gagal menghapus user: ' . $e->getMessage()]);
        }
    }

    public function disconnect(HotspotUser $hotspotUser)
    {
        try {
            $api = new MikrotikApiService($hotspotUser->router);
            $api->connect();
            $api->disconnectUser($hotspotUser->username);
            $api->disconnect();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil di-disconnect!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal disconnect user: ' . $e->getMessage()
            ], 400);
        }
    }

    public function generateBatch()
    {
        $routers = Router::where('is_active', true)->get();
        $billingPlans = BillingPlan::where('is_active', true)->get();

        return view('hotspot-users.batch', compact('routers', 'billingPlans'));
    }

    public function storeBatch(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'billing_plan_id' => 'required|exists:billing_plans,id',
            'quantity' => 'required|integer|min:1|max:100',
            'profile' => 'required|string|max:255',
            'username_prefix' => 'nullable|string|max:10',
            'password_length' => 'required|integer|min:4|max:12',
        ]);

        DB::beginTransaction();
        try {
            $router = Router::findOrFail($request->router_id);
            $billingPlan = BillingPlan::findOrFail($request->billing_plan_id);
            $batchId = 'BATCH_' . now()->format('YmdHis');
            
            $api = new MikrotikApiService($router);
            $api->connect();

            $createdUsers = [];

            for ($i = 1; $i <= $request->quantity; $i++) {
                $username = ($request->username_prefix ?? 'user') . '_' . Str::random(6);
                $password = Str::random($request->password_length);

                // Create in database
                $hotspotUser = HotspotUser::create([
                    'router_id' => $request->router_id,
                    'billing_plan_id' => $request->billing_plan_id,
                    'username' => $username,
                    'password' => $password,
                    'profile' => $request->profile,
                    'comment' => "Batch: $batchId",
                    'is_voucher' => true,
                ]);

                // Create in MikroTik
                $routerUserData = [
                    'name' => $username,
                    'password' => $password,
                    'profile' => $request->profile,
                    'comment' => "Batch: $batchId",
                ];

                if ($billingPlan->time_limit) {
                    $routerUserData['limit-uptime'] = $billingPlan->time_limit . 'm';
                }

                if ($billingPlan->data_limit) {
                    $routerUserData['limit-bytes-total'] = $billingPlan->data_limit;
                }

                $api->createHotspotUser($routerUserData);
                $createdUsers[] = $hotspotUser;
            }

            $api->disconnect();
            DB::commit();

            return redirect()->route('hotspot-users.index')
                ->with('success', "Berhasil membuat {$request->quantity} user hotspot!");

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->withErrors(['error' => 'Gagal membuat batch user: ' . $e->getMessage()]);
        }
    }
}