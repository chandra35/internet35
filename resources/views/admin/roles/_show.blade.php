<div class="role-detail">
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="info-box bg-gradient-primary">
                <span class="info-box-icon"><i class="fas fa-user-tag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Nama Role</span>
                    <span class="info-box-number">{{ $role->name }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box bg-gradient-success">
                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Jumlah User</span>
                    <span class="info-box-number">{{ $role->users_count }} user</span>
                </div>
            </div>
        </div>
    </div>

    @if($role->description)
    <div class="alert alert-light border mb-4">
        <i class="fas fa-info-circle mr-2 text-info"></i>
        <strong>Deskripsi:</strong> {{ $role->description }}
    </div>
    @endif

    <h6 class="mb-3">
        <i class="fas fa-key mr-2 text-primary"></i>
        Permissions ({{ $role->permissions->count() }})
    </h6>

    <div class="row">
        @foreach($permissionsByGroup as $group => $perms)
            @php
                $rolePermNames = $role->permissions->pluck('name')->toArray();
                $groupPerms = $perms->filter(function($p) use ($rolePermNames) {
                    return in_array($p->name, $rolePermNames);
                });
            @endphp
            @if($groupPerms->count() > 0)
            <div class="col-md-4 mb-3">
                <div class="card card-outline card-primary">
                    <div class="card-header py-2">
                        <strong><i class="fas fa-folder-open mr-1"></i> {{ ucfirst($group) }}</strong>
                        <span class="badge badge-primary float-right">{{ $groupPerms->count() }}</span>
                    </div>
                    <div class="card-body py-2">
                        @foreach($groupPerms as $perm)
                        <div class="mb-1">
                            <i class="fas fa-check-circle text-success mr-1"></i>
                            <code class="small">{{ $perm->name }}</code>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>

    @if($role->users->count() > 0)
    <hr>
    <h6 class="mb-3">
        <i class="fas fa-users mr-2 text-success"></i>
        Users dengan Role ini ({{ $role->users->count() }})
    </h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($role->users->take(10) as $user)
                <tr>
                    <td>
                        <img src="{{ $user->avatar_url }}" class="img-circle mr-2" style="width:25px;height:25px;">
                        {{ $user->name }}
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($role->users->count() > 10)
        <p class="text-muted text-center mb-0">
            <small>Menampilkan 10 dari {{ $role->users->count() }} user</small>
        </p>
        @endif
    </div>
    @endif

    <div class="mt-4 text-muted small">
        <i class="far fa-clock mr-1"></i> Dibuat: {{ $role->created_at->format('d M Y H:i') }}
        <span class="mx-2">|</span>
        <i class="far fa-edit mr-1"></i> Diupdate: {{ $role->updated_at->format('d M Y H:i') }}
    </div>
</div>
