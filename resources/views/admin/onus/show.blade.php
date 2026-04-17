@extends('layouts.admin')

@section('title', 'Detail ONU - ' . $onu->serial_number)

@section('page-title', 'Detail ONU: ' . $onu->serial_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.onus.index') }}">ONU</a></li>
    <li class="breadcrumb-item active">{{ $onu->serial_number }}</li>
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
                <h3 class="widget-user-username">{{ $onu->serial_number }}</h3>
                <h5 class="widget-user-desc">{{ $onu->name ?? $onu->description ?? '' }}</h5>
            </div>
            <div class="card-footer p-0">
                @php
                    $onuRx = $onu->rx_power;
                    $oltRx = $onu->olt_rx_power;
                    $rxDisplay = $onuRx ?? $oltRx;
                    $rxClass = 'secondary';
                    if ($rxDisplay !== null) {
                        $rxClass = $rxDisplay >= -25 ? 'success' : ($rxDisplay >= -27 ? 'warning' : 'danger');
                    }
                    $dist = $onu->distance;
                    $distFormatted = '';
                    if ($dist) {
                        $distFormatted = $dist >= 1000 ? number_format($dist / 1000, 2) . 'km' : $dist . 'm';
                    }
                @endphp
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-circle text-{{ $onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger') }} mr-1" style="font-size:8px;vertical-align:middle"></i>
                            Status
                            <span class="float-right" id="onu-status">
                                @if($onu->status == 'online')
                                    <span class="badge badge-success">Online</span>
                                @elseif($onu->status == 'offline')
                                    <span class="badge badge-danger">Offline</span>
                                @elseif($onu->status == 'los')
                                    <span class="badge badge-warning">LOS</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($onu->status ?? 'unknown') }}</span>
                                @endif
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-signal text-{{ $rxClass }} mr-1" style="font-size:10px"></i>
                            ONU/OLT Rx signal
                            <span class="float-right" id="onu-signal">
                                @if($onuRx !== null || $oltRx !== null)
                                    <span class="text-{{ $rxClass }}">
                                        {{ $onuRx !== null ? number_format($onuRx, 2) : '-' }} dBm
                                        / {{ $oltRx !== null ? number_format($oltRx, 2) : '-' }} dBm
                                        @if($distFormatted) ({{ $distFormatted }}) @endif
                                    </span>
                                    <i class="fas fa-signal text-{{ $rxClass }} ml-1" style="font-size:10px"></i>
                                @else
                                    <span class="text-muted">Memuat...</span>
                                @endif
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
                        <td>{{ $onu->port ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>ONU ID</strong></td>
                        <td>{{ $onu->onu_id ?? '-' }}</td>
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
                        <td><strong>Zone</strong></td>
                        <td>{{ $onu->zone->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>ODP</strong></td>
                        <td>{{ $onu->odp->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Line Profile</strong></td>
                        <td>{{ $onu->line_profile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Service Profile</strong></td>
                        <td>{{ $onu->service_profile ?? '-' }}</td>
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
                    @if(!empty($onu->vlan_config))
                    <tr>
                        <td><strong>VLAN Config</strong></td>
                        <td>
                            @if(is_array($onu->vlan_config))
                                {{ collect($onu->vlan_config)->filter(fn($value) => $value !== null && $value !== '')->map(function($value, $key) {
                                    return ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                })->implode(', ') ?: '-' }}
                            @else
                                {{ $onu->vlan_config }}
                            @endif
                        </td>
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
                @can('onus.reboot')
                <button type="button" class="btn btn-warning btn-sm btn-reboot-onu" data-id="{{ $onu->id }}">
                    <i class="fas fa-sync"></i> Reboot
                </button>
                @endcan
                <button type="button" class="btn btn-info btn-sm btn-refresh-signal" data-id="{{ $onu->id }}">
                    <i class="fas fa-signal"></i> Refresh Signal
                </button>
                @can('onus.unregister')
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

        <!-- Management VLAN/IP Config -->
        <div class="card card-outline card-warning collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Management IP</h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $onu->mgmt_ip ? 'success' : 'secondary' }} mr-2" id="mgmt-status-badge">
                        {{ $onu->mgmt_ip ? 'Active' : 'Inactive' }}
                    </span>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body" style="display:none;">
                @if($onu->mgmt_ip)
                <p class="mb-2"><strong>Current:</strong> <code>{{ $onu->mgmt_ip }}</code></p>
                @endif
                <form id="form-management">
                    <div class="form-group">
                        <label>Management VLAN ID</label>
                        <input type="number" name="mgmt_vlan" class="form-control form-control-sm" min="1" max="4094" value="111" placeholder="e.g. 111">
                    </div>
                    <div class="form-group">
                        <label>IP Mode</label>
                        <select name="mgmt_ip_mode" class="form-control form-control-sm">
                            <option value="dhcp">DHCP</option>
                            <option value="static">Static IP</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" id="mgmt-static-ip" style="display:none;">
                        <label>Static IP</label>
                        <input type="text" name="mgmt_ip" class="form-control form-control-sm" placeholder="172.16.x.x">
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm btn-block">
                        <i class="fas fa-save mr-1"></i>Update Management
                    </button>
                </form>
            </div>
        </div>

        <!-- WAN Setup -->
        <div class="card card-outline card-success collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-globe mr-2"></i>WAN Setup</h3>
                <div class="card-tools">
                    @php
                        $wanVlan = $onu->vlan_config['vlan_id'] ?? null;
                    @endphp
                    <span class="badge badge-{{ $wanVlan ? 'info' : 'secondary' }} mr-2">
                        {{ $wanVlan ? 'VLAN ' . $wanVlan : 'Not Set' }}
                    </span>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body" style="display:none;">
                <form id="form-wan-setup">
                    <div class="form-group">
                        <label>WAN VLAN-ID</label>
                        <input type="number" name="wan_vlan" class="form-control form-control-sm" min="1" max="4094" value="{{ $wanVlan ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>ONU Mode</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="wan_mode" value="routing" class="custom-control-input" id="wan-mode-routing" checked>
                                <label class="custom-control-label" for="wan-mode-routing">Routing</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="wan_mode" value="bridging" class="custom-control-input" id="wan-mode-bridging">
                                <label class="custom-control-label" for="wan-mode-bridging">Bridging</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>WAN Type</label>
                        <select name="wan_type" id="wan-type-select" class="form-control form-control-sm">
                            <option value="manual">Setup via ONU webpage</option>
                            <option value="dhcp">DHCP</option>
                            <option value="pppoe">PPPoE</option>
                            <option value="static">Static IP</option>
                        </select>
                    </div>
                    <div id="wan-pppoe-fields" style="display:none;">
                        <div class="form-group">
                            <label>PPPoE Username</label>
                            <input type="text" name="pppoe_username" class="form-control form-control-sm" value="{{ $onu->pppoe_username }}" placeholder="username@isp">
                        </div>
                        <div class="form-group">
                            <label>PPPoE Password</label>
                            <input type="password" name="pppoe_password" class="form-control form-control-sm" placeholder="password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-save mr-1"></i>Update WAN
                    </button>
                </form>
            </div>
        </div>

        <!-- TR069 Info -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-satellite-dish mr-2"></i>TR069 / GenieACS</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary mr-2" id="tr069-status-badge">Loading...</span>
                    <button type="button" class="btn btn-tool btn-refresh-tr069" title="Refresh TR069"><i class="fas fa-sync"></i></button>
                </div>
            </div>
            <div class="card-body p-0" id="tr069-content">
                <div class="text-center py-3" id="tr069-loading">
                    <i class="fas fa-spinner fa-spin"></i> Memuat info TR069...
                </div>
                <div id="tr069-info" style="display:none;">
                    <table class="table table-sm table-striped mb-0" id="tr069-table"></table>
                </div>
                <div id="tr069-not-found" style="display:none;" class="text-center py-3 text-muted">
                    <i class="fas fa-times-circle fa-2x mb-2 d-block"></i>
                    ONU belum terdaftar di GenieACS
                </div>
                <div id="tr069-unavailable" style="display:none;" class="text-center py-3 text-muted">
                    <i class="fas fa-server fa-2x mb-2 d-block"></i>
                    GenieACS server tidak tersedia
                </div>
            </div>
            <div class="card-footer" id="tr069-actions" style="display:none;">
                <a href="#" id="tr069-ui-link" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fas fa-external-link-alt mr-1"></i>Buka di GenieACS
                </a>
                <button type="button" class="btn btn-sm btn-info btn-refresh-tr069-data">
                    <i class="fas fa-sync mr-1"></i>Refresh Data
                </button>
            </div>
        </div>
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

        <!-- Traffic Realtime -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>Traffic Realtime</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool btn-refresh-traffic" title="Refresh Traffic">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Download (RX)</span>
                                <span class="info-box-number" id="traffic-rx">-</span>
                                <div class="progress">
                                    <div class="progress-bar" id="traffic-rx-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description" id="traffic-rx-rate">Memuat...</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Upload (TX)</span>
                                <span class="info-box-number" id="traffic-tx">-</span>
                                <div class="progress">
                                    <div class="progress-bar" id="traffic-tx-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description" id="traffic-tx-rate">Memuat...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-muted small text-center" id="traffic-updated">
                    <i class="fas fa-clock mr-1"></i>Terakhir update: -
                </div>
            </div>
        </div>

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
                return {
                    q: params.term,
                    pop_id: '{{ $onu->olt->pop_id }}',
                    without_onu: true
                };
            },
            processResults: function(data) {
                var results = data.results || [];
                return {
                    results: results.map(function(item) {
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

    // Traffic variables for rate calculation
    var lastTrafficRx = null;
    var lastTrafficTx = null;
    var lastTrafficTime = null;

    // Helper: get RX badge class from dBm value
    function rxBadgeClass(val) {
        if (val === null || val === undefined) return 'secondary';
        if (val >= -25) return 'success';
        if (val >= -27) return 'warning';
        return 'danger';
    }

    // Refresh Traffic function
    function refreshTraffic() {
        $.post('/admin/onus/{{ $onu->id }}/refresh-signal', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success && res.data) {
                    var now = new Date();

                    // Update ONU/OLT Rx signal (SmartOLT format)
                    var onuRx = res.data.rx_power;
                    var oltRx = res.data.olt_rx_power;
                    var dist = res.data.distance;
                    var rxDisplay = onuRx ?? oltRx;
                    var rc = rxBadgeClass(rxDisplay);

                    var onuRxText = (onuRx !== null && onuRx !== undefined) ? parseFloat(onuRx).toFixed(2) : '-';
                    var oltRxText = (oltRx !== null && oltRx !== undefined) ? parseFloat(oltRx).toFixed(2) : '-';
                    var distText = '';
                    if (dist !== null && dist !== undefined && dist > 0) {
                        distText = ' (' + (dist >= 1000 ? (dist / 1000).toFixed(2) + 'km' : dist + 'm') + ')';
                    }
                    $('#onu-signal').html(
                        '<span class="text-' + rc + '">' +
                        onuRxText + ' dBm / ' + oltRxText + ' dBm' + distText +
                        '</span> <i class="fas fa-signal text-' + rc + ' ml-1" style="font-size:10px"></i>'
                    );
                    
                    // Update total traffic display
                    $('#traffic-rx').text(res.data.in_octets_formatted || '-');
                    $('#traffic-tx').text(res.data.out_octets_formatted || '-');
                    
                    // Calculate rate if we have previous data
                    if (lastTrafficTime !== null && lastTrafficRx !== null) {
                        var timeDiff = (now - lastTrafficTime) / 1000; // seconds
                        if (timeDiff > 0) {
                            var rxRate = ((res.data.in_octets - lastTrafficRx) * 8 / timeDiff / 1000000).toFixed(2);
                            var txRate = ((res.data.out_octets - lastTrafficTx) * 8 / timeDiff / 1000000).toFixed(2);
                            
                            // Prevent negative rates (counter reset)
                            rxRate = Math.max(0, rxRate);
                            txRate = Math.max(0, txRate);
                            
                            $('#traffic-rx-rate').html('<i class="fas fa-tachometer-alt mr-1"></i>' + rxRate + ' Mbps');
                            $('#traffic-tx-rate').html('<i class="fas fa-tachometer-alt mr-1"></i>' + txRate + ' Mbps');
                            
                            // Update progress bars (max 100 Mbps scale)
                            $('#traffic-rx-bar').css('width', Math.min(100, rxRate) + '%');
                            $('#traffic-tx-bar').css('width', Math.min(100, txRate) + '%');
                        }
                    } else {
                        $('#traffic-rx-rate').html('<i class="fas fa-clock mr-1"></i>Menghitung...');
                        $('#traffic-tx-rate').html('<i class="fas fa-clock mr-1"></i>Menghitung...');
                    }
                    
                    // Save for next calculation
                    lastTrafficRx = res.data.in_octets;
                    lastTrafficTx = res.data.out_octets;
                    lastTrafficTime = now;
                    
                    // Update timestamp
                    $('#traffic-updated').html('<i class="fas fa-clock mr-1"></i>Terakhir update: ' + now.toLocaleTimeString());
                }
            })
            .fail(function(xhr) {
                $('#traffic-rx-rate').html('<span class="text-danger">Error</span>');
                $('#traffic-tx-rate').html('<span class="text-danger">Error</span>');
            });
    }

    // Initial load and auto-refresh every 5 seconds
    refreshTraffic();
    var trafficInterval = setInterval(refreshTraffic, 5000);

    // Manual refresh button
    $('.btn-refresh-traffic').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        refreshTraffic();
        setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 500);
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

    // ========== Management VLAN/IP ==========
    $('select[name="mgmt_ip_mode"]').change(function() {
        $('#mgmt-static-ip').toggle($(this).val() === 'static');
    });

    $('#form-management').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/configure-management',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) {
                    Swal.fire('Berhasil', res.message || 'Management IP berhasil dikonfigurasi', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal konfigurasi', 'error');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Gagal konfigurasi management';
                if (xhr.responseJSON?.errors) {
                    msg += '<br>' + Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update Management');
            }
        });
    });

    // ========== WAN Setup ==========
    $('#wan-type-select').change(function() {
        $('#wan-pppoe-fields').toggle($(this).val() === 'pppoe');
    });

    $('#form-wan-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/configure-wan',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) {
                    Swal.fire('Berhasil', res.message || 'WAN berhasil dikonfigurasi', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal konfigurasi WAN', 'error');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Gagal konfigurasi WAN';
                if (xhr.responseJSON?.errors) {
                    msg += '<br>' + Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update WAN');
            }
        });
    });

    // ========== TR069 / GenieACS ==========
    function loadTr069Info() {
        $('#tr069-loading').show();
        $('#tr069-info, #tr069-not-found, #tr069-unavailable, #tr069-actions').hide();
        $('#tr069-status-badge').removeClass().addClass('badge badge-secondary mr-2').text('Loading...');

        $.get('/admin/onus/{{ $onu->id }}/tr069-info')
            .done(function(res) {
                $('#tr069-loading').hide();

                if (!res.success && !res.available) {
                    $('#tr069-unavailable').show();
                    $('#tr069-status-badge').removeClass().addClass('badge badge-danger mr-2').text('Unavailable');
                    return;
                }

                if (!res.found) {
                    $('#tr069-not-found').show();
                    $('#tr069-status-badge').removeClass().addClass('badge badge-warning mr-2').text('Not Found');
                    return;
                }

                // Show device info
                var dev = res.device || {};
                var rows = '';
                if (dev.manufacturer) rows += '<tr><td width="40%"><strong>Manufacturer</strong></td><td>' + dev.manufacturer + '</td></tr>';
                if (dev.model) rows += '<tr><td><strong>Model</strong></td><td>' + dev.model + '</td></tr>';
                if (dev.serial_number) rows += '<tr><td><strong>Serial</strong></td><td><code>' + dev.serial_number + '</code></td></tr>';
                if (dev.software_version) rows += '<tr><td><strong>Software</strong></td><td>' + dev.software_version + '</td></tr>';
                if (dev.hardware_version) rows += '<tr><td><strong>Hardware</strong></td><td>' + dev.hardware_version + '</td></tr>';
                if (dev.uptime) rows += '<tr><td><strong>Uptime</strong></td><td>' + formatUptime(dev.uptime) + '</td></tr>';

                // WAN connections
                if (res.wan_connections && res.wan_connections.length > 0) {
                    res.wan_connections.forEach(function(wan, i) {
                        var label = wan.connection_type || 'WAN ' + (i + 1);
                        var status = wan.connection_status || '-';
                        var statusBadge = status === 'Connected' ? 'success' : 'secondary';
                        rows += '<tr><td><strong>' + label + '</strong></td><td><span class="badge badge-' + statusBadge + '">' + status + '</span>';
                        if (wan.external_ip) rows += ' <code>' + wan.external_ip + '</code>';
                        if (wan.username) rows += ' <small class="text-muted">(' + wan.username + ')</small>';
                        rows += '</td></tr>';
                    });
                }

                // LAN hosts
                if (res.lan_hosts && res.lan_hosts.length > 0) {
                    rows += '<tr><td><strong>LAN Hosts</strong></td><td>';
                    res.lan_hosts.forEach(function(host) {
                        rows += '<span class="badge badge-light mr-1 mb-1">' + (host.host_name || host.ip || host.mac || '-') + '</span>';
                    });
                    rows += '</td></tr>';
                }

                if (res.pending_tasks > 0) {
                    rows += '<tr><td><strong>Pending Tasks</strong></td><td><span class="badge badge-warning">' + res.pending_tasks + '</span></td></tr>';
                }

                $('#tr069-table').html(rows);
                $('#tr069-info').show();
                $('#tr069-actions').show();
                $('#tr069-status-badge').removeClass().addClass('badge badge-success mr-2').text('Connected');

                if (res.genieacs_ui_url) {
                    $('#tr069-ui-link').attr('href', res.genieacs_ui_url);
                }
            })
            .fail(function() {
                $('#tr069-loading').hide();
                $('#tr069-unavailable').show();
                $('#tr069-status-badge').removeClass().addClass('badge badge-danger mr-2').text('Error');
            });
    }

    function formatUptime(seconds) {
        if (!seconds) return '-';
        var d = Math.floor(seconds / 86400);
        var h = Math.floor((seconds % 86400) / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        var parts = [];
        if (d > 0) parts.push(d + 'd');
        if (h > 0) parts.push(h + 'h');
        if (m > 0) parts.push(m + 'm');
        return parts.join(' ') || '< 1m';
    }

    // Load TR069 on page load
    loadTr069Info();

    // Refresh TR069 buttons
    $('.btn-refresh-tr069, .btn-refresh-tr069-data').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');

        $.post('/admin/onus/{{ $onu->id }}/tr069-refresh', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    setTimeout(loadTr069Info, 2000);
                } else {
                    Swal.fire('Info', res.message || 'Tidak dapat refresh', 'info');
                }
            })
            .fail(function() {
                loadTr069Info();
            })
            .always(function() {
                setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 1000);
            });
    });
});
</script>
@endpush
