@extends('layouts.admin')

@section('main-content')

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Dashboard MikroTik Management') }}</h1>

    @if (session('success'))
    <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success border-left-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">

        <!-- Total Routers -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Router</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['total_routers'] }}</div>
                            <div class="text-xs text-success">
                                <i class="fas fa-check-circle"></i> {{ $widget['active_routers'] }} Aktif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-router fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hotspot Users -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Hotspot Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['total_users'] }}</div>
                            <div class="text-xs text-success">
                                <i class="fas fa-user-check"></i> {{ $widget['active_users'] }} Aktif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vouchers -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Vouchers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['total_vouchers'] }}</div>
                            <div class="text-xs text-info">
                                <i class="fas fa-ticket-alt"></i> {{ $widget['unused_vouchers'] }} Belum Digunakan
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Revenue Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($widget['today_revenue'], 0, ',', '.') }}</div>
                            <div class="text-xs text-warning">
                                <i class="fas fa-calendar-alt"></i> Bulan Ini: Rp {{ number_format($widget['monthly_revenue'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- Recent Hotspot Users -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">User Hotspot Terbaru</h6>
                    <a href="{{ route('hotspot-users.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    @if($recentUsers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Router</th>
                                        <th>Paket</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentUsers as $user)
                                    <tr>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ $user->router->name }}</td>
                                        <td>{{ $user->billingPlan->name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">Belum ada user hotspot</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Popular Billing Plans -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Paket Populer</h6>
                    <a href="{{ route('billing-plans.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    @if($popularPlans->count() > 0)
                        @foreach($popularPlans as $plan)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="mb-0">{{ $plan->name }}</h6>
                                <small class="text-muted">{{ $plan->formatted_price }} - {{ $plan->type }}</small>
                            </div>
                            <div class="text-right">
                                <span class="text-primary font-weight-bold">{{ $plan->hotspot_users_count }}</span>
                                <small class="text-muted">users</small>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-center text-muted">Belum ada paket billing</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <!-- Recent Transactions -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Transaksi Terbaru</h6>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    @if($recentTransactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->invoice_number }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</td>
                                        <td>{{ $transaction->formatted_amount }}</td>
                                        <td>{{ $transaction->payment_method ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $transaction->status_badge }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">Belum ada transaksi</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('routers.create') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Tambah Router
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('billing-plans.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus"></i> Buat Paket
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('vouchers.create') }}" class="btn btn-info btn-block">
                                <i class="fas fa-ticket-alt"></i> Generate Voucher
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('monitoring.index') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-chart-line"></i> Monitoring
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
