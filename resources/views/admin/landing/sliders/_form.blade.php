<form id="sliderForm" action="{{ $slider ? route('admin.landing.sliders.update', $slider) : route('admin.landing.sliders.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($slider)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label for="title">Judul <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="{{ $slider->title ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="subtitle">Subjudul</label>
                <input type="text" class="form-control" id="subtitle" name="subtitle" value="{{ $slider->subtitle ?? '' }}">
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $slider->description ?? '' }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="link">Link URL</label>
                        <input type="url" class="form-control" id="link" name="link" value="{{ $slider->link ?? '' }}" placeholder="https://...">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="link_text">Teks Link</label>
                        <input type="text" class="form-control" id="link_text" name="link_text" value="{{ $slider->link_text ?? '' }}" placeholder="Selengkapnya">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="image">Gambar @if(!$slider)<span class="text-danger">*</span>@endif</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*" {{ !$slider ? 'required' : '' }}>
                    <label class="custom-file-label" for="image">Pilih file...</label>
                </div>
                <small class="text-muted">Max 2MB. Format: JPG, PNG, WebP</small>
                <div class="mt-2">
                    <img id="imagePreview" src="{{ $slider && $slider->image ? asset('storage/sliders/' . $slider->image) : '' }}" 
                         class="img-thumbnail" style="max-height:150px;{{ !$slider || !$slider->image ? 'display:none;' : '' }}">
                </div>
            </div>

            <div class="form-group">
                <label for="order">Urutan</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ $slider->order ?? 0 }}" min="0">
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ (!$slider || $slider->is_active) ? 'checked' : '' }}>
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
