@extends('layouts.admin')

@section('title', 'Edit ODP')

@section('page-title', 'Edit ODP: ' . $odp->code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}">ODP</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@push('css')
<style>
    #map { height: 400px; border-radius: 5px; }
    .connection-type-card { cursor: pointer; transition: all 0.3s; border: 2px solid #dee2e6; }
    .connection-type-card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); border-color: #adb5bd; }
    .connection-type-card.active { border-color: #007bff !important; background-color: #e7f1ff; box-shadow: 0 0 0 3px rgba(0,123,255,0.25); }
    .connection-type-card .badge { font-size: 0.7rem; }
    .connection-type-card i { transition: transform 0.3s; }
    .connection-type-card:hover i { transform: scale(1.1); }
    .custom-odp-marker { background: transparent; border: none; }
    .leaflet-control-layers { border-radius: 8px; }
    .leaflet-control-layers-toggle { width: 36px; height: 36px; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.odps.update', $odp) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="connection_type" id="connection_type" value="{{ old('connection_type', $connectionType) }}">
    
    <div class="row">
        <div class="col-md-8">
            <!-- Connection Type Selection -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Jenis Koneksi</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'odc' ? 'active' : '' }}" 
                                 data-type="odc" role="button" tabindex="0">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1">Via ODC</h6>
                                    <small class="text-muted">OLT → ODC → ODP</small>
                                    <br><span class="badge badge-primary">Standard</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'olt' ? 'active' : '' }}" 
                                 data-type="olt" role="button" tabindex="0">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-server fa-2x text-success mb-2"></i>
                                    <h6 class="mb-1">Direct OLT</h6>
                                    <small class="text-muted">OLT → ODP</small>
                                    <br><span class="badge badge-success">Tanpa ODC</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'cascade' ? 'active' : '' }}" 
                                 data-type="cascade" role="button" tabindex="0">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-sitemap fa-2x text-warning mb-2"></i>
                                    <h6 class="mb-1">Cascade/Relay</h6>
                                    <small class="text-muted">ODP → ODP</small>
                                    <br><span class="badge badge-warning">Estafet Splitter</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <!-- ODC Selection (shown when connection_type = odc) -->
                    <div id="odc-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'odc' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="odc_id">ODC <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('odc_id') is-invalid @enderror" 
                                            id="odc_id" name="odc_id" style="width: 100%;">
                                        <option value="">-- Pilih ODC --</option>
                                        @foreach($odcs as $odc)
                                            <option value="{{ $odc->id }}" 
                                                    data-total-ports="{{ $odc->total_ports }}"
                                                    {{ old('odc_id', $odp->odc_id) == $odc->id ? 'selected' : '' }}>
                                                {{ $odc->code }} - {{ $odc->name }} ({{ $odc->available_ports }} port tersedia)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('odc_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="odc_port">Port ODC <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('odc_port') is-invalid @enderror" 
                                           id="odc_port" name="odc_port" value="{{ old('odc_port', $odp->odc_port) }}" min="1">
                                    @error('odc_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- ODC Splitter Configuration -->
                            @include('admin.odps.partials.splitter-config', [
                                'prefix' => 'odc',
                                'inputPowerDefault' => -3,
                                'inputPowerLabel' => 'Input Power ODC',
                                'equalSplitters' => $equalSplitters,
                                'unequalSplitters' => $unequalSplitters
                            ])
                    </div>

                    <!-- OLT Selection (shown when connection_type = olt) -->
                    <div id="olt-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'olt' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_id">OLT <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('olt_id') is-invalid @enderror" 
                                            id="olt_id" name="olt_id" style="width: 100%;">
                                        <option value="">-- Pilih OLT --</option>
                                        @foreach($olts as $olt)
                                            <option value="{{ $olt->id }}" 
                                                    data-pon-ports="{{ $olt->pon_ports }}"
                                                    {{ old('olt_id', $odp->olt_id) == $olt->id ? 'selected' : '' }}>
                                                {{ $olt->name }} ({{ $olt->pon_ports }} PON)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('olt_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_pon_port">PON Port <span class="text-danger">*</span></label>
                                    <select class="form-control @error('olt_pon_port') is-invalid @enderror" 
                                            id="olt_pon_port" name="olt_pon_port">
                                        <option value="">-- Pilih OLT Dulu --</option>
                                    </select>
                                    <small class="text-muted" id="pon_port_info"></small>
                                    @error('olt_pon_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_slot">Slot (Opsional)</label>
                                    <input type="number" class="form-control @error('olt_slot') is-invalid @enderror" 
                                           id="olt_slot" name="olt_slot" value="{{ old('olt_slot', $odp->olt_slot) }}" min="0">
                                    @error('olt_slot')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- OLT Direct Splitter Configuration -->
                            @include('admin.odps.partials.splitter-config', [
                                'prefix' => 'olt',
                                'inputPowerDefault' => 4,
                                'inputPowerLabel' => 'TX Power OLT',
                                'equalSplitters' => $equalSplitters,
                                'unequalSplitters' => $unequalSplitters
                            ])
                    </div>

                    <!-- Parent ODP Selection (shown when connection_type = cascade) -->
                    <div id="cascade-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'cascade' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="parent_odp_id">Parent ODP <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('parent_odp_id') is-invalid @enderror" 
                                            id="parent_odp_id" name="parent_odp_id" style="width: 100%;">
                                        <option value="">-- Pilih ODP Parent --</option>
                                        @foreach($parentOdps as $podp)
                                            <option value="{{ $podp->id }}" 
                                                    {{ old('parent_odp_id', $odp->parent_odp_id) == $podp->id ? 'selected' : '' }}>
                                                {{ $podp->code }} - {{ $podp->name }} (Level {{ $podp->splitter_level ?? 1 }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_odp_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">ODP ini akan menjadi turunan dari parent (estafet splitter)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="splitter_level_display">Splitter Level</label>
                                    <input type="text" class="form-control" id="splitter_level_display" readonly 
                                           value="Level {{ $odp->splitter_level ?? 2 }}">
                                </div>
                            </div>
                        </div>
                            @include('admin.odps.partials.splitter-config', [
                                'prefix' => 'cascade',
                                'inputPowerDefault' => $odp->input_power ?? 4,
                                'inputPowerLabel' => 'Input Power Parent ODP',
                                'equalSplitters' => $equalSplitters,
                                'unequalSplitters' => $unequalSplitters
                            ])
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Kode ODP</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       id="code" name="code" value="{{ old('code', $odp->code) }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama ODP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $odp->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="active" {{ old('status', $odp->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="maintenance" {{ old('status', $odp->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="inactive" {{ old('status', $odp->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pole_number">Nomor Tiang</label>
                                <input type="text" class="form-control @error('pole_number') is-invalid @enderror" 
                                       id="pole_number" name="pole_number" value="{{ old('pole_number', $odp->pole_number) }}" 
                                       placeholder="Contoh: T-001">
                                @error('pole_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="2">{{ old('address', $odp->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                </div>
                <div class="card-body">
                    <div id="map"></div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latitude">Latitude</label>
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" 
                                       id="latitude" name="latitude" value="{{ old('latitude', $odp->latitude) }}" step="any">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" 
                                       id="longitude" name="longitude" value="{{ old('longitude', $odp->longitude) }}" step="any">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Klik pada peta untuk menentukan lokasi atau masukkan koordinat manual</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Port Configuration -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Konfigurasi Port</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="total_ports">Total Port <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('total_ports') is-invalid @enderror" 
                               id="total_ports" name="total_ports" value="{{ old('total_ports', $odp->total_ports) }}" 
                               min="{{ $odp->used_ports }}" max="100" required>
                        @error('total_ports')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal {{ $odp->used_ports }} (sudah terpakai)</small>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <small>
                            <strong>Port Terpakai:</strong> {{ $odp->used_ports }} / {{ $odp->total_ports }}<br>
                            <strong>Port Tersedia:</strong> {{ $odp->available_ports }}
                        </small>
                    </div>
                </div>
            </div>

            <!-- Physical Specifications -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Spesifikasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="odp_type">Jenis ODP <span class="text-danger">*</span></label>
                        <select class="form-control @error('odp_type') is-invalid @enderror" id="odp_type" name="odp_type" required>
                            <option value="gpon" {{ old('odp_type', $odp->odp_type) === 'gpon' ? 'selected' : '' }}>GPON</option>
                            <option value="epon" {{ old('odp_type', $odp->odp_type) === 'epon' ? 'selected' : '' }}>EPON</option>
                            <option value="xgpon" {{ old('odp_type', $odp->odp_type) === 'xgpon' ? 'selected' : '' }}>XG-PON</option>
                            <option value="xgspon" {{ old('odp_type', $odp->odp_type) === 'xgspon' ? 'selected' : '' }}>XGS-PON</option>
                        </select>
                        @error('odp_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Satu lokasi tiang bisa memiliki ODP GPON dan ODP EPON sekaligus.</small>
                    </div>

                    <div class="form-group">
                        <label for="box_type">Tipe Box</label>
                        <input type="text" class="form-control @error('box_type') is-invalid @enderror" 
                               id="box_type" name="box_type" value="{{ old('box_type', $odp->box_type) }}" 
                               placeholder="Contoh: ODP 8 Core">
                        @error('box_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="splitter_type">Tipe Splitter</label>
                        <input type="text" class="form-control @error('splitter_type') is-invalid @enderror" 
                               id="splitter_type" name="splitter_type" value="{{ old('splitter_type', $odp->splitter_type) }}"
                               placeholder="Contoh: 1:8">
                        @error('splitter_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Optical Power Calculator -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Optical Power Budget Calculator</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($odp->output_power)
                    <div class="alert alert-{{ $odp->output_power >= -25 ? 'success' : ($odp->output_power >= -28 ? 'info' : ($odp->output_power >= -30 ? 'warning' : 'danger')) }} mb-3">
                        <strong><i class="fas fa-bolt mr-1"></i> Power Tersimpan:</strong>
                        Output: <strong>{{ $odp->output_power }} dBm</strong>
                        @if($odp->cascade_output_power)
                        | Cascade: <strong>{{ $odp->cascade_output_power }} dBm</strong>
                        @endif
                        @if($odp->is_power_manual)
                        <span class="badge badge-secondary ml-2">Manual</span>
                        @else
                        <span class="badge badge-primary ml-2">Auto</span>
                        @endif
                    </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Kalkulator ini membantu menghitung power budget dari sumber (OLT/ODC/Parent ODP) ke ODP ini.
                        <button type="button" class="btn btn-sm btn-info ml-2" id="btn-fetch-power">
                            <i class="fas fa-sync-alt mr-1"></i> Baca Power Otomatis
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="input_power">Input Power (dBm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control @error('input_power') is-invalid @enderror" 
                                           id="input_power" name="input_power" value="{{ old('input_power', $odp->input_power ?? 4) }}"
                                           placeholder="Contoh: 4.0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">dBm</span>
                                    </div>
                                </div>
                                <small id="power-source-info" class="form-text text-muted">TX Power dari sumber</small>
                                @error('input_power')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fiber_distance">Jarak Fiber (km)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control @error('fiber_distance') is-invalid @enderror" 
                                           id="fiber_distance" name="fiber_distance" value="{{ old('fiber_distance', $odp->fiber_distance ?? 0) }}"
                                           placeholder="0.5">
                                    <div class="input-group-append">
                                        <span class="input-group-text">km</span>
                                    </div>
                                </div>
                                @error('fiber_distance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fiber_loss_per_km">Loss Fiber (dB/km)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="2" class="form-control @error('fiber_loss_per_km') is-invalid @enderror" 
                                           id="fiber_loss_per_km" name="fiber_loss_per_km" value="{{ old('fiber_loss_per_km', $odp->fiber_loss_per_km ?? 0.35) }}"
                                           placeholder="0.35">
                                    <div class="input-group-append">
                                        <span class="input-group-text">dB/km</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Default: 0.35 dB/km (G.652)</small>
                                @error('fiber_loss_per_km')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="splitter_ratio">Rasio Splitter</label>
                                <select class="form-control @error('splitter_ratio') is-invalid @enderror" 
                                        id="splitter_ratio" name="splitter_ratio">
                                    <optgroup label="Equal Splitter (Output sama rata)">
                                        <option value="1:2" {{ old('splitter_ratio', $odp->splitter_ratio) == '1:2' ? 'selected' : '' }}>1:2 (Loss: 3.5 dB)</option>
                                        <option value="1:4" {{ old('splitter_ratio', $odp->splitter_ratio) == '1:4' ? 'selected' : '' }}>1:4 (Loss: 7.0 dB)</option>
                                        <option value="1:8" {{ old('splitter_ratio', $odp->splitter_ratio ?? '1:8') == '1:8' ? 'selected' : '' }}>1:8 (Loss: 10.5 dB) - Default</option>
                                        <option value="1:16" {{ old('splitter_ratio', $odp->splitter_ratio) == '1:16' ? 'selected' : '' }}>1:16 (Loss: 14.0 dB)</option>
                                        <option value="1:32" {{ old('splitter_ratio', $odp->splitter_ratio) == '1:32' ? 'selected' : '' }}>1:32 (Loss: 17.5 dB)</option>
                                        <option value="1:64" {{ old('splitter_ratio', $odp->splitter_ratio) == '1:64' ? 'selected' : '' }}>1:64 (Loss: 21.0 dB)</option>
                                    </optgroup>
                                    <optgroup label="Unequal Splitter (Output berbeda)">
                                        <option value="90:10" {{ old('splitter_ratio', $odp->splitter_ratio) == '90:10' ? 'selected' : '' }}>90:10 (Main: 0.5 dB, Branch: 10.0 dB)</option>
                                        <option value="85:15" {{ old('splitter_ratio', $odp->splitter_ratio) == '85:15' ? 'selected' : '' }}>85:15 (Main: 0.7 dB, Branch: 8.2 dB)</option>
                                        <option value="80:20" {{ old('splitter_ratio', $odp->splitter_ratio) == '80:20' ? 'selected' : '' }}>80:20 (Main: 1.0 dB, Branch: 7.0 dB)</option>
                                        <option value="70:30" {{ old('splitter_ratio', $odp->splitter_ratio) == '70:30' ? 'selected' : '' }}>70:30 (Main: 1.5 dB, Branch: 5.2 dB)</option>
                                        <option value="60:40" {{ old('splitter_ratio', $odp->splitter_ratio) == '60:40' ? 'selected' : '' }}>60:40 (Main: 2.2 dB, Branch: 4.0 dB)</option>
                                        <option value="50:50" {{ old('splitter_ratio', $odp->splitter_ratio) == '50:50' ? 'selected' : '' }}>50:50 (Main: 3.0 dB, Branch: 3.0 dB)</option>
                                    </optgroup>
                                </select>
                                <small class="form-text text-muted" id="splitter-info">
                                    Equal: semua output sama. Unequal: main ke relay, branch ke customer.
                                </small>
                                @error('splitter_ratio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hasil Kalkulasi</label>
                                <div class="card bg-light mb-0">
                                    <div class="card-body py-2">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Output Power</small>
                                                <h4 id="calc-output" class="mb-0 text-primary">-- dBm</h4>
                                                <small class="text-muted">Ke customer/ONU</small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Cascade Output</small>
                                                <h4 id="calc-cascade" class="mb-0 text-info">-- dBm</h4>
                                                <small class="text-muted">Ke ODP berikutnya</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div id="power-status" class="alert alert-secondary mb-0" style="display: none;">
                                <i class="fas fa-info-circle mr-1"></i>
                                <span id="power-status-text">Isi form untuk melihat hasil kalkulasi</span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="is_power_manual" id="is_power_manual" value="{{ old('is_power_manual', $odp->is_power_manual ?? 1) }}">
                    
                    <!-- Hidden fields for splitter configuration -->
                    <input type="hidden" name="splitter_config_type" id="splitter_config_type" value="{{ old('splitter_config_type', $odp->splitter_config_type ?? 'equal') }}">
                    <input type="hidden" name="unequal_ratio" id="unequal_ratio" value="{{ old('unequal_ratio', $odp->unequal_ratio) }}">
                    <input type="hidden" name="branch_splitter" id="branch_splitter" value="{{ old('branch_splitter', $odp->branch_splitter) }}">
                    <input type="hidden" name="fiber_loss" id="fiber_loss" value="{{ old('fiber_loss', $odp->fiber_loss) }}">
                    <input type="hidden" name="unequal_loss" id="unequal_loss" value="{{ old('unequal_loss', $odp->unequal_loss) }}">
                    <input type="hidden" name="branch_loss" id="branch_loss" value="{{ old('branch_loss', $odp->branch_loss) }}">
                    <input type="hidden" name="total_loss" id="total_loss" value="{{ old('total_loss', $odp->total_loss) }}">
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" name="notes" rows="4" placeholder="Catatan tambahan...">{{ old('notes', $odp->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-2"></i>Foto Dokumentasi</h3>
                </div>
                <div class="card-body">
                    @if($odp->photos && count($odp->photos) > 0)
                    <div class="mb-3">
                        <label class="mb-2"><strong>Foto Saat Ini:</strong></label>
                        <div class="d-flex flex-wrap">
                            @foreach($odp->photos as $idx => $photo)
                            <div id="photo-{{ $idx }}" class="position-relative mr-2 mb-2">
                                <img src="{{ $odp->getThumbnailUrl($photo) }}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
                                <input type="hidden" name="keep_photos[]" id="keep-{{ $idx }}" value="{{ $photo }}">
                                <button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" 
                                        onclick="markPhotoForRemoval('{{ $photo }}', {{ $idx }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <div class="form-group">
                        <label>Tambah Foto Baru <small class="text-muted">(Maks. 10 foto total, masing-masing maks. 5MB)</small></label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('photos.*') is-invalid @enderror" 
                                       id="photos" name="photos[]" accept="image/*" multiple>
                                <label class="custom-file-label" for="photos">Pilih foto...</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btn-camera" title="Ambil foto dari kamera">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        @error('photos.*')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div id="photo-preview" class="d-flex flex-wrap mt-2"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.odps.show', $odp) }}" class="btn btn-info btn-block">
                        <i class="fas fa-eye mr-1"></i> Lihat Detail
                    </a>
                    <a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('js')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/splitter-calculator.js') }}"></script>
<script>
function setConnectionType(type) {
    console.log('Setting connection type:', type);
    $('#connection_type').val(type);
    
    // Update card styles
    $('.connection-type-card').removeClass('active');
    $('.connection-type-card[data-type="' + type + '"]').addClass('active');
    
    // Show/hide fields
    $('.connection-fields').hide();
    $('#' + type + '-fields').show();
}

$(function() {
    // Connection type card click handler (more robust)
    $(document).on('click', '.connection-type-card', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const type = $(this).data('type');
        setConnectionType(type);
    });

    // Initialize map with satellite view
    const defaultLat = {{ old('latitude', $odp->latitude ?? -7.9666) }};
    const defaultLng = {{ old('longitude', $odp->longitude ?? 110.6283) }};
    
    const map = L.map('map').setView([defaultLat, defaultLng], 16);
    
    // Layer Satellite dari Google
    const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Satellite'
    });
    
    // Layer Hybrid (Satellite + Labels)
    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Hybrid'
    });
    
    // Layer Street dari Google
    const googleStreet = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Maps'
    });
    
    // Layer OpenStreetMap
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    });
    
    // Default to Hybrid view
    googleHybrid.addTo(map);
    
    // Layer control
    const baseMaps = {
        "🛰️ Satelit + Label": googleHybrid,
        "🛰️ Satelit": googleSat,
        "🗺️ Street": googleStreet,
        "🗺️ OpenStreetMap": osm
    };
    
    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);
    
    // Add scale control
    L.control.scale({ imperial: false }).addTo(map);
    
    let marker = null;
    
    // Custom icon for ODP
    const odpIcon = L.divIcon({
        className: 'custom-odp-marker',
        html: '<div style="background: #ffc107; color: #333; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-box"></i> {{ $odp->code }}</div>',
        iconSize: [80, 30],
        iconAnchor: [40, 30]
    });
    
    // Add marker if coordinates exist
    if ($('#latitude').val() && $('#longitude').val()) {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
        map.setView([lat, lng], 18);
        
        // Enable dragging marker
        marker.on('dragend', function(e) {
            const latlng = e.target.getLatLng();
            $('#latitude').val(latlng.lat.toFixed(8));
            $('#longitude').val(latlng.lng.toFixed(8));
        });
    }
    
    // Click on map to set location
    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);
        
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { icon: odpIcon, draggable: true }).addTo(map);
            marker.on('dragend', function(e) {
                const latlng = e.target.getLatLng();
                $('#latitude').val(latlng.lat.toFixed(8));
                $('#longitude').val(latlng.lng.toFixed(8));
            });
        }
    });
    
    // Update marker when coordinates change manually
    $('#latitude, #longitude').on('change', function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const latlng = e.target.getLatLng();
                    $('#latitude').val(latlng.lat.toFixed(8));
                    $('#longitude').val(latlng.lng.toFixed(8));
                });
            }
            map.setView([lat, lng], 18);
        }
    });
    
    // Geolocation button
    if (navigator.geolocation) {
        const locateBtn = L.control({ position: 'topleft' });
        locateBtn.onAdd = function() {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<a href="#" title="Lokasi Saya" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:white;font-size:16px;"><i class="fas fa-crosshairs"></i></a>';
            div.onclick = function(e) {
                e.preventDefault();
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat, lng], 18);
                    
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
                        marker.on('dragend', function(e) {
                            const latlng = e.target.getLatLng();
                            $('#latitude').val(latlng.lat.toFixed(8));
                            $('#longitude').val(latlng.lng.toFixed(8));
                        });
                    }
                    
                    $('#latitude').val(lat.toFixed(8));
                    $('#longitude').val(lng.toFixed(8));
                });
                return false;
            };
            return div;
        };
        locateBtn.addTo(map);
    }
    
    // Used PON ports data from controller
    var usedPonPorts = @json($usedPonPorts ?? []);
    var currentOdpPonPort = {{ $odp->olt_pon_port ?? 'null' }};
    var currentOdpOltId = "{{ $odp->olt_id ?? '' }}";
    
    // OLT change handler - populate PON port dropdown with protection
    $('#olt_id').on('change', function() {
        const $selected = $(this).find(':selected');
        const oltId = $(this).val();
        const ponPorts = parseInt($selected.data('pon-ports')) || 8;
        const $ponSelect = $('#olt_pon_port');
        const $ponInfo = $('#pon_port_info');
        
        // Get used ports for this OLT
        const usedForOlt = usedPonPorts[oltId] || {};
        const usedCount = Object.keys(usedForOlt).length;
        const availableCount = ponPorts - usedCount;
        
        // Build options
        let options = '<option value="">-- Pilih PON Port --</option>';
        for (let i = 1; i <= ponPorts; i++) {
            const usedData = usedForOlt[i];
            // Check if this is the current ODP's port (allow re-selection)
            const isCurrentPort = (oltId === currentOdpOltId && i === currentOdpPonPort);
            
            if (usedData && !isCurrentPort) {
                // Port is used by another ODP - disable it
                options += `<option value="${i}" disabled class="text-danger">PON ${i} - ⛔ Digunakan: ${usedData.odp_code}</option>`;
            } else if (isCurrentPort) {
                // This is the current ODP's port - select it
                options += `<option value="${i}" selected>PON ${i} - ✅ Port saat ini</option>`;
            } else {
                options += `<option value="${i}">PON ${i} - ✅ Tersedia</option>`;
            }
        }
        $ponSelect.html(options);
        
        // Update info text
        if (oltId) {
            if (availableCount > 0) {
                $ponInfo.html(`<span class="text-success"><i class="fas fa-info-circle"></i> ${availableCount} dari ${ponPorts} PON port tersedia</span>`);
            } else {
                $ponInfo.html(`<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Semua PON port sudah digunakan!</span>`);
            }
        } else {
            $ponInfo.html('');
        }
    });
    
    // Trigger OLT change on load to populate PON ports
    @if($odp->olt_id)
    $('#olt_id').trigger('change');
    @endif
    
    // Photo handling
    var photoInput = document.getElementById('photos');
    var photoPreview = document.getElementById('photo-preview');
    
    $('#photos').on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            $(this).next('.custom-file-label').text(files.length + ' foto baru dipilih');
            updatePhotoPreview();
        } else {
            $(this).next('.custom-file-label').text('Pilih foto...');
        }
    });
    
    function updatePhotoPreview() {
        photoPreview.innerHTML = '';
        var files = photoInput.files;
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = (function(f, idx) {
                    return function(e) {
                        var div = document.createElement('div');
                        div.className = 'position-relative mr-2 mb-2';
                        div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">' +
                            '<span class="badge badge-success position-absolute" style="top:5px;left:5px;">Baru</span>' +
                            '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                        photoPreview.appendChild(div);
                    };
                })(file, i);
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Camera capture
    $('#btn-camera').on('click', function() {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'environment';
        input.onchange = function(e) {
            if (e.target.files.length > 0) {
                var dt = new DataTransfer();
                var existingFiles = photoInput.files;
                for (var i = 0; i < existingFiles.length; i++) {
                    dt.items.add(existingFiles[i]);
                }
                dt.items.add(e.target.files[0]);
                photoInput.files = dt.files;
                $('#photos').next('.custom-file-label').text(dt.files.length + ' foto baru dipilih');
                updatePhotoPreview();
            }
        };
        input.click();
    });
});

