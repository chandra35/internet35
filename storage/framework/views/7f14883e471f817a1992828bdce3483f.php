

<?php $__env->startSection('title', 'ODP'); ?>

<?php $__env->startSection('page-title', 'Manajemen ODP (Optical Distribution Point)'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item active">ODP</li>
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
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo e($stats['total']); ?></h3>
                        <p>Total ODP</p>
                    </div>
                    <div class="icon"><i class="fas fa-box"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo e($stats['active']); ?></h3>
                        <p>Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo e($stats['maintenance']); ?></h3>
                        <p>Maintenance</p>
                    </div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?php echo e($stats['via_odc'] ?? 0); ?></h3>
                        <p>Via ODC</p>
                    </div>
                    <div class="icon"><i class="fas fa-box-open"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-olive">
                    <div class="inner">
                        <h3><?php echo e($stats['direct_olt'] ?? 0); ?></h3>
                        <p>Direct OLT</p>
                    </div>
                    <div class="icon"><i class="fas fa-server"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-orange">
                    <div class="inner">
                        <h3><?php echo e($stats['cascade'] ?? 0); ?></h3>
                        <p>Cascade</p>
                    </div>
                    <div class="icon"><i class="fas fa-sitemap"></i></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter & Action -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odps.create')): ?>
                    <?php if($popId): ?>
                    <a href="<?php echo e(route('admin.odps.create', ['pop_id' => $popId])); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Tambah ODP
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo e(route('admin.odps.index')); ?>">
                    <?php if($popId): ?>
                    <input type="hidden" name="pop_id" value="<?php echo e($popId); ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>ODC</label>
                                <select name="odc_id" class="form-control select2">
                                    <option value="">Semua ODC</option>
                                    <?php $__currentLoopData = $odcs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $odc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($odc->id); ?>" <?php echo e(request('odc_id') == $odc->id ? 'selected' : ''); ?>>
                                            <?php echo e($odc->code); ?> - <?php echo e($odc->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Tipe Koneksi</label>
                                <select name="connection_type" class="form-control">
                                    <option value="">Semua Tipe</option>
                                    <option value="odc" <?php echo e(request('connection_type') == 'odc' ? 'selected' : ''); ?>>Via ODC</option>
                                    <option value="olt" <?php echo e(request('connection_type') == 'olt' ? 'selected' : ''); ?>>Direct OLT</option>
                                    <option value="cascade" <?php echo e(request('connection_type') == 'cascade' ? 'selected' : ''); ?>>Cascade</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Cari</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, Kode..." value="<?php echo e(request('search')); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                    <a href="<?php echo e(route('admin.odps.index', ['pop_id' => $popId])); ?>" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ODP List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-box mr-2"></i>Daftar ODP</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Sumber Koneksi</th>
                            <th>Port</th>
                            <th>Lokasi</th>
                            <th>ODP Port</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $odps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $odp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><code><?php echo e($odp->code); ?></code></td>
                            <td>
                                <strong><?php echo e($odp->name); ?></strong>
                                <?php if($odp->splitter_level && $odp->splitter_level > 1): ?>
                                <br><small class="text-muted">Level <?php echo e($odp->splitter_level); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($odp->odc): ?>
                                    <span class="badge badge-primary"><i class="fas fa-box mr-1"></i>ODC</span>
                                    <a href="<?php echo e(route('admin.odcs.show', $odp->odc)); ?>"><?php echo e($odp->odc->code); ?></a>
                                <?php elseif($odp->parentOdp): ?>
                                    <span class="badge badge-warning"><i class="fas fa-sitemap mr-1"></i>Cascade</span>
                                    <a href="<?php echo e(route('admin.odps.show', $odp->parentOdp)); ?>"><?php echo e($odp->parentOdp->code); ?></a>
                                <?php elseif($odp->olt): ?>
                                    <span class="badge badge-success"><i class="fas fa-server mr-1"></i>Direct OLT</span>
                                    <a href="<?php echo e(route('admin.olts.show', $odp->olt)); ?>"><?php echo e($odp->olt->name); ?></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($odp->odc_port): ?>
                                    <span class="badge badge-secondary">ODC Port <?php echo e($odp->odc_port); ?></span>
                                <?php elseif($odp->olt_pon_port): ?>
                                    <span class="badge badge-info">PON <?php echo e($odp->olt_pon_port); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($odp->hasCoordinates()): ?>
                                    <a href="https://www.google.com/maps?q=<?php echo e($odp->latitude); ?>,<?php echo e($odp->longitude); ?>" target="_blank" class="text-primary">
                                        <i class="fas fa-map-marker-alt"></i> Map
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo e($odp->used_ports); ?>/<?php echo e($odp->total_ports); ?></span>
                                <div class="progress progress-xs mt-1" style="width: 60px;">
                                    <div class="progress-bar bg-<?php echo e($odp->port_usage_percent > 80 ? 'danger' : ($odp->port_usage_percent > 50 ? 'warning' : 'success')); ?>" 
                                         style="width: <?php echo e($odp->port_usage_percent); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo e(route('admin.customers.index', ['pop_id' => $odp->pop_id, 'odp_id' => $odp->id])); ?>" class="badge badge-primary">
                                    <?php echo e($odp->customers_count); ?> Pelanggan
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo e($odp->status_badge); ?>"><?php echo e($odp->status_label); ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo e(route('admin.odps.show', $odp)); ?>" class="btn btn-sm btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odps.edit')): ?>
                                    <a href="<?php echo e(route('admin.odps.edit', $odp)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odps.delete')): ?>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" 
                                            data-id="<?php echo e($odp->id); ?>" 
                                            data-name="<?php echo e($odp->code); ?>"
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <?php if(!$popId): ?>
                                    <i class="fas fa-info-circle fa-2x text-info mb-2"></i>
                                    <p class="mb-0">Silakan pilih POP terlebih dahulu</p>
                                <?php else: ?>
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Belum ada data ODP</p>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odps.create')): ?>
                                    <a href="<?php echo e(route('admin.odps.create', ['pop_id' => $popId])); ?>" class="btn btn-success btn-sm mt-2">
                                        <i class="fas fa-plus mr-1"></i> Tambah ODP Pertama
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($odps->hasPages()): ?>
            <div class="card-footer">
                <?php echo e($odps->links()); ?>

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
            window.location.href = '<?php echo e(route("admin.odps.index")); ?>?pop_id=' + popId;
        }
    });

    // Delete ODP
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Hapus ODP?',
            text: `Apakah Anda yakin ingin menghapus ODP "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('#deleteForm');
                form.attr('action', '<?php echo e(route("admin.odps.index")); ?>/' + id);
                form.submit();
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/odps/index.blade.php ENDPATH**/ ?>