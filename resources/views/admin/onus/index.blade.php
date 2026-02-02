@extends('layouts.admin')

@section('title', 'Manajemen ONU')

@section('page-title', 'Manajemen ONU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">ONU</li>
@endsection

@section('content')
<!-- Stats -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total'] ?? 0 }}</h3>
                <p>Total ONU</p>
            </div>
            <div class="icon"><i class="fas fa-hdd"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['online'] ?? 0 }}</h3>
                <p>Online</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['offline'] ?? 0 }}</h3>
                <p>Offline</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['los'] ?? 0 }}</h3>
                <p>LOS</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.onus.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>OLT</label>
                        <select name="olt_id" class="form-control select2">
                            <option value="">-- Semua OLT --</option>
                            @foreach($olts as $olt)
                            <option value="{{ $olt->id }}" {{ request('olt_id') == $olt->id ? 'selected' : '' }}>
                                {{ $olt->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">-- Semua --</option>
                            <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="los" {{ request('status') == 'los' ? 'selected' : '' }}>LOS</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Sinyal</label>
                        <select name="signal" class="form-control">
                            <option value="">-- Semua --</option>
                            <option value="good" {{ request('signal') == 'good' ? 'selected' : '' }}>Bagus (> -25dBm)</option>
                            <option value="warning" {{ request('signal') == 'warning' ? 'selected' : '' }}>Peringatan (-25 ~ -27dBm)</option>
                            <option value="bad" {{ request('signal') == 'bad' ? 'selected' : '' }}>Buruk (< -27dBm)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Pencarian</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="SN, Nama, Pelanggan..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('admin.onus.index') }}" class="btn btn-secondary">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ONU Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hdd mr-2"></i>Daftar ONU</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-success btn-sm btn-bulk-sync" title="Sync All">
                <i class="fas fa-sync"></i> Bulk Sync
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="table-onus">
                <thead class="thead-dark">
                    <tr>
                        <th width="5%">#</th>
                        <th>OLT</th>
                        <th>PON/ONU</th>
                        <th>Serial Number</th>
                        <th>Nama</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th>RX Power</th>
                        <th>TX Power</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($onus as $onu)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('admin.olts.show', $onu->olt) }}">
                                {{ $onu->olt->name }}
                            </a>
                        </td>
                        <td>
                            <strong>{{ $onu->pon_port }}/{{ $onu->onu_number }}</strong>
                        </td>
                        <td><code>{{ $onu->serial_number }}</code></td>
                        <td>{{ $onu->name ?? '-' }}</td>
                        <td>
                            @if($onu->customer)
                                <a href="{{ route('admin.customers.show', $onu->customer) }}">
                                    {{ $onu->customer->name }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($onu->status == 'online')
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Online</span>
                            @elseif($onu->status == 'offline')
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Offline</span>
                            @elseif($onu->status == 'los')
                                <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>LOS</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($onu->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $rx = $onu->rx_power;
                                $rxClass = 'success';
                                if ($rx === null) $rxClass = 'secondary';
                                elseif ($rx < -27) $rxClass = 'danger';
                                elseif ($rx < -25) $rxClass = 'warning';
                            @endphp
                            <span class="badge badge-{{ $rxClass }}">
                                {{ $rx !== null ? number_format($rx, 2) . ' dBm' : '-' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $tx = $onu->tx_power;
                            @endphp
                            <span class="badge badge-{{ $tx !== null ? 'info' : 'secondary' }}">
                                {{ $tx !== null ? number_format($tx, 2) . ' dBm' : '-' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.onus.show', $onu) }}" class="btn btn-xs btn-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('onu.reboot')
                                <button type="button" class="btn btn-xs btn-warning btn-reboot-onu" 
                                        data-id="{{ $onu->id }}" title="Reboot">
                                    <i class="fas fa-sync"></i>
                                </button>
                                @endcan
                                @can('onu.unregister')
                                <button type="button" class="btn btn-xs btn-danger btn-unregister-onu" 
                                        data-id="{{ $onu->id }}" data-sn="{{ $onu->serial_number }}" title="Unregister">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Tidak ada data ONU</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($onus->hasPages())
    <div class="card-footer">
        {{ $onus->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@push('js')
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    // Reboot ONU
    $(document).on('click', '.btn-reboot-onu', function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        Swal.fire({
            title: 'Konfirmasi Reboot',
            text: 'Apakah Anda yakin ingin me-reboot ONU ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            confirmButtonText: 'Ya, Reboot!'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true);
                $.post('/admin/onus/' + id + '/reboot', { _token: '{{ csrf_token() }}' })
                    .done(function(res) {
                        Swal.fire('Berhasil', res.message || 'ONU sedang di-reboot', 'success');
                    })
                    .fail(function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal me-reboot ONU', 'error');
                    })
                    .always(function() {
                        btn.prop('disabled', false);
                    });
            }
        });
    });

    // Unregister ONU
    $(document).on('click', '.btn-unregister-onu', function() {
        var id = $(this).data('id');
        var sn = $(this).data('sn');
        
        Swal.fire({
            title: 'Konfirmasi Unregister',
            html: `Apakah Anda yakin ingin menghapus ONU <strong>${sn}</strong>?<br><br><small class="text-danger">ONU akan dihapus dari OLT!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/onus/' + id + '/unregister',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        Swal.fire('Berhasil', res.message || 'ONU berhasil dihapus', 'success')
                            .then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menghapus ONU', 'error');
                    }
                });
            }
        });
    });

    // Bulk Sync
    $('.btn-bulk-sync').click(function() {
        var btn = $(this);
        Swal.fire({
            title: 'Bulk Sync ONU',
            text: 'Ini akan menyinkronkan semua ONU dari semua OLT. Proses ini mungkin memakan waktu.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Sync!'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
                $.post('/admin/onus/bulk-sync', { _token: '{{ csrf_token() }}' })
                    .done(function(res) {
                        Swal.fire('Berhasil', res.message || 'Semua ONU berhasil disinkronkan', 'success')
                            .then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal sinkronisasi', 'error');
                    })
                    .always(function() {
                        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Bulk Sync');
                    });
            }
        });
    });
});
</script>
@endpush
