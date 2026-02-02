@extends('layouts.admin')

@section('title', 'Tambah OLT')

@section('page-title', 'Tambah OLT Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
<form action="{{ route('admin.olts.store') }}" method="POST" id="form-olt">
    @csrf
    
    <!-- Hidden fields for identified data -->
    <input type="hidden" name="brand" id="input_brand" value="{{ old('brand') }}">
    <input type="hidden" name="total_uplink_ports" id="input_uplink_ports" value="{{ old('total_uplink_ports') }}">
    
    <div class="row">
        <!-- Step 1: Connection Info -->
        <div class="col-md-6">
            <div class="card card-primary" id="card-connection">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Step 1: Koneksi OLT</h3>
                </div>
                <div class="card-body">
                    @if($popUsers)
                    <div class="form-group">
                        <label>POP <span class="text-danger">*</span></label>
                        <select name="pop_id" id="pop_id" class="form-control select2 @error('pop_id') is-invalid @enderror" required>
                            <option value="">-- Pilih POP --</option>
                            @foreach($popUsers as $pop)
                            <option value="{{ $pop->id }}" {{ old('pop_id', $popId) == $pop->id ? 'selected' : '' }}>
                                {{ $pop->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('pop_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @else
                    <input type="hidden" name="pop_id" value="{{ $popId }}">
                    @endif

                    <div class="form-group">
                        <label>IP Address <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" id="ip_address" 
                               class="form-control @error('ip_address') is-invalid @enderror" 
                               value="{{ old('ip_address') }}" required placeholder="192.168.1.1">
                        @error('ip_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Brand OLT (Opsional)</label>
                        <select name="brand_hint" id="brand_hint" class="form-control">
                            <option value="">-- Auto Detect --</option>
                            <option value="zte">ZTE</option>
                            <option value="huawei">Huawei</option>
                            <option value="vsol">VSOL</option>
                            <option value="hioso">Hioso</option>
                            <option value="hsgq">HSGQ</option>
                        </select>
                        <small class="form-text text-muted">Pilih brand jika auto-detect tidak berhasil</small>
                    </div>

                    <div class="form-group">
                        <label>Metode Koneksi <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary active">
                                <input type="radio" name="connection_method" id="method_snmp" value="snmp" checked>
                                <i class="fas fa-network-wired mr-1"></i> SNMP
                            </label>
                            <label class="btn btn-outline-warning">
                                <input type="radio" name="connection_method" id="method_telnet" value="telnet">
                                <i class="fas fa-terminal mr-1"></i> Telnet
                            </label>
                            <label class="btn btn-outline-dark">
                                <input type="radio" name="connection_method" id="method_ssh" value="ssh">
                                <i class="fas fa-key mr-1"></i> SSH
                            </label>
                        </div>
                    </div>

                    <!-- SNMP Settings -->
                    <div id="snmp-settings">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SNMP Port</label>
                                    <input type="number" name="snmp_port" id="snmp_port" class="form-control" 
                                           value="{{ old('snmp_port', 161) }}" min="1" max="65535">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SNMP Community</label>
                                    <input type="text" name="snmp_community" id="snmp_community" class="form-control" 
                                           value="{{ old('snmp_community', 'public') }}" placeholder="public">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Telnet Settings -->
                    <div id="telnet-settings" style="display:none;">
                        <input type="hidden" name="telnet_enabled" id="telnet_enabled" value="0">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Telnet Port</label>
                                    <input type="number" name="telnet_port" id="telnet_port" class="form-control" 
                                           value="{{ old('telnet_port', 23) }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Username <span class="text-danger">*</span></label>
                                    <input type="text" name="telnet_username" id="telnet_username" class="form-control" 
                                           value="{{ old('telnet_username') }}" placeholder="admin">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="password" name="telnet_password" id="telnet_password" class="form-control" 
                                   placeholder="Masukkan password">
                        </div>
                    </div>

                    <!-- SSH Settings -->
                    <div id="ssh-settings" style="display:none;">
                        <input type="hidden" name="ssh_enabled" id="ssh_enabled" value="0">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>SSH Port</label>
                                    <input type="number" name="ssh_port" id="ssh_port" class="form-control" 
                                           value="{{ old('ssh_port', 22) }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Username <span class="text-danger">*</span></label>
                                    <input type="text" name="ssh_username" id="ssh_username" class="form-control" 
                                           value="{{ old('ssh_username') }}" placeholder="admin">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="password" name="ssh_password" id="ssh_password" class="form-control" 
                                   placeholder="Masukkan password">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-info btn-lg btn-block btn-identify" id="btn-identify">
                        <i class="fas fa-search mr-2"></i>Identify OLT
                    </button>
                </div>
            </div>

            <!-- Location (shown after identify) -->
            <div class="card card-info" id="card-location" style="display:none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                </div>
                <div class="card-body">
                    <!-- Search Location -->
                    <div class="form-group">
                        <label>Cari Lokasi</label>
                        <div class="input-group">
                            <input type="text" id="location_search" class="form-control" 
                                   placeholder="Ketik nama daerah, jalan, atau alamat...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" id="btn-search-location">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-success" id="btn-current-location" title="Gunakan lokasi saat ini">
                                    <i class="fas fa-crosshairs"></i>
                                </button>
                            </div>
                        </div>
                        <div id="search-results" class="list-group mt-1" style="display:none; position:absolute; z-index:1000; width:calc(100% - 30px);"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control" 
                                       value="{{ old('latitude') }}" placeholder="-6.2088">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control" 
                                       value="{{ old('longitude') }}" placeholder="106.8456">
                            </div>
                        </div>
                    </div>
                    <div id="map" style="height: 250px; border-radius: 5px;"></div>
                    <small class="text-muted">Klik pada peta untuk menentukan lokasi, atau gunakan tombol <i class="fas fa-crosshairs"></i> untuk lokasi saat ini</small>
                </div>
            </div>
        </div>

        <!-- Step 2: Identified Info (shown after successful identify) -->
        <div class="col-md-6">
            <!-- Identify Result Card -->
            <div class="card" id="card-result" style="display:none;">
                <div class="card-header bg-success">
                    <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i>Step 2: Hasil Identifikasi</h3>
                </div>
                <div class="card-body">
                    <!-- Brand & Model & Type -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-primary mb-3">
                                <span class="info-box-icon"><i class="fas fa-industry"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Brand</span>
                                    <span class="info-box-number" id="result_brand">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-info mb-3">
                                <span class="info-box-icon"><i class="fas fa-microchip"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Model</span>
                                    <span class="info-box-number" id="result_model">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-purple mb-3">
                                <span class="info-box-icon"><i class="fas fa-broadcast-tower"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tipe</span>
                                    <span class="info-box-number" id="result_olt_type">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ports Info -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-warning mb-3">
                                <span class="info-box-icon"><i class="fas fa-ethernet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">PON Ports</span>
                                    <span class="info-box-number" id="result_pon_ports">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-teal mb-3">
                                <span class="info-box-icon"><i class="fas fa-network-wired"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Uplink Ports</span>
                                    <span class="info-box-number" id="result_uplink_ports">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Firmware & Hardware -->
                    <div class="row" id="row_firmware" style="display:none;">
                        <div class="col-md-4">
                            <p><strong>Firmware:</strong> <span id="result_firmware">-</span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Hardware:</strong> <span id="result_hardware">-</span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Serial Number:</strong> <span id="result_serial">-</span></p>
                        </div>
                    </div>

                    <!-- System Description -->
                    <div class="alert alert-secondary mb-0">
                        <small><strong>System Description:</strong><br>
                        <span id="result_description">-</span></small>
                    </div>
                </div>
            </div>

            <!-- Board List (if available) -->
            <div class="card" id="card-boards" style="display:none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server mr-2"></i>Daftar Board/Slot</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0" id="table-boards">
                        <thead>
                            <tr>
                                <th>Shelf</th>
                                <th>Slot</th>
                                <th>Type</th>
                                <th>PON</th>
                                <th>Uplink</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- OLT Name & Settings (shown after identify) -->
            <div class="card card-success" id="card-name" style="display:none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tag mr-2"></i>Step 3: Nama & Pengaturan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nama OLT <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name') }}" required placeholder="Contoh: OLT-DESA-A">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Model OLT <small class="text-muted">(bisa diedit jika salah)</small></label>
                                <input type="text" name="model" id="input_model" class="form-control" 
                                       value="{{ old('model') }}" placeholder="Contoh: HA7304">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jumlah PON Ports <small class="text-muted">(bisa diedit)</small></label>
                                <input type="number" name="total_pon_ports" id="input_pon_ports" class="form-control" 
                                       value="{{ old('total_pon_ports', 0) }}" min="0" max="64">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Router (Opsional)</label>
                        <select name="router_id" id="router_id" class="form-control select2 @error('router_id') is-invalid @enderror">
                            <option value="">-- Pilih Router --</option>
                            @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>
                                {{ $router->name }} ({{ $router->ip_address }})
                            </option>
                            @endforeach
                        </select>
                        @error('router_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Catatan Internal</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg btn-block" id="btn-submit" disabled>
                        <i class="fas fa-save mr-2"></i>Simpan OLT
                    </button>
                </div>
            </div>

            <!-- Info Card (before identify) -->
            <div class="card" id="card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Petunjuk</h3>
                </div>
                <div class="card-body">
                    <div class="callout callout-info">
                        <h5><i class="fas fa-lightbulb"></i> Cara Menambah OLT</h5>
                        <ol class="mb-0">
                            <li>Masukkan IP Address OLT</li>
                            <li>Pilih metode koneksi:
                                <ul>
                                    <li><strong>SNMP</strong> - Paling cepat, memerlukan community string</li>
                                    <li><strong>Telnet</strong> - Lebih akurat, memerlukan login</li>
                                    <li><strong>SSH</strong> - Paling aman, memerlukan login</li>
                                </ul>
                            </li>
                            <li>Klik tombol "<strong>Identify OLT</strong>"</li>
                            <li>Sistem akan otomatis mendeteksi:
                                <ul>
                                    <li>Brand & Model OLT</li>
                                    <li>Jumlah PON Port</li>
                                    <li>Jumlah Uplink Port</li>
                                    <li>Info Board/Slot</li>
                                </ul>
                            </li>
                            <li>Setelah berhasil, isi nama OLT dan simpan</li>
                        </ol>
                    </div>

                    <div class="callout callout-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Pastikan</h5>
                        <ul class="mb-0">
                            <li>OLT dapat diakses dari server ini</li>
                            <li>Protokol koneksi yang dipilih sudah aktif di OLT</li>
                            <li>Credential yang dimasukkan benar</li>
                            <li>Firewall tidak memblokir port yang digunakan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.info-box-number { font-size: 1.2rem; }
#card-result .info-box { min-height: 80px; }
</style>
@endpush

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    var map = null;
    var marker = null;
    var identified = false;
    var connectionMethod = 'snmp';

    // Connection method toggle
    $('input[name="connection_method"]').change(function() {
        connectionMethod = $(this).val();
        
        // Hide all settings first
        $('#snmp-settings, #telnet-settings, #ssh-settings').hide();
        $('#telnet_enabled, #ssh_enabled').val('0');
        
        // Show selected settings
        if (connectionMethod === 'snmp') {
            $('#snmp-settings').show();
        } else if (connectionMethod === 'telnet') {
            $('#telnet-settings').show();
            $('#telnet_enabled').val('1');
        } else if (connectionMethod === 'ssh') {
            $('#ssh-settings').show();
            $('#ssh_enabled').val('1');
        }
    });

    // Init Map (lazy)
    function initMap() {
        if (map) return;
        
        var lat = $('#latitude').val() || -6.2088;
        var lng = $('#longitude').val() || 106.8456;
        
        map = L.map('map').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        if ($('#latitude').val() && $('#longitude').val()) {
            marker = L.marker([lat, lng]).addTo(map);
        }

        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });
    }

    // Set marker on map
    function setMarker(lat, lng, zoom) {
        if (!map) return;
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker([lat, lng]).addTo(map);
        $('#latitude').val(parseFloat(lat).toFixed(8));
        $('#longitude').val(parseFloat(lng).toFixed(8));
        map.setView([lat, lng], zoom || 16);
    }

    // Get current location
    $('#btn-current-location').click(function() {
        var btn = $(this);
        if (!navigator.geolocation) {
            Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                setMarker(lat, lng, 17);
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
                Swal.fire({
                    icon: 'success',
                    title: 'Lokasi Ditemukan',
                    text: 'Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6),
                    timer: 2000
                });
            },
            function(error) {
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
                var msg = 'Gagal mendapatkan lokasi';
                if (error.code === 1) msg = 'Izin lokasi ditolak';
                else if (error.code === 2) msg = 'Lokasi tidak tersedia';
                else if (error.code === 3) msg = 'Timeout mendapatkan lokasi';
                Swal.fire('Error', msg, 'error');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    // Search location using Nominatim
    var searchTimeout = null;
    $('#location_search').on('input', function() {
        var query = $(this).val();
        clearTimeout(searchTimeout);
        
        if (query.length < 3) {
            $('#search-results').hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchLocation(query);
        }, 500);
    });

    $('#btn-search-location').click(function() {
        var query = $('#location_search').val();
        if (query.length >= 3) {
            searchLocation(query);
        }
    });

    function searchLocation(query) {
        $('#search-results').html('<div class="list-group-item">Mencari...</div>').show();
        
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/search',
            data: {
                q: query,
                format: 'json',
                addressdetails: 1,
                limit: 5,
                countrycodes: 'id'
            },
            headers: { 'Accept-Language': 'id' },
            success: function(results) {
                var html = '';
                if (results.length === 0) {
                    html = '<div class="list-group-item text-muted">Tidak ditemukan</div>';
                } else {
                    results.forEach(function(r) {
                        html += '<a href="#" class="list-group-item list-group-item-action search-result-item" ' +
                                'data-lat="' + r.lat + '" data-lng="' + r.lon + '">' +
                                '<i class="fas fa-map-marker-alt mr-2 text-danger"></i>' + r.display_name +
                                '</a>';
                    });
                }
                $('#search-results').html(html).show();
            },
            error: function() {
                $('#search-results').html('<div class="list-group-item text-danger">Gagal mencari</div>').show();
            }
        });
    }

    // Click on search result
    $(document).on('click', '.search-result-item', function(e) {
        e.preventDefault();
        var lat = $(this).data('lat');
        var lng = $(this).data('lng');
        setMarker(lat, lng, 16);
        $('#location_search').val($(this).text().trim());
        $('#search-results').hide().empty();
    });

    // Hide search results on click outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#location_search, #search-results, #btn-search-location').length) {
            $('#search-results').hide();
        }
    });

    // Identify OLT
    $('#btn-identify').click(function() {
        var btn = $(this);
        var ip = $('#ip_address').val();

        if (!ip) {
            Swal.fire('Error', 'Masukkan IP Address OLT', 'error');
            return;
        }

        // Validate based on connection method
        if (connectionMethod === 'telnet') {
            if (!$('#telnet_username').val() || !$('#telnet_password').val()) {
                Swal.fire('Error', 'Username dan Password Telnet harus diisi', 'error');
                return;
            }
        } else if (connectionMethod === 'ssh') {
            if (!$('#ssh_username').val() || !$('#ssh_password').val()) {
                Swal.fire('Error', 'Username dan Password SSH harus diisi', 'error');
                return;
            }
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengidentifikasi... (max 30 detik)');

        var data = {
            _token: '{{ csrf_token() }}',
            ip_address: ip,
            connection_method: connectionMethod,
            brand: $('#brand_hint').val() // Optional brand hint
        };

        // Add connection-specific data
        if (connectionMethod === 'snmp') {
            data.snmp_port = $('#snmp_port').val();
            data.snmp_community = $('#snmp_community').val();
        } else if (connectionMethod === 'telnet') {
            data.telnet_enabled = 1;
            data.telnet_port = $('#telnet_port').val();
            data.telnet_username = $('#telnet_username').val();
            data.telnet_password = $('#telnet_password').val();
        } else if (connectionMethod === 'ssh') {
            data.ssh_enabled = 1;
            data.ssh_port = $('#ssh_port').val();
            data.ssh_username = $('#ssh_username').val();
            data.ssh_password = $('#ssh_password').val();
        }

        $.ajax({
            url: '{{ route("admin.olts.identify") }}',
            method: 'POST',
            data: data,
            timeout: 35000, // 35 seconds timeout (server has 30s limit)
            success: function(res) {
                if (res.success) {
                    identified = true;
                    
                    // Set hidden fields
                    $('#input_brand').val(res.brand);
                    $('#input_model').val(res.model || '');
                    $('#input_pon_ports').val(res.total_pon_ports);
                    $('#input_uplink_ports').val(res.total_uplink_ports);

                    // Show result
                    $('#result_brand').text(res.brand_label || res.brand?.toUpperCase() || '-');
                    $('#result_model').text(res.model || '-');
                    $('#result_olt_type').text(res.olt_type || '-');
                    $('#result_pon_ports').text(res.total_pon_ports || '-');
                    $('#result_uplink_ports').text(res.total_uplink_ports || '-');
                    $('#result_description').text(res.description || '-');

                    if (res.firmware || res.hardware_version || res.serial_number) {
                        $('#result_firmware').text(res.firmware || '-');
                        $('#result_hardware').text(res.hardware_version || '-');
                        $('#result_serial').text(res.serial_number || '-');
                        $('#row_firmware').show();
                    }

                    // Show boards table
                    if (res.boards && res.boards.length > 0) {
                        var tbody = $('#table-boards tbody');
                        tbody.empty();
                        res.boards.forEach(function(board) {
                            var statusBadge = board.oper_state == 'online' 
                                ? '<span class="badge badge-success">Online</span>' 
                                : '<span class="badge badge-danger">Offline</span>';
                            tbody.append(`
                                <tr>
                                    <td>${board.shelf}</td>
                                    <td>${board.slot}</td>
                                    <td><strong>${board.board_type}</strong><br><small class="text-muted">${board.type_category}</small></td>
                                    <td>${board.pon_ports || '-'}</td>
                                    <td>${board.uplink_ports || '-'}</td>
                                    <td>${statusBadge}</td>
                                </tr>
                            `);
                        });
                        $('#card-boards').show();
                    }

                    // Auto-generate name suggestion
                    var suggestedName = 'OLT-' + (res.brand_label || res.brand || 'UNKNOWN').toUpperCase();
                    if (res.model) {
                        suggestedName += '-' + res.model.replace(/\s+/g, '');
                    }
                    $('#name').val(suggestedName);

                    // Show/hide cards
                    $('#card-info').hide();
                    $('#card-result').show();
                    $('#card-name').show();
                    $('#card-location').show();
                    $('#btn-submit').prop('disabled', false);

                    // Init map
                    setTimeout(initMap, 100);

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'OLT berhasil diidentifikasi: ' + (res.brand_label || res.brand) + ' ' + (res.model || ''),
                        timer: 2000
                    });
                } else {
                    Swal.fire('Gagal', res.message || 'Tidak dapat mengidentifikasi OLT', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Identify OLT');
            }
        });
    });

    // Form submit validation
    $('#form-olt').submit(function(e) {
        if (!identified) {
            e.preventDefault();
            Swal.fire('Error', 'Silakan identify OLT terlebih dahulu', 'error');
            return false;
        }
        
        if (!$('#name').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Nama OLT harus diisi', 'error');
            return false;
        }

        return true;
    });

    // POP change - reload routers
    $('#pop_id').change(function() {
        var popId = $(this).val();
        if (!popId) return;
        
        $.get('/admin/routers/by-pop', { pop_id: popId }, function(data) {
            var select = $('#router_id');
            select.empty().append('<option value="">-- Pilih Router --</option>');
            data.forEach(function(router) {
                select.append('<option value="' + router.id + '">' + router.name + ' (' + router.ip_address + ')</option>');
            });
        });
    });
});
</script>
@endpush
