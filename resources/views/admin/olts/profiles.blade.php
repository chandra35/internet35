@extends('layouts.admin')

@section('title', 'GPON Profiles - ' . $olt->name)
@section('page-title', 'GPON Profiles: ' . $olt->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.show', $olt) }}">{{ $olt->name }}</a></li>
    <li class="breadcrumb-item active">GPON Profiles</li>
@endsection

@push('css')
<style>
    .profile-badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; }
    .dba-type-1 { background: #e3f2fd; color: #1565c0; }
    .dba-type-2 { background: #e8f5e9; color: #2e7d32; }
    .dba-type-3 { background: #fff8e1; color: #f57f17; }
    .dba-type-4 { background: #fce4ec; color: #c62828; }
    .dba-type-5 { background: #f3e5f5; color: #6a1b9a; }
    .bw-pill { display:inline-block; background:#f0f0f0; border-radius:10px; padding:2px 10px; font-size:12px; margin:1px; }
    .card-header-tabs .nav-link.active { background: #fff; border-bottom-color: #fff; font-weight:600; }
    .table td { vertical-align: middle; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

<div class="row mb-3">
    <div class="col">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
            <button type="button" id="btn-sync" class="btn btn-info btn-sm">
                <i class="fas fa-sync mr-1"></i>Sync dari OLT
            </button>
            @can('olts.edit')
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-create-tcont">
                <i class="fas fa-plus mr-1"></i>TCONT Profile
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-create-traffic">
                <i class="fas fa-plus mr-1"></i>Traffic Profile
            </button>
            @endcan
        </div>
    </div>
</div>

<!-- Info Card -->
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <div><i class="fas fa-server text-primary mr-1"></i> <strong>{{ $olt->name }}</strong></div>
            <div><code>{{ $olt->ip_address }}</code></div>
            <div class="text-muted">{{ $olt->brandLabel ?? $olt->brand }}</div>
            <div class="ml-auto">
                <span class="badge badge-warning">{{ $tcontProfiles->count() }} TCONT Profile</span>
                <span class="badge badge-primary ml-1">{{ $trafficProfiles->count() }} Traffic Profile</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs" id="profile-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active px-4 py-3" id="tab-tcont" data-toggle="tab" href="#pane-tcont" role="tab">
                    <i class="fas fa-arrow-up text-warning mr-1"></i>
                    TCONT (Upstream DBA)
                    <span class="badge badge-warning ml-1">{{ $tcontProfiles->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-4 py-3" id="tab-traffic" data-toggle="tab" href="#pane-traffic" role="tab">
                    <i class="fas fa-arrow-down text-primary mr-1"></i>
                    Traffic (Downstream)
                    <span class="badge badge-primary ml-1">{{ $trafficProfiles->count() }}</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content">

            <!-- ── TCONT Profiles ── -->
            <div class="tab-pane fade show active" id="pane-tcont" role="tabpanel">
                <div class="p-3">
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        TCONT profile mengatur <strong>upstream bandwidth</strong> (DBA = Dynamic Bandwidth Allocation).
                        Dipakai pada konfigurasi ONU: <code>tcont 1 profile NAMA</code>
                    </p>
                </div>
                @if($tcontProfiles->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block opacity-50"></i>
                    Belum ada TCONT profile. Klik <strong>Sync dari OLT</strong> untuk membaca dari perangkat,
                    atau <strong>+ TCONT Profile</strong> untuk membuat baru.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Profile</th>
                                <th>DBA Type</th>
                                <th>FBW (Fixed)</th>
                                <th>ABW (Assured)</th>
                                <th>MBW (Max)</th>
                                <th>Keterangan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tcontProfiles as $p)
                            @php
                                $cfg = $p->config ?? [];
                                $dbaType = $cfg['type'] ?? '-';
                                $dbaLabels = [1=>'Fixed',2=>'Assured',3=>'Non-Assured',4=>'Best Effort',5=>'Hybrid'];
                                $dbaLabel = $dbaLabels[$dbaType] ?? "Type {$dbaType}";
                            @endphp
                            <tr>
                                <td>
                                    <strong class="text-dark">{{ $p->name }}</strong>
                                </td>
                                <td>
                                    <span class="profile-badge dba-type-{{ $dbaType }}">
                                        {{ $dbaLabel }}
                                    </span>
                                </td>
                                <td>
                                    @if(isset($cfg['fbw']) && $cfg['fbw'] > 0)
                                        <span class="bw-pill">{{ number_format($cfg['fbw']) }} kbps</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($cfg['abw']) && $cfg['abw'] > 0)
                                        <span class="bw-pill">{{ number_format($cfg['abw']) }} kbps</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($cfg['mbw']) && $cfg['mbw'] > 0)
                                        <span class="bw-pill">{{ number_format($cfg['mbw']) }} kbps</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $p->description }}</td>
                                <td class="text-right">
                                    @can('olts.edit')
                                    <button class="btn btn-danger btn-xs btn-delete-profile"
                                            data-id="{{ $p->id }}"
                                            data-name="{{ $p->name }}"
                                            data-type="TCONT">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            <!-- ── Traffic Profiles ── -->
            <div class="tab-pane fade" id="pane-traffic" role="tabpanel">
                <div class="p-3">
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Traffic profile mengatur <strong>downstream bandwidth</strong> per gemport.
                        Dipakai pada konfigurasi ONU: <code>gemport 1 traffic-limit downstream NAMA</code>
                    </p>
                </div>
                @if($trafficProfiles->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block opacity-50"></i>
                    Belum ada Traffic profile. Klik <strong>Sync dari OLT</strong> untuk membaca dari perangkat,
                    atau <strong>+ Traffic Profile</strong> untuk membuat baru.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Profile</th>
                                <th>SIR (Sustained)</th>
                                <th>PIR (Peak)</th>
                                <th>Keterangan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trafficProfiles as $p)
                            @php $cfg = $p->config ?? []; @endphp
                            <tr>
                                <td><strong class="text-dark">{{ $p->name }}</strong></td>
                                <td>
                                    @if(isset($cfg['sir']))
                                        <span class="bw-pill">{{ number_format($cfg['sir']) }} kbps</span>
                                        <span class="text-muted small">({{ round($cfg['sir']/1000, 1) }} Mbps)</span>
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td>
                                    @if(isset($cfg['pir']))
                                        <span class="bw-pill">{{ number_format($cfg['pir']) }} kbps</span>
                                        <span class="text-muted small">({{ round($cfg['pir']/1000, 1) }} Mbps)</span>
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-muted small">{{ $p->description }}</td>
                                <td class="text-right">
                                    @can('olts.edit')
                                    <button class="btn btn-danger btn-xs btn-delete-profile"
                                            data-id="{{ $p->id }}"
                                            data-name="{{ $p->name }}"
                                            data-type="Traffic">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div><!-- /tab-content -->
    </div>
</div>

<!-- ──────────────────────────────────────────────────────────────── -->
<!-- Modal: Create TCONT Profile -->
<!-- ──────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modal-create-tcont" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.olts.profiles.store', $olt) }}" id="form-tcont">
                @csrf
                <input type="hidden" name="profile_type" value="tcont">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-arrow-up mr-2"></i>Buat TCONT Profile (Upstream DBA)</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <label>Nama Profile <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="contoh: POP35-1G-UP"
                               pattern="[A-Za-z0-9._\-]+"
                               title="Hanya huruf, angka, titik, underscore, atau strip">
                        <small class="text-muted">Hanya huruf, angka, titik, underscore, strip. Maks 64 karakter.</small>
                    </div>

                    <div class="form-group">
                        <label>DBA Type <span class="text-danger">*</span></label>
                        <select name="dba_type" id="dba_type" class="form-control" required>
                            <option value="1">Type 1 — Fixed Bandwidth (FBW guaranteed)</option>
                            <option value="2">Type 2 — Assured Bandwidth (ABW guaranteed)</option>
                            <option value="3">Type 3 — Non-Assured (ABW + MBW burst)</option>
                            <option value="4">Type 4 — Best Effort (MBW only, no guarantee)</option>
                            <option value="5" selected>Type 5 — Hybrid (FBW + ABW + MBW) ✓ Recommended</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group" id="group-fbw">
                                <label>FBW <small class="text-muted">(Fixed)</small></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="fbw" class="form-control" value="64" min="0" max="9953280">
                                    <div class="input-group-append"><span class="input-group-text">kbps</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" id="group-abw">
                                <label>ABW <small class="text-muted">(Assured)</small></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="abw" class="form-control" value="64" min="0" max="9953280">
                                    <div class="input-group-append"><span class="input-group-text">kbps</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" id="group-mbw">
                                <label>MBW <small class="text-muted">(Max)</small></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="mbw" class="form-control" value="1048064" min="0" max="9953280">
                                    <div class="input-group-append"><span class="input-group-text">kbps</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small" id="dba-help">
                        <strong>Type 5 Hybrid</strong>: FBW = bandwidth tetap terjamin,
                        ABW = bandwidth assure minimum, MBW = batas maksimum burst.
                        Untuk 1G internet: FBW=64, ABW=64, MBW=1048064 kbps.
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>Buat di OLT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ──────────────────────────────────────────────────────────────── -->
<!-- Modal: Create Traffic Profile -->
<!-- ──────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modal-create-traffic" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.olts.profiles.store', $olt) }}" id="form-traffic">
                @csrf
                <input type="hidden" name="profile_type" value="traffic">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-arrow-down mr-2"></i>Buat Traffic Profile (Downstream)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <label>Nama Profile <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="contoh: POP35-1G-DOWN"
                               pattern="[A-Za-z0-9._\-]+"
                               title="Hanya huruf, angka, titik, underscore, atau strip">
                    </div>

                    <div class="form-group">
                        <label>Preset Kecepatan</label>
                        <select id="traffic-preset" class="form-control">
                            <option value="">-- Pilih Preset --</option>
                            <option value="10240,10240">10 Mbps</option>
                            <option value="20480,20480">20 Mbps</option>
                            <option value="30720,30720">30 Mbps</option>
                            <option value="51200,51200">50 Mbps</option>
                            <option value="102400,102400">100 Mbps</option>
                            <option value="204800,204800">200 Mbps</option>
                            <option value="512000,512000">500 Mbps</option>
                            <option value="1048064,1048064">1 Gbps</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SIR (Sustained) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="sir" id="sir" class="form-control" value="1048064" min="1" max="9953280" required>
                                    <div class="input-group-append"><span class="input-group-text">kbps</span></div>
                                </div>
                                <small class="text-muted">Kecepatan sustained (normal)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PIR (Peak) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="pir" id="pir" class="form-control" value="1048064" min="1" max="9953280" required>
                                    <div class="input-group-append"><span class="input-group-text">kbps</span></div>
                                </div>
                                <small class="text-muted">Kecepatan burst maksimum (≥ SIR)</small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Buat di OLT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ──────────────────────────────────────────────────────────────── -->
<!-- Modal: Confirm Delete -->
<!-- ──────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modal-delete-profile" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-trash mr-2"></i>Hapus Profile</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <p>Hapus <span id="delete-profile-type" class="badge badge-warning"></span> profile:</p>
                <p><strong id="delete-profile-name" class="text-danger"></strong></p>
                <p class="small text-muted">Profile akan dihapus dari OLT dan database.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-delete">
                    <i class="fas fa-trash mr-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sync overlay -->
<div id="sync-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; display:none; align-items:center; justify-content:center;">
    <div class="bg-white rounded p-4 text-center shadow-lg" style="min-width:220px;">
        <div class="spinner-border text-info mb-3" role="status"></div>
        <div class="font-weight-bold">Membaca dari OLT...</div>
        <div class="text-muted small">Mohon tunggu</div>
    </div>
</div>

@endsection

@push('js')
<script>
$(function () {

    // ── DBA type helper ──
    const dbaHelp = {
        1: '<strong>Type 1 Fixed</strong>: Isi kolom <strong>FBW</strong> saja.',
        2: '<strong>Type 2 Assured</strong>: Isi kolom <strong>ABW</strong> saja.',
        3: '<strong>Type 3 Non-Assured</strong>: Isi <strong>ABW</strong> (minimum) + <strong>MBW</strong> (burst).',
        4: '<strong>Type 4 Best Effort</strong>: Isi kolom <strong>MBW</strong> saja.',
        5: '<strong>Type 5 Hybrid</strong>: FBW = fixed guarantee, ABW = assured min, MBW = max burst. Recommended untuk internet.',
    };

    $('#dba_type').on('change', function () {
        const t = parseInt(this.value);
        $('#dba-help').html(dbaHelp[t] || '');
        $('#group-fbw').toggle(t === 1 || t === 5);
        $('#group-abw').toggle(t === 2 || t === 3 || t === 5);
        $('#group-mbw').toggle(t === 3 || t === 4 || t === 5);
    }).trigger('change');

    // ── Traffic preset ──
    $('#traffic-preset').on('change', function () {
        if (!this.value) return;
        const [sir, pir] = this.value.split(',');
        $('#sir').val(sir);
        $('#pir').val(pir);
    });

    // ── Sync from OLT ──
    $('#btn-sync').on('click', function () {
        $('#sync-overlay').css('display', 'flex');
        $.get('{{ route('admin.olts.profiles.sync', $olt) }}')
            .done(function (res) {
                $('#sync-overlay').hide();
                if (res.success) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(res.message || 'Sync gagal');
                }
            })
            .fail(function () {
                $('#sync-overlay').hide();
                toastr.error('Sync gagal — periksa koneksi ke OLT');
            });
    });

    // ── Delete profile ──
    let deleteId = null;

    $(document).on('click', '.btn-delete-profile', function () {
        deleteId = $(this).data('id');
        $('#delete-profile-name').text($(this).data('name'));
        $('#delete-profile-type').text($(this).data('type'));
        $('#modal-delete-profile').modal('show');
    });

    $('#btn-confirm-delete').on('click', function () {
        if (!deleteId) return;
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menghapus...');

        $.ajax({
            url: '{{ route('admin.olts.profiles.destroy', [$olt, ':id']) }}'.replace(':id', deleteId),
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
        })
        .done(function (res) {
            if (res.success) {
                toastr.success(res.message);
                $('#modal-delete-profile').modal('hide');
                setTimeout(() => location.reload(), 800);
            } else {
                toastr.error(res.message || 'Gagal menghapus');
            }
        })
        .fail(function () {
            toastr.error('Request gagal');
        })
        .always(function () {
            btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i>Hapus');
        });
    });

});
</script>
@endpush
