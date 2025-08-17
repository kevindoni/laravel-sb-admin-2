@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Network Monitoring</h1>
        <div>
            <select class="form-control" onchange="selectRouter(this.value)" style="display: inline-block; width: auto;">
                <option value="">Pilih Router</option>
                @foreach($routers as $router)
                    <option value="{{ $router->id }}" {{ $selectedRouter && $selectedRouter->id == $router->id ? 'selected' : '' }}>
                        {{ $router->name }}
                    </option>
                @endforeach
            </select>
            @if($selectedRouter)
                <button class="btn btn-sm btn-primary ml-2" onclick="refreshData()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            @endif
        </div>
    </div>

    @if(!$selectedRouter)
        <div class="card shadow mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-chart-line fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">Pilih Router untuk Monitoring</h5>
                <p class="text-gray-400">Pilih router dari dropdown di atas untuk melihat data monitoring real-time.</p>
            </div>
        </div>
    @else
        <!-- Router Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Status Router: {{ $selectedRouter->name }}
                            <span id="router-status" class="badge badge-{{ isset($monitoringData['error']) ? 'danger' : 'success' }} ml-2">
                                {{ isset($monitoringData['error']) ? 'Offline' : 'Online' }}
                            </span>
                        </h6>
                    </div>
                    <div class="card-body">
                        @if(isset($monitoringData['error']))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Error: {{ $monitoringData['error'] }}
                            </div>
                        @else
                            <div class="row">
                                <div class="col-md-3">
                                    <h6 class="font-weight-bold">System Info</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Board:</strong> {{ $monitoringData['system_info']['board-name'] ?? '-' }}</li>
                                        <li><strong>Version:</strong> {{ $monitoringData['system_info']['version'] ?? '-' }}</li>
                                        <li><strong>Uptime:</strong> {{ $monitoringData['system_info']['uptime'] ?? '-' }}</li>
                                    </ul>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="font-weight-bold">CPU & Memory</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>CPU:</strong> {{ $monitoringData['resource_usage']['cpu-load'] ?? '-' }}%</li>
                                        <li><strong>Memory:</strong> {{ $monitoringData['resource_usage']['free-memory'] ?? '-' }} / {{ $monitoringData['resource_usage']['total-memory'] ?? '-' }}</li>
                                        <li><strong>HDD:</strong> {{ $monitoringData['resource_usage']['free-hdd-space'] ?? '-' }} / {{ $monitoringData['resource_usage']['total-hdd-space'] ?? '-' }}</li>
                                    </ul>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="font-weight-bold">Active Users</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Total:</strong> <span id="active-users-count">{{ count($monitoringData['active_users']) }}</span></li>
                                        <li><strong>Online:</strong> <span class="text-success">{{ count($monitoringData['active_users']) }}</span></li>
                                        <li><strong>Registered:</strong> {{ $selectedRouter->hotspotUsers()->count() }}</li>
                                    </ul>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="font-weight-bold">Traffic</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>TX:</strong> <span id="total-tx">{{ $this->formatBytes(collect($monitoringData['active_users'])->sum('bytes-out')) }}</span></li>
                                        <li><strong>RX:</strong> <span id="total-rx">{{ $this->formatBytes(collect($monitoringData['active_users'])->sum('bytes-in')) }}</span></li>
                                        <li><strong>Total:</strong> <span id="total-traffic">{{ $this->formatBytes(collect($monitoringData['active_users'])->sum('bytes-out') + collect($monitoringData['active_users'])->sum('bytes-in')) }}</span></li>
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(!isset($monitoringData['error']))
            <!-- Active Users -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Active Users</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="activeUsersTable">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>IP Address</th>
                                            <th>MAC Address</th>
                                            <th>Session Time</th>
                                            <th>Upload</th>
                                            <th>Download</th>
                                            <th>Total Data</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="active-users-tbody">
                                        @foreach($monitoringData['active_users'] as $user)
                                            <tr>
                                                <td>{{ $user['user'] ?? '-' }}</td>
                                                <td>{{ $user['address'] ?? '-' }}</td>
                                                <td>{{ $user['mac-address'] ?? '-' }}</td>
                                                <td>{{ $this->formatTime($user['session-time'] ?? 0) }}</td>
                                                <td>{{ $this->formatBytes($user['bytes-out'] ?? 0) }}</td>
                                                <td>{{ $this->formatBytes($user['bytes-in'] ?? 0) }}</td>
                                                <td>{{ $this->formatBytes(($user['bytes-out'] ?? 0) + ($user['bytes-in'] ?? 0)) }}</td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm" onclick="disconnectUser('{{ $user['user'] ?? '' }}')">
                                                        <i class="fas fa-sign-out-alt"></i> Disconnect
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

@endsection

@push('scripts')
<script>
    let refreshInterval;
    let selectedRouterId = {{ $selectedRouter->id ?? 'null' }};

    function selectRouter(routerId) {
        if (routerId) {
            window.location.href = '{{ route("monitoring.index") }}?router_id=' + routerId;
        } else {
            window.location.href = '{{ route("monitoring.index") }}';
        }
    }

    function refreshData() {
        if (!selectedRouterId) return;

        fetch(`/monitoring/realtime/${selectedRouterId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMonitoringDisplay(data.data);
                } else {
                    console.error('Error refreshing data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function updateMonitoringDisplay(data) {
        // Update active users count
        document.getElementById('active-users-count').textContent = data.active_users.length;

        // Update traffic totals
        let totalTx = data.active_users.reduce((sum, user) => sum + (parseInt(user['bytes-out']) || 0), 0);
        let totalRx = data.active_users.reduce((sum, user) => sum + (parseInt(user['bytes-in']) || 0), 0);
        
        document.getElementById('total-tx').textContent = formatBytes(totalTx);
        document.getElementById('total-rx').textContent = formatBytes(totalRx);
        document.getElementById('total-traffic').textContent = formatBytes(totalTx + totalRx);

        // Update active users table
        updateActiveUsersTable(data.active_users);
    }

    function updateActiveUsersTable(activeUsers) {
        const tbody = document.getElementById('active-users-tbody');
        tbody.innerHTML = '';

        activeUsers.forEach(user => {
            const row = `
                <tr>
                    <td>${user.user || '-'}</td>
                    <td>${user.address || '-'}</td>
                    <td>${user['mac-address'] || '-'}</td>
                    <td>${formatTime(user['session-time'] || 0)}</td>
                    <td>${formatBytes(user['bytes-out'] || 0)}</td>
                    <td>${formatBytes(user['bytes-in'] || 0)}</td>
                    <td>${formatBytes((user['bytes-out'] || 0) + (user['bytes-in'] || 0))}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="disconnectUser('${user.user || ''}')">
                            <i class="fas fa-sign-out-alt"></i> Disconnect
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function disconnectUser(username) {
        if (!username || !confirm(`Disconnect user ${username}?`)) return;

        // Find the hotspot user
        fetch(`/hotspot-users?search=${username}`)
            .then(response => response.text())
            .then(html => {
                // This is a simplified approach - in real implementation, 
                // you'd need to get the user ID properly
                alert('Disconnect feature requires additional implementation');
            });
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    // Auto refresh every 30 seconds
    if (selectedRouterId) {
        refreshInterval = setInterval(refreshData, 30000);
    }

    // Clean up interval on page unload
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>
@endpush