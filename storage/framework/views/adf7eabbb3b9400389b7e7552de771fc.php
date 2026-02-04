

<?php $__env->startSection('title', 'Detail OLT - ' . $olt->name); ?>

<?php $__env->startSection('page-title', 'Detail OLT: ' . $olt->name); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.olts.index')); ?>">OLT</a></li>
    <li class="breadcrumb-item active"><?php echo e($olt->name); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Progress Modal -->
<div class="modal fade" id="modal-progress" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-cog fa-spin mr-2" id="progress-spinner"></i>
                    <span id="progress-title">Memproses...</span>
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" id="progress-bar"
                         style="width: 0%">0%</div>
                </div>
                <div id="progress-logs" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <!-- Progress logs will be appended here -->
                </div>
            </div>
            <div class="modal-footer" id="progress-footer" style="display: none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync mr-1"></i>Refresh Halaman
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Port Traffic Stats -->
<div class="row" id="traffic-stats-section">
    <!-- PON Ports Traffic with TX Power -->
    <div class="col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-project-diagram mr-2"></i>PON Ports Traffic
                </h3>
                <div class="card-tools">
                    <span class="text-muted text-sm mr-2" id="pon-timestamp"></span>
                    <span class="badge badge-secondary mr-2" id="pon-cached-badge" style="display:none;" title="Data dari cache">cached</span>
                    <button type="button" class="btn btn-tool" id="btn-force-refresh" title="Force Refresh (bypass cache)">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                    <button type="button" class="btn btn-tool" id="btn-refresh-traffic" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="pon-loading" class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Loading traffic data...</p>
                </div>
                <table class="table table-sm table-striped mb-0" id="table-pon-traffic" style="display: none;">
                    <thead class="thead-light">
                        <tr>
                            <th>Port</th>
                            <th>Status</th>
                            <th class="text-right">Download</th>
                            <th class="text-right">Upload</th>
                            <th class="text-right" title="TX Power (dBm)">TX Power</th>
                            <th class="text-right" title="Temperature">Temp</th>
                        </tr>
                    </thead>
                    <tbody id="pon-traffic-body">
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-right"><strong id="pon-total-in">-</strong></td>
                            <td class="text-right"><strong id="pon-total-out">-</strong></td>
                            <td colspan="2" class="text-right text-muted" id="pon-optical-avg">-</td>
                        </tr>
                    </tfoot>
                </table>
                <div id="pon-error" class="text-center p-4 text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p class="mt-2">Failed to load traffic data</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Uplink Ports -->
    <div class="col-lg-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-network-wired mr-2"></i>Uplink Ports Traffic
                </h3>
                <div class="card-tools">
                    <span class="text-muted text-sm" id="traffic-timestamp"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="uplink-loading" class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Loading traffic data...</p>
                </div>
                <table class="table table-sm table-striped mb-0" id="table-uplink-traffic" style="display: none;">
                    <thead class="thead-light">
                        <tr>
                            <th>Port</th>
                            <th>Status</th>
                            <th class="text-right">Download</th>
                            <th class="text-right">Upload</th>
                        </tr>
                    </thead>
                    <tbody id="uplink-traffic-body">
                    </tbody>
                    <tfoot class="table-success">
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-right"><strong id="uplink-total-in">-</strong></td>
                            <td class="text-right"><strong id="uplink-total-out">-</strong></td>
                        </tr>
                    </tfoot>
                </table>
                <div id="uplink-error" class="text-center p-4 text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p class="mt-2">Failed to load traffic data</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- OLT Info -->
    <div class="col-lg-4">
        <!-- Status Card -->
        <div class="card card-widget widget-user shadow">
            <div class="widget-user-header bg-<?php echo e($olt->status == 'active' ? 'success' : ($olt->status == 'maintenance' ? 'warning' : 'danger')); ?>">
                <h3 class="widget-user-username"><?php echo e($olt->name); ?></h3>
                <h5 class="widget-user-desc"><?php echo e($olt->brandLabel); ?> - <?php echo e($olt->model ?? 'Unknown Model'); ?></h5>
            </div>
            <div class="widget-user-image">
                <div class="img-circle elevation-2 bg-primary d-flex align-items-center justify-content-center" 
                     style="width: 90px; height: 90px; font-size: 2rem; color: white;">
                    <i class="fas fa-server"></i>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-sm-4 border-right">
                        <div class="description-block">
                            <h5 class="description-header"><?php echo e($olt->total_pon_ports); ?></h5>
                            <span class="description-text">PON PORTS</span>
                        </div>
                    </div>
                    <div class="col-sm-4 border-right">
                        <div class="description-block">
                            <h5 class="description-header text-success"><?php echo e($olt->onus->where('status', 'online')->count()); ?></h5>
                            <span class="description-text">ONLINE</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="description-block">
                            <h5 class="description-header text-danger"><?php echo e($olt->onus->where('status', 'offline')->count()); ?></h5>
                            <span class="description-text">OFFLINE</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi OLT</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tr>
                        <td width="40%"><strong>IP Address</strong></td>
                        <td><code><?php echo e($olt->ip_address); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Brand</strong></td>
                        <td><?php echo e($olt->brandLabel); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Model</strong></td>
                        <td><?php echo e($olt->model ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>
                            <?php if($olt->status == 'active'): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php elseif($olt->status == 'maintenance'): ?>
                                <span class="badge badge-warning">Maintenance</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Tidak Aktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>SNMP</strong></td>
                        <td>Port <?php echo e($olt->snmp_port); ?> | <?php echo e($olt->snmp_community); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Telnet</strong></td>
                        <td>
                            <?php if($olt->telnet_enabled): ?>
                                <span class="badge badge-success">Port <?php echo e($olt->telnet_port); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Disabled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>SSH</strong></td>
                        <td>
                            <?php if($olt->ssh_enabled): ?>
                                <span class="badge badge-success">Port <?php echo e($olt->ssh_port); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Disabled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>POP</strong></td>
                        <td><?php echo e($olt->pop->name ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Router</strong></td>
                        <td><?php echo e($olt->router->name ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td><?php echo e($olt->address ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Sync</strong></td>
                        <td><?php echo e($olt->last_sync_at ? $olt->last_sync_at->diffForHumans() : 'Belum pernah'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('olts.edit')): ?>
                <a href="<?php echo e(route('admin.olts.edit', $olt)); ?>" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-info btn-sm btn-sync-olt" data-id="<?php echo e($olt->id); ?>">
                    <i class="fas fa-sync"></i> Sync ONU
                </button>
                <button type="button" class="btn btn-primary btn-sm btn-test-connection" data-id="<?php echo e($olt->id); ?>">
                    <i class="fas fa-plug"></i> Test Koneksi
                </button>
            </div>
        </div>

        <!-- Map Card -->
        <?php if($olt->latitude && $olt->longitude): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
            </div>
            <div class="card-body p-0">
                <div id="map" style="height: 200px;"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Photos -->
        <?php if($olt->photos && count($olt->photos) > 0): ?>
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images mr-2"></i>Foto (<?php echo e(count($olt->photos)); ?>)</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php $__currentLoopData = $olt->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-6 mb-2">
                        <a href="<?php echo e($olt->getPhotoUrl($photo)); ?>" data-lightbox="olt-photos" data-title="<?php echo e($olt->name); ?>">
                            <img src="<?php echo e($olt->getThumbnailUrl($photo)); ?>" class="img-thumbnail w-100" 
                                 style="height:80px;object-fit:cover;" alt="Foto OLT">
                        </a>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ONU List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hdd mr-2"></i>Daftar ONU (<?php echo e($olt->onus->count()); ?>)</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('onu.register')): ?>
                    <button type="button" class="btn btn-success btn-sm btn-scan-unregistered" data-id="<?php echo e($olt->id); ?>">
                        <i class="fas fa-search-plus"></i> Scan ONU Baru
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if($olt->onus->count() > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="table-onus">
                        <thead>
                            <tr>
                                <th>PON/ONU</th>
                                <th>Pelanggan</th>
                                <th>SN</th>
                                <th>Status</th>
                                <th>Signal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $olt->onus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $onu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($onu->slot); ?>/<?php echo e($onu->port); ?>/<?php echo e($onu->onu_id); ?></strong>
                                    <?php if($onu->name || $onu->description): ?>
                                    <br><small class="text-muted"><?php echo e($onu->name ?: $onu->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($onu->customer): ?>
                                        <a href="<?php echo e(route('admin.customers.show', $onu->customer)); ?>">
                                            <?php echo e($onu->customer->name); ?>

                                        </a>
                                    <?php elseif($onu->description): ?>
                                        <?php echo e($onu->description); ?>

                                    <?php elseif($onu->name): ?>
                                        <?php echo e($onu->name); ?>

                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo e($onu->serial_number); ?></code></td>
                                <td>
                                    <?php if($onu->status == 'online'): ?>
                                        <span class="badge badge-success">Online</span>
                                    <?php elseif($onu->status == 'offline'): ?>
                                        <span class="badge badge-danger">Offline</span>
                                    <?php elseif($onu->status == 'los'): ?>
                                        <span class="badge badge-warning">LOS</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo e(ucfirst($onu->status)); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $signal = $onu->rx_power;
                                        $signalClass = 'success';
                                        if ($signal === null) {
                                            $signalClass = 'secondary';
                                        } elseif ($signal < -27) {
                                            $signalClass = 'danger';
                                        } elseif ($signal < -25) {
                                            $signalClass = 'warning';
                                        }
                                    ?>
                                    <span class="badge badge-<?php echo e($signalClass); ?>">
                                        <?php echo e($signal !== null ? number_format($signal, 2) . ' dBm' : '-'); ?>

                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('admin.onus.show', $onu)); ?>" class="btn btn-xs btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('onu.reboot')): ?>
                                    <button type="button" class="btn btn-xs btn-warning btn-reboot-onu" 
                                            data-id="<?php echo e($onu->id); ?>" title="Reboot">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('onu.unregister')): ?>
                                    <button type="button" class="btn btn-xs btn-danger btn-unregister-onu" 
                                            data-id="<?php echo e($onu->id); ?>" data-sn="<?php echo e($onu->serial_number); ?>" title="Unregister">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Belum ada ONU terdaftar</p>
                    <button type="button" class="btn btn-success btn-scan-unregistered" data-id="<?php echo e($olt->id); ?>">
                        <i class="fas fa-search-plus"></i> Scan ONU Baru
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PON Port Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Statistik PON Port</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php for($i = 1; $i <= ($olt->total_pon_ports ?? 8); $i++): ?>
                    <?php
                        $onuCount = $olt->onus->where('port', $i)->count();
                        $onlineCount = $olt->onus->where('port', $i)->where('status', 'online')->count();
                        $percentage = $onuCount > 0 ? round(($onlineCount / $onuCount) * 100) : 0;
                    ?>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="info-box bg-<?php echo e($percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger')); ?> mb-0">
                            <span class="info-box-icon"><i class="fas fa-ethernet"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">PON <?php echo e($i); ?></span>
                                <span class="info-box-number"><?php echo e($onlineCount); ?>/<?php echo e($onuCount); ?></span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo e($percentage); ?>%"></div>
                                </div>
                                <span class="progress-description"><?php echo e($percentage); ?>% Online</span>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Unregistered ONU -->
<div class="modal fade" id="modal-unregistered" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-search-plus mr-2"></i>ONU Belum Terdaftar</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="unregistered-loading" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                    <p>Sedang scanning ONU...</p>
                </div>
                <div id="unregistered-result" style="display:none;">
                    <table class="table table-bordered table-sm" id="table-unregistered">
                        <thead>
                            <tr>
                                <th>PON</th>
                                <th>Serial Number</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="unregistered-empty" class="text-center py-5" style="display:none;">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p>Tidak ada ONU baru yang ditemukan</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Register ONU -->
<div class="modal fade" id="modal-register" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-register">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Register ONU</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="olt_id" value="<?php echo e($olt->id); ?>">
                    <input type="hidden" name="pon_port" id="reg_pon_port">
                    <input type="hidden" name="serial_number" id="reg_serial_number">
                    
                    <div class="alert alert-info">
                        <strong>PON Port:</strong> <span id="reg_pon_display"></span><br>
                        <strong>Serial Number:</strong> <span id="reg_sn_display"></span>
                    </div>

                    <div class="form-group">
                        <label>Nama ONU <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: ONU-AHMAD">
                    </div>

                    <div class="form-group">
                        <label>Pelanggan (Opsional)</label>
                        <select name="customer_id" class="form-control select2-customer" style="width:100%">
                            <option value="">-- Pilih Pelanggan --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Profile OLT <span class="text-danger">*</span></label>
                        <select name="profile_id" class="form-control" required>
                            <option value="">-- Pilih Profile --</option>
                            <?php $__currentLoopData = $profiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($profile->id); ?>"><?php echo e($profile->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <?php if($olt->brand == 'zte'): ?>
                    <div class="card card-outline card-info mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">ZTE C320 - Advanced Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>VLAN ID</label>
                                        <input type="number" name="vlan_id" class="form-control" value="100" min="1" max="4094">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>GEM Port</label>
                                        <input type="number" name="gem_port" class="form-control" value="1" min="1">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>T-CONT ID</label>
                                        <input type="number" name="tcont_id" class="form-control" value="1" min="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Service Port Mode</label>
                                        <select name="service_port_mode" class="form-control">
                                            <option value="transparent">Transparent</option>
                                            <option value="tag">Tag</option>
                                            <option value="translate">Translate</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Register ONU
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('js'); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    // DataTable - only init if table exists
    if ($('#table-onus').length) {
        $('#table-onus').DataTable({
            pageLength: 25,
            order: [[0, 'asc']],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
        });
    }

    // Map
    <?php if($olt->latitude && $olt->longitude): ?>
    var map = L.map('map').setView([<?php echo e($olt->latitude); ?>, <?php echo e($olt->longitude); ?>], 15);
    
    // Define base layers - Google Satellite
    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    });
    
    var satelliteLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google'
    });
    
    var hybridLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google'
    });
    
    // Add default layer
    satelliteLayer.addTo(map);
    
    // Layer control
    L.control.layers({
        "Satelit": satelliteLayer,
        "Peta": osmLayer,
        "Hybrid": hybridLayer
    }, null, { position: 'topright' }).addTo(map);
    
    L.marker([<?php echo e($olt->latitude); ?>, <?php echo e($olt->longitude); ?>])
        .addTo(map)
        .bindPopup('<strong><?php echo e($olt->name); ?></strong>');
    <?php endif; ?>

    // Select2 for customer
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#modal-register'),
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

    // Sync OLT with Progress
    $('.btn-sync-olt').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        // Show progress modal
        $('#progress-title').text('Sinkronisasi ONU');
        $('#progress-bar').css('width', '0%').text('0%').removeClass('bg-success bg-danger bg-warning');
        $('#progress-logs').html('');
        $('#progress-footer').hide();
        $('#progress-spinner').show();
        $('#modal-progress').modal('show');
        
        btn.prop('disabled', true);
        
        // Use Server-Sent Events for streaming progress
        var eventSource = new EventSource('/admin/olts/' + id + '/sync-stream');
        
        eventSource.onmessage = function(event) {
            var data = JSON.parse(event.data);
            
            if (data.type === 'progress') {
                // Update progress bar
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');
                
                // Add log entry
                var logClass = 'text-muted';
                var icon = 'fa-info-circle';
                if (data.status === 'success') {
                    logClass = 'text-success';
                    icon = 'fa-check-circle';
                } else if (data.status === 'error') {
                    logClass = 'text-danger';
                    icon = 'fa-times-circle';
                } else if (data.status === 'warning') {
                    logClass = 'text-warning';
                    icon = 'fa-exclamation-circle';
                }
                
                $('#progress-logs').append(
                    '<div class="' + logClass + '">' +
                    '<i class="fas ' + icon + ' mr-1"></i>' +
                    '<small class="text-muted">[' + data.time + ']</small> ' + 
                    data.message + '</div>'
                );
                
                // Auto scroll to bottom
                $('#progress-logs').scrollTop($('#progress-logs')[0].scrollHeight);
            }
            
            if (data.type === 'complete') {
                eventSource.close();
                $('#progress-spinner').hide();
                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync ONU');
                
                if (data.success) {
                    $('#progress-bar').addClass('bg-success').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-check-circle mr-2"></i>Sinkronisasi Selesai');
                } else {
                    $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Sinkronisasi Gagal');
                }
            }
        };
        
        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').hide();
            $('#progress-footer').show();
            $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
            $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Koneksi Terputus');
            $('#progress-logs').append('<div class="text-danger"><i class="fas fa-times-circle mr-1"></i>Koneksi ke server terputus</div>');
            btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync ONU');
        };
    });

    // Test Connection with Progress
    $('.btn-test-connection').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        // Show progress modal
        $('#progress-title').text('Test Koneksi OLT');
        $('#progress-bar').css('width', '0%').text('0%').removeClass('bg-success bg-danger bg-warning');
        $('#progress-logs').html('');
        $('#progress-footer').hide();
        $('#progress-spinner').show();
        $('#modal-progress').modal('show');
        
        btn.prop('disabled', true);
        
        // Use Server-Sent Events for streaming progress
        var eventSource = new EventSource('/admin/olts/' + id + '/test-connection-stream');
        
        eventSource.onmessage = function(event) {
            var data = JSON.parse(event.data);
            
            if (data.type === 'progress') {
                // Update progress bar
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');
                
                // Add log entry
                var logClass = 'text-muted';
                var icon = 'fa-info-circle';
                if (data.status === 'success') {
                    logClass = 'text-success';
                    icon = 'fa-check-circle';
                } else if (data.status === 'error') {
                    logClass = 'text-danger';
                    icon = 'fa-times-circle';
                } else if (data.status === 'warning') {
                    logClass = 'text-warning';
                    icon = 'fa-exclamation-circle';
                }
                
                $('#progress-logs').append(
                    '<div class="' + logClass + '">' +
                    '<i class="fas ' + icon + ' mr-1"></i>' +
                    '<small class="text-muted">[' + data.time + ']</small> ' + 
                    data.message + '</div>'
                );
                
                // Auto scroll to bottom
                $('#progress-logs').scrollTop($('#progress-logs')[0].scrollHeight);
            }
            
            if (data.type === 'complete') {
                eventSource.close();
                $('#progress-spinner').hide();
                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Koneksi');
                
                if (data.success) {
                    $('#progress-bar').addClass('bg-success').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-check-circle mr-2"></i>Test Koneksi Selesai');
                } else {
                    $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Test Koneksi Gagal');
                }
            }
        };
        
        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').hide();
            $('#progress-footer').show();
            $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
            $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Koneksi Terputus');
            $('#progress-logs').append('<div class="text-danger"><i class="fas fa-times-circle mr-1"></i>Koneksi ke server terputus</div>');
            btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Koneksi');
        };
    });

    // Scan Unregistered
    $('.btn-scan-unregistered').click(function() {
        var id = $(this).data('id');
        $('#modal-unregistered').modal('show');
        $('#unregistered-loading').show();
        $('#unregistered-result').hide();
        $('#unregistered-empty').hide();
        
        $.get('/admin/olts/' + id + '/unregistered-onus')
            .done(function(res) {
                $('#unregistered-loading').hide();
                if (res.data && res.data.length > 0) {
                    var tbody = $('#table-unregistered tbody');
                    tbody.empty();
                    res.data.forEach(function(onu) {
                        tbody.append(`
                            <tr>
                                <td>${onu.pon_port}</td>
                                <td><code>${onu.serial_number}</code></td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm btn-register-onu"
                                            data-pon="${onu.pon_port}" data-sn="${onu.serial_number}">
                                        <i class="fas fa-plus"></i> Register
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                    $('#unregistered-result').show();
                } else {
                    $('#unregistered-empty').show();
                }
            })
            .fail(function(xhr) {
                $('#unregistered-loading').hide();
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal scanning ONU', 'error');
                $('#modal-unregistered').modal('hide');
            });
    });

    // Register ONU - Open Modal
    $(document).on('click', '.btn-register-onu', function() {
        var pon = $(this).data('pon');
        var sn = $(this).data('sn');
        
        $('#reg_pon_port').val(pon);
        $('#reg_serial_number').val(sn);
        $('#reg_pon_display').text(pon);
        $('#reg_sn_display').text(sn);
        
        $('#modal-unregistered').modal('hide');
        $('#modal-register').modal('show');
    });

    // Register ONU - Submit
    $('#form-register').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registering...');
        
        $.ajax({
            url: '<?php echo e(route("admin.onus.register")); ?>',
            method: 'POST',
            data: $(this).serialize() + '&_token=<?php echo e(csrf_token()); ?>',
            success: function(res) {
                Swal.fire('Berhasil', res.message || 'ONU berhasil didaftarkan', 'success')
                    .then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal mendaftarkan ONU', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Register ONU');
            }
        });
    });

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
                    data: { _token: '<?php echo e(csrf_token()); ?>' },
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

    // Load traffic stats - optimized for partial update
    var trafficInitialized = false;
    var oltBrand = '<?php echo e($olt->brand); ?>';
    
    function loadTrafficStats(isRefresh = false, forceRefresh = false) {
        var oltId = '<?php echo e($olt->id); ?>';
        var url = '/admin/olts/' + oltId + '/traffic-stats';
        if (forceRefresh) {
            url += '?refresh=1';
        }
        
        // Only show loading on first load, not on refresh
        if (!trafficInitialized) {
            $('#pon-loading, #uplink-loading').show();
            $('#table-pon-traffic, #table-uplink-traffic').hide();
        }
        $('#pon-error, #uplink-error').hide();
        
        $.get(url)
            .done(function(res) {
                if (res.success && res.data) {
                    var data = res.data;
                    
                    // Build optical data map by port index for easy lookup
                    var opticalMap = {};
                    if (data.optical_power && data.optical_power.pon_ports) {
                        data.optical_power.pon_ports.forEach(function(opt) {
                            opticalMap[opt.port] = opt;
                        });
                    }
                    
                    // Render PON ports with optical data
                    if (data.pon_ports && data.pon_ports.ports && data.pon_ports.ports.length > 0) {
                        data.pon_ports.ports.forEach(function(port, idx) {
                            var rowId = 'pon-row-' + port.index;
                            var $row = $('#' + rowId);
                            
                            var statusBadge = port.status === 'up' 
                                ? '<span class="badge badge-success">UP</span>'
                                : '<span class="badge badge-danger">DOWN</span>';
                            
                            // Get optical data for this port
                            var optical = opticalMap[port.index] || {};
                            var txPower = optical.tx_power_formatted || '-';
                            var temp = optical.temperature_formatted || '-';
                            var txClass = '';
                            if (optical.signal_quality === 'excellent') txClass = 'text-success';
                            else if (optical.signal_quality === 'good') txClass = 'text-info';
                            else if (optical.signal_quality === 'acceptable') txClass = 'text-warning';
                            else if (optical.signal_quality === 'warning') txClass = 'text-danger';
                            
                            if ($row.length) {
                                // Update existing row (partial update - no flicker)
                                $row.find('.col-status').html(statusBadge);
                                $row.find('.col-download').text(port.in_bytes_formatted);
                                $row.find('.col-upload').text(port.out_bytes_formatted);
                                $row.find('.col-txpower').html('<span class="' + txClass + '">' + txPower + '</span>');
                                $row.find('.col-temp').text(temp);
                            } else {
                                // Create new row
                                var rowHtml = '<tr id="' + rowId + '">';
                                rowHtml += '<td><strong>' + port.name + '</strong></td>';
                                rowHtml += '<td class="col-status">' + statusBadge + '</td>';
                                rowHtml += '<td class="text-right text-info col-download">' + port.in_bytes_formatted + '</td>';
                                rowHtml += '<td class="text-right text-success col-upload">' + port.out_bytes_formatted + '</td>';
                                rowHtml += '<td class="text-right col-txpower"><span class="' + txClass + '">' + txPower + '</span></td>';
                                rowHtml += '<td class="text-right col-temp">' + temp + '</td>';
                                rowHtml += '</tr>';
                                $('#pon-traffic-body').append(rowHtml);
                            }
                        });
                        
                        $('#pon-total-in').text(data.pon_ports.in_formatted || '0 B');
                        $('#pon-total-out').text(data.pon_ports.out_formatted || '0 B');
                        
                        // Optical summary
                        if (data.optical_power && data.optical_power.summary) {
                            $('#pon-optical-avg').text('Avg TX: ' + data.optical_power.summary.overall_tx_power_formatted);
                        }
                        
                        $('#pon-loading').hide();
                        $('#table-pon-traffic').show();
                    } else if (!trafficInitialized) {
                        $('#pon-loading').hide();
                        $('#pon-error').html('<div class="text-center p-3 text-muted"><i class="fas fa-info-circle mr-2"></i>Traffic data tidak tersedia</div>').show();
                    }
                    
                    // Render Uplink ports
                    if (data.uplink_ports && data.uplink_ports.ports && data.uplink_ports.ports.length > 0) {
                        data.uplink_ports.ports.forEach(function(port) {
                            var rowId = 'uplink-row-' + port.index;
                            var $row = $('#' + rowId);
                            
                            var statusBadge = port.status === 'up' 
                                ? '<span class="badge badge-success">UP</span>'
                                : '<span class="badge badge-danger">DOWN</span>';
                            
                            if ($row.length) {
                                // Update existing row
                                $row.find('.col-status').html(statusBadge);
                                $row.find('.col-download').text(port.in_bytes_formatted);
                                $row.find('.col-upload').text(port.out_bytes_formatted);
                            } else {
                                // Create new row
                                var rowHtml = '<tr id="' + rowId + '">';
                                rowHtml += '<td><strong>' + port.name + '</strong></td>';
                                rowHtml += '<td class="col-status">' + statusBadge + '</td>';
                                rowHtml += '<td class="text-right text-info col-download">' + port.in_bytes_formatted + '</td>';
                                rowHtml += '<td class="text-right text-success col-upload">' + port.out_bytes_formatted + '</td>';
                                rowHtml += '</tr>';
                                $('#uplink-traffic-body').append(rowHtml);
                            }
                        });
                        
                        $('#uplink-total-in').text(data.uplink_ports.in_formatted || '0 B');
                        $('#uplink-total-out').text(data.uplink_ports.out_formatted || '0 B');
                        $('#uplink-loading').hide();
                        $('#table-uplink-traffic').show();
                    } else if (!trafficInitialized) {
                        $('#uplink-loading').hide();
                    }
                    
                    // Update timestamp
                    if (data.collected_at) {
                        var dt = new Date(data.collected_at);
                        var timeStr = dt.toLocaleTimeString();
                        $('#pon-timestamp').text(timeStr);
                        $('#traffic-timestamp').text(timeStr);
                    }
                    
                    trafficInitialized = true;
                } else {
                    throw new Error('Invalid response');
                }
            })
            .fail(function(xhr) {
                console.error('Traffic stats error:', xhr);
                if (!trafficInitialized) {
                    $('#pon-loading, #uplink-loading').hide();
                    $('#pon-error, #uplink-error').show();
                }
            });
    }
    
    // Load traffic stats on page load
    loadTrafficStats();
    
    // Refresh traffic button
    $('#btn-refresh-traffic').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        loadTrafficStats(true, false);
        setTimeout(function() {
            btn.find('i').removeClass('fa-spin');
        }, 1000);
    });
    
    // Force refresh button (bypass cache)
    $('#btn-force-refresh').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        loadTrafficStats(true, true);
        setTimeout(function() {
            btn.find('i').removeClass('fa-spin');
        }, 2000);
    });
    
    // Auto refresh - shorter interval for SNMP (real-time), longer for telnet
    var refreshInterval = (oltBrand === 'hioso') ? 15000 : 10000; // 15s for telnet, 10s for SNMP
    setInterval(function() {
        loadTrafficStats(true);
    }, refreshInterval);
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/olts/show.blade.php ENDPATH**/ ?>