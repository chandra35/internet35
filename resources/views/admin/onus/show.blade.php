@extends('layouts.admin')

@section('title', 'Detail ONU - ' . ($onu->name ?? $onu->serial_number))

@section('page-title', 'Detail ONU: ' . ($onu->name ?? $onu->serial_number))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.onus.index') }}">ONU</a></li>
    <li class="breadcrumb-item active">{{ $onu->name ?? $onu->serial_number }}</li>
@endsection

@section('content')
<div class="row">
    <!-- ONU Info -->
    <div class="col-lg-4">
        <!-- Status Card -->
        <div class="card card-widget widget-user-2 shadow">
            <div class="widget-user-header bg-{{ $onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger') }}">
                <div class="widget-user-image">
                    <i class="fas fa-hdd fa-3x"></i>
                </div>
                <h3 class="widget-user-username">{{ $onu->name ?? 'ONU' }}</h3>
                <h5 class="widget-user-desc">{{ $onu->serial_number }}</h5>
            </div>
            <div class="card-footer p-0">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <span class="nav-link">
                            Status
                            <span class="float-right">
                                @if($onu->status == 'online')
                                    <span class="badge badge-success">Online</span>
                                @elseif($onu->status == 'offline')
                                    <span class="badge badge-danger">Offline</span>
                                @elseif($onu->status == 'los')
                                    <span class="badge badge-warning">LOS</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($onu->status) }}</span>
                                @endif
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            RX Power
                            @php
                                $rx = $onu->rx_power;
                                $rxClass = 'success';
                                if ($rx === null) $rxClass = 'secondary';
                                elseif ($rx < -27) $rxClass = 'danger';
                                elseif ($rx < -25) $rxClass = 'warning';
                            @endphp
                            <span class="float-right badge badge-{{ $rxClass }}">
                                {{ $rx !== null ? number_format($rx, 2) . ' dBm' : '-' }}
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            TX Power
                            <span class="float-right badge badge-{{ $onu->tx_power !== null ? 'info' : 'secondary' }}">
                                {{ $onu->tx_power !== null ? number_format($onu->tx_power, 2) . ' dBm' : '-' }}
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            Distance
                            <span class="float-right">
                                {{ $onu->distance ? number_format($onu->distance, 2) . ' km' : '-' }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi ONU</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tr>
                        <td width="40%"><strong>OLT</strong></td>
                        <td>
                            <a href="{{ route('admin.olts.show', $onu->olt) }}">
                                {{ $onu->olt->name }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>PON Port</strong></td>
                        <td>{{ $onu->pon_port }}</td>
                    </tr>
                    <tr>
                        <td><strong>ONU Number</strong></td>
                        <td>{{ $onu->onu_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Serial Number</strong></td>
                        <td><code>{{ $onu->serial_number }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>ONU Type</strong></td>
                        <td>{{ $onu->onu_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Profile</strong></td>
                        <td>{{ $onu->profile->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Pelanggan</strong></td>
                        <td>
                            @if($onu->customer)
                                <a href="{{ route('admin.customers.show', $onu->customer) }}">
                                    {{ $onu->customer->name }}
                                </a>
                            @else
                                <span class="text-muted">Belum dipasangkan</span>
                            @endif
                        </td>
                    </tr>
                    @if($onu->vlan_id)
                    <tr>
                        <td><strong>VLAN ID</strong></td>
                        <td>{{ $onu->vlan_id }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Last Online</strong></td>
                        <td>{{ $onu->last_online_at ? $onu->last_online_at->diffForHumans() : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Sync</strong></td>
                        <td>{{ $onu->last_sync_at ? $onu->last_sync_at->diffForHumans() : '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                @can('onu.reboot')
                <button type="button" class="btn btn-warning btn-sm btn-reboot-onu" data-id="{{ $onu->id }}">
                    <i class="fas fa-sync"></i> Reboot
                </button>
                @endcan
                <button type="button" class="btn btn-info btn-sm btn-refresh-signal" data-id="{{ $onu->id }}">
                    <i class="fas fa-signal"></i> Refresh Signal
                </button>
                @can('onu.unregister')
                <button type="button" class="btn btn-danger btn-sm btn-unregister-onu" 
                        data-id="{{ $onu->id }}" data-sn="{{ $onu->serial_number }}">
                    <i class="fas fa-trash"></i> Unregister
                </button>
                @endcan
            </div>
        </div>

        <!-- Assign Customer -->
        @if(!$onu->customer)
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Assign ke Pelanggan</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.onus.assign-customer', $onu) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Pilih Pelanggan</label>
                        <select name="customer_id" class="form-control select2-customer" style="width:100%" required>
                            <option value="">-- Pilih Pelanggan --</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-link"></i> Assign
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <!-- Signal Chart & Details -->
    <div class="col-lg-8">
        <!-- Signal History Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Histori Signal (7 Hari Terakhir)</h3>
                <div class="card-tools">
                    <select id="chart-period" class="form-control form-control-sm">
                        <option value="24h">24 Jam</option>
                        <option value="7d" selected>7 Hari</option>
                        <option value="30d">30 Hari</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <canvas id="signal-chart" style="height: 300px;"></canvas>
            </div>
        </div>

        <!-- Signal Quality -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-signal mr-2"></i>Kualitas Signal</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-{{ $rxClass }} elevation-1">
                                <i class="fas fa-arrow-down"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">RX Power (Downstream)</span>
                                <span class="info-box-number">{{ $rx !== null ? number_format($rx, 2) . ' dBm' : '-' }}</span>
                                <div class="progress">
                                    @php
                                        $rxPercent = $rx !== null ? min(100, max(0, (($rx + 40) / 25) * 100)) : 0;
                                    @endphp
                                    <div class="progress-bar bg-{{ $rxClass }}" style="width: {{ $rxPercent }}%"></div>
                                </div>
                                <span class="progress-description">
                                    @if($rx === null)
                                        Tidak tersedia
                                    @elseif($rx >= -25)
                                        <i class="fas fa-check-circle text-success"></i> Excellent
                                    @elseif($rx >= -27)
                                        <i class="fas fa-exclamation-circle text-warning"></i> Good, perlu monitoring
                                    @else
                                        <i class="fas fa-times-circle text-danger"></i> Poor, perlu perbaikan
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-info elevation-1">
                                <i class="fas fa-arrow-up"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">TX Power (Upstream)</span>
                                <span class="info-box-number">{{ $onu->tx_power !== null ? number_format($onu->tx_power, 2) . ' dBm' : '-' }}</span>
                                <div class="progress">
                                    @php
                                        $tx = $onu->tx_power;
                                        $txPercent = $tx !== null ? min(100, max(0, (($tx + 10) / 15) * 100)) : 0;
                                    @endphp
                                    <div class="progress-bar bg-info" style="width: {{ $txPercent }}%"></div>
                                </div>
                                <span class="progress-description">
                                    @if($tx === null)
                                        Tidak tersedia
                                    @else
                                        Range normal: 0.5 ~ 5 dBm
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signal Thresholds -->
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Threshold Signal</h5>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td><span class="badge badge-success">Excellent</span></td>
                            <td>> -25 dBm</td>
                            <td>Signal sangat baik, optimal</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">Warning</span></td>
                            <td>-25 ~ -27 dBm</td>
                            <td>Signal cukup, perlu monitoring</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-danger">Critical</span></td>
                            <td>< -27 dBm</td>
                            <td>Signal lemah, perlu perbaikan</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Description & Notes -->
        @if($onu->description || $onu->notes)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
            </div>
            <div class="card-body">
                @if($onu->description)
                <p><strong>Deskripsi:</strong> {{ $onu->description }}</p>
                @endif
                @if($onu->notes)
                <p><strong>Catatan:</strong> {{ $onu->notes }}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Recent Signal History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Signal Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>RX Power</th>
                                <th>TX Power</th>
                                <th>Status</th>
                                <th>Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($signalHistory as $history)
                            <tr>
                                <td>{{ $history->recorded_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @php
                                        $histRx = $history->rx_power;
                                        $histRxClass = $histRx >= -25 ? 'success' : ($histRx >= -27 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge badge-{{ $histRxClass }}">
                                        {{ number_format($histRx, 2) }} dBm
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ $history->tx_power ? number_format($history->tx_power, 2) . ' dBm' : '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($history->status == 'online')
                                        <span class="badge badge-success">Online</span>
                                    @elseif($history->status == 'offline')
                                        <span class="badge badge-danger">Offline</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($history->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $history->distance ? number_format($history->distance, 2) . ' km' : '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada data histori</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
    // Select2 for customer
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        ajax: {
            url: '{{ route("admin.customers.search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return { id: item.id, text: item.customer_id + ' - ' + item.name };
                    })
                };
            }
        }
    });

    // Signal Chart
    var ctx = document.getElementById('signal-chart').getContext('2d');
    var signalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels ?? []) !!},
            datasets: [{
                label: 'RX Power (dBm)',
                data: {!! json_encode($chartRxData ?? []) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'TX Power (dBm)',
                data: {!! json_encode($chartTxData ?? []) !!},
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Power (dBm)'
                    }
                }
            },
            plugins: {
                annotation: {
                    annotations: {
                        warningLine: {
                            type: 'line',
                            yMin: -25,
                            yMax: -25,
                            borderColor: 'orange',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            label: {
                                enabled: true,
                                content: 'Warning (-25dBm)'
                            }
                        },
                        criticalLine: {
                            type: 'line',
                            yMin: -27,
                            yMax: -27,
                            borderColor: 'red',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            label: {
                                enabled: true,
                                content: 'Critical (-27dBm)'
                            }
                        }
                    }
                }
            }
        }
    });

    // Period change
    $('#chart-period').change(function() {
        var period = $(this).val();
        $.get('{{ route("admin.onus.signal-history", $onu) }}', { period: period }, function(res) {
            signalChart.data.labels = res.labels;
            signalChart.data.datasets[0].data = res.rx_data;
            signalChart.data.datasets[1].data = res.tx_data;
            signalChart.update();
        });
    });

    // Refresh Signal
    $('.btn-refresh-signal').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post('/admin/onus/' + id + '/refresh-signal', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                Swal.fire('Berhasil', res.message || 'Signal berhasil di-refresh', 'success')
                    .then(() => location.reload());
            })
            .fail(function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal refresh signal', 'error');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-signal"></i> Refresh Signal');
            });
    });

    // Reboot ONU
    $('.btn-reboot-onu').click(function() {
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
    $('.btn-unregister-onu').click(function() {
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
                            .then(() => window.location.href = '{{ route("admin.onus.index") }}');
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menghapus ONU', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
