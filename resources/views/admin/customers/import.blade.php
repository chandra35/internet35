@extends('layouts.admin')

@section('title', 'Import Pelanggan')

@section('page-title', 'Import Pelanggan dari Excel')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Pelanggan</a></li>
    <li class="breadcrumb-item active">Import</li>
@endsection

@push('css')
<style>
    .import-dropzone {
        border: 2px dashed #007bff;
        border-radius: 10px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9ff;
    }
    .import-dropzone:hover,
    .import-dropzone.dragover {
        background: #e8ecff;
        border-color: #0056b3;
        transform: scale(1.01);
    }
    .import-dropzone .icon {
        font-size: 48px;
        color: #007bff;
        margin-bottom: 15px;
    }
    .import-dropzone .file-selected { color: #28a745; }
    .import-dropzone .file-selected i { color: #28a745; }
    .step-number {
        width: 32px; height: 32px; border-radius: 50%; background: #007bff;
        color: white; display: inline-flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 14px; flex-shrink: 0;
    }
    .step-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .preview-table th { font-size: 0.8rem; white-space: nowrap; }
    .preview-table td { font-size: 0.85rem; }
    .preview-table tr.row-valid { background: #d4edda; }
    .preview-table tr.row-error { background: #f8d7da; }
    .preview-table tr.row-skipped { background: #fff3cd; }
    .progress-section { display: none; }
    .import-progress .progress { height: 25px; border-radius: 12px; }
    .import-progress .progress-bar { 
        font-size: 0.85rem; font-weight: 600; 
        transition: width 0.3s ease;
    }
    .wizard-step { display: none; }
    .wizard-step.active { display: block; }
    .summary-badge { font-size: 1rem; padding: 10px 18px; }
</style>
@endpush

@section('content')
<div class="row">
    {{-- Main import area --}}
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-import mr-2"></i>Import Pelanggan
                </h3>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-triangle mr-1"></i> {!! session('error') !!}
                </div>
                @endif

                {{-- Superadmin POP info --}}
                @if(isset($popUsers))
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Sebagai <strong>Superadmin</strong>, pilih POP tujuan import terlebih dahulu.
                </div>
                @endif

                {{-- ============ STEP 1: Upload ============ --}}
                <div class="wizard-step active" id="step1">
                    <h5 class="mb-3"><span class="step-number">1</span> Upload File Excel</h5>

                    @if(isset($popUsers))
                    <div class="form-group">
                        <label for="pop_id"><i class="fas fa-server mr-1"></i> Pilih POP <span class="text-danger">*</span></label>
                        <select name="pop_id" id="pop_id" class="form-control select2" data-placeholder="-- Pilih POP --" required>
                            <option value="">-- Pilih POP --</option>
                            @foreach($popUsers as $pop)
                            <option value="{{ $pop->id }}" {{ (old('pop_id', $popId) == $pop->id) ? 'selected' : '' }}>
                                {{ $pop->name }}
                                @if($pop->popSetting)
                                    ({{ $pop->popSetting->pop_code ?? $pop->popSetting->pop_prefix ?? '-' }})
                                @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <hr>
                    @endif

                    <div class="form-group">
                        <div class="import-dropzone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                            <div id="dropzoneContent">
                                <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <h5>Klik atau seret file Excel ke sini</h5>
                                <p class="text-muted mb-0">Format: .xlsx, .xls, .csv (maks. 5MB)</p>
                            </div>
                        </div>
                        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                        <button type="button" class="btn btn-info btn-lg" id="btnPreview" disabled>
                            <i class="fas fa-search mr-1"></i> Preview Data
                        </button>
                    </div>
                </div>

                {{-- ============ STEP 2: Preview ============ --}}
                <div class="wizard-step" id="step2">
                    <h5 class="mb-3"><span class="step-number">2</span> Preview Data Import</h5>

                    {{-- Summary badges --}}
                    <div class="d-flex flex-wrap mb-3" id="previewSummary">
                        <span class="badge badge-success summary-badge mr-2 mb-1" id="badgeValid">
                            <i class="fas fa-check mr-1"></i> Valid: <span>0</span>
                        </span>
                        <span class="badge badge-danger summary-badge mr-2 mb-1" id="badgeError">
                            <i class="fas fa-times mr-1"></i> Error: <span>0</span>
                        </span>
                        <span class="badge badge-warning summary-badge mr-2 mb-1" id="badgeSkipped">
                            <i class="fas fa-forward mr-1"></i> Dilewati: <span>0</span>
                        </span>
                        <span class="badge badge-info summary-badge mb-1" id="badgeTotal">
                            <i class="fas fa-list mr-1"></i> Total: <span>0</span>
                        </span>
                    </div>

                    {{-- Preview table --}}
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-bordered preview-table" id="previewTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Status</th>
                                    <th>Nama</th>
                                    <th>Telepon</th>
                                    <th>Username</th>
                                    <th>Router</th>
                                    <th>Paket</th>
                                    <th>Biaya</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="previewBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button type="button" class="btn btn-default" id="btnBackToUpload">
                            <i class="fas fa-arrow-left mr-1"></i> Ganti File
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="btnStartImport" disabled>
                            <i class="fas fa-file-import mr-1"></i> Import <span id="importCount">0</span> Pelanggan
                        </button>
                    </div>
                </div>

                {{-- ============ STEP 3: Progress ============ --}}
                <div class="wizard-step" id="step3">
                    <h5 class="mb-3"><span class="step-number">3</span> Proses Import</h5>

                    <div class="import-progress mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span id="progressLabel">Memproses import...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" id="progressBar" style="width: 0%">
                            </div>
                        </div>
                    </div>

                    <div id="importResultArea" style="display:none;">
                        {{-- Result will be filled by JS --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- Import Results from session (after redirect) --}}
        @if(session('import_results'))
        @php $results = session('import_results'); @endphp
        <div class="card card-outline {{ $results['failed_count'] > 0 ? 'card-warning' : 'card-success' }}">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Hasil Import</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap mb-3">
                    <span class="badge badge-success summary-badge mr-2"><i class="fas fa-check mr-1"></i> Berhasil: {{ $results['success_count'] }}</span>
                    <span class="badge badge-danger summary-badge mr-2"><i class="fas fa-times mr-1"></i> Gagal: {{ $results['failed_count'] }}</span>
                    <span class="badge badge-warning summary-badge mr-2"><i class="fas fa-forward mr-1"></i> Dilewati: {{ $results['skipped_count'] }}</span>
                    <span class="badge badge-info summary-badge"><i class="fas fa-list mr-1"></i> Total: {{ $results['total_processed'] }}</span>
                </div>

                @if(!empty($results['imported_customers']))
                <h6 class="mt-3"><i class="fas fa-check-circle text-success mr-1"></i> Pelanggan Berhasil:</h6>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-striped">
                        <thead class="thead-light">
                            <tr><th>Baris</th><th>ID</th><th>Nama</th><th>Username PPPoE</th></tr>
                        </thead>
                        <tbody>
                            @foreach($results['imported_customers'] as $cust)
                            <tr>
                                <td>{{ $cust['row'] }}</td>
                                <td><code>{{ $cust['customer_id'] }}</code></td>
                                <td>{{ $cust['name'] }}</td>
                                <td><code>{{ $cust['pppoe_username'] }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(!empty($results['errors']))
                <h6 class="mt-3"><i class="fas fa-exclamation-circle text-danger mr-1"></i> Detail Error:</h6>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr><th width="60">Baris</th><th width="150">Nama</th><th>Error</th></tr>
                        </thead>
                        <tbody>
                            @foreach($results['errors'] as $err)
                            <tr class="table-danger">
                                <td>{{ $err['row'] }}</td>
                                <td>{{ $err['name'] }}</td>
                                <td><small>{{ $err['error'] }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Download Template --}}
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-download mr-2"></i>Template Excel</h3>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                <p>Download template untuk format data yang benar.</p>
                <a href="{{ route('admin.customers.download-template', isset($popUsers) ? ['pop_id' => $popId] : []) }}" 
                   class="btn btn-success btn-block" id="btnDownloadTemplate">
                    <i class="fas fa-download mr-1"></i> Download Template
                </a>
                <small class="text-muted mt-2 d-block">Berisi contoh data &amp; referensi router/paket</small>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Panduan Import</h3>
            </div>
            <div class="card-body">
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div>Download template Excel</div>
                </div>
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div>Isi data, hapus baris contoh</div>
                </div>
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div>Upload &amp; preview sebelum import</div>
                </div>
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div>Konfirmasi dan proses import</div>
                </div>

                <hr>
                <h6><i class="fas fa-columns mr-1 text-danger"></i> Kolom Wajib:</h6>
                <div class="mb-2">
                    <span class="badge badge-danger">nama</span>
                    <span class="badge badge-danger">telepon</span>
                    <span class="badge badge-danger">pppoe_username</span>
                    <span class="badge badge-danger">pppoe_password</span>
                </div>

                <h6><i class="fas fa-columns mr-1 text-secondary"></i> Kolom Opsional:</h6>
                <div>
                    <span class="badge badge-secondary">router</span>
                    <span class="badge badge-secondary">paket</span>
                    <span class="badge badge-secondary">email</span>
                    <span class="badge badge-secondary">nik</span>
                    <span class="badge badge-secondary">jenis_kelamin</span>
                    <span class="badge badge-secondary">alamat</span>
                    <span class="badge badge-secondary">tipe_layanan</span>
                    <span class="badge badge-secondary">biaya_bulanan</span>
                    <span class="badge badge-secondary">biaya_instalasi</span>
                    <span class="badge badge-secondary">tanggal_instalasi</span>
                    <span class="badge badge-secondary">tanggal_tagihan</span>
                    <span class="badge badge-secondary">catatan</span>
                </div>

                <hr>
                <div class="callout callout-warning p-2">
                    <small>
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Catatan:</strong>
                        <ul class="mb-0 pl-3">
                            <li>Status import: <strong>Pending</strong></li>
                            <li>Tidak otomatis sync ke Mikrotik</li>
                            <li>Telepon duplikat di POP sama dilewati</li>
                            <li>Username PPPoE harus unik</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>

        {{-- Router & Package Reference --}}
        @if($routers->count() > 0)
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Referensi Router & Paket</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                @foreach($routers as $router)
                <div class="p-2 border-bottom">
                    <strong><i class="fas fa-server mr-1 text-primary"></i> {{ $router->name }}</strong>
                    <div class="ml-3 mt-1">
                        @php $routerPackages = $packages->where('router_id', $router->id); @endphp
                        @forelse($routerPackages as $pkg)
                        <span class="badge badge-info mr-1 mb-1">{{ $pkg->name }}</span>
                        @empty
                        <small class="text-muted">Tidak ada paket aktif</small>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const btnPreview = document.getElementById('btnPreview');
    const btnStartImport = document.getElementById('btnStartImport');
    let selectedFile = null;
    let validCount = 0;

    // ========== Drag & Drop ==========
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
        dropZone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); });
    });
    ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.add('dragover')));
    ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('dragover')));

    dropZone.addEventListener('drop', function(e) {
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) handleFileSelect(this.files[0]);
    });

    function handleFileSelect(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(ext)) {
            toastr.error('Format file tidak valid. Gunakan .xlsx, .xls, atau .csv');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            toastr.error('Ukuran file maksimal 5MB.');
            return;
        }
        selectedFile = file;
        const sizeKB = (file.size / 1024).toFixed(1);
        document.getElementById('dropzoneContent').innerHTML = `
            <div class="file-selected">
                <i class="fas fa-file-excel fa-3x mb-2"></i>
                <h5>${file.name}</h5>
                <p class="text-muted mb-0">${sizeKB} KB — Klik untuk ganti file</p>
            </div>`;
        btnPreview.disabled = false;
    }

    // ========== STEP 1 → STEP 2: Preview ==========
    btnPreview.addEventListener('click', function() {
        if (!selectedFile) return;

        @if(isset($popUsers))
        const popId = $('#pop_id').val();
        if (!popId) { toastr.error('Pilih POP terlebih dahulu'); return; }
        @endif

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', '{{ csrf_token() }}');
        @if(isset($popUsers))
        formData.append('pop_id', popId);
        @endif

        btnPreview.disabled = true;
        btnPreview.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Membaca file...';

        $.ajax({
            url: '{{ route("admin.customers.preview-import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showPreview(response);
                } else {
                    toastr.error(response.message || 'Gagal membaca file');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Gagal membaca file Excel';
                toastr.error(msg);
            },
            complete: function() {
                btnPreview.disabled = false;
                btnPreview.innerHTML = '<i class="fas fa-search mr-1"></i> Preview Data';
            }
        });
    });

    function showPreview(response) {
        const { preview, summary } = response;
        validCount = summary.valid;

        // Update summary badges
        $('#badgeValid span').text(summary.valid);
        $('#badgeError span').text(summary.errors);
        $('#badgeSkipped span').text(summary.skipped);
        $('#badgeTotal span').text(summary.total);
        $('#importCount').text(summary.valid);

        // Build table
        let html = '';
        preview.forEach(function(row) {
            const cls = row.status === 'valid' ? 'row-valid' : (row.status === 'error' ? 'row-error' : 'row-skipped');
            const icon = row.status === 'valid' 
                ? '<i class="fas fa-check-circle text-success"></i>' 
                : (row.status === 'error' ? '<i class="fas fa-times-circle text-danger"></i>' : '<i class="fas fa-forward text-warning"></i>');
            html += `<tr class="${cls}">
                <td>${row.row}</td>
                <td>${icon}</td>
                <td>${row.name}</td>
                <td>${row.phone}</td>
                <td><code>${row.username}</code></td>
                <td>${row.router}</td>
                <td>${row.package}</td>
                <td>${row.monthly_fee || '-'}</td>
                <td><small>${row.error || '✓ Siap import'}</small></td>
            </tr>`;
        });

        // Also show error-only rows from errors array (rows that threw exceptions before being added to preview)
        if (response.errors) {
            response.errors.forEach(function(err) {
                // Check if already in preview
                const exists = preview.find(p => p.row === err.row);
                if (!exists) {
                    html += `<tr class="row-error">
                        <td>${err.row}</td>
                        <td><i class="fas fa-times-circle text-danger"></i></td>
                        <td>${err.name}</td>
                        <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
                        <td><small>${err.error}</small></td>
                    </tr>`;
                }
            });
        }

        $('#previewBody').html(html);
        btnStartImport.disabled = (validCount === 0);

        // Switch to step 2
        $('.wizard-step').removeClass('active');
        $('#step2').addClass('active');
    }

    // ========== Back to Step 1 ==========
    document.getElementById('btnBackToUpload').addEventListener('click', function() {
        $('.wizard-step').removeClass('active');
        $('#step1').addClass('active');
    });

    // ========== STEP 2 → STEP 3: Import ==========
    btnStartImport.addEventListener('click', function() {
        if (validCount === 0) return;

        Swal.fire({
            title: 'Konfirmasi Import',
            html: `<p>Import <strong>${validCount}</strong> pelanggan?</p>
                   <small class="text-muted">Data yang error/duplikat akan dilewati otomatis.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-import mr-1"></i> Ya, Import',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#007bff',
        }).then((result) => {
            if (result.isConfirmed) {
                doImport();
            }
        });
    });

    function doImport() {
        // Switch to step 3
        $('.wizard-step').removeClass('active');
        $('#step3').addClass('active');

        // Animate progress
        let progress = 0;
        const progressBar = document.getElementById('progressBar');
        const progressLabel = document.getElementById('progressLabel');
        const progressPercent = document.getElementById('progressPercent');

        // Simulate progress while waiting for server
        const interval = setInterval(function() {
            if (progress < 85) {
                progress += Math.random() * 8;
                if (progress > 85) progress = 85;
                progressBar.style.width = progress + '%';
                progressPercent.textContent = Math.round(progress) + '%';
            }
        }, 300);

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', '{{ csrf_token() }}');
        @if(isset($popUsers))
        formData.append('pop_id', $('#pop_id').val());
        @endif

        $.ajax({
            url: '{{ route("admin.customers.process-import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                clearInterval(interval);
                // Complete progress
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-success');
                progressLabel.innerHTML = '<i class="fas fa-check-circle text-success mr-1"></i> Import selesai!';

                // Show button to view results
                $('#importResultArea').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-1"></i> ${response.message || 'Import berhasil!'}
                    </div>
                    <div class="text-center">
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-primary">
                            <i class="fas fa-users mr-1"></i> Lihat Daftar Pelanggan
                        </a>
                        <button type="button" class="btn btn-outline-info ml-2" onclick="location.reload()">
                            <i class="fas fa-redo mr-1"></i> Import Lagi
                        </button>
                    </div>
                `).show();
            },
            error: function(xhr) {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-danger');
                progressLabel.innerHTML = '<i class="fas fa-times-circle text-danger mr-1"></i> Import gagal';
                progressPercent.textContent = 'Error';

                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat import';
                $('#importResultArea').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-1"></i> ${msg}
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-redo mr-1"></i> Coba Lagi
                        </button>
                    </div>
                `).show();
            }
        });
    }

    @if(isset($popUsers))
    // Superadmin: Update template link & reload reference on POP change
    $('#pop_id').on('change', function() {
        const popId = $(this).val();
        const btn = document.getElementById('btnDownloadTemplate');
        if (popId) {
            btn.href = "{{ route('admin.customers.download-template') }}?pop_id=" + popId;
            window.location.href = "{{ route('admin.customers.import') }}?pop_id=" + popId;
        }
    });
    @endif
});
</script>
@endpush
