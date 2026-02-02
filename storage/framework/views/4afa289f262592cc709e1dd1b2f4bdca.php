

<?php $__env->startSection('title', 'Manajemen ONU'); ?>

<?php $__env->startSection('page-title', 'Manajemen ONU'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item active">ONU</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Stats -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo e($stats['total'] ?? 0); ?></h3>
                <p>Total ONU</p>
            </div>
            <div class="icon"><i class="fas fa-hdd"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo e($stats['online'] ?? 0); ?></h3>
                <p>Online</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo e($stats['offline'] ?? 0); ?></h3>
                <p>Offline</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo e($stats['los'] ?? 0); ?></h3>
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
        <form method="GET" action="<?php echo e(route('admin.onus.index')); ?>">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>OLT</label>
                        <select name="olt_id" class="form-control select2">
                            <option value="">-- Semua OLT --</option>
                            <?php $__currentLoopData = $olts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $olt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($olt->id); ?>" <?php echo e(request('olt_id') == $olt->id ? 'selected' : ''); ?>>
                                <?php echo e($olt->name); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">-- Semua --</option>
                            <option value="online" <?php echo e(request('status') == 'online' ? 'selected' : ''); ?>>Online</option>
                            <option value="offline" <?php echo e(request('status') == 'offline' ? 'selected' : ''); ?>>Offline</option>
                            <option value="los" <?php echo e(request('status') == 'los' ? 'selected' : ''); ?>>LOS</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Sinyal</label>
                        <select name="signal" class="form-control">
                            <option value="">-- Semua --</option>
                            <option value="good" <?php echo e(request('signal') == 'good' ? 'selected' : ''); ?>>Bagus (> -25dBm)</option>
                            <option value="warning" <?php echo e(request('signal') == 'warning' ? 'selected' : ''); ?>>Peringatan (-25 ~ -27dBm)</option>
                            <option value="bad" <?php echo e(request('signal') == 'bad' ? 'selected' : ''); ?>>Buruk (< -27dBm)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Pencarian</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="SN, Nama, Pelanggan..." value="<?php echo e(request('search')); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="<?php echo e(route('admin.onus.index')); ?>" class="btn btn-secondary">
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
                    <?php $__empty_1 = true; $__currentLoopData = $onus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $onu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($loop->iteration); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.olts.show', $onu->olt)); ?>">
                                <?php echo e($onu->olt->name); ?>

                            </a>
                        </td>
                        <td>
                            <strong><?php echo e($onu->pon_port); ?>/<?php echo e($onu->onu_number); ?></strong>
                        </td>
                        <td><code><?php echo e($onu->serial_number); ?></code></td>
                        <td><?php echo e($onu->name ?? '-'); ?></td>
                        <td>
                            <?php if($onu->customer): ?>
                                <a href="<?php echo e(route('admin.customers.show', $onu->customer)); ?>">
                                    <?php echo e($onu->customer->name); ?>

                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($onu->status == 'online'): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Online</span>
                            <?php elseif($onu->status == 'offline'): ?>
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Offline</span>
                            <?php elseif($onu->status == 'los'): ?>
                                <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>LOS</span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo e(ucfirst($onu->status)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $rx = $onu->rx_power;
                                $rxClass = 'success';
                                if ($rx === null) $rxClass = 'secondary';
                                elseif ($rx < -27) $rxClass = 'danger';
                                elseif ($rx < -25) $rxClass = 'warning';
                            ?>
                            <span class="badge badge-<?php echo e($rxClass); ?>">
                                <?php echo e($rx !== null ? number_format($rx, 2) . ' dBm' : '-'); ?>

                            </span>
                        </td>
                        <td>
                            <?php
                                $tx = $onu->tx_power;
                            ?>
                            <span class="badge badge-<?php echo e($tx !== null ? 'info' : 'secondary'); ?>">
                                <?php echo e($tx !== null ? number_format($tx, 2) . ' dBm' : '-'); ?>

                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
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
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Tidak ada data ONU</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($onus->hasPages()): ?>
    <div class="card-footer">
        <?php echo e($onus->withQueryString()->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
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
                $.post('/admin/onus/bulk-sync', { _token: '<?php echo e(csrf_token()); ?>' })
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/onus/index.blade.php ENDPATH**/ ?>