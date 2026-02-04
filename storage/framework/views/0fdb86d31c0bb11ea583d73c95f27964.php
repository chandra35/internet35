

<?php $__env->startSection('title', 'ODC'); ?>

<?php $__env->startSection('page-title', 'Manajemen ODC (Optical Distribution Cabinet)'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item active">ODC</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <!-- Superadmin POP Selector -->
        <?php if($popUsers && auth()->user()->hasRole('superadmin')): ?>
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPop" style="width: 100%;">
                            <option value="">-- Pilih POP --</option>
                            <?php $__currentLoopData = $popUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($pop->id); ?>" <?php echo e($popId == $pop->id ? 'selected' : ''); ?>>
                                    <?php echo e($pop->name); ?> (<?php echo e($pop->email); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <?php if($popId): ?>
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo e($stats['total']); ?></h3>
                        <p>Total ODC</p>
                    </div>
                    <div class="icon"><i class="fas fa-server"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo e($stats['active']); ?></h3>
                        <p>Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo e($stats['maintenance']); ?></h3>
                        <p>Maintenance</p>
                    </div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo e($stats['inactive']); ?></h3>
                        <p>Tidak Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter & Action -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odcs.create')): ?>
                    <?php if($popId): ?>
                    <a href="<?php echo e(route('admin.odcs.create', ['pop_id' => $popId])); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Tambah ODC
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo e(route('admin.odcs.index')); ?>">
                    <?php if($popId): ?>
                    <input type="hidden" name="pop_id" value="<?php echo e($popId); ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>OLT</label>
                                <select name="olt_id" class="form-control select2">
                                    <option value="">Semua OLT</option>
                                    <?php $__currentLoopData = $olts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $olt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($olt->id); ?>" <?php echo e(request('olt_id') == $olt->id ? 'selected' : ''); ?>>
                                            <?php echo e($olt->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Aktif</option>
                                    <option value="maintenance" <?php echo e(request('status') == 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                                    <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cari</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, Kode, Alamat..." value="<?php echo e(request('search')); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                    <a href="<?php echo e(route('admin.odcs.index', ['pop_id' => $popId])); ?>" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ODC List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Daftar ODC</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>OLT / PON</th>
                            <th>Lokasi</th>
                            <th>Port</th>
                            <th>ODP</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $odcs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $odc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><code><?php echo e($odc->code); ?></code></td>
                            <td><strong><?php echo e($odc->name); ?></strong></td>
                            <td>
                                <?php if($odc->olt): ?>
                                    <a href="<?php echo e(route('admin.olts.show', $odc->olt)); ?>" class="text-primary">
                                        <?php echo e($odc->olt->name); ?>

                                    </a>
                                    <?php if($odc->olt_pon_port): ?>
                                        <br><small class="text-muted">PON <?php echo e($odc->olt_pon_port); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($odc->hasCoordinates()): ?>
                                    <a href="https://www.google.com/maps?q=<?php echo e($odc->latitude); ?>,<?php echo e($odc->longitude); ?>" target="_blank" class="text-primary">
                                        <i class="fas fa-map-marker-alt"></i> Lihat Map
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Belum diset</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo e($odc->used_ports); ?>/<?php echo e($odc->total_ports); ?></span>
                                <div class="progress progress-xs mt-1" style="width: 60px;">
                                    <div class="progress-bar bg-<?php echo e($odc->port_usage_percent > 80 ? 'danger' : ($odc->port_usage_percent > 50 ? 'warning' : 'success')); ?>" 
                                         style="width: <?php echo e($odc->port_usage_percent); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo e(route('admin.odps.index', ['pop_id' => $odc->pop_id, 'odc_id' => $odc->id])); ?>" class="badge badge-primary">
                                    <?php echo e($odc->odps_count); ?> ODP
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo e($odc->status_badge); ?>"><?php echo e($odc->status_label); ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo e(route('admin.odcs.show', $odc)); ?>" class="btn btn-sm btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odcs.edit')): ?>
                                    <a href="<?php echo e(route('admin.odcs.edit', $odc)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odcs.delete')): ?>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" 
                                            data-id="<?php echo e($odc->id); ?>" 
                                            data-name="<?php echo e($odc->code); ?>"
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <?php if(!$popId): ?>
                                    <i class="fas fa-info-circle fa-2x text-info mb-2"></i>
                                    <p class="mb-0">Silakan pilih POP terlebih dahulu</p>
                                <?php else: ?>
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Belum ada data ODC</p>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odcs.create')): ?>
                                    <a href="<?php echo e(route('admin.odcs.create', ['pop_id' => $popId])); ?>" class="btn btn-success btn-sm mt-2">
                                        <i class="fas fa-plus mr-1"></i> Tambah ODC Pertama
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($odcs->hasPages()): ?>
            <div class="card-footer">
                <?php echo e($odcs->links()); ?>

            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrf_field(); ?>
    <?php echo method_field('DELETE'); ?>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(function() {
    // POP Selector
    $('#selectPop').on('change', function() {
        const popId = $(this).val();
        if (popId) {
            window.location.href = '<?php echo e(route("admin.odcs.index")); ?>?pop_id=' + popId;
        }
    });

    // Delete ODC
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Hapus ODC?',
            text: `Apakah Anda yakin ingin menghapus ODC "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('#deleteForm');
                form.attr('action', '<?php echo e(route("admin.odcs.index")); ?>/' + id);
                form.submit();
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/odcs/index.blade.php ENDPATH**/ ?>