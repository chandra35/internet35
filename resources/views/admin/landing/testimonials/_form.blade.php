<form id="testimonialForm" action="{{ $testimonial ? route('admin.landing.testimonials.update', $testimonial) : route('admin.landing.testimonials.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($testimonial)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label for="name">Nama <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $testimonial->name ?? '' }}" required>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="position">Jabatan</label>
                        <input type="text" class="form-control" id="position" name="position" value="{{ $testimonial->position ?? '' }}" placeholder="CEO">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="company">Perusahaan</label>
                        <input type="text" class="form-control" id="company" name="company" value="{{ $testimonial->company ?? '' }}" placeholder="PT Contoh">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="content">Testimoni <span class="text-danger">*</span></label>
                <textarea class="form-control" id="content" name="content" rows="4" required>{{ $testimonial->content ?? '' }}</textarea>
            </div>

            <div class="form-group">
                <label>Rating <span class="text-danger">*</span></label>
                <div class="rating-input">
                    @for($i = 5; $i >= 1; $i--)
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" id="rating{{ $i }}" name="rating" value="{{ $i }}" {{ ($testimonial->rating ?? 5) == $i ? 'checked' : '' }} required>
                        <label class="custom-control-label" for="rating{{ $i }}">
                            @for($j = 1; $j <= $i; $j++)
                                <i class="fas fa-star text-warning"></i>
                            @endfor
                        </label>
                    </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="image">Foto</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                    <label class="custom-file-label" for="image">Pilih file...</label>
                </div>
                <small class="text-muted">Max 1MB</small>
                <div class="mt-2 text-center">
                    <img id="imagePreview" src="{{ $testimonial && $testimonial->image ? asset('storage/testimonials/' . $testimonial->image) : '' }}" 
                         class="img-circle" style="width:100px;height:100px;object-fit:cover;{{ !$testimonial || !$testimonial->image ? 'display:none;' : '' }}">
                </div>
            </div>

            <div class="form-group">
                <label for="order">Urutan</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ $testimonial->order ?? 0 }}" min="0">
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ (!$testimonial || $testimonial->is_active) ? 'checked' : '' }}>
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
