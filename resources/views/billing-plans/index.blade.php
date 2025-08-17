@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Billing Plans</h1>
        <a href="{{ route('billing-plans.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Paket
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

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Paket Billing</h6>
        </div>
        <div class="card-body">
            @if($plans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nama Paket</th>
                                <th>Type</th>
                                <th>Time Limit</th>
                                <th>Data Limit</th>
                                <th>Rate Limit</th>
                                <th>Harga</th>
                                <th>Users</th>
                                <th>Vouchers</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                                <tr>
                                    <td>
                                        <strong>{{ $plan->name }}</strong>
                                        @if($plan->description)
                                            <br><small class="text-muted">{{ $plan->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $plan->type === 'unlimited' ? 'success' : ($plan->type === 'time' ? 'primary' : 'info') }}">
                                            {{ ucfirst($plan->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $plan->formatted_time_limit }}</td>
                                    <td>{{ $plan->formatted_data_limit }}</td>
                                    <td>{{ $plan->rate_limit ?? '-' }}</td>
                                    <td>{{ $plan->formatted_price }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $plan->hotspot_users_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">{{ $plan->vouchers_count }}</span>
                                    </td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="switch{{ $plan->id }}" 
                                                   {{ $plan->is_active ? 'checked' : '' }}
                                                   onchange="togglePlan({{ $plan->id }})">
                                            <label class="custom-control-label" for="switch{{ $plan->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('billing-plans.show', $plan) }}" class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('billing-plans.edit', $plan) }}" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($plan->hotspot_users_count == 0)
                                                <form action="{{ route('billing-plans.destroy', $plan) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Yakin ingin menghapus paket ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-tags fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-500">Belum ada paket billing</h5>
                    <p class="text-gray-400">Buat paket billing pertama untuk menentukan limit dan harga.</p>
                    <a href="{{ route('billing-plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Paket
                    </a>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function togglePlan(planId) {
        fetch(`/billing-plans/${planId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Optional: Show success message
            } else {
                // Revert switch if failed
                document.getElementById(`switch${planId}`).checked = !document.getElementById(`switch${planId}`).checked;
                alert('Gagal mengubah status paket');
            }
        })
        .catch(error => {
            // Revert switch if error
            document.getElementById(`switch${planId}`).checked = !document.getElementById(`switch${planId}`).checked;
            alert('Error: ' + error.message);
        });
    }
</script>
@endpush