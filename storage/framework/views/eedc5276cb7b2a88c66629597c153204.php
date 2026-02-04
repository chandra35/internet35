

<?php $__env->startSection('title', 'Edit ODC'); ?>

<?php $__env->startSection('page-title', 'Edit ODC: ' . $odc->code); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.odcs.index', ['pop_id' => $odc->pop_id])); ?>">ODC</a></li>
    <li class="breadcrumb-item active">Edit</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<style>
    #map { height: 300px; border-radius: 5px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<form action="<?php echo e(route('admin.odcs.update', $odc)); ?>" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Basic Info -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Kode ODC</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="code" name="code" value="<?php echo e(old('code', $odc->code)); ?>">
                                <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama ODC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="name" name="name" value="<?php echo e(old('name', $odc->name)); ?>" required>
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="olt_id">OLT <span class="text-danger">*</span></label>
                                <select class="form-control select2 <?php $__errorArgs = ['olt_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="olt_id" name="olt_id" required style="width: 100%;">
                                    <option value="">-- Pilih OLT --</option>
                                    <?php $__currentLoopData = $olts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $olt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($olt->id); ?>" 
                                                data-pon-ports="<?php echo e($olt->ponPorts->toJson()); ?>"
                                                <?php echo e(old('olt_id', $odc->olt_id) == $olt->id ? 'selected' : ''); ?>>
                                            <?php echo e($olt->name); ?> (<?php echo e($olt->ip_address); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['olt_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="olt_slot">Slot</label>
                                <input type="number" class="form-control <?php $__errorArgs = ['olt_slot'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="olt_slot" name="olt_slot" value="<?php echo e(old('olt_slot', $odc->olt_slot ?? 0)); ?>" min="0">
                                <?php $__errorArgs = ['olt_slot'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="olt_pon_port">PON Port</label>
                                <select class="form-control <?php $__errorArgs = ['olt_pon_port'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="olt_pon_port" name="olt_pon_port">
                                    <option value="">-- Pilih --</option>
                                </select>
                                <?php $__errorArgs = ['olt_pon_port'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="status" name="status" required>
                                    <option value="active" <?php echo e(old('status', $odc->status) == 'active' ? 'selected' : ''); ?>>Aktif</option>
                                    <option value="maintenance" <?php echo e(old('status', $odc->status) == 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                                    <option value="inactive" <?php echo e(old('status', $odc->status) == 'inactive' ? 'selected' : ''); ?>>Tidak Aktif</option>
                                </select>
                                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="text-muted small" id="pon-port-info"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea class="form-control <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                  id="address" name="address" rows="2"><?php echo e(old('address', $odc->address)); ?></textarea>
                        <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-10">
                            <div class="input-group">
                                <input type="text" id="searchAddress" class="form-control" placeholder="Cari alamat atau lokasi...">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" id="btnSearch">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success btn-block" id="btnMyLocation">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                        </div>
                    </div>
                    <div id="map"></div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latitude">Latitude</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['latitude'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="latitude" name="latitude" value="<?php echo e(old('latitude', $odc->latitude)); ?>" step="any">
                                <?php $__errorArgs = ['latitude'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['longitude'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="longitude" name="longitude" value="<?php echo e(old('longitude', $odc->longitude)); ?>" step="any">
                                <?php $__errorArgs = ['longitude'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Klik pada peta untuk menentukan lokasi atau masukkan koordinat manual</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Port Configuration -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Konfigurasi Port</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="total_ports">Total Port <span class="text-danger">*</span></label>
                        <input type="number" class="form-control <?php $__errorArgs = ['total_ports'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="total_ports" name="total_ports" value="<?php echo e(old('total_ports', $odc->total_ports)); ?>" 
                               min="<?php echo e($odc->used_ports); ?>" max="1000" required>
                        <?php $__errorArgs = ['total_ports'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="text-muted">
                            Port terpakai: <?php echo e($odc->used_ports); ?> | Tersedia: <?php echo e($odc->available_ports); ?>

                        </small>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-<?php echo e($odc->port_usage_percent > 80 ? 'danger' : ($odc->port_usage_percent > 50 ? 'warning' : 'success')); ?>" 
                             style="width: <?php echo e($odc->port_usage_percent); ?>%">
                            <?php echo e($odc->port_usage_percent); ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Physical Specifications -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Spesifikasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="cabinet_type">Tipe Cabinet</label>
                        <input type="text" class="form-control <?php $__errorArgs = ['cabinet_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="cabinet_type" name="cabinet_type" value="<?php echo e(old('cabinet_type', $odc->cabinet_type)); ?>" 
                               placeholder="Contoh: Outdoor 144 Core">
                        <?php $__errorArgs = ['cabinet_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group">
                        <label for="cable_type">Tipe Kabel</label>
                        <input type="text" class="form-control <?php $__errorArgs = ['cable_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="cable_type" name="cable_type" value="<?php echo e(old('cable_type', $odc->cable_type)); ?>"
                               placeholder="Contoh: Fiber Optic Single Mode">
                        <?php $__errorArgs = ['cable_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group">
                        <label for="cable_core">Jumlah Core</label>
                        <input type="number" class="form-control <?php $__errorArgs = ['cable_core'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="cable_core" name="cable_core" value="<?php echo e(old('cable_core', $odc->cable_core)); ?>" min="1">
                        <?php $__errorArgs = ['cable_core'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group">
                        <label for="cable_distance">Jarak Kabel (meter)</label>
                        <input type="number" class="form-control <?php $__errorArgs = ['cable_distance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="cable_distance" name="cable_distance" value="<?php echo e(old('cable_distance', $odc->cable_distance)); ?>" min="0" step="0.01">
                        <?php $__errorArgs = ['cable_distance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-2"></i>Foto Dokumentasi</h3>
                </div>
                <div class="card-body">
                    <!-- Existing Photos -->
                    <?php if($odc->photos && count($odc->photos) > 0): ?>
                    <div class="mb-3">
                        <label class="d-block mb-2">Foto yang sudah ada:</label>
                        <div class="d-flex flex-wrap" id="existing-photos">
                            <?php $__currentLoopData = $odc->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="position-relative mr-2 mb-2" id="photo-<?php echo e($loop->index); ?>">
                                <img src="<?php echo e($odc->getThumbnailUrl($photo)); ?>" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
                                <button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" 
                                        onclick="markPhotoForRemoval('<?php echo e($photo); ?>', <?php echo e($loop->index); ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                                <input type="hidden" name="keep_photos[]" value="<?php echo e($photo); ?>" id="keep-<?php echo e($loop->index); ?>">
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Upload New Photos -->
                    <div class="form-group mb-0">
                        <label>Tambah Foto Baru</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input <?php $__errorArgs = ['photos.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="photos" name="photos[]" multiple accept="image/*">
                                <label class="custom-file-label" for="photos">Pilih foto...</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btn-camera" title="Ambil dari Kamera">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        <?php $__errorArgs = ['photos.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="text-muted">Format: JPG, PNG, WebP. Maks 5MB per foto.</small>
                    </div>
                    <div id="photo-preview" class="mt-3 d-flex flex-wrap"></div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <textarea class="form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                  id="notes" name="notes" rows="4" placeholder="Catatan tambahan..."><?php echo e(old('notes', $odc->notes)); ?></textarea>
                        <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                    <a href="<?php echo e(route('admin.odcs.index', ['pop_id' => $odc->pop_id])); ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    // Initialize map
    const defaultLat = <?php echo e(old('latitude', $odc->latitude ?? -6.2088)); ?>;
    const defaultLng = <?php echo e(old('longitude', $odc->longitude ?? 106.8456)); ?>;
    
    const map = L.map('map').setView([defaultLat, defaultLng], 16);
    
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
    var baseLayers = {
        "Satelit": satelliteLayer,
        "Peta": osmLayer,
        "Hybrid": hybridLayer
    };
    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);
    
    let marker = null;
    
    // Function to set marker
    function setMarker(lat, lng) {
        $('#latitude').val(lat.toFixed(8));
        $('#longitude').val(lng.toFixed(8));
        
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function(ev) {
                var ll = ev.target.getLatLng();
                $('#latitude').val(ll.lat.toFixed(8));
                $('#longitude').val(ll.lng.toFixed(8));
            });
        }
    }
    
    // Add marker if coordinates exist
    if ($('#latitude').val() && $('#longitude').val()) {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function(ev) {
            var ll = ev.target.getLatLng();
            $('#latitude').val(ll.lat.toFixed(8));
            $('#longitude').val(ll.lng.toFixed(8));
        });
    }
    
    // Click on map to set location
    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });
    
    // Update marker when coordinates change manually
    $('#latitude, #longitude').on('change', function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            setMarker(lat, lng);
            map.setView([lat, lng], 17);
        }
    });
    
    // Search address using Nominatim
    function searchAddress() {
        var query = $('#searchAddress').val().trim();
        if (!query) return;
        
        $('#btnSearch').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/search',
            data: { q: query, format: 'json', limit: 1, countrycodes: 'id' },
            success: function(data) {
                if (data && data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lng = parseFloat(data[0].lon);
                    setMarker(lat, lng);
                    map.setView([lat, lng], 17);
                } else {
                    alert('Lokasi tidak ditemukan');
                }
            },
            error: function() { alert('Gagal mencari lokasi'); },
            complete: function() {
                $('#btnSearch').prop('disabled', false).html('<i class="fas fa-search"></i> Cari');
            }
        });
    }
    
    $('#btnSearch').on('click', searchAddress);
    $('#searchAddress').on('keypress', function(e) {
        if (e.which === 13) { e.preventDefault(); searchAddress(); }
    });
    
    // My location button
    $('#btnMyLocation').on('click', function() {
        if (!navigator.geolocation) { alert('Browser tidak mendukung geolocation'); return; }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                setMarker(position.coords.latitude, position.coords.longitude);
                map.setView([position.coords.latitude, position.coords.longitude], 17);
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
            },
            function(error) {
                alert('Gagal mendapatkan lokasi');
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
    
    setTimeout(function() { map.invalidateSize(); }, 200);
    
    // Handle OLT change - populate PON ports
    var currentPonPort = '<?php echo e(old('olt_pon_port', $odc->olt_pon_port)); ?>';
    
    $('#olt_id').on('change', function() {
        var $selected = $(this).find(':selected');
        var ponPorts = $selected.data('pon-ports') || [];
        var $ponSelect = $('#olt_pon_port');
        
        $ponSelect.empty().append('<option value="">-- Pilih --</option>');
        $('#pon-port-info').html('');
        
        if (ponPorts && ponPorts.length > 0) {
            ponPorts.forEach(function(port) {
                var label = 'PON ' + port.port;
                if (port.slot > 0) {
                    label = 'Slot ' + port.slot + ' / PON ' + port.port;
                }
                var status = port.status === 'up' ? '✓' : '✗';
                var selected = (port.port == currentPonPort) ? 'selected' : '';
                $ponSelect.append('<option value="' + port.port + '" data-slot="' + port.slot + '" ' + selected + '>' + label + ' [' + status + ']</option>');
            });
            $('#pon-port-info').html('<i class="fas fa-info-circle"></i> ' + ponPorts.length + ' PON ports tersedia');
        } else {
            $('#pon-port-info').html('<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Tidak ada PON port terdaftar</span>');
        }
    });
    
    // Update slot when PON port is selected
    $('#olt_pon_port').on('change', function() {
        var $selected = $(this).find(':selected');
        var slot = $selected.data('slot') || 0;
        $('#olt_slot').val(slot);
    });
    
    // Trigger OLT change on load if already selected
    if ($('#olt_id').val()) {
        $('#olt_id').trigger('change');
    }
    
    // Photo handling
    var photoInput = document.getElementById('photos');
    var photoPreview = document.getElementById('photo-preview');
    
    $('#photos').on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            $(this).next('.custom-file-label').text(files.length + ' foto baru dipilih');
            updatePhotoPreview();
        } else {
            $(this).next('.custom-file-label').text('Pilih foto...');
        }
    });
    
    function updatePhotoPreview() {
        photoPreview.innerHTML = '';
        var files = photoInput.files;
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = (function(f, idx) {
                    return function(e) {
                        var div = document.createElement('div');
                        div.className = 'position-relative mr-2 mb-2';
                        div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">' +
                            '<span class="badge badge-success position-absolute" style="top:5px;left:5px;">Baru</span>' +
                            '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                        photoPreview.appendChild(div);
                    };
                })(file, i);
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Camera capture
    $('#btn-camera').on('click', function() {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'environment';
        input.onchange = function(e) {
            if (e.target.files.length > 0) {
                var dt = new DataTransfer();
                var existingFiles = photoInput.files;
                for (var i = 0; i < existingFiles.length; i++) {
                    dt.items.add(existingFiles[i]);
                }
                dt.items.add(e.target.files[0]);
                photoInput.files = dt.files;
                $('#photos').next('.custom-file-label').text(dt.files.length + ' foto baru dipilih');
                updatePhotoPreview();
            }
        };
        input.click();
    });
});

// Mark existing photo for removal
window.markPhotoForRemoval = function(filename, idx) {
    if (confirm('Hapus foto ini?')) {
        $('#photo-' + idx).hide();
        $('#keep-' + idx).remove();
        // Add hidden input to mark for removal
        $('<input>').attr({
            type: 'hidden',
            name: 'remove_photos[]',
            value: filename
        }).appendTo('form');
    }
};

// Remove new photo from preview
window.removePreviewPhoto = function(idx) {
    var dt = new DataTransfer();
    var files = document.getElementById('photos').files;
    for (var i = 0; i < files.length; i++) {
        if (i !== idx) {
            dt.items.add(files[i]);
        }
    }
    document.getElementById('photos').files = dt.files;
    $('#photos').next('.custom-file-label').text(dt.files.length > 0 ? dt.files.length + ' foto baru dipilih' : 'Pilih foto...');
    
    var photoPreview = document.getElementById('photo-preview');
    photoPreview.innerHTML = '';
    for (var i = 0; i < dt.files.length; i++) {
        var file = dt.files[i];
        if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = (function(f, idx) {
                return function(e) {
                    var div = document.createElement('div');
                    div.className = 'position-relative mr-2 mb-2';
                    div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">' +
                        '<span class="badge badge-success position-absolute" style="top:5px;left:5px;">Baru</span>' +
                        '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                    photoPreview.appendChild(div);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }
};
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/odcs/edit.blade.php ENDPATH**/ ?>