@extends('layouts.admin')

@section('title', 'Infrastruktur OLT - ' . $olt->name)

@section('page-title', 'Infrastruktur OLT: ' . $olt->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.show', $olt) }}">{{ $olt->name }}</a></li>
    <li class="breadcrumb-item active">Infrastruktur</li>
@endsection

@section('content')
<!-- Progress Modal (Full Overlay) -->
<div class="modal fade" id="modal-progress" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-white">
                    <i class="fas fa-cog fa-spin mr-2" id="progress-spinner"></i>
                    <span id="progress-title">Sync Infrastruktur dari OLT...</span>
                </h5>
            </div>
            <div class="modal-body">
                <!-- Progress Bar -->
                <div class="progress mb-3" style="height: 28px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-dark" 
                         role="progressbar" id="progress-bar"
                         style="width: 0%">0%</div>
                </div>

                <!-- Step Indicators -->
                <div class="row text-center mb-3" id="step-indicators">
                    <div class="col-4">
                        <div class="p-2 rounded" id="step-cards" style="background: #f4f6f9;">
                            <i class="fas fa-microchip fa-lg mb-1 text-muted" id="step-cards-icon"></i>
                            <div class="font-weight-bold" style="font-size: 13px;">Kartu/Slot</div>
                            <small class="text-muted" id="step-cards-status">Menunggu...</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded" id="step-vlans" style="background: #f4f6f9;">
                            <i class="fas fa-tags fa-lg mb-1 text-muted" id="step-vlans-icon"></i>
                            <div class="font-weight-bold" style="font-size: 13px;">VLAN</div>
                            <small class="text-muted" id="step-vlans-status">Menunggu...</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded" id="step-uplinks" style="background: #f4f6f9;">
                            <i class="fas fa-arrow-up fa-lg mb-1 text-muted" id="step-uplinks-icon"></i>
                            <div class="font-weight-bold" style="font-size: 13px;">Uplink</div>
                            <small class="text-muted" id="step-uplinks-status">Menunggu...</small>
                        </div>
                    </div>
                </div>

                <!-- Log Output -->
                <div class="card card-outline card-secondary mb-0">
                    <div class="card-header py-1">
                        <h6 class="card-title mb-0"><i class="fas fa-terminal mr-1"></i>Log</h6>
                    </div>
                    <div class="card-body p-2" id="progress-logs" 
                         style="max-height: 200px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; background: #1e1e1e; color: #d4d4d4; border-radius: 0 0 4px 4px;">
                    </div>
                </div>

                <!-- Results Preview (hidden until complete) -->
                <div id="sync-results" class="mt-3" style="display: none;">
                    <div class="alert mb-0" id="sync-results-alert">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-2x mr-3" id="sync-results-icon"></i>
                            <div>
                                <strong id="sync-results-title"></strong>
                                <div class="text-sm" id="sync-results-message"></div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row text-center" id="sync-results-counts">
                            <div class="col-4">
                                <div class="h4 mb-0" id="result-cards-count">0</div>
                                <small>Kartu</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0" id="result-vlans-count">0</div>
                                <small>VLAN</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0" id="result-uplinks-count">0</div>
                                <small>Uplink</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="progress-footer" style="display: none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-dark" onclick="location.reload()">
                    <i class="fas fa-sync mr-1"></i>Refresh Halaman
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit VLAN Type Modal -->
<div class="modal fade" id="modal-edit-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-tag mr-2"></i>Klasifikasi VLAN</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-vlan-id">
                <div class="form-group">
                    <label>VLAN ID</label>
                    <input type="text" class="form-control" id="edit-vlan-display" readonly>
                </div>
                <div class="form-group">
                    <label>Tipe <span class="text-danger">*</span></label>
                    <select class="form-control" id="edit-vlan-type">
                        <option value="service">Service (Internet/PPPoE/IPoE)</option>
                        <option value="management">Management (TR069/CWMP)</option>
                        <option value="voip">VoIP</option>
                        <option value="iptv">IPTV</option>
                        <option value="infra">Infrastructure</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" class="form-control" id="edit-vlan-description" placeholder="Opsional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btn-save-vlan-type">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Header Actions -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-dark card-outline">
            <div class="card-body py-2 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-server mr-2"></i>{{ $olt->name }}
                        <small class="text-muted ml-2">{{ $olt->ip_address }} &middot; {{ $olt->brand }} {{ $olt->model }}</small>
                    </h5>
                </div>
                <div>
                    <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-outline-primary btn-sm mr-1">
                        <i class="fas fa-arrow-left mr-1"></i>Detail OLT
                    </a>
                    <button type="button" class="btn btn-dark btn-sm" id="btn-sync-infra">
                        <i class="fas fa-sync mr-1"></i>Sync dari OLT
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slot / Card Visual -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-microchip mr-2"></i>Kartu & Slot</h3>
                <div class="card-tools">
                    @if($olt->cards->where('last_sync_at', '!=', null)->first())
                        <span class="text-muted text-sm">
                            <i class="fas fa-clock mr-1"></i>Sync: {{ $olt->cards->first()->last_sync_at?->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($olt->cards->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada data kartu. Klik <strong>"Sync dari OLT"</strong> untuk mengambil data.</p>
                    </div>
                @else
                    <!-- Shelf Visual -->
                    <div class="shelf-visual mb-3 p-3 bg-dark rounded">
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-white font-weight-bold"><i class="fas fa-th mr-1"></i>RACK 1 / SHELF 1</span>
                            <span class="badge badge-light ml-auto">{{ $olt->cards->count() }} slot terisi</span>
                        </div>
                        <div class="row no-gutters">
                            @for($slot = 1; $slot <= 20; $slot++)
                                @php $card = $olt->cards->firstWhere('slot', $slot); @endphp
                                <div class="col px-1" style="min-width: 80px; max-width: 120px;">
                                    <div class="card mb-0 {{ $card ? ($card->status === 'inservice' ? 'border-success' : ($card->status === 'standby' ? 'border-info' : 'border-secondary')) : 'border-dark' }}" 
                                         style="border-width: 2px; {{ !$card ? 'opacity: 0.3;' : '' }}"
                                         @if($card) title="{{ $card->display_name }} — {{ $card->port_count }} ports — {{ $card->status }}" @endif>
                                        <div class="card-body p-1 text-center" style="min-height: 70px;">
                                            <small class="d-block text-muted">Slot {{ $slot }}</small>
                                            @if($card)
                                                <strong class="d-block" style="font-size: 11px;">{{ $card->real_type ?: $card->configured_type }}</strong>
                                                <small class="d-block text-muted">{{ $card->port_count }}P</small>
                                                @if($card->role === 'gpon')
                                                    <span class="badge badge-primary" style="font-size: 9px;">GPON</span>
                                                @elseif($card->role === 'management')
                                                    <span class="badge badge-warning" style="font-size: 9px;">MGMT</span>
                                                @elseif($card->role === 'power')
                                                    <span class="badge badge-danger" style="font-size: 9px;">PWR</span>
                                                @else
                                                    <span class="badge badge-secondary" style="font-size: 9px;">{{ strtoupper($card->role) }}</span>
                                                @endif
                                            @else
                                                <span class="d-block text-muted" style="font-size: 10px; margin-top: 10px;">—</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Card Details Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Slot</th>
                                    <th>Tipe Konfigurasi</th>
                                    <th>Tipe Aktual</th>
                                    <th>Port</th>
                                    <th>Role</th>
                                    <th>HW Ver</th>
                                    <th>SW Ver</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->cards as $card)
                                <tr>
                                    <td><strong>{{ $card->slot }}</strong></td>
                                    <td>{{ $card->configured_type ?? '-' }}</td>
                                    <td><strong>{{ $card->real_type ?? '-' }}</strong></td>
                                    <td>{{ $card->port_count }}</td>
                                    <td>{!! $card->role_badge !!}</td>
                                    <td><small>{{ $card->hardware_version ?? '-' }}</small></td>
                                    <td><small>{{ $card->software_version ?? '-' }}</small></td>
                                    <td>{!! $card->status_badge !!}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- VLANs + Uplinks Row -->
<div class="row">
    <!-- VLAN Database -->
    <div class="col-lg-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>VLAN Database</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $olt->vlans->count() }} VLANs</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($olt->vlans->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada data VLAN.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="80">VLAN ID</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Uplink Ports</th>
                                    <th>Keterangan</th>
                                    <th width="60">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->vlans as $vlan)
                                <tr>
                                    <td><strong>{{ $vlan->vlan_id }}</strong></td>
                                    <td>{{ $vlan->name }}</td>
                                    <td>{!! $vlan->type_badge !!}</td>
                                    <td><small class="text-muted">{{ $vlan->uplink_ports_display }}</small></td>
                                    <td><small>{{ $vlan->description ?? '-' }}</small></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-info btn-edit-vlan" 
                                                data-id="{{ $vlan->id }}"
                                                data-vlan-id="{{ $vlan->vlan_id }}"
                                                data-name="{{ $vlan->name }}"
                                                data-type="{{ $vlan->type }}"
                                                data-description="{{ $vlan->description }}"
                                                title="Ubah Tipe">
                                            <i class="fas fa-tag"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Uplink Ports -->
    <div class="col-lg-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-arrow-up mr-2"></i>Uplink Ports</h3>
                <div class="card-tools">
                    <span class="badge badge-success">{{ $olt->uplinks->where('status', 'up')->count() }}/{{ $olt->uplinks->count() }} Up</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($olt->uplinks->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-arrow-up fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada data uplink.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Interface</th>
                                    <th>Slot</th>
                                    <th>Status</th>
                                    <th>Tagged VLANs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->uplinks as $uplink)
                                <tr>
                                    <td>
                                        <strong>{{ $uplink->interface_name }}</strong>
                                        @if($uplink->interface_type === 'xgei')
                                            <span class="badge badge-info" style="font-size: 9px;">10G</span>
                                        @endif
                                    </td>
                                    <td>{{ $uplink->slot }}</td>
                                    <td>{!! $uplink->status_badge !!}</td>
                                    <td>
                                        @if($uplink->tagged_vlans)
                                            @foreach($uplink->tagged_vlans as $vid)
                                                <span class="badge badge-outline-secondary badge-sm" style="font-size: 10px;">{{ $vid }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // =========================================================================
    // Sync Infrastructure via SSE
    // =========================================================================
    $('#btn-sync-infra').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...');

        // Reset modal state
        $('#progress-logs').html('');
        $('#progress-bar').css('width', '0%').text('0%')
            .removeClass('bg-success bg-danger bg-warning').addClass('bg-dark progress-bar-animated');
        $('#progress-footer').hide();
        $('#sync-results').hide();
        $('#progress-spinner').addClass('fa-spin');
        $('#progress-title').text('Sync Infrastruktur dari OLT...');

        // Reset step indicators
        ['cards', 'vlans', 'uplinks'].forEach(function(step) {
            $('#step-' + step).css('background', '#f4f6f9');
            $('#step-' + step + '-icon').removeClass('text-success text-primary text-danger').addClass('text-muted');
            $('#step-' + step + '-status').text('Menunggu...').removeClass('text-success text-primary text-danger').addClass('text-muted');
        });

        $('#modal-progress').modal('show');

        let url = '{{ route("admin.olts.sync-infrastructure-stream", $olt) }}';
        let eventSource = new EventSource(url);

        function appendLog(message, status) {
            let color = '#d4d4d4';
            let icon = '&#9679;';
            if (status === 'success') { color = '#6bcf7f'; icon = '&#10003;'; }
            else if (status === 'warning') { color = '#f0ad4e'; icon = '&#9888;'; }
            else if (status === 'error') { color = '#e74c3c'; icon = '&#10007;'; }
            else if (status === 'info') { color = '#87ceeb'; icon = '&#8226;'; }

            let time = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            $('#progress-logs').append(
                '<div style="color:' + color + ';">' + icon + ' <span style="color:#888;">[' + time + ']</span> ' + message + '</div>'
            );
            let el = $('#progress-logs')[0];
            el.scrollTop = el.scrollHeight;
        }

        function setStepActive(step) {
            $('#step-' + step).css('background', '#e3f2fd');
            $('#step-' + step + '-icon').removeClass('text-muted').addClass('text-primary');
            $('#step-' + step + '-status').text('Memproses...').removeClass('text-muted').addClass('text-primary');
        }

        function setStepDone(step, count) {
            $('#step-' + step).css('background', '#e8f5e9');
            $('#step-' + step + '-icon').removeClass('text-primary text-muted').addClass('text-success');
            $('#step-' + step + '-status').text(count + ' tersinkronisasi').removeClass('text-primary text-muted').addClass('text-success');
        }

        function setStepError(step) {
            $('#step-' + step).css('background', '#fbe9e7');
            $('#step-' + step + '-icon').removeClass('text-primary text-muted').addClass('text-danger');
            $('#step-' + step + '-status').text('Error').removeClass('text-primary text-muted').addClass('text-danger');
        }

        eventSource.onmessage = function(event) {
            let data = JSON.parse(event.data);

            if (data.type === 'progress') {
                // Update progress bar
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');

                // Detect which step is active based on message
                let msg = data.message.toLowerCase();
                if (msg.includes('kartu') || msg.includes('show card') || msg.includes('card')) {
                    if (msg.includes('show card') || msg.includes('membaca data kartu')) {
                        setStepActive('cards');
                    }
                    let match = data.message.match(/(\d+)\s+slot\s+tersinkronisasi/i);
                    if (match) {
                        setStepDone('cards', match[1]);
                    }
                }
                if (msg.includes('vlan') || msg.includes('show vlan')) {
                    if (msg.includes('show vlan') || msg.includes('membaca database vlan')) {
                        setStepActive('vlans');
                    }
                    let match = data.message.match(/(\d+)\s+vlan\s+tersinkronisasi/i);
                    if (match) {
                        setStepDone('vlans', match[1]);
                    }
                }
                if (msg.includes('uplink') || msg.includes('interface brief')) {
                    if (msg.includes('interface brief') || msg.includes('membaca uplink')) {
                        setStepActive('uplinks');
                    }
                    let match = data.message.match(/(\d+)\s+port\s+tersinkronisasi/i);
                    if (match) {
                        setStepDone('uplinks', match[1]);
                    }
                }

                appendLog(data.message, data.status);
            }

            if (data.type === 'complete') {
                eventSource.close();

                // Stop spinner
                $('#progress-spinner').removeClass('fa-spin');
                $('#progress-bar').removeClass('progress-bar-animated');

                // Show results preview
                $('#sync-results').show();

                if (data.success) {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-success');
                    $('#progress-title').text('Sync Berhasil!');
                    $('#sync-results-alert').removeClass('alert-danger').addClass('alert-success');
                    $('#sync-results-icon').removeClass('fa-times-circle text-danger').addClass('fa-check-circle text-success');
                    $('#sync-results-title').text('Sync Infrastruktur Berhasil');
                    $('#sync-results-message').text(data.message);
                } else {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-danger');
                    $('#progress-title').text('Sync Gagal');
                    $('#sync-results-alert').removeClass('alert-success').addClass('alert-danger');
                    $('#sync-results-icon').removeClass('fa-check-circle text-success').addClass('fa-times-circle text-danger');
                    $('#sync-results-title').text('Sync Gagal');
                    $('#sync-results-message').text(data.message);
                    setStepError('cards');
                    setStepError('vlans');
                    setStepError('uplinks');
                }

                // Update result counts
                $('#result-cards-count').text(data.cards_synced || 0);
                $('#result-vlans-count').text(data.vlans_synced || 0);
                $('#result-uplinks-count').text(data.uplinks_synced || 0);

                // Show footer buttons
                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync dari OLT');

                appendLog('--- Selesai ---', data.success ? 'success' : 'error');
            }
        };

        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').removeClass('fa-spin');
            $('#progress-bar').removeClass('progress-bar-animated bg-dark').addClass('bg-danger').css('width', '100%').text('Error');
            $('#progress-footer').show();
            btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync dari OLT');
            appendLog('Koneksi SSE terputus. Periksa koneksi ke OLT.', 'error');

            // Show error result
            $('#sync-results').show();
            $('#sync-results-alert').removeClass('alert-success').addClass('alert-danger');
            $('#sync-results-icon').addClass('fa-exclamation-triangle text-danger');
            $('#sync-results-title').text('Koneksi Terputus');
            $('#sync-results-message').text('Tidak dapat terhubung ke server. Pastikan OLT dapat dijangkau.');
        };
    });

    // =========================================================================
    // Edit VLAN Type
    // =========================================================================
    $(document).on('click', '.btn-edit-vlan', function() {
        let btn = $(this);
        $('#edit-vlan-id').val(btn.data('id'));
        $('#edit-vlan-display').val(btn.data('vlan-id') + ' - ' + btn.data('name'));
        $('#edit-vlan-type').val(btn.data('type'));
        $('#edit-vlan-description').val(btn.data('description') || '');
        $('#modal-edit-vlan').modal('show');
    });

    $('#btn-save-vlan-type').click(function() {
        let btn = $(this);
        let vlanId = $('#edit-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.update-type", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                type: $('#edit-vlan-type').val(),
                description: $('#edit-vlan-description').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan');
            }
        });
    });
});
</script>
@endpush
