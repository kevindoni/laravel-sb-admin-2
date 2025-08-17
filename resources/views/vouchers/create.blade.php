@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Generate Voucher</h1>
        <a href="{{ route('vouchers.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
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
                    <h6 class="m-0 font-weight-bold text-primary">Setting Voucher</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('vouchers.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="router_id">Router <span class="text-danger">*</span></label>
                                    <select class="form-control @error('router_id') is-invalid @enderror" id="router_id" name="router_id" required>
                                        <option value="">Pilih Router</option>
                                        @foreach($routers as $router)
                                            <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>
                                                {{ $router->name }} ({{ $router->host }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('router_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_plan_id">Paket Billing <span class="text-danger">*</span></label>
                                    <select class="form-control @error('billing_plan_id') is-invalid @enderror" id="billing_plan_id" name="billing_plan_id" required>
                                        <option value="">Pilih Paket</option>
                                        @foreach($billingPlans as $plan)
                                            <option value="{{ $plan->id }}" {{ old('billing_plan_id') == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }} - {{ $plan->formatted_price }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('billing_plan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="quantity">Jumlah Voucher <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('quantity') is-invalid @enderror" 
                                           id="quantity" name="quantity" value="{{ old('quantity', 10) }}" 
                                           min="1" max="500" required>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="code_length">Panjang Kode <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('code_length') is-invalid @enderror" 
                                           id="code_length" name="code_length" value="{{ old('code_length', 8) }}" 
                                           min="4" max="12" required>
                                    @error('code_length')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password_length">Panjang Password <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('password_length') is-invalid @enderror" 
                                           id="password_length" name="password_length" value="{{ old('password_length', 6) }}" 
                                           min="4" max="12" required>
                                    @error('password_length')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code_prefix">Prefix Kode</label>
                                    <input type="text" class="form-control @error('code_prefix') is-invalid @enderror" 
                                           id="code_prefix" name="code_prefix" value="{{ old('code_prefix') }}" 
                                           placeholder="WF" maxlength="5">
                                    <small class="form-text text-muted">Awalan untuk kode voucher (opsional)</small>
                                    @error('code_prefix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="selling_price">Harga Jual</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" class="form-control @error('selling_price') is-invalid @enderror" 
                                               id="selling_price" name="selling_price" value="{{ old('selling_price') }}" 
                                               placeholder="5000" min="0" step="100">
                                    </div>
                                    <small class="form-text text-muted">Kosongkan untuk menggunakan harga paket</small>
                                    @error('selling_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="expires_at">Tanggal Kadaluarsa</label>
                            <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" 
                                   id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                            <small class="form-text text-muted">Kosongkan jika tidak ada batas waktu kadaluarsa</small>
                            @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="comment">Keterangan</label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" 
                                      id="comment" name="comment" rows="2" 
                                      placeholder="Keterangan tambahan">{{ old('comment') }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-ticket-alt"></i> Generate Voucher
                            </button>
                            <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">
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
                    <h6 class="m-0 font-weight-bold text-info">Preview Voucher</h6>
                </div>
                <div class="card-body">
                    <div class="border p-3 text-center" style="border-style: dashed;">
                        <h6 class="font-weight-bold">WIFI VOUCHER</h6>
                        <hr>
                        <div class="row">
                            <div class="col-6 text-left">
                                <small>Username:</small><br>
                                <strong id="preview-code">WF12AB34</strong>
                            </div>
                            <div class="col-6 text-right">
                                <small>Password:</small><br>
                                <strong id="preview-password">abc123</strong>
                            </div>
                        </div>
                        <hr>
                        <small class="text-muted">
                            <div>Paket: <span id="preview-plan">-</span></div>
                            <div>Harga: <span id="preview-price">-</span></div>
                            <div>Expired: <span id="preview-expires">-</span></div>
                        </small>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-lightbulb text-warning"></i> Gunakan prefix untuk membedakan jenis voucher</li>
                        <li><i class="fas fa-lightbulb text-warning"></i> Kode yang lebih panjang lebih aman</li>
                        <li><i class="fas fa-lightbulb text-warning"></i> Set masa kadaluarsa untuk kontrol inventory</li>
                        <li><i class="fas fa-lightbulb text-warning"></i> Maksimal 500 voucher per batch</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function updatePreview() {
        const prefix = document.getElementById('code_prefix').value || '';
        const codeLength = document.getElementById('code_length').value || 8;
        const passwordLength = document.getElementById('password_length').value || 6;
        
        // Generate preview codes
        const sampleCode = prefix + 'A'.repeat(Math.max(1, codeLength - prefix.length));
        const samplePassword = 'a'.repeat(passwordLength);
        
        document.getElementById('preview-code').textContent = sampleCode;
        document.getElementById('preview-password').textContent = samplePassword;
        
        // Update plan info if selected
        const planSelect = document.getElementById('billing_plan_id');
        const selectedPlan = planSelect.options[planSelect.selectedIndex];
        document.getElementById('preview-plan').textContent = selectedPlan.text || '-';
        
        // Update expiry
        const expiresAt = document.getElementById('expires_at').value;
        document.getElementById('preview-expires').textContent = expiresAt ? new Date(expiresAt).toLocaleDateString('id-ID') : 'No Limit';
    }

    // Add event listeners
    document.addEventListener('DOMContentLoaded', function() {
        ['code_prefix', 'code_length', 'password_length', 'billing_plan_id', 'expires_at'].forEach(function(id) {
            document.getElementById(id).addEventListener('input', updatePreview);
            document.getElementById(id).addEventListener('change', updatePreview);
        });
        
        updatePreview();
    });
</script>
@endpush