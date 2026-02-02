<form id="routerForm" action="{{ $router ? route('admin.routers.update', $router) : route('admin.routers.store') }}" method="POST">
    @csrf
    @if($router)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name">Nama Router <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $router?->name }}" required>
                <small class="text-muted">Nama untuk identifikasi router</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="host">Host / IP Address <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="host" name="host" value="{{ $router?->host }}" placeholder="192.168.1.1" required>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="api_port">API Port <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="api_port" name="api_port" value="{{ $router?->api_port ?? 8728 }}" min="1" max="65535" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="api_ssl_port">API SSL Port</label>
                <input type="number" class="form-control" id="api_ssl_port" name="api_ssl_port" value="{{ $router?->api_ssl_port ?? 8729 }}" min="1" max="65535">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="use_ssl" name="use_ssl" value="1" {{ $router?->use_ssl ? 'checked' : '' }}>
                    <label class="custom-control-label" for="use_ssl">Gunakan SSL</label>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="username">Username <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" value="{{ $router?->username }}" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="password">Password @if(!$router)<span class="text-danger">*</span>@endif</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" {{ $router ? '' : 'required' }}>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                @if($router)
                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <button type="button" class="btn btn-info mb-3" id="btnTestConnection">
                <i class="fas fa-plug mr-1"></i> Test Koneksi
            </button>
        </div>
    </div>

    @if(!auth()->user()->hasRole('admin-pop'))
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="pop_id">Pemilik POP</label>
                <select class="form-control select2" id="pop_id" name="pop_id">
                    <option value="">-- Pilih POP --</option>
                    @foreach($pops as $pop)
                        <option value="{{ $pop->id }}" {{ $router?->pop_id == $pop->id ? 'selected' : '' }}>
                            {{ $pop->name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Admin POP yang bertanggung jawab atas router ini</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ $router ? ($router->is_active ? 'checked' : '') : 'checked' }}>
                    <label class="custom-control-label" for="is_active">Router Aktif</label>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="form-group">
        <label for="notes">Catatan</label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ $router?->notes }}</textarea>
    </div>

    @if($router)
    <div class="card bg-light">
        <div class="card-body">
            <h6 class="card-title"><i class="fas fa-info-circle mr-1"></i> Informasi Router</h6>
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Identity:</small>
                    <p class="mb-1">{{ $router->identity ?? '-' }}</p>
                    <small class="text-muted">ROS Version:</small>
                    <p class="mb-1">{{ $router->ros_version ?? '-' }}</p>
                    <small class="text-muted">Board:</small>
                    <p class="mb-1">{{ $router->board_name ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">Architecture:</small>
                    <p class="mb-1">{{ $router->architecture ?? '-' }}</p>
                    <small class="text-muted">CPU:</small>
                    <p class="mb-1">{{ $router->cpu ?? '-' }}</p>
                    <small class="text-muted">Uptime:</small>
                    <p class="mb-1">{{ $router->uptime ?? '-' }}</p>
                </div>
            </div>
            <small class="text-muted">Terakhir Terhubung:</small>
            <p class="mb-0">{{ $router->last_connected_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
        </div>
    </div>
    @endif

    <div class="modal-footer px-0 pb-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
    </div>
</form>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
