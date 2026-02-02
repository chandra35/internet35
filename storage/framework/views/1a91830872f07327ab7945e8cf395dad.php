

<?php $__env->startSection('title', 'Detail ONU - ' . ($onu->description ?? $onu->name ?? $onu->serial_number)); ?>

<?php $__env->startSection('page-title', 'Detail ONU: ' . ($onu->description ?? $onu->name ?? $onu->serial_number)); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.onus.index')); ?>">ONU</a></li>
    <li class="breadcrumb-item active"><?php echo e($onu->description ?? $onu->name ?? $onu->serial_number); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <!-- ONU Info -->
    <div class="col-lg-4">
        <!-- Status Card -->
        <div class="card card-widget widget-user-2 shadow">
            <div class="widget-user-header bg-<?php echo e($onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger')); ?>">
                <div class="widget-user-image">
                    <i class="fas fa-hdd fa-3x"></i>
                </div>
                <h3 class="widget-user-username"><?php echo e($onu->description ?? $onu->name ?? 'ONU'); ?></h3>
                <h5 class="widget-user-desc"><?php echo e($onu->serial_number); ?></h5>
            </div>
            <div class="card-footer p-0">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <span class="nav-link">
                            Status
                            <span class="float-right">
                                <?php if($onu->status == 'online'): ?>
                                    <span class="badge badge-success">Online</span>
                                <?php elseif($onu->status == 'offline'): ?>
                                    <span class="badge badge-danger">Offline</span>
                                <?php elseif($onu->status == 'los'): ?>
                                    <span class="badge badge-warning">LOS</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?php echo e(ucfirst($onu->status)); ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            RX Power
                            <?php
                                $rx = $onu->rx_power;
                                $rxClass = 'success';
                                if ($rx === null) $rxClass = 'secondary';
                                elseif ($rx < -27) $rxClass = 'danger';
                                elseif ($rx < -25) $rxClass = 'warning';
                            ?>
                            <span class="float-right badge badge-<?php echo e($rxClass); ?>">
                                <?php echo e($rx !== null ? number_format($rx, 2) . ' dBm' : '-'); ?>

                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            TX Power
                            <span class="float-right badge badge-<?php echo e($onu->tx_power !== null ? 'info' : 'secondary'); ?>">
                                <?php echo e($onu->tx_power !== null ? number_format($onu->tx_power, 2) . ' dBm' : '-'); ?>

                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            Distance
                            <span class="float-right">
                                <?php echo e($onu->distance ? number_format($onu->distance, 0) . 'm' : '-'); ?>

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
                            <a href="<?php echo e(route('admin.olts.show', $onu->olt)); ?>">
                                <?php echo e($onu->olt->name); ?>

                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>PON Port</strong></td>
                        <td><?php echo e($onu->port ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>ONU ID</strong></td>
                        <td><?php echo e($onu->onu_id ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Serial Number</strong></td>
                        <td><code><?php echo e($onu->serial_number); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>ONU Type</strong></td>
                        <td><?php echo e($onu->onu_type ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Deskripsi (OLT)</strong></td>
                        <td><?php echo e($onu->description ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Profile</strong></td>
                        <td><?php echo e($onu->profile->name ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pelanggan</strong></td>
                        <td>
                            <?php if($onu->customer): ?>
                                <a href="<?php echo e(route('admin.customers.show', $onu->customer)); ?>">
                                    <?php echo e($onu->customer->name); ?>

                                </a>
                            <?php else: ?>
                                <span class="text-muted">Belum dipasangkan</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if($onu->vlan_id): ?>
                    <tr>
                        <td><strong>VLAN ID</strong></td>
                        <td><?php echo e($onu->vlan_id); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Last Online</strong></td>
                        <td><?php echo e($onu->last_online_at ? $onu->last_online_at->diffForHumans() : '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Sync</strong></td>
                        <td><?php echo e($onu->last_sync_at ? $onu->last_sync_at->diffForHumans() : '-'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('onu.reboot')): ?>
                <button type="button" class="btn btn-warning btn-sm btn-reboot-onu" data-id="<?php echo e($onu->id); ?>">
                    <i class="fas fa-sync"></i> Reboot
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-info btn-sm btn-refresh-signal" data-id="<?php echo e($onu->id); ?>">
                    <i class="fas fa-signal"></i> Refresh Signal
                </button>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('onu.unregister')): ?>
                <button type="button" class="btn btn-danger btn-sm btn-unregister-onu" 
                        data-id="<?php echo e($onu->id); ?>" data-sn="<?php echo e($onu->serial_number); ?>">
                    <i class="fas fa-trash"></i> Unregister
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assign Customer -->
        <?php if(!$onu->customer): ?>
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Assign ke Pelanggan</h3>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('admin.onus.assign-customer', $onu)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
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
        <?php endif; ?>
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
                            <span class="info-box-icon bg-<?php echo e($rxClass); ?> elevation-1">
                                <i class="fas fa-arrow-down"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">RX Power (Downstream)</span>
                                <span class="info-box-number"><?php echo e($rx !== null ? number_format($rx, 2) . ' dBm' : '-'); ?></span>
                                <div class="progress">
                                    <?php
                                        $rxPercent = $rx !== null ? min(100, max(0, (($rx + 40) / 25) * 100)) : 0;
                                    ?>
                                    <div class="progress-bar bg-<?php echo e($rxClass); ?>" style="width: <?php echo e($rxPercent); ?>%"></div>
                                </div>
                                <span class="progress-description">
                                    <?php if($rx === null): ?>
                                        Tidak tersedia
                                    <?php elseif($rx >= -25): ?>
                                        <i class="fas fa-check-circle text-success"></i> Excellent
                                    <?php elseif($rx >= -27): ?>
                                        <i class="fas fa-exclamation-circle text-warning"></i> Good, perlu monitoring
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger"></i> Poor, perlu perbaikan
                                    <?php endif; ?>
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
                                <span class="info-box-number"><?php echo e($onu->tx_power !== null ? number_format($onu->tx_power, 2) . ' dBm' : '-'); ?></span>
                                <div class="progress">
                                    <?php
                                        $tx = $onu->tx_power;
                                        $txPercent = $tx !== null ? min(100, max(0, (($tx + 10) / 15) * 100)) : 0;
                                    ?>
                                    <div class="progress-bar bg-info" style="width: <?php echo e($txPercent); ?>%"></div>
                                </div>
                                <span class="progress-description">
                                    <?php if($tx === null): ?>
                                        Tidak tersedia
                                    <?php else: ?>
                                        Range normal: 0.5 ~ 5 dBm
                                    <?php endif; ?>
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

        <!-- Description & Notes -->
        <?php if($onu->description || $onu->notes): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
            </div>
            <div class="card-body">
                <?php if($onu->description): ?>
                <p><strong>Deskripsi:</strong> <?php echo e($onu->description); ?></p>
                <?php endif; ?>
                <?php if($onu->notes): ?>
                <p><strong>Catatan:</strong> <?php echo e($onu->notes); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

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
                            <?php $__empty_1 = true; $__currentLoopData = $signalHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($history->recorded_at->format('d/m/Y H:i')); ?></td>
                                <td>
                                    <?php
                                        $histRx = $history->rx_power;
                                        $histRxClass = $histRx >= -25 ? 'success' : ($histRx >= -27 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge badge-<?php echo e($histRxClass); ?>">
                                        <?php echo e(number_format($histRx, 2)); ?> dBm
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo e($history->tx_power ? number_format($history->tx_power, 2) . ' dBm' : '-'); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if($history->status == 'online'): ?>
                                        <span class="badge badge-success">Online</span>
                                    <?php elseif($history->status == 'offline'): ?>
                                        <span class="badge badge-danger">Offline</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo e(ucfirst($history->status)); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($history->distance ? number_format($history->distance, 2) . ' km' : '-'); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada data histori</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
    // Select2 for customer
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        ajax: {
            url: '<?php echo e(route("admin.customers.search")); ?>',
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
            labels: <?php echo json_encode($chartLabels ?? []); ?>,
            datasets: [{
                label: 'RX Power (dBm)',
                data: <?php echo json_encode($chartRxData ?? []); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'TX Power (dBm)',
                data: <?php echo json_encode($chartTxData ?? []); ?>,
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
        $.get('<?php echo e(route("admin.onus.signal-history", $onu)); ?>', { period: period }, function(res) {
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

    // Refresh Traffic function
    function refreshTraffic() {
        $.post('/admin/onus/<?php echo e($onu->id); ?>/refresh-signal', { _token: '<?php echo e(csrf_token()); ?>' })
            .done(function(res) {
                if (res.success && res.data) {
                    var now = new Date();
                    
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
        
        $.post('/admin/onus/' + id + '/refresh-signal', { _token: '<?php echo e(csrf_token()); ?>' })
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
                $.post('/admin/onus/' + id + '/reboot', { _token: '<?php echo e(csrf_token()); ?>' })
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
                    data: { _token: '<?php echo e(csrf_token()); ?>' },
                    success: function(res) {
                        Swal.fire('Berhasil', res.message || 'ONU berhasil dihapus', 'success')
                            .then(() => window.location.href = '<?php echo e(route("admin.onus.index")); ?>');
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/onus/show.blade.php ENDPATH**/ ?>