// Mark existing photo for removal
window.markPhotoForRemoval = function(filename, idx) {
    if (confirm('Hapus foto ini?')) {
        $('#photo-' + idx).hide();
        $('#keep-' + idx).remove();
        $('<input>').attr({
            type: 'hidden',
            name: 'remove_photos[]',
            value: filename
        }).appendTo('form');
    }
};

// Remove new photo from preview
window.removePreviewPhoto = function(idx) {
    var dt = new DataTransfer();
    var files = document.getElementById('photos').files;
    for (var i = 0; i < files.length; i++) {
        if (i !== idx) {
            dt.items.add(files[i]);
        }
    }
    document.getElementById('photos').files = dt.files;
    $('#photos').next('.custom-file-label').text(dt.files.length > 0 ? dt.files.length + ' foto baru dipilih' : 'Pilih foto...');
    
    var photoPreview = document.getElementById('photo-preview');
    photoPreview.innerHTML = '';
    for (var i = 0; i < dt.files.length; i++) {
        var file = dt.files[i];
        if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = (function(f, idx) {
                return function(e) {
                    var div = document.createElement('div');
                    div.className = 'position-relative mr-2 mb-2';
                    div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">' +
                        '<span class="badge badge-success position-absolute" style="top:5px;left:5px;">Baru</span>' +
                        '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                    photoPreview.appendChild(div);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }
};

// Optical Power Calculator
var splitterLosses = {
    // Equal splitters
    '1:2': { main: 3.5, branch: 3.5 },
    '1:4': { main: 7.0, branch: 7.0 },
    '1:8': { main: 10.5, branch: 10.5 },
    '1:16': { main: 14.0, branch: 14.0 },
    '1:32': { main: 17.5, branch: 17.5 },
    '1:64': { main: 21.0, branch: 21.0 },
    // Unequal splitters
    '90:10': { main: 0.5, branch: 10.0 },
    '85:15': { main: 0.7, branch: 8.2 },
    '80:20': { main: 1.0, branch: 7.0 },
    '70:30': { main: 1.5, branch: 5.2 },
    '60:40': { main: 2.2, branch: 4.0 },
    '50:50': { main: 3.0, branch: 3.0 }
};

function isUnequalSplitter(ratio) {
    return ratio.indexOf(':') !== -1 && !ratio.startsWith('1:');
}

function calculateOpticalPower() {
    var inputPower = parseFloat($('#input_power').val()) || 0;
    var fiberDistance = parseFloat($('#fiber_distance').val()) || 0;
    var fiberLossPerKm = parseFloat($('#fiber_loss_per_km').val()) || 0.35;
    var splitterRatio = $('#splitter_ratio').val() || '1:8';
    
    // Calculate fiber loss
    var fiberLoss = fiberDistance * fiberLossPerKm;
    var powerAfterFiber = inputPower - fiberLoss;
    
    // Get splitter loss
    var losses = splitterLosses[splitterRatio] || { main: 10.5, branch: 10.5 };
    
    // For equal splitters, output = power after fiber - splitter loss
    // For unequal splitters, customer gets branch output, cascade gets main output
    var outputPower, cascadeOutput;
    
    if (isUnequalSplitter(splitterRatio)) {
        outputPower = powerAfterFiber - losses.branch;
        cascadeOutput = powerAfterFiber - losses.main;
    } else {
        outputPower = powerAfterFiber - losses.main;
        cascadeOutput = powerAfterFiber - losses.main;
    }
    
    // Update display
    $('#calc-output').text(outputPower.toFixed(2) + ' dBm');
    $('#calc-cascade').text(cascadeOutput.toFixed(2) + ' dBm');
    
    // Update colors based on power level
    var statusDiv = $('#power-status');
    var statusText = $('#power-status-text');
    
    statusDiv.show();
    statusDiv.removeClass('alert-success alert-warning alert-danger alert-info alert-secondary');
    
    if (outputPower >= -25) {
        statusDiv.addClass('alert-success');
        statusText.html('<i class="fas fa-check-circle mr-1"></i> Power level OPTIMAL. ONU akan bekerja dengan baik.');
        $('#calc-output').removeClass('text-warning text-danger').addClass('text-success');
    } else if (outputPower >= -28) {
        statusDiv.addClass('alert-info');
        statusText.html('<i class="fas fa-info-circle mr-1"></i> Power CUKUP tapi margin terbatas. Pertimbangkan jarak atau splitter.');
        $('#calc-output').removeClass('text-success text-danger').addClass('text-warning');
    } else if (outputPower >= -30) {
        statusDiv.addClass('alert-warning');
        statusText.html('<i class="fas fa-exclamation-triangle mr-1"></i> WARNING: Power mendekati batas minimum ONU (-30 dBm)!');
        $('#calc-output').removeClass('text-success').addClass('text-warning');
    } else {
        statusDiv.addClass('alert-danger');
        statusText.html('<i class="fas fa-times-circle mr-1"></i> CRITICAL: Power terlalu rendah! ONU tidak akan sync. Gunakan splitter ratio lebih kecil atau kurangi jarak.');
        $('#calc-output').removeClass('text-success text-warning').addClass('text-danger');
    }
}

// Auto calculate on change
$('#input_power, #fiber_distance, #fiber_loss_per_km, #splitter_ratio').on('change keyup', function() {
    calculateOpticalPower();
});

// Fetch power from source
$('#btn-fetch-power').on('click', function() {
    var btn = $(this);
    var connectionType = $('#connection_type').val();
    var oltId = $('#olt_id').val();
    var odcId = $('#odc_id').val();
    var parentOdpId = $('#parent_odp_id').val();
    var ponPort = $('#olt_pon_port').val();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Membaca...');
    
    $.ajax({
        url: '{{ route("admin.odps.source-power") }}',
        method: 'GET',
        data: {
            connection_type: connectionType,
            olt_id: oltId,
            odc_id: odcId,
            parent_odp_id: parentOdpId,
            pon_port: ponPort
        },
        success: function(response) {
            if (response.success && response.source_power !== null) {
                $('#input_power').val(response.source_power);
                $('#is_power_manual').val(response.is_auto ? 0 : 1);
                $('#power-source-info').html('<span class="text-success"><i class="fas fa-check mr-1"></i>' + response.message + '</span>');
                calculateOpticalPower();
            } else {
                $('#power-source-info').html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>' + response.message + '</span>');
            }
        },
        error: function() {
            $('#power-source-info').html('<span class="text-danger"><i class="fas fa-times mr-1"></i>Gagal membaca power dari sumber</span>');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Baca Power Otomatis');
        }
    });
});

// Initial calculation
calculateOpticalPower();

// Re-calculate when fiber distance changes in Optical Power Calculator section
$('#fiber_distance').on('change keyup', function() {
    calculateOpticalPower();
});

// Initialize existing splitter config on page load - using new partial IDs
$(function() {
    var connectionType = '{{ $connectionType }}';
    var existingConfigType = '{{ $odp->splitter_config_type ?? "" }}';
    var existingRatio = '{{ $odp->unequal_ratio ?? "" }}';
    var existingBranch = '{{ $odp->branch_splitter ?? "" }}';
    var existingSplitter = '{{ $odp->splitter_ratio ?? "" }}';
    
    // Splitter config initialization is now handled by splitter-calculator.js
    // Just need to set initial values for the dropdowns
    if (connectionType === 'olt' && existingConfigType) {
        if (existingConfigType === 'cascade' && existingRatio) {
            $('#olt_splitter_type').val('unequal').trigger('change');
            setTimeout(function() {
                $('#olt_unequal_ratio').val(existingRatio);
                if (existingBranch) {
                    $('#olt_branch_splitter').val(existingBranch);
                }
                SplitterCalculator.calculate('olt', 'unequal');
            }, 100);
        } else if (existingConfigType === 'equal' && existingSplitter) {
            $('#olt_splitter_type').val('equal').trigger('change');
            setTimeout(function() {
                $('#olt_equal_splitter').val(existingSplitter);
                SplitterCalculator.calculate('olt', 'equal');
            }, 100);
        }
    } else if (connectionType === 'odc' && existingConfigType) {
        if (existingConfigType === 'cascade' && existingRatio) {
            $('#odc_splitter_type').val('unequal').trigger('change');
            setTimeout(function() {
                $('#odc_unequal_ratio').val(existingRatio);
                if (existingBranch) {
                    $('#odc_branch_splitter').val(existingBranch);
                }
                SplitterCalculator.calculate('odc', 'unequal');
            }, 100);
        } else if (existingConfigType === 'equal' && existingSplitter) {
            $('#odc_splitter_type').val('equal').trigger('change');
            setTimeout(function() {
                $('#odc_equal_splitter').val(existingSplitter);
                SplitterCalculator.calculate('odc', 'equal');
            }, 100);
        }
    }
});
</script>
@endpush
