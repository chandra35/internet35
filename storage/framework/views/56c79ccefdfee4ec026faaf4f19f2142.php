

<?php $__env->startSection('title', 'OLT'); ?>

<?php $__env->startSection('page-title', 'Manajemen OLT (Optical Line Terminal)'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item active">OLT</li>
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
                        <h3><?php echo e($olts->total()); ?></h3>
                        <p>Total OLT</p>
                    </div>
                    <div class="icon"><i class="fas fa-server"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo e($olts->where('status', 'active')->count()); ?></h3>
                        <p>Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo e($olts->where('status', 'maintenance')->count()); ?></h3>
                        <p>Maintenance</p>
                    </div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo e($olts->where('status', 'inactive')->count()); ?></h3>
                        <p>Tidak Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!$popId && auth()->user()->hasRole('superadmin')): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Silakan pilih POP terlebih dahulu untuk melihat data OLT.
        </div>
        <?php else: ?>
        <!-- OLT List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Daftar OLT</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('olts.create')): ?>
                    <a href="<?php echo e(route('admin.olts.create')); ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah OLT
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control select2" id="filterBrand">
                            <option value="">Semua Brand</option>
                            <?php $__currentLoopData = \App\Models\Olt::BRANDS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e(request('brand') == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Aktif</option>
                            <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Tidak Aktif</option>
                            <option value="maintenance" <?php echo e(request('status') == 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="filterSearch" placeholder="Cari nama, IP, kode..." value="<?php echo e(request('search')); ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary btn-block" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- OLT Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">#</th>
                                <th>Nama</th>
                                <th>Brand/Model</th>
                                <th>IP Address</th>
                                <th>PON Ports</th>
                                <th>ONU</th>
                                <th>Status</th>
                                <th>Last Sync</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $olts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $olt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($olts->firstItem() + $index); ?></td>
                                <td>
                                    <strong><?php echo e($olt->name); ?></strong>
                                    <br><small class="text-muted"><?php echo e($olt->code); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?php echo e($olt->brand_name); ?></span>
                                    <?php if($olt->model): ?>
                                    <br><small><?php echo e($olt->model); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo e($olt->ip_address); ?></code></td>
                                <td class="text-center"><?php echo e($olt->total_pon_ports); ?></td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo e($olt->onus_count ?? 0); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($olt->status == 'active'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Aktif</span>
                                    <?php elseif($olt->status == 'maintenance'): ?>
                                        <span class="badge badge-warning"><i class="fas fa-tools"></i> Maintenance</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($olt->last_sync_at): ?>
                                        <small><?php echo e($olt->last_sync_at->diffForHumans()); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Belum sync</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo e(route('admin.olts.show', $olt)); ?>" class="btn btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('olts.sync')): ?>
                                        <button class="btn btn-success" onclick="syncOlt('<?php echo e($olt->id); ?>')" title="Sync">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('olts.edit')): ?>
                                        <a href="<?php echo e(route('admin.olts.edit', $olt)); ?>" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('olts.delete')): ?>
                                        <button class="btn btn-danger" onclick="deleteOlt('<?php echo e($olt->id); ?>', '<?php echo e($olt->name); ?>')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada data OLT</p>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('olts.create')): ?>
                                    <a href="<?php echo e(route('admin.olts.create')); ?>" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Tambah OLT Pertama
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($olts->hasPages()): ?>
                <div class="mt-3">
                    <?php echo e($olts->withQueryString()->links()); ?>

                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    
    // POP Selector
    $('#selectPop').on('change', function() {
        var popId = $(this).val();
        window.location.href = '<?php echo e(route("admin.olts.index")); ?>?pop_id=' + popId;
    });

    // Filters
    $('#filterBrand, #filterStatus').on('change', applyFilters);
    $('#filterSearch').on('keypress', function(e) {
        if (e.which == 13) applyFilters();
    });
});

function applyFilters() {
    var params = new URLSearchParams(window.location.search);
    
    var brand = $('#filterBrand').val();
    var status = $('#filterStatus').val();
    var search = $('#filterSearch').val();
    
    if (brand) params.set('brand', brand); else params.delete('brand');
    if (status) params.set('status', status); else params.delete('status');
    if (search) params.set('search', search); else params.delete('search');
    
    window.location.href = '<?php echo e(route("admin.olts.index")); ?>?' + params.toString();
}

function resetFilters() {
    var popId = '<?php echo e($popId); ?>';
    window.location.href = '<?php echo e(route("admin.olts.index")); ?>' + (popId ? '?pop_id=' + popId : '');
}

function syncOlt(id) {
    Swal.fire({
        title: 'Sync OLT?',
        text: 'Proses ini akan mengambil data ONU terbaru dari OLT',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: '<i class="fas fa-sync-alt"></i> Ya, Sync!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/olts/' + id + '/sync',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Syncing...',
                        text: 'Mohon tunggu',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                },
                success: function(res) {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Gagal sync OLT', 'error');
                }
            });
        }
    });
}

function deleteOlt(id, name) {
    Swal.fire({
        title: 'Hapus OLT?',
        html: `Anda yakin ingin menghapus OLT <strong>${name}</strong>?<br><small class="text-danger">Semua data ONU terkait akan ikut terhapus!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/olts/' + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                success: function(res) {
                    toastr.success(res.message || 'OLT berhasil dihapus');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Gagal menghapus OLT');
                }
            });
        }
    });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/olts/index.blade.php ENDPATH**/ ?>