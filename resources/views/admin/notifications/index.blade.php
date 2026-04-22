@extends('layouts.admin')

@section('title', 'Semua Notifikasi')
@section('page-title', 'Semua Notifikasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Notifikasi</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-bell mr-2"></i> Notifikasi ({{ $items->total() }})</h3>
        <button class="btn btn-sm btn-outline-secondary" id="markAllReadBtn">
            <i class="fas fa-check-double mr-1"></i> Tandai semua dibaca
        </button>
    </div>
    <div class="card-body p-0">
        @if($items->count() === 0)
            <div class="text-center text-muted py-5">
                <i class="far fa-bell-slash fa-3x mb-3 d-block"></i>
                Belum ada notifikasi.
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($items as $n)
                    @php $d = $n->data; @endphp
                    <div class="list-group-item {{ $n->read_at ? '' : 'bg-light' }}">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong class="text-primary">
                                    <i class="fas fa-satellite-dish mr-1"></i>
                                    ONU baru di {{ $d['olt_name'] ?? 'OLT' }}
                                </strong>
                                @if(!$n->read_at)
                                    <span class="badge badge-danger ml-2">Baru</span>
                                @endif
                                <div class="small text-muted mt-1">
                                    SN: <code>{{ $d['serial_number'] ?? '-' }}</code>
                                    @if(!empty($d['vendor']))
                                        <span class="badge badge-secondary">{{ $d['vendor'] }}</span>
                                    @endif
                                    &middot; Slot {{ $d['slot'] ?? '-' }} / Port {{ $d['port'] ?? '-' }}
                                    &middot; {{ $n->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div>
                                @if(!empty($d['olt_id']))
                                    <a href="{{ url('admin/olts/'.$d['olt_id']).'#unregistered' }}"
                                       class="btn btn-sm btn-success notif-go" data-id="{{ $n->id }}">
                                        <i class="fas fa-arrow-right mr-1"></i> Buka
                                    </a>
                                @endif
                                <button class="btn btn-sm btn-outline-danger notif-del" data-id="{{ $n->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if($items->hasPages())
        <div class="card-footer">{{ $items->links() }}</div>
    @endif
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#markAllReadBtn').on('click', function () {
        $.post('{{ route("admin.notifications.mark-all-read") }}').done(() => location.reload());
    });
    $('.notif-go').on('click', function () {
        const id = $(this).data('id');
        $.post('{{ url("admin/notifications") }}/' + id + '/read');
    });
    $('.notif-del').on('click', function () {
        const id = $(this).data('id');
        const $row = $(this).closest('.list-group-item');
        Swal.fire({title:'Hapus notifikasi?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33'})
        .then(r => { if (r.isConfirmed) {
            $.ajax({ url: '{{ url("admin/notifications") }}/' + id, method: 'DELETE' })
                .done(() => $row.fadeOut(200, () => $row.remove()));
        }});
    });
});
</script>
@endpush
