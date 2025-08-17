<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\HotspotUser;
use App\Models\UserSession;
use App\Services\MikrotikApiService;
use Illuminate\Http\Request;
use Exception;

class MonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $routers = Router::where('is_active', true)->get();
        $selectedRouter = null;
        $monitoringData = [];

        if ($request->router_id) {
            $selectedRouter = Router::findOrFail($request->router_id);
            $monitoringData = $this->getMonitoringData($selectedRouter);
        }

        return view('monitoring.index', compact('routers', 'selectedRouter', 'monitoringData'));
    }

    public function getRealtimeData(Router $router)
    {
        try {
            $data = $this->getMonitoringData($router);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function getMonitoringData(Router $router): array
    {
        $data = [
            'router_status' => 'offline',
            'system_info' => [],
            'active_users' => [],
            'interface_stats' => [],
            'resource_usage' => [],
            'error' => null
        ];

        try {
            $api = new MikrotikApiService($router);
            $api->connect();

            // Get system resource info
            $systemResource = $api->getSystemInfo();
            $data['system_info'] = $systemResource[0] ?? [];
            $data['router_status'] = 'online';

            // Get active connections
            $activeConnections = $api->getActiveConnections();
            $data['active_users'] = $activeConnections;

            // Get interface statistics
            $api->write('/interface/print');
            $interfaces = $api->parseResponse($api->read());
            $data['interface_stats'] = $interfaces;

            // Get additional resource info
            $api->write('/system/resource/print');
            $resources = $api->parseResponse($api->read());
            $data['resource_usage'] = $resources[0] ?? [];

            $api->disconnect();

            // Update database with latest session info
            $this->updateSessionData($router, $activeConnections);

        } catch (Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    private function updateSessionData(Router $router, array $activeConnections): void
    {
        foreach ($activeConnections as $connection) {
            $username = $connection['user'] ?? null;
            if (!$username) continue;

            $hotspotUser = $router->hotspotUsers()->where('username', $username)->first();
            if (!$hotspotUser) continue;

            // Update or create session record
            UserSession::updateOrCreate(
                [
                    'router_id' => $router->id,
                    'session_id' => $connection['.id'] ?? null,
                ],
                [
                    'hotspot_user_id' => $hotspotUser->id,
                    'username' => $username,
                    'nas_ip' => $connection['nas-ip'] ?? '',
                    'framed_ip' => $connection['address'] ?? '',
                    'calling_station_id' => $connection['mac-address'] ?? '',
                    'started_at' => now(), // Should be parsed from router
                    'last_update' => now(),
                    'session_time' => (int)($connection['session-time'] ?? 0),
                    'upload_bytes' => (int)($connection['bytes-in'] ?? 0),
                    'download_bytes' => (int)($connection['bytes-out'] ?? 0),
                    'is_active' => true,
                ]
            );

            // Update hotspot user info
            $hotspotUser->update([
                'last_login_at' => now(),
                'last_ip' => $connection['address'] ?? null,
                'last_mac' => $connection['mac-address'] ?? null,
                'time_used' => (int)($connection['session-time'] ?? 0),
            ]);
        }

        // Mark inactive sessions
        $activeSessionIds = collect($activeConnections)->pluck('.id')->filter();
        if ($activeSessionIds->isNotEmpty()) {
            UserSession::where('router_id', $router->id)
                ->where('is_active', true)
                ->whereNotIn('session_id', $activeSessionIds)
                ->update(['is_active' => false]);
        }
    }

    public function bandwidthChart(Router $router)
    {
        try {
            $api = new MikrotikApiService($router);
            $api->connect();

            // Get interface traffic
            $api->write('/interface/monitor-traffic', [
                '=interface=ether1',
                '=duration=1'
            ]);
            
            $trafficData = $api->parseResponse($api->read());
            $api->disconnect();

            return response()->json([
                'success' => true,
                'data' => $trafficData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}