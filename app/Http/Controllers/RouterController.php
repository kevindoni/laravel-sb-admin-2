<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\MikrotikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RouterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $routers = Router::withCount(['hotspotUsers', 'vouchers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('routers.index', compact('routers'));
    }

    public function create()
    {
        return view('routers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $router = Router::create($request->all());

            // Test connection
            $api = new MikrotikApiService($router);
            $api->connect();
            
            // Get system info
            $systemInfo = $api->getSystemInfo();
            $router->update(['system_info' => $systemInfo]);
            
            $api->disconnect();

            DB::commit();
            
            return redirect()->route('routers.index')
                ->with('success', 'Router berhasil ditambahkan dan terhubung!');

        } catch (Exception $e) {
            DB::rollback();
            
            if (isset($router)) {
                $router->delete();
            }
            
            return back()->withInput()
                ->withErrors(['connection' => 'Gagal terhubung ke router: ' . $e->getMessage()]);
        }
    }

    public function show(Router $router)
    {
        $router->load(['hotspotUsers', 'vouchers', 'userSessions']);
        
        // Get real-time data if router is online
        $realtimeData = [];
        try {
            if ($router->isOnline()) {
                $api = new MikrotikApiService($router);
                $api->connect();
                
                $realtimeData = [
                    'system_info' => $api->getSystemInfo(),
                    'active_users' => $api->getActiveConnections(),
                ];
                
                $api->disconnect();
            }
        } catch (Exception $e) {
            $realtimeData['error'] = $e->getMessage();
        }

        return view('routers.show', compact('router', 'realtimeData'));
    }

    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Test connection with new credentials
            $testRouter = new Router($request->all());
            $api = new MikrotikApiService($testRouter);
            $api->connect();
            $api->disconnect();

            $router->update($request->all());

            DB::commit();
            
            return redirect()->route('routers.index')
                ->with('success', 'Router berhasil diperbarui!');

        } catch (Exception $e) {
            DB::rollback();
            
            return back()->withInput()
                ->withErrors(['connection' => 'Gagal terhubung ke router: ' . $e->getMessage()]);
        }
    }

    public function destroy(Router $router)
    {
        try {
            $router->delete();
            
            return redirect()->route('routers.index')
                ->with('success', 'Router berhasil dihapus!');
                
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus router: ' . $e->getMessage()]);
        }
    }

    public function testConnection(Router $router)
    {
        try {
            $api = new MikrotikApiService($router);
            $api->connect();
            
            $systemInfo = $api->getSystemInfo();
            $router->update([
                'system_info' => $systemInfo,
                'last_connected_at' => now()
            ]);
            
            $api->disconnect();

            return response()->json([
                'success' => true,
                'message' => 'Koneksi berhasil!',
                'system_info' => $systemInfo
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage()
            ], 400);
        }
    }

    public function syncUsers(Router $router)
    {
        try {
            $api = new MikrotikApiService($router);
            $api->connect();
            
            $routerUsers = $api->getHotspotUsers();
            $activeConnections = $api->getActiveConnections();
            
            $api->disconnect();

            // Sync users data with database
            $synced = 0;
            foreach ($routerUsers as $routerUser) {
                $username = $routerUser['name'] ?? null;
                if (!$username) continue;

                // Find corresponding database user
                $hotspotUser = $router->hotspotUsers()->where('username', $username)->first();
                if ($hotspotUser) {
                    // Update last seen info
                    $activeUser = collect($activeConnections)->firstWhere('user', $username);
                    if ($activeUser) {
                        $hotspotUser->update([
                            'last_login_at' => now(),
                            'last_ip' => $activeUser['address'] ?? null,
                            'last_mac' => $activeUser['mac-address'] ?? null,
                        ]);
                    }
                    $synced++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil sinkronisasi $synced users",
                'users_count' => count($routerUsers),
                'active_count' => count($activeConnections)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi gagal: ' . $e->getMessage()
            ], 400);
        }
    }
}