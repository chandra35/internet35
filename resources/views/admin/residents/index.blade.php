@extends('layouts.admin')

@section('title', 'Data Kependudukan')
@section('page-title', 'Data Kependudukan')

@section('breadcrumb')
    <li class="breadcrumb-item active">Data Kependudukan</li>
@endsection

@section('content')
    {{-- Statistics --}}
    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-light">
                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Penduduk</span>
                    <span class="info-box-number">{{ number_format($totalResidents) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Section (Superadmin only) --}}
    @role('superadmin')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-upload mr-1"></i> Impor Data Kependudukan</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body">
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                {{-- Region Selection --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Provinsi <span class="text-danger">*</span></label>
                            <select id="importProvince" name="province_code" class="form-control select2" required>
                                <option value="">-- Pilih Provinsi --</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->code }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Kab/Kota <span class="text-danger">*</span></label>
                            <select id="importCity" name="city_code" class="form-control" required disabled>
                                <option value="">Pilih Provinsi dulu...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Kecamatan <span class="text-danger">*</span></label>
                            <select id="importDistrict" name="district_code" class="form-control" required disabled>
                                <option value="">Pilih Kab/Kota dulu...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Kelurahan/Desa <span class="text-danger">*</span></label>
                            <select id="importVillage" name="village_code" class="form-control" required disabled>
                                <option value="">Pilih Kecamatan dulu...</option>
                            </select>
                        </div>
                    </div>
                </div>
                {{-- File Upload --}}
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>File Excel (.xlsx, .xls, .csv)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="importFile" name="file" accept=".xlsx,.xls,.csv">
                                <label class="custom-file-label" for="importFile">Pilih file...</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-info" id="btnPreview" disabled>
                            <i class="fas fa-eye mr-1"></i> Preview Data
                        </button>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle mr-1"></i>Pilih wilayah terlebih dahulu, lalu upload file Excel. Klik <strong>Preview</strong> untuk memeriksa data sebelum diimpor.
                    <br>Format header: NO, ALAMAT, DUSUN, RW, RT, NAMA LENGKAP, No KK, NIK, JK, TEMPAT LHR, ANGGAL LHR, AGAMA, Pendidikan, Status Perkawinan, ..., Nama Ayah, NAMA IBU
                </small>
            </form>

            {{-- Progress Bar (hidden by default) --}}
            <div id="importProgress" class="mt-3" style="display:none;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="font-weight-bold" id="progressLabel">Mempersiapkan...</small>
                    <small id="progressPercent" class="text-muted">0%</small>
                </div>
                <div class="progress" style="height: 22px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <small id="progressDetail" class="text-muted mt-1 d-block"></small>
            </div>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div class="modal fade" id="previewModal" tabindex="-1" data-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white"><i class="fas fa-search-plus mr-2"></i>Preview Data Import</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Stats Summary Cards --}}
                    <div class="p-3">
                        <div class="row" id="previewStats">
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-primary mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvTotalRows">0</h4>
                                        <p class="mb-0" style="font-size:.8rem">Total Baris</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                                </div>
                            </div>
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-success mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvValidRows">0</h4>
                                        <p class="mb-0" style="font-size:.8rem">Valid</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                                </div>
                            </div>
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-cyan mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvAutoCorrected">0</h4>
                                        <p class="mb-0" style="font-size:.8rem">Auto-Koreksi</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-magic"></i></div>
                                </div>
                            </div>
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-orange mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvNeedsUpdateRows">0</h4>
                                        <p class="mb-0" style="font-size:.8rem">Perlu Update</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-edit"></i></div>
                                </div>
                            </div>
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-warning mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvExistingRows">0</h4>
                                        <p class="mb-0" style="font-size:.8rem">Sudah Ada</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-sync"></i></div>
                                </div>
                            </div>
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-teal mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvNewRows">0</h4>
                                        <p class="mb-0" style="font-size:.8rem">Data Baru</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-user-plus"></i></div>
                                </div>
                            </div>
                            <div class="col-lg col-sm-4 col-6 mb-2">
                                <div class="small-box bg-gradient-indigo mb-0" style="min-height:auto">
                                    <div class="inner py-2 px-3">
                                        <h4 class="mb-0" id="pvGender">-</h4>
                                        <p class="mb-0" style="font-size:.8rem">L / P</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-venus-mars"></i></div>
                                </div>
                            </div>
                        </div>

                        {{-- Detected Headers --}}
                        <div class="callout callout-info py-2 px-3 mb-3" id="pvHeaderInfo">
                            <small><i class="fas fa-columns mr-1"></i> <strong>Kolom terdeteksi:</strong> <span id="pvHeaders">-</span></small>
                        </div>

                        {{-- Issues Alert --}}
                        <div class="alert alert-warning py-2 mb-3" id="pvIssuesAlert" style="display:none;">
                            <h6 class="mb-1"><i class="fas fa-edit mr-1"></i> Data Perlu Update (tetap diimpor, ditandai di tabel)</h6>
                            <ul class="mb-0 pl-3" id="pvIssuesList" style="font-size:.85rem;"></ul>
                        </div>
                    </div>

                    {{-- Sample Data Table --}}
                    <div class="px-3 pb-3">
                        <h6 class="mb-2"><i class="fas fa-table mr-1"></i> Sample Data (10 baris pertama)</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover mb-0" style="font-size:.85rem;">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:30px">#</th>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>No KK</th>
                                        <th>JK</th>
                                        <th>Tempat Lahir</th>
                                        <th>Tgl Lahir</th>
                                        <th>Alamat</th>
                                        <th style="width:80px">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="pvSampleBody">
                                    <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <span class="text-muted" style="font-size:.85rem;" id="pvSummaryText"></span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="button" class="btn btn-success" id="btnConfirmImport">
                            <i class="fas fa-check mr-1"></i> Konfirmasi & Impor
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Access Management (Superadmin only) --}}
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-key mr-1"></i> Kelola Akses POP</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            @if(count($popAccess) > 0)
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>POP</th>
                        <th>Wilayah Akses</th>
                        <th class="text-center" style="width: 200px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($popAccess as $pop)
                    <tr>
                        <td><strong>{{ $pop['name'] }}</strong></td>
                        <td>
                            @if($pop['has_all_access'])
                                <span class="badge badge-success p-1"><i class="fas fa-globe mr-1"></i>Semua Wilayah</span>
                            @elseif($pop['has_access'])
                                @foreach($pop['villages'] as $v)
                                    <span class="badge badge-info p-1 mr-1 mb-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $v['name'] }}
                                        <button type="button" class="btn btn-xs text-white ml-1 p-0 btn-revoke-village"
                                            data-pop-id="{{ $pop['id'] }}" data-village-code="{{ $v['code'] }}" data-village-name="{{ $v['name'] }}"
                                            title="Cabut {{ $v['name'] }}" style="line-height:1;font-size:10px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </span>
                                @endforeach
                            @else
                                <span class="text-muted"><i class="fas fa-lock mr-1"></i>Belum ada akses</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-success btn-grant" data-pop-id="{{ $pop['id'] }}" data-pop-name="{{ $pop['name'] }}">
                                <i class="fas fa-plus mr-1"></i>Tambah
                            </button>
                            @if($pop['has_access'])
                                <button class="btn btn-sm btn-outline-danger btn-revoke-all" data-pop-id="{{ $pop['id'] }}" data-pop-name="{{ $pop['name'] }}">
                                    <i class="fas fa-ban mr-1"></i>Cabut Semua
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
                <div class="p-3 text-muted">Belum ada POP yang terdaftar.</div>
            @endif
        </div>
    </div>
    @endrole

    {{-- Data Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> Daftar Penduduk</h3>
            <div class="card-tools">
                @role('superadmin')
                <button class="btn btn-sm btn-outline-danger mr-2" id="btnClearAll">
                    <i class="fas fa-trash mr-1"></i> Hapus Semua
                </button>
                @endrole
            </div>
        </div>
        <div class="card-body">
            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.residents.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari NIK, Nama, No KK..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="kelurahan" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Semua Kelurahan --</option>
                            @foreach($kelurahans as $kel)
                                <option value="{{ $kel }}" {{ request('kelurahan') == $kel ? 'selected' : '' }}>{{ $kel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="data_status" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="valid" {{ request('data_status') == 'valid' ? 'selected' : '' }}>Valid</option>
                            <option value="auto_corrected" {{ request('data_status') == 'auto_corrected' ? 'selected' : '' }}>Auto-Koreksi</option>
                            <option value="perlu_update" {{ request('data_status') == 'perlu_update' ? 'selected' : '' }}>Perlu Update</option>
                        </select>
                    </div>
                    @if(request('search') || request('kelurahan') || request('data_status'))
                    <div class="col-md-2">
                        <a href="{{ route('admin.residents.index') }}" class="btn btn-default"><i class="fas fa-times mr-1"></i>Reset</a>
                    </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 30px">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>No KK</th>
                            <th>JK</th>
                            <th>TTL</th>
                            <th>Alamat</th>
                            <th>Wilayah</th>
                            <th style="width: 110px">Status</th>
                            @role('superadmin')
                            <th style="width: 50px"></th>
                            @endrole
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($residents as $r)
                        <tr class="{{ $r->data_status === 'perlu_update' ? 'table-warning' : ($r->data_status === 'auto_corrected' ? 'table-info' : '') }}">
                            <td><input type="checkbox" class="row-check" value="{{ $r->id }}"></td>
                            <td>
                                <code>{{ $r->nik ?: '-' }}</code>
                                @if($r->data_status === 'perlu_update' && $r->data_notes)
                                    <br><small class="text-danger"><i class="fas fa-info-circle"></i> {{ $r->data_notes }}</small>
                                @elseif($r->data_status === 'auto_corrected' && $r->data_notes)
                                    <br><small class="text-info"><i class="fas fa-magic"></i> {{ $r->data_notes }}</small>
                                @endif
                            </td>
                            <td>{{ $r->nama }}</td>
                            <td>{{ $r->no_kk }}</td>
                            <td>{{ $r->jenis_kelamin == 'LAKI-LAKI' ? 'L' : 'P' }}</td>
                            <td>{{ $r->tempat_lahir }}{{ $r->tanggal_lahir ? ', ' . $r->tanggal_lahir->format('d/m/Y') : '' }}</td>
                            <td>{{ $r->alamat }} {{ $r->dusun ? 'Dsn. ' . $r->dusun : '' }} RT{{ $r->rt }}/RW{{ $r->rw }}</td>
                            <td>
                                {{ $r->kelurahan }}
                                @if($r->village_code)
                                    <br><small class="text-muted">{{ $r->village?->name ?? '' }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($r->data_status === 'perlu_update')
                                    <span class="badge badge-warning" title="{{ $r->data_notes }}"><i class="fas fa-edit mr-1"></i>Perlu Update</span>
                                @elseif($r->data_status === 'auto_corrected')
                                    <span class="badge badge-info" title="{{ $r->data_notes }}"><i class="fas fa-magic mr-1"></i>Auto-Koreksi</span>
                                @else
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Valid</span>
                                @endif
                            </td>
                            @role('superadmin')
                            <td>
                                <button class="btn btn-xs btn-outline-danger btn-delete" data-id="{{ $r->id }}" data-nama="{{ $r->nama }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            @endrole
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Belum ada data kependudukan. Silakan impor file Excel.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($residents->hasPages())
            <div class="mt-3">
                {{ $residents->links() }}
            </div>
            @endif
        </div>
    </div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // ===== Cascading Region Dropdowns =====
    function initSelect2(selector) {
        let $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({ theme: 'bootstrap-5' });
    }

    function loadRegionOptions(url, targetId, placeholder, callback) {
        let $target = $(targetId);
        if ($target.hasClass('select2-hidden-accessible')) {
            $target.select2('destroy');
        }
        $target.html(`<option value="">Memuat...</option>`).prop('disabled', true);
        $.get(url, function(data) {
            let html = `<option value="">${placeholder}</option>`;
            data.forEach(item => { html += `<option value="${item.code}">${item.name}</option>`; });
            $target.html(html).prop('disabled', false);
            initSelect2(targetId);
            if (callback) callback();
        }).fail(function() {
            $target.html(`<option value="">${placeholder}</option>`).prop('disabled', false);
            initSelect2(targetId);
        });
    }

    $('#importProvince').on('change', function() {
        let code = $(this).val();
        if ($('#importCity').hasClass('select2-hidden-accessible')) $('#importCity').select2('destroy');
        if ($('#importDistrict').hasClass('select2-hidden-accessible')) $('#importDistrict').select2('destroy');
        if ($('#importVillage').hasClass('select2-hidden-accessible')) $('#importVillage').select2('destroy');
        $('#importCity').html('<option value="">Pilih Provinsi dulu...</option>').prop('disabled', true);
        $('#importDistrict').html('<option value="">Pilih Kab/Kota dulu...</option>').prop('disabled', true);
        $('#importVillage').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        checkImportReady();
        if (code) {
            loadRegionOptions(`/api/wilayah/cities/${code}`, '#importCity', '-- Pilih Kab/Kota --');
        }
    });

    $('#importCity').on('change', function() {
        let code = $(this).val();
        if ($('#importDistrict').hasClass('select2-hidden-accessible')) $('#importDistrict').select2('destroy');
        if ($('#importVillage').hasClass('select2-hidden-accessible')) $('#importVillage').select2('destroy');
        $('#importDistrict').html('<option value="">Pilih Kab/Kota dulu...</option>').prop('disabled', true);
        $('#importVillage').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        checkImportReady();
        if (code) {
            loadRegionOptions(`/api/wilayah/districts/${code}`, '#importDistrict', '-- Pilih Kecamatan --');
        }
    });

    $('#importDistrict').on('change', function() {
        let code = $(this).val();
        if ($('#importVillage').hasClass('select2-hidden-accessible')) $('#importVillage').select2('destroy');
        $('#importVillage').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        checkImportReady();
        if (code) {
            loadRegionOptions(`/api/wilayah/villages/${code}`, '#importVillage', '-- Pilih Kelurahan/Desa --');
        }
    });

    $('#importVillage').on('change', function() {
        checkImportReady();
    });

    function checkImportReady() {
        let hasFile = $('#importFile').val();
        let hasVillage = $('#importVillage').val();
        $('#btnPreview').prop('disabled', !(hasFile && hasVillage));
    }

    // Custom file input label
    $('#importFile').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Pilih file...');
        checkImportReady();
    });

    // ===== Smart Progress Bar =====
    let progressTimer = null;
    let currentProgress = 0;
    let targetProgress = 0;
    let totalRowsForImport = 0;

    function showProgress(label, detail) {
        $('#importProgress').slideDown(200);
        if (label) $('#progressLabel').text(label);
        if (detail !== undefined) $('#progressDetail').text(detail);
    }

    function hideProgress() {
        clearInterval(progressTimer);
        $('#importProgress').slideUp(300);
        currentProgress = 0;
        targetProgress = 0;
    }

    function setProgress(percent, label, detail, barClass) {
        targetProgress = Math.min(percent, 100);
        if (label) $('#progressLabel').text(label);
        if (detail !== undefined) $('#progressDetail').text(detail);
        if (barClass) {
            $('#progressBar').removeClass('bg-info bg-success bg-danger bg-warning bg-primary').addClass(barClass);
        }
        $('#progressPercent').text(Math.round(targetProgress) + '%');
        $('#progressBar').css('width', targetProgress + '%');
    }

    function startSmartProgress(totalRows) {
        totalRowsForImport = totalRows;
        currentProgress = 0;
        targetProgress = 0;
        let estimatedTime = Math.max(3, totalRows / 100); // rough: 100 rows/sec
        let stepInterval = 200; // ms
        let totalSteps = (estimatedTime * 1000) / stepInterval;
        let stepSize = 85 / totalSteps; // max auto-progress to 85%

        setProgress(0, 'Mengimpor data...', `Memproses ${totalRows.toLocaleString()} baris data...`, 'bg-primary');
        showProgress();

        let step = 0;
        progressTimer = setInterval(function() {
            step++;
            currentProgress = Math.min(currentProgress + stepSize, 85);
            // Slow down as we approach 85%
            if (currentProgress > 60) stepSize *= 0.98;

            let rowsEstimate = Math.round((currentProgress / 85) * totalRows);
            setProgress(
                currentProgress,
                'Mengimpor data...',
                `~${rowsEstimate.toLocaleString()} / ${totalRows.toLocaleString()} baris diproses`
            );
        }, stepInterval);
    }

    function finishProgress(success, message) {
        clearInterval(progressTimer);
        if (success) {
            setProgress(100, 'Impor Selesai!', message, 'bg-success');
            $('#progressBar').removeClass('progress-bar-animated');
        } else {
            setProgress(100, 'Impor Gagal', message, 'bg-danger');
            $('#progressBar').removeClass('progress-bar-animated');
        }
    }

    // ===== Preview =====
    $('#btnPreview').on('click', function() {
        let formData = new FormData($('#importForm')[0]);
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menganalisis...');

        setProgress(0, 'Menganalisis file...', 'Membaca dan memeriksa data Excel...', 'bg-info');
        showProgress();

        // Simulate reading progress
        let readProgress = 0;
        let readTimer = setInterval(() => {
            readProgress = Math.min(readProgress + 15, 90);
            setProgress(readProgress);
        }, 300);

        $.ajax({
            url: '{{ route("admin.residents.preview") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                clearInterval(readTimer);
                setProgress(100, 'Analisis selesai!', '', 'bg-success');
                $('#progressBar').removeClass('progress-bar-animated');

                setTimeout(() => {
                    hideProgress();
                    $('#progressBar').addClass('progress-bar-animated');
                    renderPreview(res.data);
                    $('#previewModal').modal('show');
                }, 500);
            },
            error: function(xhr) {
                clearInterval(readTimer);
                let msg = xhr.responseJSON?.message || 'Gagal membaca file Excel';
                finishProgress(false, msg);
                setTimeout(() => hideProgress(), 3000);
                Swal.fire('Gagal', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-eye mr-1"></i> Preview Data');
                checkImportReady();
            }
        });
    });

    function renderPreview(data) {
        // Stats
        $('#pvTotalRows').text(data.total_rows.toLocaleString());
        $('#pvValidRows').text(data.valid_rows.toLocaleString());
        $('#pvAutoCorrected').text((data.auto_corrected_rows || 0).toLocaleString());
        $('#pvNeedsUpdateRows').text(data.needs_update_rows.toLocaleString());
        $('#pvExistingRows').text(data.existing_rows.toLocaleString());
        $('#pvNewRows').text(data.new_rows.toLocaleString());
        $('#pvGender').text(data.gender_stats.L + ' / ' + data.gender_stats.P);

        // Headers
        if (data.headers.length) {
            let headerBadges = data.headers.map(h => `<span class="badge badge-light border mr-1">${h}</span>`).join('');
            $('#pvHeaders').html(headerBadges);
        }

        // Issues (perlu update + corrections info)
        if (data.issues.length > 0) {
            let html = '';
            data.issues.forEach(issue => {
                let detail = issue.issues.join(', ');
                let corrInfo = '';
                if (issue.corrections && issue.corrections.length) {
                    corrInfo = ' <small class="text-info">(' + issue.corrections.join('; ') + ')</small>';
                }
                let nikDisplay = issue.nik;
                if (issue.nik_cleaned && issue.nik_cleaned !== issue.nik) {
                    nikDisplay = `<del>${issue.nik}</del> → ${issue.nik_cleaned}`;
                }
                html += `<li>Baris ${issue.row} — <strong>${issue.nama}</strong> (NIK: ${nikDisplay}): ${detail}${corrInfo} <span class="badge badge-warning">tetap diimpor</span></li>`;
            });
            if (data.needs_update_rows > data.issues.length) {
                html += `<li class="text-muted">...dan ${data.needs_update_rows - data.issues.length} data lainnya perlu update</li>`;
            }
            $('#pvIssuesList').html(html);
            $('#pvIssuesAlert').show();
        } else {
            $('#pvIssuesAlert').hide();
        }

        // Sample rows
        let rows = '';
        data.sample_rows.forEach(row => {
            let statusBadge, rowClass;
            if (row.status === 'skip') {
                statusBadge = '<span class="badge badge-secondary">Dilewati</span>';
                rowClass = 'table-secondary';
            } else if (row.status === 'perlu_update') {
                statusBadge = '<span class="badge badge-warning"><i class="fas fa-edit mr-1"></i>Perlu Update</span>';
                rowClass = 'table-warning';
            } else if (row.status === 'auto_corrected') {
                statusBadge = '<span class="badge badge-info"><i class="fas fa-magic mr-1"></i>Auto-Koreksi</span>';
                rowClass = 'table-info';
            } else if (row.is_existing) {
                statusBadge = '<span class="badge badge-primary"><i class="fas fa-sync mr-1"></i>Update</span>';
                rowClass = '';
            } else {
                statusBadge = '<span class="badge badge-success"><i class="fas fa-plus mr-1"></i>Baru</span>';
                rowClass = '';
            }

            // NIK display: show correction if any
            let nikHtml = `<code>${row.nik || '-'}</code>`;
            if (row.nik_corrected && row.nik_raw) {
                nikHtml = `<code>${row.nik}</code><br><small class="text-info"><i class="fas fa-magic"></i> <del>${row.nik_raw}</del></small>`;
            }
            // Issues under NIK
            if (row.issues && row.issues.length) {
                nikHtml += `<br><small class="text-danger">${row.issues.join(', ')}</small>`;
            }
            // Corrections detail
            if (row.corrections && row.corrections.length) {
                nikHtml += `<br><small class="text-info">${row.corrections.join('; ')}</small>`;
            }

            rows += `<tr class="${rowClass}">
                <td>${row.no}</td>
                <td>${nikHtml}</td>
                <td>${row.nama || '-'}</td>
                <td>${row.no_kk || '-'}</td>
                <td>${row.jk}</td>
                <td>${row.tempat_lahir || '-'}</td>
                <td>${row.tanggal_lahir || '-'}</td>
                <td>${row.alamat || '-'}</td>
                <td class="text-center">${statusBadge}</td>
            </tr>`;
        });
        $('#pvSampleBody').html(rows || '<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>');

        // Summary text
        let summaryParts = [];
        let importable = data.valid_rows + data.needs_update_rows + (data.auto_corrected_rows || 0);
        if (data.new_rows > 0) summaryParts.push(`<strong class="text-success">${data.new_rows}</strong> baru`);
        if (data.existing_rows > 0) summaryParts.push(`<strong class="text-primary">${data.existing_rows}</strong> diperbarui`);
        if (data.auto_corrected_rows > 0) summaryParts.push(`<strong class="text-info">${data.auto_corrected_rows}</strong> auto-koreksi`);
        if (data.needs_update_rows > 0) summaryParts.push(`<strong class="text-warning">${data.needs_update_rows}</strong> perlu update`);
        if (data.skip_rows > 0) summaryParts.push(`<strong class="text-secondary">${data.skip_rows}</strong> dilewati`);
        $('#pvSummaryText').html('<i class="fas fa-info-circle mr-1"></i>' + summaryParts.join(' &middot; '));

        // Store total for progress
        totalRowsForImport = data.total_rows;

        // Enable/disable confirm button
        if (importable === 0) {
            $('#btnConfirmImport').prop('disabled', true).addClass('btn-secondary').removeClass('btn-success');
        } else {
            $('#btnConfirmImport').prop('disabled', false).addClass('btn-success').removeClass('btn-secondary');
        }
    }

    // ===== Confirm Import =====
    $('#btnConfirmImport').on('click', function() {
        $('#previewModal').modal('hide');

        let formData = new FormData($('#importForm')[0]);
        let btn = $('#btnPreview');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengimpor...');

        startSmartProgress(totalRowsForImport);

        $.ajax({
            url: '{{ route("admin.residents.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                let d = res.data;
                finishProgress(true,
                    `${d.success} baru, ${d.updated} diperbarui, ${d.auto_corrected || 0} auto-koreksi, ${d.flagged || 0} perlu update, ${d.failed} gagal, ${d.skipped} dilewati`
                );

                let html = `<div class="text-left">
                    <div class="row mb-2">
                        <div class="col-6"><span class="badge badge-success p-2 d-block"><i class="fas fa-plus mr-1"></i>${d.success} Baru</span></div>
                        <div class="col-6"><span class="badge badge-primary p-2 d-block"><i class="fas fa-sync mr-1"></i>${d.updated} Diperbarui</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><span class="badge badge-info p-2 d-block"><i class="fas fa-magic mr-1"></i>${d.auto_corrected || 0} Auto-Koreksi</span></div>
                        <div class="col-4"><span class="badge badge-warning p-2 d-block"><i class="fas fa-edit mr-1"></i>${d.flagged || 0} Perlu Update</span></div>
                        <div class="col-4"><span class="badge badge-secondary p-2 d-block"><i class="fas fa-forward mr-1"></i>${d.skipped} Dilewati</span></div>
                    </div>`;
                if (d.errors && d.errors.length > 0) {
                    html += '<hr><p class="text-danger mb-1"><strong>Detail Error:</strong></p><ul class="text-left mb-0" style="font-size:.85rem;">';
                    d.errors.forEach(function(err) {
                        html += `<li>Baris ${err.row} (${err.nama}): ${err.error}</li>`;
                    });
                    html += '</ul>';
                }
                html += '</div>';

                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Impor Berhasil!',
                        html: html,
                    }).then(() => location.reload());
                }, 1500);
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat mengimpor';
                finishProgress(false, msg);
                setTimeout(() => {
                    Swal.fire('Gagal', msg, 'error');
                }, 500);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-eye mr-1"></i> Preview Data');
                checkImportReady();
            }
        });
    });

    // Grant access with village selection
    $('.btn-grant').on('click', function() {
        let popId = $(this).data('pop-id');
        let popName = $(this).data('pop-name');
        let villageOptions = '<option value="">🌐 Semua Wilayah</option>';
        @foreach($residentVillages ?? [] as $v)
            villageOptions += '<option value="{{ $v['code'] }}">{{ $v['name'] }}</option>';
        @endforeach

        Swal.fire({
            title: 'Berikan Akses Wilayah',
            html: `<p class="mb-2">POP: <strong>${popName}</strong></p>
                <div class="form-group text-left">
                    <label>Pilih Wilayah</label>
                    <select id="swalVillageCode" class="form-control">${villageOptions}</select>
                    <small class="text-muted">Kosong = akses semua wilayah</small>
                </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Berikan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                return document.getElementById('swalVillageCode').value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ route("admin.residents.grant-access") }}', {
                    _token: '{{ csrf_token() }}',
                    pop_id: popId,
                    village_code: result.value || ''
                }).done(function(res) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 1000);
                }).fail(function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                });
            }
        });
    });

    // Revoke specific village access
    $(document).on('click', '.btn-revoke-village', function(e) {
        e.preventDefault();
        let popId = $(this).data('pop-id');
        let villageCode = $(this).data('village-code');
        let villageName = $(this).data('village-name');
        Swal.fire({
            title: 'Cabut Akses Wilayah?',
            text: `Cabut akses "${villageName}" dari POP ini.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Cabut',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ route("admin.residents.revoke-access") }}', {
                    _token: '{{ csrf_token() }}',
                    pop_id: popId,
                    village_code: villageCode
                }).done(function(res) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 1000);
                }).fail(function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                });
            }
        });
    });

    // Revoke all access
    $('.btn-revoke-all').on('click', function() {
        let popId = $(this).data('pop-id');
        let popName = $(this).data('pop-name');
        Swal.fire({
            title: 'Cabut Semua Akses?',
            text: `POP "${popName}" tidak akan bisa mengakses data kependudukan lagi.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Cabut Semua',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ route("admin.residents.revoke-access") }}', {
                    _token: '{{ csrf_token() }}',
                    pop_id: popId,
                    revoke_all: 1
                }).done(function(res) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 1000);
                }).fail(function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                });
            }
        });
    });

    // Delete single
    $('.btn-delete').on('click', function() {
        let id = $(this).data('id');
        let nama = $(this).data('nama');
        Swal.fire({
            title: 'Hapus Data?',
            text: `Data penduduk "${nama}" akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/residents/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        toastr.success(res.message);
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    });

    // Check all
    $('#checkAll').on('change', function() {
        $('.row-check').prop('checked', $(this).is(':checked'));
    });

    // Clear all
    $('#btnClearAll').on('click', function() {
        Swal.fire({
            title: 'Hapus SEMUA Data Penduduk?',
            text: 'Semua data kependudukan akan dihapus permanen. Aksi ini tidak bisa dibatalkan!',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            input: 'text',
            inputPlaceholder: 'Ketik HAPUS untuk konfirmasi',
            inputValidator: (value) => {
                if (value !== 'HAPUS') return 'Ketik HAPUS untuk melanjutkan';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                $.post('{{ route("admin.residents.clear-all") }}', {
                    _token: '{{ csrf_token() }}'
                }).done(function(res) {
                    Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                }).fail(function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                });
            }
        });
    });
});
</script>
@endpush
