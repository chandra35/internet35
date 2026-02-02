@extends('layouts.admin')

@section('title', 'Edit OLT - ' . $olt->name)

@section('page-title', 'Edit OLT: ' . $olt->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.show', $olt) }}">{{ $olt->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form action="{{ route('admin.olts.update', $olt) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Basic Info -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    @if($popUsers)
                    <div class="form-group">
                        <label>POP <span class="text-danger">*</span></label>
                        <select name="pop_id" id="pop_id" class="form-control select2 @error('pop_id') is-invalid @enderror" required>
                            <option value="">-- Pilih POP --</option>
                            @foreach($popUsers as $pop)
                            <option value="{{ $pop->id }}" {{ old('pop_id', $olt->pop_id) == $pop->id ? 'selected' : '' }}>
                                {{ $pop->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('pop_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @else
                    <input type="hidden" name="pop_id" value="{{ $olt->pop_id }}">
                    @endif

                    <div class="form-group">
                        <label>Router (Opsional)</label>
                        <select name="router_id" id="router_id" class="form-control select2 @error('router_id') is-invalid @enderror">
                            <option value="">-- Pilih Router --</option>
                            @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ old('router_id', $olt->router_id) == $router->id ? 'selected' : '' }}>
                                {{ $router->name }} ({{ $router->ip_address }})
                            </option>
                            @endforeach
                        </select>
                        @error('router_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Nama OLT <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $olt->name) }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand" class="form-control @error('brand') is-invalid @enderror" required>
                                    @foreach($brands as $key => $label)
                                    <option value="{{ $key }}" {{ old('brand', $olt->brand) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" 
                                       value="{{ old('model', $olt->model) }}">
                                @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>IP Address <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" id="ip_address" class="form-control @error('ip_address') is-invalid @enderror" 
                               value="{{ old('ip_address', $olt->ip_address) }}" required>
                        @error('ip_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>PON Ports</label>
                                <input type="number" name="total_pon_ports" id="total_pon_ports" class="form-control" 
                                       value="{{ old('total_pon_ports', $olt->total_pon_ports) }}" min="1" max="64">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Uplink Ports</label>
                                <input type="number" name="total_uplink_ports" id="total_uplink_ports" class="form-control" 
                                       value="{{ old('total_uplink_ports', $olt->total_uplink_ports) }}" min="1" max="16">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ old('status', $olt->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status', $olt->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                    <option value="maintenance" {{ old('status', $olt->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $olt->address) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control" 
                                       value="{{ old('latitude', $olt->latitude) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control" 
                                       value="{{ old('longitude', $olt->longitude) }}">
                            </div>
                        </div>
                    </div>
                    <div id="map" style="height: 250px; border-radius: 5px;"></div>
                    <small class="text-muted">Klik pada peta untuk mengubah lokasi</small>
                </div>
            </div>
        </div>

        <!-- Connection Settings -->
        <div class="col-md-6">
            <!-- Re-identify -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sync-alt mr-2"></i>Re-Identify OLT</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Gunakan fitur ini untuk memperbarui informasi OLT (brand, model, jumlah port).</p>
                    
                    <div class="form-group">
                        <label>Brand OLT</label>
                        <select id="reident_brand" class="form-control">
                            <option value="">-- Auto Detect --</option>
                            <option value="zte" {{ $olt->brand == 'zte' ? 'selected' : '' }}>ZTE</option>
                            <option value="huawei" {{ $olt->brand == 'huawei' ? 'selected' : '' }}>Huawei</option>
                            <option value="vsol" {{ $olt->brand == 'vsol' ? 'selected' : '' }}>VSOL</option>
                            <option value="hioso" {{ $olt->brand == 'hioso' ? 'selected' : '' }}>Hioso</option>
                            <option value="hsgq" {{ $olt->brand == 'hsgq' ? 'selected' : '' }}>HSGQ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode</label>
                        <select id="reident_method" class="form-control">
                            <option value="snmp">SNMP</option>
                            <option value="telnet" {{ $olt->telnet_enabled ? 'selected' : '' }}>Telnet</option>
                            <option value="ssh" {{ $olt->ssh_enabled ? 'selected' : '' }}>SSH</option>
                        </select>
                    </div>
                    
                    <!-- Status Koneksi -->
                    <div id="connection-status" class="mb-3" style="display:none;">
                        <small id="connection-status-text" class="text-muted"></small>
                    </div>
                    
                    <button type="button" class="btn btn-info btn-block" id="btn-reidentify">
                        <i class="fas fa-search mr-2"></i>Re-Identify OLT
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary btn-block mt-2" id="btn-test-connection">
                        <i class="fas fa-plug mr-2"></i>Test Koneksi
                    </button>
                </div>
            </div>

            <!-- SNMP -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>SNMP Settings</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SNMP Port</label>
                                <input type="number" name="snmp_port" id="snmp_port" class="form-control" 
                                       value="{{ old('snmp_port', $olt->snmp_port) }}" min="1" max="65535">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Community</label>
                                <input type="text" name="snmp_community" id="snmp_community" class="form-control" 
                                       value="{{ old('snmp_community', $olt->snmp_community) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telnet -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-terminal mr-2"></i>Telnet Settings</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="telnet_enabled" name="telnet_enabled" value="1"
                               {{ old('telnet_enabled', $olt->telnet_enabled) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="telnet_enabled">Aktifkan Telnet</label>
                    </div>
                    <div class="telnet-settings" style="{{ old('telnet_enabled', $olt->telnet_enabled) ? '' : 'display:none' }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="telnet_port" id="telnet_port" class="form-control" 
                                           value="{{ old('telnet_port', $olt->telnet_port) }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="telnet_username" id="telnet_username" class="form-control" 
                                           value="{{ old('telnet_username', $olt->telnet_username) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="telnet_password" id="telnet_password" class="form-control" 
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SSH -->
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>SSH Settings</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="ssh_enabled" name="ssh_enabled" value="1"
                               {{ old('ssh_enabled', $olt->ssh_enabled) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="ssh_enabled">Aktifkan SSH</label>
                    </div>
                    <div class="ssh-settings" style="{{ old('ssh_enabled', $olt->ssh_enabled) ? '' : 'display:none' }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="ssh_port" class="form-control" 
                                           value="{{ old('ssh_port', $olt->ssh_port) }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="ssh_username" class="form-control" 
                                           value="{{ old('ssh_username', $olt->ssh_username) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="ssh_password" class="form-control" 
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $olt->description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Catatan Internal</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $olt->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save mr-2"></i>Update OLT
                    </button>
                    <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-2"></i>Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    // Toggle settings
    $('#telnet_enabled').change(function() {
        $('.telnet-settings').toggle(this.checked);
    });
    $('#ssh_enabled').change(function() {
        $('.ssh-settings').toggle(this.checked);
    });

    // Map
    var lat = {{ $olt->latitude ?? -6.2088 }};
    var lng = {{ $olt->longitude ?? 106.8456 }};
    
    var map = L.map('map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([lat, lng]).addTo(map);

    map.on('click', function(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng).addTo(map);
        $('#latitude').val(e.latlng.lat.toFixed(8));
        $('#longitude').val(e.latlng.lng.toFixed(8));
    });

    // Re-identify OLT
    $('#btn-reidentify').click(function() {
        var btn = $(this);
        var ip = $('input[name="ip_address"]').val();
        var snmpPort = $('#snmp_port').val();
        var snmpCommunity = $('#snmp_community').val();
        var method = $('#reident_method').val();
        var brand = $('#reident_brand').val();
        var statusDiv = $('#connection-status');
        var statusText = $('#connection-status-text');

        if (!ip) {
            Swal.fire('Error', 'IP Address tidak ditemukan', 'error');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengidentifikasi...');
        statusDiv.show();
        statusText.removeClass('text-success text-danger text-muted text-warning').addClass('text-muted')
            .html('<i class="fas fa-circle-notch fa-spin mr-1"></i> Menghubungkan via ' + method.toUpperCase() + '...');

        var data = {
            _token: '{{ csrf_token() }}',
            ip_address: ip,
            connection_method: method,
            brand: brand
        };

        // Add connection-specific data
        if (method === 'snmp') {
            data.snmp_port = snmpPort;
            data.snmp_community = snmpCommunity;
        } else if (method === 'telnet') {
            data.telnet_enabled = 1;
            data.telnet_port = $('#telnet_port').val();
            data.telnet_username = $('#telnet_username').val();
            var pwd = $('#telnet_password').val();
            if (pwd) data.telnet_password = pwd;
            else data.telnet_password = '{{ $olt->telnet_password ?? "" }}';
        } else if (method === 'ssh') {
            data.ssh_enabled = 1;
            data.ssh_port = $('#ssh_port').val();
            data.ssh_username = $('#ssh_username').val();
            var pwd = $('#ssh_password').val();
            if (pwd) data.ssh_password = pwd;
            else data.ssh_password = '{{ $olt->ssh_password ?? "" }}';
        }

        $.ajax({
            url: '{{ route("admin.olts.identify") }}',
            method: 'POST',
            data: data,
            timeout: 35000,
            success: function(res) {
                if (res.success) {
                    // Update status
                    statusText.removeClass('text-muted text-danger text-warning').addClass('text-success')
                        .html('<i class="fas fa-check-circle mr-1"></i> Koneksi berhasil - OLT terdeteksi');
                    
                    // Show confirmation dialog
                    var msg = '<table class="table table-sm">' +
                        '<tr><td>Brand</td><td><strong>' + (res.brand_label || res.brand || '-') + '</strong></td></tr>' +
                        '<tr><td>Model</td><td><strong>' + (res.model || '-') + '</strong></td></tr>' +
                        '<tr><td>PON Ports</td><td><strong>' + (res.total_pon_ports || '-') + '</strong></td></tr>' +
                        '<tr><td>Uplink Ports</td><td><strong>' + (res.total_uplink_ports || '-') + '</strong></td></tr>' +
                        '</table>';

                    Swal.fire({
                        title: 'Update Info OLT?',
                        html: msg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Update',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Update form fields
                            $('select[name="brand"]').val(res.brand).trigger('change');
                            $('input[name="model"]').val(res.model || '');
                            $('input[name="total_pon_ports"]').val(res.total_pon_ports || '');
                            $('input[name="total_uplink_ports"]').val(res.total_uplink_ports || '');
                            
                            Swal.fire('Berhasil!', 'Info OLT berhasil diperbarui, silakan simpan untuk menyimpan perubahan.', 'success');
                        }
                    });
                } else {
                    statusText.removeClass('text-muted text-success text-warning').addClass('text-danger')
                        .html('<i class="fas fa-times-circle mr-1"></i> ' + (res.message || 'Gagal mengidentifikasi OLT'));
                    Swal.fire('Gagal', res.message || 'Tidak dapat mengidentifikasi OLT', 'error');
                }
            },
            error: function(xhr) {
                statusText.removeClass('text-muted text-success text-warning').addClass('text-danger')
                    .html('<i class="fas fa-times-circle mr-1"></i> ' + (xhr.responseJSON?.message || 'Terjadi kesalahan'));
                Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Re-Identify OLT');
            }
        });
    });

    // Test Connection
    $('#btn-test-connection').click(function() {
        var btn = $(this);
        var statusDiv = $('#connection-status');
        var statusText = $('#connection-status-text');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Testing...');
        statusDiv.show();
        statusText.removeClass('text-success text-danger text-muted').addClass('text-muted').html('<i class="fas fa-circle-notch fa-spin mr-1"></i> Menghubungkan ke OLT...');
        
        $.post('{{ route("admin.olts.test-connection", $olt) }}', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                statusText.removeClass('text-muted text-danger').addClass('text-success')
                    .html('<i class="fas fa-check-circle mr-1"></i> ' + (res.message || 'Koneksi berhasil'));
            })
            .fail(function(xhr) {
                statusText.removeClass('text-muted text-success').addClass('text-danger')
                    .html('<i class="fas fa-times-circle mr-1"></i> ' + (xhr.responseJSON?.message || 'Koneksi gagal'));
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-plug mr-2"></i>Test Koneksi');
            });
    });

    // Show status during identify
    function showStatus(message, type) {
        var statusDiv = $('#connection-status');
        var statusText = $('#connection-status-text');
        statusDiv.show();
        statusText.removeClass('text-success text-danger text-muted text-warning')
            .addClass('text-' + type)
            .html(message);
    }
});
</script>
@endpush
