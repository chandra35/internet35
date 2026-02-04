

<?php $__env->startSection('title', 'Edit OLT - ' . $olt->name); ?>

<?php $__env->startSection('page-title', 'Edit OLT: ' . $olt->name); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.olts.index')); ?>">OLT</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.olts.show', $olt)); ?>"><?php echo e($olt->name); ?></a></li>
    <li class="breadcrumb-item active">Edit</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<form action="<?php echo e(route('admin.olts.update', $olt)); ?>" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <div class="row">
        <!-- Basic Info -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <?php if($popUsers): ?>
                    <div class="form-group">
                        <label>POP <span class="text-danger">*</span></label>
                        <select name="pop_id" id="pop_id" class="form-control select2 <?php $__errorArgs = ['pop_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                            <option value="">-- Pilih POP --</option>
                            <?php $__currentLoopData = $popUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($pop->id); ?>" <?php echo e(old('pop_id', $olt->pop_id) == $pop->id ? 'selected' : ''); ?>>
                                <?php echo e($pop->name); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['pop_id'];
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
                    <?php else: ?>
                    <input type="hidden" name="pop_id" value="<?php echo e($olt->pop_id); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Router (Opsional)</label>
                        <select name="router_id" id="router_id" class="form-control select2 <?php $__errorArgs = ['router_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">-- Pilih Router --</option>
                            <?php $__currentLoopData = $routers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $router): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($router->id); ?>" <?php echo e(old('router_id', $olt->router_id) == $router->id ? 'selected' : ''); ?>>
                                <?php echo e($router->name); ?> (<?php echo e($router->ip_address); ?>)
                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['router_id'];
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
                        <label>Nama OLT <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               value="<?php echo e(old('name', $olt->name)); ?>" required>
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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand" class="form-control <?php $__errorArgs = ['brand'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e(old('brand', $olt->brand) == $key ? 'selected' : ''); ?>>
                                        <?php echo e($label); ?>

                                    </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['brand'];
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
                                <label>Model</label>
                                <input type="text" name="model" class="form-control <?php $__errorArgs = ['model'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('model', $olt->model)); ?>">
                                <?php $__errorArgs = ['model'];
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

                    <div class="form-group">
                        <label>IP Address <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" id="ip_address" class="form-control <?php $__errorArgs = ['ip_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               value="<?php echo e(old('ip_address', $olt->ip_address)); ?>" required>
                        <?php $__errorArgs = ['ip_address'];
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

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>PON Ports</label>
                                <input type="number" name="total_pon_ports" id="total_pon_ports" class="form-control" 
                                       value="<?php echo e(old('total_pon_ports', $olt->total_pon_ports)); ?>" min="1" max="64">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Uplink Ports</label>
                                <input type="number" name="total_uplink_ports" id="total_uplink_ports" class="form-control" 
                                       value="<?php echo e(old('total_uplink_ports', $olt->total_uplink_ports)); ?>" min="1" max="16">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo e(old('status', $olt->status) == 'active' ? 'selected' : ''); ?>>Aktif</option>
                                    <option value="inactive" <?php echo e(old('status', $olt->status) == 'inactive' ? 'selected' : ''); ?>>Tidak Aktif</option>
                                    <option value="maintenance" <?php echo e(old('status', $olt->status) == 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo e(old('address', $olt->address)); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control" 
                                       value="<?php echo e(old('latitude', $olt->latitude)); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control" 
                                       value="<?php echo e(old('longitude', $olt->longitude)); ?>">
                            </div>
                        </div>
                    </div>
                    <div id="map" style="height: 250px; border-radius: 5px;"></div>
                    <small class="text-muted">Klik pada peta untuk mengubah lokasi</small>
                </div>
            </div>
        </div>

        <!-- Connection Settings -->
        <div class="col-md-6">
            <!-- Re-identify -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sync-alt mr-2"></i>Re-Identify OLT</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Gunakan fitur ini untuk memperbarui informasi OLT (brand, model, jumlah port).</p>
                    
                    <div class="form-group">
                        <label>Brand OLT</label>
                        <select id="reident_brand" class="form-control">
                            <option value="">-- Auto Detect --</option>
                            <option value="zte" <?php echo e($olt->brand == 'zte' ? 'selected' : ''); ?>>ZTE</option>
                            <option value="huawei" <?php echo e($olt->brand == 'huawei' ? 'selected' : ''); ?>>Huawei</option>
                            <option value="vsol" <?php echo e($olt->brand == 'vsol' ? 'selected' : ''); ?>>VSOL</option>
                            <option value="hioso" <?php echo e($olt->brand == 'hioso' ? 'selected' : ''); ?>>Hioso</option>
                            <option value="hsgq" <?php echo e($olt->brand == 'hsgq' ? 'selected' : ''); ?>>HSGQ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode</label>
                        <select id="reident_method" class="form-control">
                            <option value="snmp">SNMP</option>
                            <option value="telnet" <?php echo e($olt->telnet_enabled ? 'selected' : ''); ?>>Telnet</option>
                            <option value="ssh" <?php echo e($olt->ssh_enabled ? 'selected' : ''); ?>>SSH</option>
                        </select>
                    </div>
                    
                    <!-- Status Koneksi -->
                    <div id="connection-status" class="mb-3" style="display:none;">
                        <small id="connection-status-text" class="text-muted"></small>
                    </div>
                    
                    <button type="button" class="btn btn-info btn-block" id="btn-reidentify">
                        <i class="fas fa-search mr-2"></i>Re-Identify OLT
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary btn-block mt-2" id="btn-test-connection">
                        <i class="fas fa-plug mr-2"></i>Test Koneksi
                    </button>
                </div>
            </div>

            <!-- SNMP -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>SNMP Settings</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SNMP Port</label>
                                <input type="number" name="snmp_port" id="snmp_port" class="form-control" 
                                       value="<?php echo e(old('snmp_port', $olt->snmp_port)); ?>" min="1" max="65535">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Community</label>
                                <input type="text" name="snmp_community" id="snmp_community" class="form-control" 
                                       value="<?php echo e(old('snmp_community', $olt->snmp_community)); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telnet -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-terminal mr-2"></i>Telnet Settings</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="telnet_enabled" name="telnet_enabled" value="1"
                               <?php echo e(old('telnet_enabled', $olt->telnet_enabled) ? 'checked' : ''); ?>>
                        <label class="custom-control-label" for="telnet_enabled">Aktifkan Telnet</label>
                    </div>
                    <div class="telnet-settings" style="<?php echo e(old('telnet_enabled', $olt->telnet_enabled) ? '' : 'display:none'); ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="telnet_port" id="telnet_port" class="form-control" 
                                           value="<?php echo e(old('telnet_port', $olt->telnet_port)); ?>">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="telnet_username" id="telnet_username" class="form-control" 
                                           value="<?php echo e(old('telnet_username', $olt->telnet_username)); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="telnet_password" id="telnet_password" class="form-control" 
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SSH -->
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>SSH Settings</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="ssh_enabled" name="ssh_enabled" value="1"
                               <?php echo e(old('ssh_enabled', $olt->ssh_enabled) ? 'checked' : ''); ?>>
                        <label class="custom-control-label" for="ssh_enabled">Aktifkan SSH</label>
                    </div>
                    <div class="ssh-settings" style="<?php echo e(old('ssh_enabled', $olt->ssh_enabled) ? '' : 'display:none'); ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="ssh_port" class="form-control" 
                                           value="<?php echo e(old('ssh_port', $olt->ssh_port)); ?>">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="ssh_username" class="form-control" 
                                           value="<?php echo e(old('ssh_username', $olt->ssh_username)); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="ssh_password" class="form-control" 
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo e(old('description', $olt->description)); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Catatan Internal</label>
                        <textarea name="notes" class="form-control" rows="2"><?php echo e(old('notes', $olt->notes)); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-2"></i>Foto Dokumentasi</h3>
                </div>
                <div class="card-body">
                    <?php if($olt->photos && count($olt->photos) > 0): ?>
                    <div class="mb-3">
                        <label class="mb-2"><strong>Foto Saat Ini:</strong></label>
                        <div class="d-flex flex-wrap">
                            <?php $__currentLoopData = $olt->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div id="photo-<?php echo e($idx); ?>" class="position-relative mr-2 mb-2">
                                <img src="<?php echo e($olt->getThumbnailUrl($photo)); ?>" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">
                                <input type="hidden" name="keep_photos[]" id="keep-<?php echo e($idx); ?>" value="<?php echo e($photo); ?>">
                                <button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 5px;font-size:10px;" 
                                        onclick="markPhotoForRemoval('<?php echo e($photo); ?>', <?php echo e($idx); ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Tambah Foto Baru</label>
                        <small class="form-text text-muted mb-2">(Maks. 10 foto total, masing-masing maks. 5MB)</small>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="photos" name="photos[]" accept="image/*" multiple>
                                <label class="custom-file-label" for="photos">Pilih foto...</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btn-camera" title="Kamera">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        <div id="photo-preview" class="d-flex flex-wrap mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save mr-2"></i>Update OLT
                    </button>
                    <a href="<?php echo e(route('admin.olts.show', $olt)); ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-2"></i>Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('js'); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    // Toggle settings
    $('#telnet_enabled').change(function() {
        $('.telnet-settings').toggle(this.checked);
    });
    $('#ssh_enabled').change(function() {
        $('.ssh-settings').toggle(this.checked);
    });

    // Map
    var lat = <?php echo e($olt->latitude ?? -6.2088); ?>;
    var lng = <?php echo e($olt->longitude ?? 106.8456); ?>;
    
    var map = L.map('map').setView([lat, lng], 15);
    
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

    var marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', function(e) {
        var ll = e.target.getLatLng();
        $('#latitude').val(ll.lat.toFixed(8));
        $('#longitude').val(ll.lng.toFixed(8));
    });

    map.on('click', function(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng, { draggable: true }).addTo(map);
        marker.on('dragend', function(ev) {
            var ll = ev.target.getLatLng();
            $('#latitude').val(ll.lat.toFixed(8));
            $('#longitude').val(ll.lng.toFixed(8));
        });
        $('#latitude').val(e.latlng.lat.toFixed(8));
        $('#longitude').val(e.latlng.lng.toFixed(8));
    });

    // Re-identify OLT
    $('#btn-reidentify').click(function() {
        var btn = $(this);
        var ip = $('input[name="ip_address"]').val();
        var snmpPort = $('#snmp_port').val();
        var snmpCommunity = $('#snmp_community').val();
        var method = $('#reident_method').val();
        var brand = $('#reident_brand').val();
        var statusDiv = $('#connection-status');
        var statusText = $('#connection-status-text');

        if (!ip) {
            Swal.fire('Error', 'IP Address tidak ditemukan', 'error');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengidentifikasi...');
        statusDiv.show();
        statusText.removeClass('text-success text-danger text-muted text-warning').addClass('text-muted')
            .html('<i class="fas fa-circle-notch fa-spin mr-1"></i> Menghubungkan via ' + method.toUpperCase() + '...');

        var data = {
            _token: '<?php echo e(csrf_token()); ?>',
            ip_address: ip,
            connection_method: method,
            brand: brand
        };

        // Add connection-specific data
        if (method === 'snmp') {
            data.snmp_port = snmpPort;
            data.snmp_community = snmpCommunity;
        } else if (method === 'telnet') {
            data.telnet_enabled = 1;
            data.telnet_port = $('#telnet_port').val();
            data.telnet_username = $('#telnet_username').val();
            var pwd = $('#telnet_password').val();
            if (pwd) data.telnet_password = pwd;
            else data.telnet_password = '<?php echo e($olt->telnet_password ?? ""); ?>';
        } else if (method === 'ssh') {
            data.ssh_enabled = 1;
            data.ssh_port = $('#ssh_port').val();
            data.ssh_username = $('#ssh_username').val();
            var pwd = $('#ssh_password').val();
            if (pwd) data.ssh_password = pwd;
            else data.ssh_password = '<?php echo e($olt->ssh_password ?? ""); ?>';
        }

        $.ajax({
            url: '<?php echo e(route("admin.olts.identify")); ?>',
            method: 'POST',
            data: data,
            timeout: 35000,
            success: function(res) {
                if (res.success) {
                    // Update status
                    statusText.removeClass('text-muted text-danger text-warning').addClass('text-success')
                        .html('<i class="fas fa-check-circle mr-1"></i> Koneksi berhasil - OLT terdeteksi');
                    
                    // Show confirmation dialog
                    var msg = '<table class="table table-sm">' +
                        '<tr><td>Brand</td><td><strong>' + (res.brand_label || res.brand || '-') + '</strong></td></tr>' +
                        '<tr><td>Model</td><td><strong>' + (res.model || '-') + '</strong></td></tr>' +
                        '<tr><td>PON Ports</td><td><strong>' + (res.total_pon_ports || '-') + '</strong></td></tr>' +
                        '<tr><td>Uplink Ports</td><td><strong>' + (res.total_uplink_ports || '-') + '</strong></td></tr>' +
                        '</table>';

                    Swal.fire({
                        title: 'Update Info OLT?',
                        html: msg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Update',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Update form fields
                            $('select[name="brand"]').val(res.brand).trigger('change');
                            $('input[name="model"]').val(res.model || '');
                            $('input[name="total_pon_ports"]').val(res.total_pon_ports || '');
                            $('input[name="total_uplink_ports"]').val(res.total_uplink_ports || '');
                            
                            Swal.fire('Berhasil!', 'Info OLT berhasil diperbarui, silakan simpan untuk menyimpan perubahan.', 'success');
                        }
                    });
                } else {
                    statusText.removeClass('text-muted text-success text-warning').addClass('text-danger')
                        .html('<i class="fas fa-times-circle mr-1"></i> ' + (res.message || 'Gagal mengidentifikasi OLT'));
                    Swal.fire('Gagal', res.message || 'Tidak dapat mengidentifikasi OLT', 'error');
                }
            },
            error: function(xhr) {
                statusText.removeClass('text-muted text-success text-warning').addClass('text-danger')
                    .html('<i class="fas fa-times-circle mr-1"></i> ' + (xhr.responseJSON?.message || 'Terjadi kesalahan'));
                Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Re-Identify OLT');
            }
        });
    });

    // Test Connection
    $('#btn-test-connection').click(function() {
        var btn = $(this);
        var statusDiv = $('#connection-status');
        var statusText = $('#connection-status-text');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Testing...');
        statusDiv.show();
        statusText.removeClass('text-success text-danger text-muted').addClass('text-muted').html('<i class="fas fa-circle-notch fa-spin mr-1"></i> Menghubungkan ke OLT...');
        
        $.post('<?php echo e(route("admin.olts.test-connection", $olt)); ?>', { _token: '<?php echo e(csrf_token()); ?>' })
            .done(function(res) {
                statusText.removeClass('text-muted text-danger').addClass('text-success')
                    .html('<i class="fas fa-check-circle mr-1"></i> ' + (res.message || 'Koneksi berhasil'));
            })
            .fail(function(xhr) {
                statusText.removeClass('text-muted text-success').addClass('text-danger')
                    .html('<i class="fas fa-times-circle mr-1"></i> ' + (xhr.responseJSON?.message || 'Koneksi gagal'));
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-plug mr-2"></i>Test Koneksi');
            });
    });

    // Show status during identify
    function showStatus(message, type) {
        var statusDiv = $('#connection-status');
        var statusText = $('#connection-status-text');
        statusDiv.show();
        statusText.removeClass('text-success text-danger text-muted text-warning')
            .addClass('text-' + type)
            .html(message);
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
                        div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">' +
                            '<span class="badge badge-success position-absolute" style="top:5px;left:5px;font-size:9px;">Baru</span>' +
                            '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 5px;font-size:10px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
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
                    div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">' +
                        '<span class="badge badge-success position-absolute" style="top:5px;left:5px;font-size:9px;">Baru</span>' +
                        '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 5px;font-size:10px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                    photoPreview.appendChild(div);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }
};
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/olts/edit.blade.php ENDPATH**/ ?>