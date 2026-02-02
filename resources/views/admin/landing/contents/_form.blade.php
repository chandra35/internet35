<form id="contentForm" action="{{ $content ? route('admin.landing.contents.update', $content) : route('admin.landing.contents.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($content)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="section">Section <span class="text-danger">*</span></label>
                <select class="form-control select2" id="section" name="section" required>
                    <option value="">-- Pilih Section --</option>
                    @foreach($sections as $key => $label)
                        <option value="{{ $key }}" {{ ($content->section ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="key">Key <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="key" name="key" value="{{ $content->key ?? '' }}" required placeholder="contoh: main, header, sidebar">
                <small class="text-muted">Identifier unik, gunakan format snake_case</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="title">Judul</label>
        <input type="text" class="form-control" id="title" name="title" value="{{ $content->title ?? '' }}">
    </div>

    <div class="form-group">
        <label for="subtitle">Subtitle</label>
        <input type="text" class="form-control" id="subtitle" name="subtitle" value="{{ $content->subtitle ?? '' }}">
    </div>

    <div class="form-group">
        <label for="content">Konten</label>
        <textarea class="form-control" id="content" name="content" rows="5">{{ $content->content ?? '' }}</textarea>
        <small class="text-muted">Anda dapat menggunakan tag HTML jika diperlukan</small>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="icon">Icon (Font Awesome)</label>
                <input type="text" class="form-control" id="icon" name="icon" value="{{ $content->icon ?? '' }}" placeholder="fas fa-star">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="image">Gambar</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                    <label class="custom-file-label" for="image">Pilih file...</label>
                </div>
                @if($content && $content->image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/contents/' . $content->image) }}" class="img-thumbnail" style="max-height:80px;">
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="link">Link URL</label>
                <input type="text" class="form-control" id="link" name="link" value="{{ $content->link ?? '' }}" placeholder="https://... atau #section">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="link_text">Teks Link</label>
                <input type="text" class="form-control" id="link_text" name="link_text" value="{{ $content->link_text ?? '' }}" placeholder="Klik di sini">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="order">Urutan</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ $content->order ?? 0 }}" min="0">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ (!$content || $content->is_active) ? 'checked' : '' }}>
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
