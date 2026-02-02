<form id="packageForm" action="{{ $package ? route('admin.landing.packages.update', $package) : route('admin.landing.packages.store') }}" method="POST">
    @csrf
    @if($package)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name">Nama Paket <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $package->name ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" rows="2">{{ $package->description ?? '' }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="price">Harga (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" name="price" value="{{ $package->price ?? '' }}" min="0" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="period">Periode</label>
                        <select class="form-control select2" id="period" name="period">
                            <option value="bulan" {{ ($package->period ?? 'bulan') == 'bulan' ? 'selected' : '' }}>Bulan</option>
                            <option value="tahun" {{ ($package->period ?? '') == 'tahun' ? 'selected' : '' }}>Tahun</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="speed">Kecepatan (Mbps) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="speed" name="speed" value="{{ $package->speed ?? '' }}" placeholder="10" required>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Fitur Paket</label>
                <div id="featuresContainer">
                    @if($package && is_array($package->features))
                        @foreach($package->features as $feature)
                        <div class="input-group mb-2 feature-item">
                            <input type="text" class="form-control" name="features[]" value="{{ $feature }}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-danger btn-remove-feature"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addFeature">
                    <i class="fas fa-plus mr-1"></i> Tambah Fitur
                </button>
            </div>

            <div class="form-group">
                <label for="order">Urutan</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ $package->order ?? 0 }}" min="0">
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_popular" name="is_popular" value="1" {{ ($package->is_popular ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_popular">Tandai sebagai Popular</label>
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ (!$package || $package->is_active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Aktif</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer px-0 pb-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
    </div>
</form>
