<form id="packageForm" action="{{ $package ? route('admin.packages.update', $package) : route('admin.packages.store') }}" method="POST">
    @csrf
    @if($package)
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Router <span class="text-danger">*</span></label>
                <select name="router_id" class="form-control select2" id="routerSelect" {{ $package ? 'disabled' : 'required' }}>
                    <option value="">-- Pilih Router --</option>
                    @foreach($routers as $router)
                    <option value="{{ $router->id }}" {{ ($selectedRouter ?? old('router_id')) == $router->id ? 'selected' : '' }}>
                        {{ $router->name }} ({{ $router->identity ?? $router->host }})
                    </option>
                    @endforeach
                </select>
                @if($package)
                <input type="hidden" name="router_id" value="{{ $package->router_id }}">
                <small class="text-muted">Router tidak dapat diubah setelah dibuat</small>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Mikrotik Profile Name <span class="text-danger">*</span></label>
                <input type="text" name="mikrotik_profile_name" class="form-control" required
                       value="{{ $package->mikrotik_profile_name ?? old('mikrotik_profile_name') }}"
                       placeholder="Contoh: 10M, PPP-10Mbps">
                <small class="text-muted">Nama profile yang akan digunakan di Mikrotik</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Nama Paket (Display) <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required
                       value="{{ $package->name ?? old('name') }}"
                       placeholder="Contoh: Paket 10 Mbps">
                <small class="text-muted">Nama yang ditampilkan ke pelanggan</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Rate Limit</label>
                <input type="text" name="rate_limit" class="form-control"
                       value="{{ $package->rate_limit ?? old('rate_limit') }}"
                       placeholder="Contoh: 10M/10M atau 10M/10M 5M/5M 10M/10M 10/10">
                <small class="text-muted">Format: rx/tx atau dengan burst</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Local Address</label>
                <input type="text" name="local_address" class="form-control" id="localAddress"
                       value="{{ $package->local_address ?? old('local_address') }}"
                       placeholder="IP atau nama pool" list="poolList">
                <datalist id="poolList">
                    @foreach($pools as $pool)
                    <option value="{{ $pool['name'] }}">{{ $pool['ranges'] ?? '' }}</option>
                    @endforeach
                </datalist>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Remote Address</label>
                <input type="text" name="remote_address" class="form-control"
                       value="{{ $package->remote_address ?? old('remote_address') }}"
                       placeholder="IP atau nama pool" list="poolList">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Harga (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control" required min="0"
                       value="{{ $package->price ?? old('price', 0) }}"
                       placeholder="0">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Masa Berlaku (Hari) <span class="text-danger">*</span></label>
                <input type="number" name="validity_days" class="form-control" required min="1"
                       value="{{ $package->validity_days ?? old('validity_days', 30) }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>DNS Server</label>
                <input type="text" name="dns_server" class="form-control"
                       value="{{ $package->dns_server ?? old('dns_server') }}"
                       placeholder="8.8.8.8,8.8.4.4">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Parent Queue</label>
                <input type="text" name="parent_queue" class="form-control"
                       value="{{ $package->parent_queue ?? old('parent_queue') }}"
                       placeholder="Nama parent queue">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Address List</label>
                <input type="text" name="address_list" class="form-control"
                       value="{{ $package->address_list ?? old('address_list') }}"
                       placeholder="Nama address list">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="description" class="form-control" rows="2"
                  placeholder="Deskripsi paket untuk pelanggan">{{ $package->description ?? old('description') }}</textarea>
    </div>

    <div class="form-group">
        <label>Komentar Mikrotik</label>
        <input type="text" name="mikrotik_comment" class="form-control"
               value="{{ $package->mikrotik_comment ?? old('mikrotik_comment') }}"
               placeholder="Komentar yang disimpan di Mikrotik">
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                           {{ ($package->is_active ?? true) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Aktif</label>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_public" name="is_public" value="1"
                           {{ ($package->is_public ?? true) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_public">Tampilkan di Portal</label>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="only_one" name="only_one" value="1"
                           {{ ($package->only_one ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="only_one">Only One (PPP)</label>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="form-group">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="push_to_mikrotik" name="push_to_mikrotik" value="1">
            <label class="custom-control-label" for="push_to_mikrotik">
                <strong>Push ke Mikrotik</strong>
                <small class="text-muted d-block">Langsung buat/update profile di Mikrotik saat menyimpan</small>
            </label>
        </div>
    </div>

    <div class="modal-footer px-0 pb-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i>Simpan
        </button>
    </div>
</form>

<script>
// Auto-copy profile name to display name if empty
$('input[name="mikrotik_profile_name"]').on('blur', function() {
    const profileName = $(this).val();
    const displayName = $('input[name="name"]');
    if (profileName && !displayName.val()) {
        displayName.val(profileName);
    }
});

// Load pools when router changes
$('#routerSelect').on('change', function() {
    const routerId = $(this).val();
    if (!routerId) return;
    
    $.get(`{{ url('admin/packages/router') }}/${routerId}/pools`, function(response) {
        if (response.success) {
            const datalist = $('#poolList');
            datalist.empty();
            response.data.forEach(function(pool) {
                datalist.append(`<option value="${pool.name}">${pool.ranges || ''}</option>`);
            });
        }
    });
});
</script>
