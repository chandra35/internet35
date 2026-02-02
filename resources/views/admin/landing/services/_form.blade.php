<form id="serviceForm" action="{{ $service ? route('admin.landing.services.update', $service) : route('admin.landing.services.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($service)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label for="title">Judul <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="{{ $service->title ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="4" required>{{ $service->description ?? '' }}</textarea>
            </div>

            <div class="form-group">
                <label for="icon">Icon (Font Awesome)</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="icon" name="icon" value="{{ $service->icon ?? '' }}" placeholder="fas fa-wifi">
                    <div class="input-group-append">
                        <span class="input-group-text" id="iconPreview">
                            <i class="{{ $service->icon ?? 'fas fa-question' }} fa-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted">Contoh: fas fa-wifi, fas fa-headset, fas fa-bolt</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="image">Gambar (Opsional)</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                    <label class="custom-file-label" for="image">Pilih file...</label>
                </div>
                <small class="text-muted">Max 2MB</small>
            </div>

            <div class="form-group">
                <label for="order">Urutan</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ $service->order ?? 0 }}" min="0">
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ (!$service || $service->is_active) ? 'checked' : '' }}>
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

<script>
    bsCustomFileInput.init();
</script>
