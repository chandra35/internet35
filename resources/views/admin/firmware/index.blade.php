@extends('layouts.admin')

@section('title', 'Manajemen Firmware')
@section('page-title', 'Manajemen Firmware')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Firmware</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-7">
        {{-- Daftar firmware yang sudah diupload --}}
        <div class="card card-dark card-outline">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title"><i class="fas fa-archive mr-2"></i>Daftar Firmware</h3>
                <div class="ml-auto text-muted small">Total: {{ $firmwares->flatten()->count() }} file</div>
            </div>
            <div class="card-body p-0">
                @if($firmwares->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-microchip fa-3x mb-3 d-block opacity-50"></i>
                        Belum ada firmware diupload.
                    </div>
                @else
                    @foreach($firmwares as $brand => $files)
                        <div class="brand-section">
                            <div class="px-3 py-2 bg-dark text-white d-flex align-items-center">
                                <strong class="text-uppercase">{{ $brand }}</strong>
                                <span class="badge badge-secondary ml-2">{{ $files->count() }}</span>
                            </div>
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Versi</th>
                                        <th>Model</th>
                                        <th>File Asli</th>
                                        <th>Ukuran</th>
                                        <th>Catatan</th>
                                        <th>Upload</th>
                                        <th class="text-center" width="80">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($files as $fw)
                                    <tr>
                                        <td><code>{{ $fw->version }}</code></td>
                                        <td>{{ $fw->model_pattern ?: '<em class="text-muted">semua model</em>' }}</td>
                                        <td class="text-truncate" style="max-width:160px" title="{{ $fw->original_name }}">
                                            {{ $fw->original_name }}
                                        </td>
                                        <td class="text-nowrap">{{ $fw->file_size_human }}</td>
                                        <td class="small text-muted">{{ Str::limit($fw->notes, 40) }}</td>
                                        <td class="small text-muted text-nowrap">{{ $fw->created_at->diffForHumans() }}</td>
                                        <td class="text-center text-nowrap">
                                            <a href="{{ route('admin.firmware.download', $fw) }}" class="btn btn-xs btn-outline-secondary" title="Download" target="_blank">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <form action="{{ route('admin.firmware.destroy', $fw) }}" method="POST" class="d-inline form-delete-fw">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        {{-- Upload form --}}
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload Firmware Baru</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif

                <form action="{{ route('admin.firmware.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>File Firmware <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="fw-file-input" name="file" required
                                       accept=".bin,.img,.tar,.gz,.zip,.ubi,.trx,.fw,application/octet-stream">
                                <label class="custom-file-label" for="fw-file-input" id="fw-file-label">Pilih file...</label>
                            </div>
                        </div>
                        <small class="text-muted">Format: .bin .img .tar .gz .zip .ubi .trx .fw — Maks 128 MB</small>
                        <div id="fw-detect-result" class="mt-1" style="display:none">
                            <span id="fw-detect-badge" class="badge badge-secondary">
                                <i class="fas fa-spinner fa-spin mr-1"></i>Scanning...
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Brand <span class="text-danger">*</span></label>
                        <select id="fw-brand" name="brand" class="form-control" required>
                            <option value="">-- Pilih Brand --</option>
                            @foreach(['huawei','zte','fiberhome','nokia','tp-link','sercomm','dzs','mikrotik','calix'] as $b)
                                <option value="{{ $b }}" {{ old('brand') == $b ? 'selected' : '' }}>{{ ucfirst($b) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Pola Model <small class="text-muted">(opsional)</small></label>
                        <input id="fw-model" type="text" name="model_pattern" class="form-control" value="{{ old('model_pattern') }}"
                               placeholder="Contoh: HG8145V5, HG8245*, kosong = semua model">
                        <small class="text-muted">Gunakan * di akhir untuk prefix match. Kosongkan untuk semua model brand ini.</small>
                    </div>

                    <div class="form-group">
                        <label>Versi Firmware <span class="text-danger">*</span></label>
                        <input id="fw-version" type="text" name="version" class="form-control" value="{{ old('version') }}"
                               placeholder="Contoh: V5R021C10S030" required>
                        <small id="fw-version-hint" class="text-muted" style="display:none"></small>
                    </div>

                    <div class="form-group">
                        <label>Catatan <small class="text-muted">(opsional)</small></label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Changelog, keterangan, dsb...">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-cloud-upload-alt mr-1"></i>Upload Firmware
                    </button>
                </form>
            </div>
        </div>

        <div class="card card-light card-outline">
            <div class="card-header">
                <h3 class="card-title text-muted"><i class="fas fa-info-circle mr-1"></i>Petunjuk</h3>
            </div>
            <div class="card-body small text-muted">
                <ul class="pl-3 mb-0">
                    <li>File disimpan di server dan bisa dipilih saat upgrade firmware ONU via TR-069.</li>
                    <li>ONU mengunduh file langsung dari server ini via HTTP.</li>
                    <li>Pastikan IP server (<code>{{ parse_url(config('app.url'), PHP_URL_HOST) }}</code>) bisa dicapai dari jaringan ONU.</li>
                    <li>Untuk model spesifik, isi <em>pola model</em>. Gunakan * untuk prefix (misal <code>HG8145*</code> cocok untuk semua HG8145x).</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
// ---------------------------------------------------------------
// Firmware scanner: nama file dulu, lalu scan binary via Python
// ---------------------------------------------------------------
var scanUrl = '{{ route("admin.firmware.scan") }}';

// Dual binding: jQuery change + native input event (fallback untuk browser tertentu)
document.getElementById('fw-file-input').addEventListener('change', handleFileChange);

function handleFileChange() {
    var fileInput = document.getElementById('fw-file-input');
    if (!fileInput.files || !fileInput.files.length) return;

    var file = fileInput.files[0];
    var raw  = file.name;

    // Update label
    document.getElementById('fw-file-label').textContent = raw;

    // Reset badge
    $('#fw-detect-result').hide();
    $('#fw-version-hint').hide();

    var name     = raw.replace(/\.[^.]+$/, '');
    var detected = detectFromFilename(name);

    if (detected.version || detected.brand) {
        applyDetected(detected, 'nama file');
    }

    // Scan binary di background
    showScanningBadge();
    scanBinary(file);
}

function detectFromFilename(name) {
    var BP = [
        { re: /\b(HG8\d{3}[A-Z0-9]*|MA5\d{3}[A-Z0-9]*|EG8\d{3}[A-Z0-9]*|HN8\d{3}[A-Z0-9]*)/i, brand: 'huawei',    mr: /\b(HG8\d{3}[A-Z0-9]*|MA5\d{3}[A-Z0-9]*|EG8\d{3}[A-Z0-9]*|HN8\d{3}[A-Z0-9]*)/i },
        { re: /\b(ZXHN[-_ ]?[A-Z0-9]+|F6[0-9]{2}[A-Z]?\d?)/i,                                    brand: 'zte',       mr: /\b(ZXHN[-_ ]?[A-Z0-9]+|F[0-9]{3}[A-Z]?\w*)/i },
        { re: /\b(AN\d{4}[-A-Z0-9]*|HG6\d{3}[A-Z0-9]*|AN5\d{3}[A-Z0-9]*)/i,                     brand: 'fiberhome', mr: /\b(AN\d{4}[-A-Z0-9]*|HG6\d{3}[A-Z0-9]*)/i },
        { re: /\b(G-\d{4}[A-Z0-9]*|BONT\d+)/i,                                                    brand: 'nokia',     mr: /\b(G-\d{4}[A-Z0-9-]*)/i },
    ];
    var VP = [
        /\b(V\d+R\d+C\d+S\d+)\b/i, /\b(V\d+R\d{2,3}C\d{2})\b/i,
        /\b(RP\d{4,})\b/i, /[_-](V\d+\.\d+[\.\d]*[A-Z0-9]{0,6})/i,
    ];
    var res = { brand: null, model: null, version: null };
    for (var i = 0; i < BP.length; i++) {
        if (BP[i].re.test(name)) {
            res.brand = BP[i].brand;
            var m = name.match(BP[i].mr);
            if (m) res.model = m[1];
            break;
        }
    }
    for (var j = 0; j < VP.length; j++) {
        var v = name.match(VP[j]);
        if (v) { res.version = v[1].toUpperCase(); break; }
    }
    return res;
}

function showScanningBadge() {
    $('#fw-detect-result').show();
    $('#fw-detect-badge').removeClass('badge-success badge-danger badge-secondary')
        .addClass('badge-warning')
        .html('<i class="fas fa-spinner fa-spin mr-1"></i>Scanning isi file...');
}

function scanBinary(file) {
    // Kirim hanya 4MB pertama — cukup untuk deteksi, hemat bandwidth & bypass limit
    var SCAN_MAX = 4 * 1024 * 1024;
    var slice    = file.size > SCAN_MAX ? file.slice(0, SCAN_MAX) : file;
    var partial  = new File([slice], file.name, { type: file.type });

    var fd = new FormData();
    fd.append('file', partial);
    fd.append('_token', '{{ csrf_token() }}');

    $.ajax({ url: scanUrl, type: 'POST', data: fd, processData: false, contentType: false, timeout: 30000 })
    .done(function(res) {
        if (res.error) {
            $('#fw-detect-badge').removeClass('badge-warning').addClass('badge-secondary')
                .html('<i class="fas fa-exclamation-circle mr-1"></i>Scan gagal: ' + $('<div>').text(res.error).html());
            return;
        }
        // Binary scan override: selalu update versi jika lebih spesifik
        var cur = $('#fw-version').val();
        if (res.version && (!cur || res.version.length >= cur.length)) {
            $('#fw-version').val(res.version.toUpperCase());
        }
        if (res.brand && !$('#fw-brand').val())  $('#fw-brand').val(res.brand);
        if (res.model && !$('#fw-model').val())  $('#fw-model').val(res.model);

        var extra = res.extra && res.extra.length ? ' <span class="text-muted small">(juga ditemukan: ' + res.extra.slice(0,3).join(', ') + ')</span>' : '';
        $('#fw-detect-badge').removeClass('badge-warning').addClass('badge-success')
            .html('<i class="fas fa-magic mr-1"></i>Terdeteksi dari isi binary' + extra);
    })
    .fail(function() {
        $('#fw-detect-badge').removeClass('badge-warning').addClass('badge-secondary')
            .html('<i class="fas fa-exclamation-circle mr-1"></i>Scan binary timeout/error, gunakan deteksi nama file');
    });
}

function applyDetected(res, source) {
    if (res.brand && !$('#fw-brand').val()) $('#fw-brand').val(res.brand);
    if (res.model && !$('#fw-model').val()) $('#fw-model').val(res.model);
    if (res.version && !$('#fw-version').val()) $('#fw-version').val(res.version);
}

// Reset hint jika version diedit manual
$('#fw-version').on('input', function() { $('#fw-version-hint').hide(); });

// Konfirmasi hapus
$(document).on('submit', '.form-delete-fw', function(e) {
    e.preventDefault();
    var form = this;
    Swal.fire({
        title: 'Hapus Firmware?',
        text: 'File akan dihapus permanen dari server.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
    }).then(function(result) {
        if (result.isConfirmed) form.submit();
    });
});
</script>
@endpush
