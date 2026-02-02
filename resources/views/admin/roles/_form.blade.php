<form id="roleForm" action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST">
    @csrf
    @if($role)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name"><i class="fas fa-tag mr-1 text-primary"></i> Nama Role <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-lg" id="name" name="name" 
                       value="{{ $role->name ?? '' }}" {{ $role ? 'readonly' : 'required' }}
                       placeholder="Contoh: manager">
                @if(!$role)
                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Akan diconvert ke lowercase dan spasi menjadi dash</small>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left mr-1 text-primary"></i> Deskripsi</label>
                <input type="text" class="form-control form-control-lg" id="description" name="description" 
                       value="{{ $role->description ?? '' }}" placeholder="Deskripsi singkat role ini">
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0"><i class="fas fa-key mr-2 text-primary"></i>Permissions</h5>
            <small class="text-muted">Pilih hak akses untuk role ini</small>
        </div>
        <div class="d-flex align-items-center">
            <span class="badge badge-primary mr-3 px-3 py-2">
                <i class="fas fa-check-circle mr-1"></i>
                <span id="totalSelected">{{ isset($rolePermissions) ? count($rolePermissions) : 0 }}</span> / {{ $permissions->flatten()->count() }} dipilih
            </span>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="checkAllPermissions">
                <label class="custom-control-label font-weight-bold" for="checkAllPermissions">Pilih Semua</label>
            </div>
        </div>
    </div>

    <div class="row">
        @php $colorIndex = 0; @endphp
        @foreach($permissions as $group => $perms)
            @php 
                $groupSlug = Str::slug($group);
                $checkedCount = isset($rolePermissions) ? $perms->whereIn('name', $rolePermissions)->count() : 0;
                $colors = ['primary', 'success', 'info', 'warning', 'secondary', 'danger'];
                $color = $colors[$colorIndex % count($colors)];
            @endphp
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card card-outline card-{{ $color }} h-100 permission-group-card" data-group="{{ $groupSlug }}">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input check-all-group" 
                                   id="group_{{ $groupSlug }}" data-group="{{ $groupSlug }}"
                                   {{ $checkedCount === $perms->count() && $perms->count() > 0 ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold text-{{ $color }}" for="group_{{ $groupSlug }}">
                                <i class="fas fa-folder mr-1"></i> {{ ucfirst($group) }}
                            </label>
                        </div>
                        <div>
                            <span class="badge badge-{{ $color }} group-progress">{{ $checkedCount }}/{{ $perms->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body py-2" style="max-height: 200px; overflow-y: auto;">
                        @foreach($perms as $perm)
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input perm-{{ $groupSlug }}" 
                                       id="perm_{{ $perm->id }}" name="permissions[]" value="{{ $perm->name }}"
                                       {{ isset($rolePermissions) && in_array($perm->name, $rolePermissions) ? 'checked' : '' }}>
                                <label class="custom-control-label d-flex justify-content-between align-items-center" for="perm_{{ $perm->id }}">
                                    <code class="small">{{ $perm->name }}</code>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer py-1 px-2">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-{{ $color }}" style="width: {{ $perms->count() > 0 ? ($checkedCount / $perms->count()) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            @php $colorIndex++; @endphp
        @endforeach
    </div>

    <div class="modal-footer px-0 pb-0 mt-4">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan Role
        </button>
    </div>
</form>
