<form id="faqForm" action="{{ $faq ? route('admin.landing.faqs.update', $faq) : route('admin.landing.faqs.store') }}" method="POST">
    @csrf
    @if($faq)
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="question">Pertanyaan <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="question" name="question" value="{{ $faq->question ?? '' }}" required>
    </div>

    <div class="form-group">
        <label for="answer">Jawaban <span class="text-danger">*</span></label>
        <textarea class="form-control" id="answer" name="answer" rows="5" required>{{ $faq->answer ?? '' }}</textarea>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="category">Kategori</label>
                <input type="text" class="form-control" id="category" name="category" value="{{ $faq->category ?? '' }}" placeholder="Umum, Teknis, Pembayaran...">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="order">Urutan</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ $faq->order ?? 0 }}" min="0">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ (!$faq || $faq->is_active) ? 'checked' : '' }}>
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
