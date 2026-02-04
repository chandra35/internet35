

<?php $__env->startSection('title', 'Tambah ODP'); ?>

<?php $__env->startSection('page-title', 'Tambah ODP Baru'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.odps.index', ['pop_id' => $popId])); ?>">ODP</a></li>
    <li class="breadcrumb-item active">Tambah</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<style>
    #map { height: 400px; border-radius: 5px; }
    .connection-type-card { 
        cursor: pointer !important; 
        transition: all 0.3s ease !important; 
        border: 3px solid #dee2e6 !important; 
        user-select: none;
        position: relative;
        overflow: visible !important;
        background: #fff !important;
    }
    .connection-type-card:hover { 
        transform: translateY(-5px) !important; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important; 
        border-color: #6c757d !important; 
    }
    .connection-type-card.active { 
        border: 4px solid #28a745 !important; 
        background: #d4edda !important;
        box-shadow: 0 0 0 5px rgba(40,167,69,0.4), 0 10px 25px rgba(40,167,69,0.3) !important;
        transform: translateY(-3px) scale(1.02) !important;
    }
    .connection-type-card.active::after {
        content: '✓ DIPILIH';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #28a745;
        color: white;
        padding: 3px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        z-index: 100;
        white-space: nowrap;
    }
    .connection-type-card.active .card-body {
        background: transparent !important;
    }
    .connection-type-card.active h6 {
        color: #155724 !important;
        font-weight: bold !important;
    }
    .connection-type-card.active i {
        color: #28a745 !important;
    }
    .connection-type-card .badge { font-size: 0.7rem; }
    .connection-type-card i { transition: transform 0.3s; }
    .connection-type-card:hover i { transform: scale(1.2); }
    .connection-type-card.active i { transform: scale(1.3) !important; }
    .connection-type-card .card-body { pointer-events: none; }
    .custom-odp-marker { background: transparent; border: none; }
    .leaflet-control-layers { border-radius: 8px; }
    .leaflet-control-layers-toggle { width: 36px; height: 36px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<script>
function setConnectionType(type) {
    // Set hidden input value
    document.getElementById('connection_type').value = type;
    
    // Get all cards
    var cards = ['odc', 'olt', 'cascade'];
    
    // Reset all cards style
    cards.forEach(function(t) {
        var card = document.getElementById('card-' + t);
        card.style.border = '3px solid #dee2e6';
        card.style.background = '#fff';
        card.style.boxShadow = 'none';
        card.style.transform = 'none';
        // Remove label if exists
        var label = card.querySelector('.selected-label');
        if (label) label.remove();
    });
    
    // Apply active style to selected card
    var activeCard = document.getElementById('card-' + type);
    activeCard.style.border = '4px solid #28a745';
    activeCard.style.background = '#d4edda';
    activeCard.style.boxShadow = '0 0 0 5px rgba(40,167,69,0.4), 0 10px 25px rgba(40,167,69,0.3)';
    activeCard.style.transform = 'translateY(-3px) scale(1.02)';
    
    // Add "DIPILIH" label
    var label = document.createElement('div');
    label.className = 'selected-label';
    label.innerHTML = '✓ DIPILIH';
    label.style.cssText = 'position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#28a745;color:white;padding:3px 12px;border-radius:12px;font-size:11px;font-weight:bold;z-index:100;white-space:nowrap;';
    activeCard.style.position = 'relative';
    activeCard.style.overflow = 'visible';
    activeCard.appendChild(label);
    
    // Hide all fields
    document.getElementById('odc-fields').style.display = 'none';
    document.getElementById('olt-fields').style.display = 'none';
    document.getElementById('cascade-fields').style.display = 'none';
    
    // Show selected fields
    document.getElementById(type + '-fields').style.display = 'block';
    
    // Generate new code
    if (typeof generateCode === 'function') {
        generateCode();
    }
}
</script>
<form action="<?php echo e(route('admin.odps.store')); ?>" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="pop_id" value="<?php echo e($popId); ?>">
    <input type="hidden" name="connection_type" id="connection_type" value="<?php echo e(old('connection_type', $connectionType)); ?>">
    
    <div class="row">
        <div class="col-md-8">
            <!-- Connection Type Selection -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Jenis Koneksi</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="card connection-type-card <?php echo e(old('connection_type', $connectionType) == 'odc' ? 'active' : ''); ?>" 
                                 id="card-odc" onclick="setConnectionType('odc')">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1">Via ODC</h6>
                                    <small class="text-muted">OLT → ODC → ODP</small>
                                    <br><span class="badge badge-primary">Standard</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card connection-type-card <?php echo e(old('connection_type', $connectionType) == 'olt' ? 'active' : ''); ?>" 
                                 id="card-olt" onclick="setConnectionType('olt')">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-server fa-2x text-success mb-2"></i>
                                    <h6 class="mb-1">Direct OLT</h6>
                                    <small class="text-muted">OLT → ODP</small>
                                    <br><span class="badge badge-success">Tanpa ODC</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card connection-type-card <?php echo e(old('connection_type', $connectionType) == 'cascade' ? 'active' : ''); ?>" 
                                 id="card-cascade" onclick="setConnectionType('cascade')">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-sitemap fa-2x text-warning mb-2"></i>
                                    <h6 class="mb-1">Cascade/Relay</h6>
                                    <small class="text-muted">ODP → ODP</small>
                                    <br><span class="badge badge-warning">Estafet Splitter</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <!-- ODC Selection (shown when connection_type = odc) -->
                    <div id="odc-fields" class="connection-fields" style="<?php echo e(old('connection_type', $connectionType) != 'odc' ? 'display:none;' : ''); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="odc_id">ODC <span class="text-danger">*</span></label>
                                    <select class="form-control select2 <?php $__errorArgs = ['odc_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                            id="odc_id" name="odc_id" style="width: 100%;">
                                        <option value="">-- Pilih ODC --</option>
                                        <?php $__currentLoopData = $odcs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $odc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($odc->id); ?>" 
                                                    data-total-ports="<?php echo e($odc->total_ports); ?>"
                                                    data-used-ports="<?php echo e($odc->used_ports); ?>"
                                                    <?php echo e(old('odc_id', $selectedOdc) == $odc->id ? 'selected' : ''); ?>>
                                                <?php echo e($odc->code); ?> - <?php echo e($odc->name); ?> (<?php echo e($odc->available_ports); ?> port tersedia)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['odc_id'];
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
                                    <label for="odc_port">Port ODC <span class="text-danger">*</span></label>
                                    <select class="form-control <?php $__errorArgs = ['odc_port'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                            id="odc_port" name="odc_port">
                                        <option value="">-- Pilih ODC Dulu --</option>
                                    </select>
                                    <?php $__errorArgs = ['odc_port'];
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
                    </div>

                    <!-- OLT Selection (shown when connection_type = olt) -->
                    <div id="olt-fields" class="connection-fields" style="<?php echo e(old('connection_type', $connectionType) != 'olt' ? 'display:none;' : ''); ?>">
                        <div class="row">
                            <div class="col-md-4">
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
                                            id="olt_id" name="olt_id" style="width: 100%;">
                                        <option value="">-- Pilih OLT --</option>
                                        <?php $__currentLoopData = $olts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $olt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($olt->id); ?>" 
                                                    data-pon-ports="<?php echo e($olt->pon_ports); ?>"
                                                    <?php echo e(old('olt_id', $selectedOlt) == $olt->id ? 'selected' : ''); ?>>
                                                <?php echo e($olt->name); ?> (<?php echo e($olt->pon_ports); ?> PON)
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_pon_port">PON Port <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control <?php $__errorArgs = ['olt_pon_port'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="olt_pon_port" name="olt_pon_port" value="<?php echo e(old('olt_pon_port', 1)); ?>" min="1">
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_slot">Slot (Opsional)</label>
                                    <input type="number" class="form-control <?php $__errorArgs = ['olt_slot'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="olt_slot" name="olt_slot" value="<?php echo e(old('olt_slot')); ?>" min="0">
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
                        </div>
                    </div>

                    <!-- Parent ODP Selection (shown when connection_type = cascade) -->
                    <div id="cascade-fields" class="connection-fields" style="<?php echo e(old('connection_type', $connectionType) != 'cascade' ? 'display:none;' : ''); ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="parent_odp_id">Parent ODP <span class="text-danger">*</span></label>
                                    <select class="form-control select2 <?php $__errorArgs = ['parent_odp_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                            id="parent_odp_id" name="parent_odp_id" style="width: 100%;">
                                        <option value="">-- Pilih ODP Parent --</option>
                                        <?php $__currentLoopData = $parentOdps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $podp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($podp->id); ?>" 
                                                    <?php echo e(old('parent_odp_id') == $podp->id ? 'selected' : ''); ?>>
                                                <?php echo e($podp->code); ?> - <?php echo e($podp->name); ?> (Level <?php echo e($podp->splitter_level ?? 1); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['parent_odp_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="text-muted">ODP ini akan menjadi turunan dari parent (estafet splitter)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="splitter_level_display">Splitter Level</label>
                                    <input type="text" class="form-control" id="splitter_level_display" readonly value="Auto (Level 2+)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Kode ODP</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="code" name="code" value="<?php echo e(old('code', $nextCode)); ?>" readonly>
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
                                <small class="text-muted">Kode otomatis, double-click untuk ubah manual</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama ODP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="name" name="name" value="<?php echo e(old('name')); ?>" required>
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
                                    <option value="active" <?php echo e(old('status', 'active') == 'active' ? 'selected' : ''); ?>>Aktif</option>
                                    <option value="maintenance" <?php echo e(old('status') == 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                                    <option value="inactive" <?php echo e(old('status') == 'inactive' ? 'selected' : ''); ?>>Tidak Aktif</option>
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
                                <label for="pole_number">Nomor Tiang</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['pole_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="pole_number" name="pole_number" value="<?php echo e(old('pole_number')); ?>" 
                                       placeholder="Contoh: T-001">
                                <?php $__errorArgs = ['pole_number'];
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
                        <label for="address">Alamat</label>
                        <textarea class="form-control <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                  id="address" name="address" rows="2"><?php echo e(old('address')); ?></textarea>
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
                                       id="latitude" name="latitude" value="<?php echo e(old('latitude')); ?>" step="any">
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
                                       id="longitude" name="longitude" value="<?php echo e(old('longitude')); ?>" step="any">
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
                               id="total_ports" name="total_ports" value="<?php echo e(old('total_ports', 8)); ?>" min="1" max="100" required>
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
                        <label for="box_type">Tipe Box</label>
                        <input type="text" class="form-control <?php $__errorArgs = ['box_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="box_type" name="box_type" value="<?php echo e(old('box_type')); ?>" 
                               placeholder="Contoh: ODP 8 Core">
                        <?php $__errorArgs = ['box_type'];
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
                        <label for="splitter_type">Tipe Splitter</label>
                        <input type="text" class="form-control <?php $__errorArgs = ['splitter_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="splitter_type" name="splitter_type" value="<?php echo e(old('splitter_type')); ?>"
                               placeholder="Contoh: 1:8">
                        <?php $__errorArgs = ['splitter_type'];
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
                                  id="notes" name="notes" rows="4" placeholder="Catatan tambahan..."><?php echo e(old('notes')); ?></textarea>
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

            <!-- Photos -->
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-2"></i>Foto Dokumentasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Upload Foto <small class="text-muted">(Maks. 10 foto, masing-masing maks. 5MB)</small></label>
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
                                       id="photos" name="photos[]" accept="image/*" multiple>
                                <label class="custom-file-label" for="photos">Pilih foto...</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btn-camera" title="Ambil foto dari kamera">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        <?php $__errorArgs = ['photos.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <small class="text-danger"><?php echo e($message); ?></small>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="form-text text-muted">
                            <i class="fas fa-mobile-alt mr-1"></i> Di perangkat mobile, tombol kamera akan membuka kamera langsung.
                        </small>
                    </div>
                    <div id="photo-preview" class="d-flex flex-wrap mt-2"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Simpan ODP
                    </button>
                    <a href="<?php echo e(route('admin.odps.index', ['pop_id' => $popId])); ?>" class="btn btn-secondary btn-block">
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
function generateCode() {
    var type = document.getElementById('connection_type').value;
    var params = 'pop_id=<?php echo e($popId); ?>';
    
    if (type === 'odc') {
        var odcSelect = document.getElementById('odc_id');
        if (odcSelect && odcSelect.value) {
            params += '&odc_id=' + odcSelect.value;
        }
    } else if (type === 'olt') {
        var oltSelect = document.getElementById('olt_id');
        if (oltSelect && oltSelect.value) {
            params += '&olt_id=' + oltSelect.value;
        }
    }
    
    fetch('<?php echo e(route("admin.odps.generate-code")); ?>?' + params)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            document.getElementById('code').value = data.code;
        });
}
</script>
<script>
$(function() {
    // Initialize map
    var defaultLat = <?php echo e(old('latitude', -7.9666)); ?>;
    var defaultLng = <?php echo e(old('longitude', 110.6283)); ?>;
    
    var map = L.map('map').setView([defaultLat, defaultLng], 16);
    
    // Layer Satellite dari Google
    const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Satellite'
    });
    
    // Layer Hybrid (Satellite + Labels)
    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Hybrid'
    });
    
    // Layer Street dari Google
    const googleStreet = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Maps'
    });
    
    // Layer OpenStreetMap
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    });
    
    // Default to Hybrid view
    googleHybrid.addTo(map);
    
    // Layer control
    const baseMaps = {
        "🛰️ Satelit + Label": googleHybrid,
        "🛰️ Satelit": googleSat,
        "🗺️ Street": googleStreet,
        "🗺️ OpenStreetMap": osm
    };
    
    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);
    
    // Add scale control
    L.control.scale({ imperial: false }).addTo(map);
    
    let marker = null;
    
    // Custom icon for ODP
    const odpIcon = L.divIcon({
        className: 'custom-odp-marker',
        html: '<div style="background: #007bff; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-box"></i> ODP</div>',
        iconSize: [60, 30],
        iconAnchor: [30, 30]
    });
    
    // Add marker if coordinates exist
    if ($('#latitude').val() && $('#longitude').val()) {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
        map.setView([lat, lng], 18);
        
        // Enable dragging marker
        marker.on('dragend', function(e) {
            const latlng = e.target.getLatLng();
            $('#latitude').val(latlng.lat.toFixed(8));
            $('#longitude').val(latlng.lng.toFixed(8));
        });
    }
    
    // Click on map to set location
    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);
        
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { icon: odpIcon, draggable: true }).addTo(map);
            marker.on('dragend', function(e) {
                const latlng = e.target.getLatLng();
                $('#latitude').val(latlng.lat.toFixed(8));
                $('#longitude').val(latlng.lng.toFixed(8));
            });
        }
    });
    
    // Update marker when coordinates change manually
    $('#latitude, #longitude').on('change', function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const latlng = e.target.getLatLng();
                    $('#latitude').val(latlng.lat.toFixed(8));
                    $('#longitude').val(latlng.lng.toFixed(8));
                });
            }
            map.setView([lat, lng], 18);
        }
    });
    
    // Geolocation button
    if (navigator.geolocation) {
        const locateBtn = L.control({ position: 'topleft' });
        locateBtn.onAdd = function() {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<a href="#" title="Lokasi Saya" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:white;font-size:16px;"><i class="fas fa-crosshairs"></i></a>';
            div.onclick = function(e) {
                e.preventDefault();
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat, lng], 18);
                    
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
                        marker.on('dragend', function(e) {
                            const latlng = e.target.getLatLng();
                            $('#latitude').val(latlng.lat.toFixed(8));
                            $('#longitude').val(latlng.lng.toFixed(8));
                        });
                    }
                    
                    $('#latitude').val(lat.toFixed(8));
                    $('#longitude').val(lng.toFixed(8));
                });
                return false;
            };
            return div;
        };
        locateBtn.addTo(map);
    }
    
    // Enable code editing
    $('#code').on('dblclick', function() {
        $(this).prop('readonly', false).focus();
    });

    // ODC change handler - update port options and generate code
    $('#odc_id').on('change', function() {
        const odcId = $(this).val();
        const $portSelect = $('#odc_port');
        
        $portSelect.html('<option value="">Memuat...</option>');
        
        if (!odcId) {
            $portSelect.html('<option value="">-- Pilih ODC Dulu --</option>');
            $('#code').val('');
            return;
        }
        
        const $selected = $(this).find(':selected');
        const totalPorts = parseInt($selected.data('total-ports')) || 12;
        
        // Get used ports from server
        $.get('<?php echo e(route("admin.odps.by-odc")); ?>', { odc_id: odcId }, function(usedOdps) {
            const usedPorts = usedOdps.map(o => o.odc_port);
            
            let options = '<option value="">-- Pilih Port --</option>';
            for (let i = 1; i <= totalPorts; i++) {
                const isUsed = usedPorts.includes(i);
                options += `<option value="${i}" ${isUsed ? 'disabled' : ''}>${i} ${isUsed ? '(Terpakai)' : ''}</option>`;
            }
            $portSelect.html(options);
        }).fail(function() {
            // Fallback if endpoint fails
            let options = '<option value="">-- Pilih Port --</option>';
            for (let i = 1; i <= totalPorts; i++) {
                options += `<option value="${i}">${i}</option>`;
            }
            $portSelect.html(options);
        });
        
        generateCode();
    });
    
    // OLT change handler
    $('#olt_id').on('change', function() {
        const $selected = $(this).find(':selected');
        const ponPorts = parseInt($selected.data('pon-ports')) || 8;
        $('#olt_pon_port').attr('max', ponPorts);
        generateCode();
    });
    
    // Parent ODP change handler
    $('#parent_odp_id').on('change', function() {
        generateCode();
    });
    
    // Trigger change if pre-selected
    <?php if($selectedOdc): ?>
    $('#odc_id').trigger('change');
    <?php endif; ?>
    <?php if($selectedOlt): ?>
    $('#olt_id').trigger('change');
    <?php endif; ?>
    
    // Photo handling
    var photoInput = document.getElementById('photos');
    var photoPreview = document.getElementById('photo-preview');
    
    $('#photos').on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            $(this).next('.custom-file-label').text(files.length + ' foto dipilih');
            updatePhotoPreview();
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
                            '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
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
                $('#photos').next('.custom-file-label').text(dt.files.length + ' foto dipilih');
                updatePhotoPreview();
            }
        };
        input.click();
    });
});

// Remove photo from preview
window.removePhoto = function(idx) {
    var dt = new DataTransfer();
    var files = document.getElementById('photos').files;
    for (var i = 0; i < files.length; i++) {
        if (i !== idx) {
            dt.items.add(files[i]);
        }
    }
    document.getElementById('photos').files = dt.files;
    $('#photos').next('.custom-file-label').text(dt.files.length > 0 ? dt.files.length + ' foto dipilih' : 'Pilih foto...');
    
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
                        '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                    photoPreview.appendChild(div);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }
};
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projek\internet35\resources\views/admin/odps/create.blade.php ENDPATH**/ ?>