@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Activity Logs</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history mr-1"></i> Log Aktivitas
            </h3>
            @can('activity-logs.delete')
            <div class="card-tools">
                <button type="button" class="btn btn-danger btn-sm" id="btnBulkDelete">
                    <i class="fas fa-trash mr-1"></i> Hapus Log Lama
                </button>
            </div>
            @endcan
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-2">
                    <select class="form-control select2" id="filterAction">
                        <option value="">Semua Action</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control select2" id="filterModule">
                        <option value="">Semua Module</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="filterDateFrom" placeholder="Dari Tanggal">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="filterDateTo" placeholder="Sampai Tanggal">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary btn-block" id="btnReset">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="activityTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>IP</th>
                            <th>Lokasi</th>
                            <th>Browser</th>
                            <th>Waktu</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle mr-2"></i>Detail Activity Log</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Hapus Log Lama</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Hapus semua log yang lebih lama dari:</p>
                    <div class="form-group">
                        <select class="form-control select2" id="deleteDays">
                            <option value="7">7 hari</option>
                            <option value="14">14 hari</option>
                            <option value="30" selected>30 hari</option>
                            <option value="60">60 hari</option>
                            <option value="90">90 hari</option>
                        </select>
                    </div>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Tindakan ini tidak dapat dibatalkan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmBulkDelete">
                        <i class="fas fa-trash mr-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let table;

    $(document).ready(function() {
        // Initialize DataTable
        table = $('#activityTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.activity-logs.index') }}',
                data: function(d) {
                    d.action = $('#filterAction').val();
                    d.module = $('#filterModule').val();
                    d.date_from = $('#filterDateFrom').val();
                    d.date_to = $('#filterDateTo').val();
                }
            },
            columns: [
                { data: 'user' },
                { data: 'action' },
                { data: 'module' },
                { data: 'ip_address' },
                { data: 'location' },
                { data: 'browser' },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[6, 'desc']],
            language: dtLanguageID
        });

        // Filters
        $('#filterAction, #filterModule, #filterDateFrom, #filterDateTo').on('change', function() {
            table.ajax.reload();
        });

        $('#btnReset').on('click', function() {
            $('#filterAction, #filterModule').val('');
            $('#filterDateFrom, #filterDateTo').val('');
            table.ajax.reload();
        });

        // View detail
        $(document).on('click', '.btn-view', function() {
            const id = $(this).data('id');
            $('#detailContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#detailModal').modal('show');
            
            $.get(`{{ url('admin/activity-logs') }}/${id}`, function(response) {
                $('#detailContent').html(response.html);
                initMap();
            });
        });

        // Delete button
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            confirmDelete(`{{ url('admin/activity-logs') }}/${id}`, 'log ini', table);
        });

        // Bulk delete
        $('#btnBulkDelete').on('click', function() {
            $('#bulkDeleteModal').modal('show');
        });

        $('#btnConfirmBulkDelete').on('click', function() {
            const days = $('#deleteDays').val();
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menghapus...').prop('disabled', true);

            $.post('{{ route('admin.activity-logs.bulk-delete') }}', { days: days }, function(response) {
                btn.html('<i class="fas fa-trash mr-1"></i> Hapus').prop('disabled', false);
                $('#bulkDeleteModal').modal('hide');
                
                if (response.success) {
                    toastr.success(response.message);
                    table.ajax.reload();
                }
            }).fail(function(xhr) {
                btn.html('<i class="fas fa-trash mr-1"></i> Hapus').prop('disabled', false);
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
            });
        });
    });

    function initMap() {
        const mapContainer = document.getElementById('logMap');
        if (mapContainer && mapContainer.dataset.lat && mapContainer.dataset.lng) {
            const lat = parseFloat(mapContainer.dataset.lat);
            const lng = parseFloat(mapContainer.dataset.lng);
            
            if (lat && lng) {
                const map = L.map('logMap').setView([lat, lng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                L.marker([lat, lng]).addTo(map)
                    .bindPopup('Lokasi aktivitas')
                    .openPopup();
            }
        }
    }
</script>
@endpush
