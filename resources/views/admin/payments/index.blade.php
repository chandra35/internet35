@extends('layouts.admin')

@php($isPaidView = ($paymentMode ?? 'unpaid') === 'paid')
@section('title', $isPaidView ? 'Sudah Bayar' : 'Belum Bayar')
@section('page-title', $isPaidView ? 'Sudah Bayar' : 'Belum Bayar')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pembayaran</li>
@endsection

@push('css')
<style>
    .card-payments { border: none !important; border-radius: 10px !important; overflow: hidden; }
    .card-payments > .card-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-bottom: none; padding: 14px 20px; }
    .card-payments > .card-header .card-title { color: white; font-size: 1rem; font-weight: 600; }
    .payment-filter-bar { background: #fff; border: 1px solid #dde3ec; border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; box-shadow: 0 1px 5px rgba(0,0,0,.04); }
    #paymentSearch { border-right: none; border-radius: 6px 0 0 6px; }
    #paymentSearch:focus { box-shadow: none; border-color: #80bdff; }
    .payment-search-icon { background: #fff; border-left: none; color: #6c757d; border-radius: 0 6px 6px 0; }
    #paymentsTable { width: 100% !important; font-size: .875rem; }
    #paymentsTable th { background: #f4f6fb; color: #5a6a7e; font-size: .69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; border-top: none; border-bottom: 2px solid #dde3ec; padding: 10px 14px; white-space: nowrap; }
    #paymentsTable td { padding: 12px 14px; vertical-align: middle; border-top: 1px solid #f0f2f8; }
    #paymentsTable tbody tr:hover td { background: #f0f5ff; }
    #paymentsTable_wrapper .dataTables_info { font-size: .78rem; color: #6c757d; padding-top: 0; }
    #paymentsTable_wrapper .pagination { margin: 0; }
    .payment-action { border-radius: 18px; white-space: nowrap; }
    .payment-view-tabs { display:flex; gap:6px; flex-wrap:wrap; }
    .payment-view-tabs .btn { border-radius:18px; font-size:.78rem; padding:6px 12px; }
    .payment-view-tabs .btn.active { box-shadow:0 3px 9px rgba(30,60,114,.2); }
    #paymentDetailModal .modal-dialog { max-width: 920px; }
    #paymentDetailModal .modal-body { min-height: 220px; }
    @media (max-width: 767.98px) {
        .payment-view-tabs { width:100%; }
        .payment-view-tabs .btn { flex:1; text-align:center; }
        #paymentsTable_wrapper .dataTables_info, #paymentsTable_wrapper .dataTables_paginate { float: none; text-align: center; margin: .5rem 0; }
        #paymentsTable thead { display: none; }
        #paymentsTable, #paymentsTable tbody, #paymentsTable tr, #paymentsTable td { display: block; width: 100% !important; }
        #paymentsTable tr { border: 1px solid #dee2e6; border-radius: .5rem; margin: .75rem .5rem; padding: .45rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        #paymentsTable td { border: 0; padding: .3rem .45rem .3rem 42%; position: relative; min-height: 30px; text-align: left !important; }
        #paymentsTable td::before { content: attr(data-label); position: absolute; left: .45rem; width: 38%; color: #6c757d; font-size: .76rem; font-weight: 700; }
        #paymentsTable td:last-child { padding-left: .45rem; padding-top: .6rem; }
        #paymentsTable td:last-child::before { display: none; }
        #paymentsTable .btn { width: 100%; }
    }
</style>
@endpush

@section('content')
@if($popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline">
            <label class="mr-2"><i class="fas fa-user-shield text-info mr-1"></i>POP:</label>
            <select name="pop_id" class="form-control select2" style="min-width:280px" onchange="this.form.submit()">
                <option value="">-- Pilih POP --</option>
                @foreach($popUsers as $pop)
                <option value="{{ $pop->id }}" {{ $popId === $pop->id ? 'selected' : '' }}>{{ $pop->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
@endif

@if(!$popId)
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Pilih POP terlebih dahulu.</div>
@else
<div class="card card-payments shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-{{ $isPaidView ? 'check-circle' : 'cash-register' }} mr-2"></i>Daftar {{ $isPaidView ? 'Sudah Bayar' : 'Belum Bayar' }}</h3>
        <div class="payment-view-tabs">
            <a href="{{ route('admin.payments.index', $popId ? ['pop_id' => $popId] : []) }}" class="btn {{ !$isPaidView ? 'btn-danger active' : 'btn-outline-danger' }}"><i class="fas fa-clock mr-1"></i>Belum Bayar</a>
            <a href="{{ route('admin.payments.paid', $popId ? ['pop_id' => $popId] : []) }}" class="btn {{ $isPaidView ? 'btn-success active' : 'btn-outline-success' }}"><i class="fas fa-check-circle mr-1"></i>Sudah Bayar</a>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="payment-filter-bar">
            <div class="form-row align-items-end">
                <div class="col-md-12">
                    <label for="paymentSearch" class="small font-weight-bold text-muted mb-1">Cari Pelanggan</label>
                    <div class="input-group">
                        <input type="search" id="paymentSearch" class="form-control" autocomplete="off" placeholder="&#xf002;  Cari nama, ID pelanggan, telepon, atau PPPoE...">
                        <div class="input-group-append"><span class="input-group-text payment-search-icon"><i class="fas fa-search"></i></span></div>
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-0 mt-2">{{ $isPaidView ? 'Menampilkan pelanggan yang sudah melunasi invoice pada bulan berjalan.' : 'Menampilkan invoice bulan berjalan dan seluruh tunggakan sebelumnya yang belum lunas.' }}</p>
        </div>
        <div class="table-responsive">
            <table id="paymentsTable" class="table table-hover mb-0">
                <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Jumlah Invoice</th><th>{{ $isPaidView ? 'Tanggal Bayar' : 'Jatuh Tempo Terdekat' }}</th><th class="text-right">{{ $isPaidView ? 'Total Dibayar' : 'Total Tunggakan' }}</th><th class="text-right">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="paymentDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Detail Belum Bayar</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="paymentDetailBody">
                <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">Memuat detail...</div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    if (!$('#paymentsTable').length) return;
    const labels = ['Pelanggan', 'Kontak', 'Jumlah Invoice', '{{ $isPaidView ? 'Tanggal Bayar' : 'Jatuh Tempo Terdekat' }}', '{{ $isPaidView ? 'Total Dibayar' : 'Total Tunggakan' }}', ''];
    $.fn.dataTable.ext.errMode = 'none';
    const table = $('#paymentsTable').DataTable({
        processing: true, serverSide: true, searching: true, ordering: false, pageLength: 20,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        ajax: {
            url: '{{ route('admin.payments.data') }}',
            data: function (data) { data.view = '{{ $isPaidView ? 'paid' : 'unpaid' }}'; @if(auth()->user()->hasRole('superadmin')) data.pop_id = '{{ $popId }}'; @endif }
        },
        columns: [
            {data: 'customer'}, {data: 'contact'}, {data: 'invoices'}, {data: 'due_date'},
            {data: 'outstanding', className: 'text-right'}, {data: 'action', orderable: false, searchable: false, className: 'text-right'}
        ],
        createdRow: function (row) { $('td', row).each(function (index) { $(this).attr('data-label', labels[index]); }); },
        dom: 'rt<"d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 px-1"ip>',
        language: {
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ pelanggan',
            infoEmpty: '{{ $isPaidView ? 'Belum ada pembayaran bulan ini' : 'Tidak ada tunggakan' }}', processing: 'Memuat data...', zeroRecords: '{{ $isPaidView ? 'Tidak ada pembayaran yang sesuai' : 'Tidak ada tunggakan yang sesuai' }}',
            paginate: {previous: 'Sebelumnya', next: 'Berikutnya'}
        }
    });
    $('#paymentsTable').on('error.dt', function () {
        toastr.error('Daftar tunggakan tidak dapat dimuat. Silakan muat ulang halaman atau coba lagi.');
    });
    let searchTimer;
    $('#paymentSearch').on('input', function () {
        const value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { table.search(value).draw(); }, 350);
    });
    $('#paymentsTable').on('click', '.payment-detail', function () {
        const url = $(this).data('url');
        $('#paymentDetailBody').html('<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">Memuat detail...</div></div>');
        $('#paymentDetailModal').modal('show');
        $.get(url).done(function (html) {
            $('#paymentDetailBody').html(html);
        }).fail(function (xhr) {
            $('#paymentDetailBody').html('<div class="alert alert-danger mb-0">Detail pembayaran tidak dapat dimuat. Silakan coba lagi.</div>');
        });
    });
});
</script>
@endpush
