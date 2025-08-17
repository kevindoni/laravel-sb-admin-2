@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Router Management</h1>
        <a href="{{ route('routers.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Router
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Router MikroTik</h6>
        </div>
        <div class="card-body">
            @if($routers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Host</th>
                                <th>Port</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Users</th>
                                <th>Vouchers</th>
                                <th>Last Connected</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($routers as $router)
                                <tr>
                                    <td>
                                        <strong>{{ $router->name }}</strong>
                                        @if($router->description)
                                            <br><small class="text-muted">{{ $router->description }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $router->host }}</td>
                                    <td>{{ $router->port }}</td>
                                    <td>{{ $router->location ?? '-' }}</td>
                                    <td>
                                        @if($router->is_active)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Aktif
                                            </span>
                                            @if($router->isOnline())
                                                <br><small class="text-success">
                                                    <i class="fas fa-circle"></i> Online
                                                </small>
                                            @else
                                                <br><small class="text-danger">
                                                    <i class="fas fa-circle"></i> Offline
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times-circle"></i> Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $router->hotspot_users_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">{{ $router->vouchers_count }}</span>
                                    </td>
                                    <td>
                                        @if($router->last_connected_at)
                                            {{ $router->last_connected_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('routers.show', $router) }}" class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('routers.edit', $router) }}" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-success btn-sm" onclick="testConnection({{ $router->id }})" title="Test Connection">
                                                <i class="fas fa-plug"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="syncUsers({{ $router->id }})" title="Sync Users">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                            <form action="{{ route('routers.destroy', $router) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Yakin ingin menghapus router ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-router fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-500">Belum ada router</h5>
                    <p class="text-gray-400">Tambahkan router MikroTik pertama Anda untuk memulai.</p>
                    <a href="{{ route('routers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Router
                    </a>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function testConnection(routerId) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch(`/routers/${routerId}/test-connection`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }

    function syncUsers(routerId) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch(`/routers/${routerId}/sync-users`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
</script>
@endpush