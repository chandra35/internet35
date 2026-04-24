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
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="fw-file-input" name="file" required>
                            <label class="custom-file-label" for="fw-file-input">Pilih file...</label>
                        </div>
                        <small class="text-muted">Format: .bin .img .tar .gz .zip .ubi .trx .fw — Maks 64 MB</small>
                        <div id="fw-detect-result" class="mt-1" style="display:none">
                            <span class="badge badge-success"><i class="fas fa-magic mr-1"></i>Terdeteksi otomatis dari nama file</span>
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

@section('scripts')
<script>
// ---------------------------------------------------------------
// Auto-detect version / brand / model dari nama file firmware
// ---------------------------------------------------------------
var BRAND_PATTERNS = [
    {
        re:      /\b(HG8\d{3}[A-Z0-9]*|MA5\d{3}[A-Z0-9]*|EG8\d{3}[A-Z0-9]*|HN8\d{3}[A-Z0-9]*)/i,
        brand:   'huawei',
        modelRe: /\b(HG8\d{3}[A-Z0-9]*|MA5\d{3}[A-Z0-9]*|EG8\d{3}[A-Z0-9]*|HN8\d{3}[A-Z0-9]*)/i,
    },
    {
        re:      /\b(ZXHN[-_ ]?[A-Z0-9]+|F6[0-9]{2}[A-Z]?\d?|F[0-9]{3}[A-Z])\b/i,
        brand:   'zte',
        modelRe: /\b(ZXHN[-_ ]?[A-Z0-9]+|F[0-9]{3}[A-Z]?\w*)/i,
    },
    {
        re:      /\b(AN\d{4}[-A-Z0-9]*|HG6\d{3}[A-Z0-9]*|AN5\d{3}[A-Z0-9]*)/i,
        brand:   'fiberhome',
        modelRe: /\b(AN\d{4}[-A-Z0-9]*|HG6\d{3}[A-Z0-9]*|AN5\d{3}[A-Z0-9]*)/i,
    },
    {
        re:      /\b(G-\d{4}[A-Z0-9]*|BONT[-]?\d+|G-010[A-Z0-9-]*)/i,
        brand:   'nokia',
        modelRe: /\b(G-\d{4}[A-Z0-9]*|G-010[A-Z0-9-]*)/i,
    },
    {
        re:      /\b(Archer[-_ ]?[A-Z0-9]+|TL-[A-Z0-9]+)/i,
        brand:   'tp-link',
        modelRe: /\b(Archer[-_ ]?[A-Z0-9]+|TL-[A-Z0-9]+)/i,
    },
];

var VERSION_PATTERNS = [
    { re: /\b(V\d+R\d+C\d+S\d+)\b/i,            hint: 'Huawei: VxRxxxCxxSxxx' },
    { re: /\b(V\d+R\d+C\d+)\b/i,                 hint: 'Huawei: VxRxxxCxx' },
    { re: /\b(RP\d{4,})\b/i,                      hint: 'FiberHome: RPxxxx' },
    { re: /[_-](V\d+\.\d+[\.\d]*[A-Z0-9]*)/i,    hint: 'ZTE/Nokia: Vx.x.x' },
    { re: /[_-](\d+\.\d+\.\d+[A-Z0-9._-]*)\b/,   hint: 'Generic: x.x.x' },
];

$('#fw-file-input').on('change', function() {
    var raw  = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(raw || 'Pilih file...');

    var name     = raw.replace(/\.[^.]+$/, '');
    var detected = 0;

    // --- Brand + Model ---
    if (!$('#fw-brand').val()) {
        for (var i = 0; i < BRAND_PATTERNS.length; i++) {
            var bp = BRAND_PATTERNS[i];
            if (bp.re.test(name)) {
                $('#fw-brand').val(bp.brand);
                if (!$('#fw-model').val() && bp.modelRe) {
                    var m = name.match(bp.modelRe);
                    if (m) $('#fw-model').val(m[1]);
                }
                detected++;
                break;
            }
        }
    }

    // --- Versi ---
    if (!$('#fw-version').val()) {
        for (var j = 0; j < VERSION_PATTERNS.length; j++) {
            var vp = VERSION_PATTERNS[j];
            var v  = name.match(vp.re);
            if (v) {
                $('#fw-version').val(v[1].toUpperCase());
                $('#fw-version-hint').text('Format: ' + vp.hint).show();
                detected++;
                break;
            }
        }
    }

    $('#fw-detect-result').toggle(detected > 0);
});

// Reset hint jika version diedit manual
$('#fw-version').on('input', function() {
    $('#fw-version-hint').hide();
});

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
@endsection
