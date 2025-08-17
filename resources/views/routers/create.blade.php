@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Router</h1>
        <a href="{{ route('routers.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
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
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Router</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('routers.store') }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="name">Nama Router <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Contoh: Router Kantor Utama" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="host">IP Address <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('host') is-invalid @enderror" 
                                           id="host" name="host" value="{{ old('host') }}" 
                                           placeholder="192.168.1.1" required>
                                    @error('host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="port">Port API <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('port') is-invalid @enderror" 
                                           id="port" name="port" value="{{ old('port', 8728) }}" 
                                           min="1" max="65535" required>
                                    @error('port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                           id="username" name="username" value="{{ old('username') }}" 
                                           placeholder="admin" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Password router" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">Lokasi</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   id="location" name="location" value="{{ old('location') }}" 
                                   placeholder="Contoh: Kantor Pusat Jakarta">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Deskripsi tambahan tentang router ini">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Router
                            </button>
                            <a href="{{ route('routers.index') }}" class="btn btn-secondary">
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
                    <h6 class="m-0 font-weight-bold text-info">Petunjuk</h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Persyaratan Router:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> RouterOS v6.x atau v7.x</li>
                        <li><i class="fas fa-check text-success"></i> API service aktif</li>
                        <li><i class="fas fa-check text-success"></i> User dengan akses API</li>
                        <li><i class="fas fa-check text-success"></i> Hotspot service setup</li>
                    </ul>

                    <hr>

                    <h6 class="font-weight-bold">Cara Mengaktifkan API:</h6>
                    <div class="bg-light p-2 rounded mb-2">
                        <code>/ip service enable api</code>
                    </div>
                    <div class="bg-light p-2 rounded">
                        <code>/ip service set api port=8728</code>
                    </div>

                    <hr>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <small>Pastikan router dapat diakses dari server aplikasi ini.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection