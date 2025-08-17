@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Paket Billing</h1>
        <a href="{{ route('billing-plans.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Paket</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('billing-plans.store') }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="name">Nama Paket <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Contoh: Paket 1 Jam, Paket 1GB" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="type">Tipe Paket <span class="text-danger">*</span></label>
                            <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required onchange="toggleLimits()">
                                <option value="">Pilih Tipe Paket</option>
                                <option value="time" {{ old('type') === 'time' ? 'selected' : '' }}>Time Based (Berdasarkan Waktu)</option>
                                <option value="data" {{ old('type') === 'data' ? 'selected' : '' }}>Data Based (Berdasarkan Kuota)</option>
                                <option value="unlimited" {{ old('type') === 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="time-limit-group">
                                    <label for="time_limit">Time Limit (menit)</label>
                                    <input type="number" class="form-control @error('time_limit') is-invalid @enderror" 
                                           id="time_limit" name="time_limit" value="{{ old('time_limit') }}" 
                                           placeholder="60" min="1">
                                    <small class="form-text text-muted">Kosongkan untuk unlimited time</small>
                                    @error('time_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" id="data-limit-group">
                                    <label for="data_limit">Data Limit (MB)</label>
                                    <input type="number" class="form-control @error('data_limit') is-invalid @enderror" 
                                           id="data_limit" name="data_limit" value="{{ old('data_limit') }}" 
                                           placeholder="1024" min="1">
                                    <small class="form-text text-muted">Dalam MB (1024 MB = 1 GB)</small>
                                    @error('data_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="rate_limit">Rate Limit (Bandwidth)</label>
                            <input type="text" class="form-control @error('rate_limit') is-invalid @enderror" 
                                   id="rate_limit" name="rate_limit" value="{{ old('rate_limit') }}" 
                                   placeholder="2M/1M">
                            <small class="form-text text-muted">Format: Upload/Download (contoh: 2M/1M untuk 2Mbps up / 1Mbps down)</small>
                            @error('rate_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">Harga <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" class="form-control @error('price') is-invalid @enderror" 
                                               id="price" name="price" value="{{ old('price') }}" 
                                               placeholder="5000" min="0" step="100" required>
                                    </div>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="validity_period">Masa Berlaku <span class="text-danger">*</span></label>
                                    <select class="form-control @error('validity_period') is-invalid @enderror" id="validity_period" name="validity_period" required>
                                        <option value="1h" {{ old('validity_period') === '1h' ? 'selected' : '' }}>1 Jam</option>
                                        <option value="1d" {{ old('validity_period', '1d') === '1d' ? 'selected' : '' }}>1 Hari</option>
                                        <option value="7d" {{ old('validity_period') === '7d' ? 'selected' : '' }}>7 Hari</option>
                                        <option value="30d" {{ old('validity_period') === '30d' ? 'selected' : '' }}>30 Hari</option>
                                    </select>
                                    @error('validity_period')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Deskripsi paket ini">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Paket
                            </button>
                            <a href="{{ route('billing-plans.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Contoh Paket</h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Paket Time Based:</h6>
                    <ul class="list-unstyled mb-3">
                        <li><strong>1 Jam:</strong> Time Limit: 60 menit</li>
                        <li><strong>1 Hari:</strong> Time Limit: 1440 menit</li>
                        <li><strong>1 Minggu:</strong> Time Limit: 10080 menit</li>
                    </ul>

                    <h6 class="font-weight-bold">Paket Data Based:</h6>
                    <ul class="list-unstyled mb-3">
                        <li><strong>500 MB:</strong> Data Limit: 500</li>
                        <li><strong>1 GB:</strong> Data Limit: 1024</li>
                        <li><strong>5 GB:</strong> Data Limit: 5120</li>
                    </ul>

                    <h6 class="font-weight-bold">Rate Limit Examples:</h6>
                    <ul class="list-unstyled">
                        <li><code>1M/512k</code> - 1Mbps up / 512Kbps down</li>
                        <li><code>2M/1M</code> - 2Mbps up / 1Mbps down</li>
                        <li><code>5M/5M</code> - 5Mbps up/down</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function toggleLimits() {
        const type = document.getElementById('type').value;
        const timeGroup = document.getElementById('time-limit-group');
        const dataGroup = document.getElementById('data-limit-group');
        const timeInput = document.getElementById('time_limit');
        const dataInput = document.getElementById('data_limit');

        if (type === 'time') {
            timeGroup.style.display = 'block';
            dataGroup.style.display = 'none';
            timeInput.required = true;
            dataInput.required = false;
            dataInput.value = '';
        } else if (type === 'data') {
            timeGroup.style.display = 'none';
            dataGroup.style.display = 'block';
            timeInput.required = false;
            dataInput.required = true;
            timeInput.value = '';
        } else if (type === 'unlimited') {
            timeGroup.style.display = 'none';
            dataGroup.style.display = 'none';
            timeInput.required = false;
            dataInput.required = false;
            timeInput.value = '';
            dataInput.value = '';
        } else {
            timeGroup.style.display = 'block';
            dataGroup.style.display = 'block';
            timeInput.required = false;
            dataInput.required = false;
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleLimits();
    });
</script>
@endpush