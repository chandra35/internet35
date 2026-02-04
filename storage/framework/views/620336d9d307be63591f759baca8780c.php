

<?php $__env->startSection('title', 'Detail ODC'); ?>

<?php $__env->startSection('page-title', 'Detail ODC: ' . $odc->code); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.odcs.index', ['pop_id' => $odc->pop_id])); ?>">ODC</a></li>
    <li class="breadcrumb-item active">Detail</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<style>
    #map { height: 300px; border-radius: 5px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-8">
        <!-- Basic Info -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odcs.edit')): ?>
                    <a href="<?php echo e(route('admin.odcs.edit', $odc)); ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="150"><strong>Kode</strong></td>
                        <td><code class="text-lg"><?php echo e($odc->code); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Nama</strong></td>
                        <td><?php echo e($odc->name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>OLT</strong></td>
                        <td>
                            <?php if($odc->olt): ?>
                            <a href="<?php echo e(route('admin.olts.show', $odc->olt)); ?>">
                                <?php echo e($odc->olt->name); ?>

                            </a>
                            <?php if($odc->olt_pon_port): ?>
                                <span class="badge badge-info ml-2">PON <?php echo e($odc->olt_pon_port); ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td><span class="badge badge-<?php echo e($odc->status_badge); ?> badge-lg"><?php echo e($odc->status_label); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td><?php echo e($odc->address ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat Oleh</strong></td>
                        <td><?php echo e($odc->creator->name ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat Pada</strong></td>
                        <td><?php echo e($odc->created_at->format('d M Y H:i')); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Location Map -->
        <?php if($odc->hasCoordinates()): ?>
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                <div class="card-tools">
                    <a href="https://www.google.com/maps?q=<?php echo e($odc->latitude); ?>,<?php echo e($odc->longitude); ?>" 
                       target="_blank" class="btn btn-info btn-sm">
                        <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="map"></div>
                <div class="mt-2">
                    <small class="text-muted">
                        Koordinat: <?php echo e($odc->latitude); ?>, <?php echo e($odc->longitude); ?>

                    </small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Photo Gallery -->
        <?php if($odc->photos && count($odc->photos) > 0): ?>
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images mr-2"></i>Foto Dokumentasi</h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?php echo e(count($odc->photos)); ?> foto</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php $__currentLoopData = $odc->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <a href="<?php echo e($odc->getPhotoUrl($photo)); ?>" data-lightbox="odc-gallery" data-title="<?php echo e($odc->code); ?> - Foto <?php echo e($loop->iteration); ?>">
                            <img src="<?php echo e($odc->getThumbnailUrl($photo)); ?>" class="img-fluid img-thumbnail" 
                                 style="width:100%;height:150px;object-fit:cover;" alt="Foto <?php echo e($loop->iteration); ?>">
                        </a>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ODP List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Daftar ODP (<?php echo e($odc->odps->count()); ?>)</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('odps.create')): ?>
                    <a href="<?php echo e(route('admin.odps.create', ['pop_id' => $odc->pop_id, 'odc_id' => $odc->id])); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Tambah ODP
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Port ODC</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $odc->odps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $odp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><code><?php echo e($odp->code); ?></code></td>
                            <td><?php echo e($odp->name); ?></td>
                            <td><span class="badge badge-info">Port <?php echo e($odp->odc_port); ?></span></td>
                            <td><?php echo e($odp->customers->count()); ?>/<?php echo e($odp->total_ports); ?></td>
                            <td><span class="badge badge-<?php echo e($odp->status_badge); ?>"><?php echo e($odp->status_label); ?></span></td>
                            <td>
                                <a href="<?php echo e(route('admin.odps.show', $odp)); ?>" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada ODP di ODC ini
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Port Usage -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Penggunaan Port</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h2 class="mb-0"><?php echo e($odc->used_ports); ?>/<?php echo e($odc->total_ports); ?></h2>
                    <small class="text-muted">Port Terpakai</small>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-<?php echo e($odc->port_usage_percent > 80 ? 'danger' : ($odc->port_usage_percent > 50 ? 'warning' : 'success')); ?>" 
                         style="width: <?php echo e($odc->port_usage_percent); ?>%">
                        <?php echo e($odc->port_usage_percent); ?>%
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span class="badge badge-success"><?php echo e($odc->available_ports); ?> port tersedia</span>
                </div>
            </div>
        </div>

        <!-- Specifications -->
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Spesifikasi</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Tipe Cabinet</strong></td>
                        <td><?php echo e($odc->cabinet_type ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tipe Kabel</strong></td>
                        <td><?php echo e($odc->cable_type ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Jumlah Core</strong></td>
                        <td><?php echo e($odc->cable_core ? $odc->cable_core . ' core' : '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Jarak Kabel</strong></td>
                        <td><?php echo e($odc->cable_distance ? number_format($odc->cable_distance, 2) . ' m' : '-'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if($odc->notes): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
            </div>
            <div class="card-body">
                <?php echo e($odc->notes); ?>

            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="card">
            <div class="card-body">
                <a href="<?php echo e(route('admin.odcs.index', ['pop_id' => $odc->pop_id])); ?>" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('network-map.view')): ?>
                <a href="<?php echo e(route('admin.network-map.index', ['pop_id' => $odc->pop_id])); ?>" class="btn btn-info btn-block">
                    <i class="fas fa-map mr-1"></i> Lihat di Network Map
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<?php if($odc->hasCoordinates()): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    const lat = <?php echo e($odc->latitude); ?>;
    const lng = <?php echo e($odc->longitude); ?>;
    
    const map = L.map('map').setView([lat, lng], 16);
    
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
    
    // ODC marker (blue)
    const odcIcon = L.divIcon({
        className: 'custom-marker',
        html: '<i class="fas fa-server fa-2x text-primary"></i>',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    L.marker([lat, lng], {icon: odcIcon})
        .addTo(map)
        .bindPopup('<strong><?php echo e($odc->code); ?></strong><br><?php echo e($odc->name); ?>');
    
    // Add ODP markers
    <?php $__currentLoopData = $odc->odps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $odp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($odp->hasCoordinates()): ?>
        const odpIcon<?php echo e($loop->index); ?> = L.divIcon({
            className: 'custom-marker',
            html: '<i class="fas fa-box fa-lg text-success"></i>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
        
        L.marker([<?php echo e($odp->latitude); ?>, <?php echo e($odp->longitude); ?>], {icon: odpIcon<?php echo e($loop->index); ?>})
            .addTo(map)
            .bindPopup('<strong><?php echo e($odp->code); ?></strong><br><?php echo e($odp->name); ?>');
        
        // Draw line from ODC to ODP
        L.polyline([
            [lat, lng],
            [<?php echo e($odp->latitude); ?>, <?php echo e($odp->longitude); ?>]
        ], {color: '#28a745', weight: 2}).addTo(map);
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    
    setTimeout(function() { map.invalidateSize(); }, 200);
});
</script>
<style>
    .custom-marker {
        background: transparent;
        border: none;
    }
</style>
<?php endif; ?>

<!-- Lightbox for photo gallery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<script>
lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'albumLabel': 'Foto %1 dari %2'
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/odcs/show.blade.php ENDPATH**/ ?>