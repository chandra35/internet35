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
<!-- Progress Modal -->
<div class="modal fade" id="modal-progress" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-white">
                    <i class="fas fa-cog fa-spin mr-2" id="progress-spinner"></i>
                    <span id="progress-title">Sync Infrastruktur...</span>
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-dark" 
                         role="progressbar" id="progress-bar"
                         style="width: 0%">0%</div>
                </div>
                <div id="progress-logs" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                </div>
            </div>
            <div class="modal-footer" id="progress-footer" style="display: none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-dark" onclick="location.reload()">
                    <i class="fas fa-sync mr-1"></i>Refresh
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

@section('js')
<script>
$(function() {
    // Sync Infrastructure via SSE
    $('#btn-sync-infra').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Sync...');

        $('#progress-logs').html('');
        $('#progress-bar').css('width', '0%').text('0%').removeClass('bg-success bg-danger bg-warning').addClass('bg-dark');
        $('#progress-footer').hide();
        $('#modal-progress').modal('show');

        let url = '{{ route("admin.olts.sync-infrastructure-stream", $olt) }}';
        let eventSource = new EventSource(url);

        eventSource.onmessage = function(event) {
            let data = JSON.parse(event.data);

            if (data.type === 'progress') {
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');

                let colorClass = 'text-dark';
                let icon = 'fas fa-info-circle';
                if (data.status === 'success') { colorClass = 'text-success'; icon = 'fas fa-check-circle'; }
                else if (data.status === 'warning') { colorClass = 'text-warning'; icon = 'fas fa-exclamation-triangle'; }
                else if (data.status === 'error') { colorClass = 'text-danger'; icon = 'fas fa-times-circle'; }

                $('#progress-logs').append(
                    '<div class="' + colorClass + '"><i class="' + icon + ' mr-1"></i><small>[' + data.time + '] ' + data.message + '</small></div>'
                );
                $('#progress-logs').scrollTop($('#progress-logs')[0].scrollHeight);
            }

            if (data.type === 'complete') {
                eventSource.close();
                $('#progress-spinner').removeClass('fa-spin');
                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync dari OLT');

                if (data.success) {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-success');
                    $('#progress-title').text('Sync Berhasil');
                } else {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-danger');
                    $('#progress-title').text('Sync Gagal');
                }
            }
        };

        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').removeClass('fa-spin');
            $('#progress-footer').show();
            btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync dari OLT');
            $('#progress-bar').removeClass('bg-dark').addClass('bg-danger').css('width', '100%').text('Error');
            $('#progress-logs').append('<div class="text-danger"><i class="fas fa-times-circle mr-1"></i><small>Koneksi terputus</small></div>');
        };
    });

    // Edit VLAN Type
    $(document).on('click', '.btn-edit-vlan', function() {
        let btn = $(this);
        $('#edit-vlan-id').val(btn.data('id'));
        $('#edit-vlan-display').val(btn.data('vlan-id') + ' - ' + btn.data('name'));
        $('#edit-vlan-type').val(btn.data('type'));
        $('#edit-vlan-description').val(btn.data('description') || '');
        $('#modal-edit-vlan').modal('show');
    });

    // Save VLAN Type
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
@endsection
