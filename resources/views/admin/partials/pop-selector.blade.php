{{--
  Reusable POP Selector for Superadmin.
  Usage: @include('admin.partials.pop-selector', ['popUsers' => $popUsers, 'popId' => $popId])
--}}
@if(isset($popUsers) && $popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-user-shield text-info fa-lg"></i>
                <strong class="ml-2">Mode Superadmin:</strong>
            </div>
            <div class="col-md-4">
                <select class="form-control select2" id="selectPop" onchange="changePop(this.value)">
                    <option value="">-- Pilih POP --</option>
                    @foreach($popUsers as $pop)
                        <option value="{{ $pop->id }}" {{ ($popId ?? null) == $pop->id ? 'selected' : '' }}>
                            {{ $pop->name }} ({{ $pop->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            @if(!($popId ?? null))
            <div class="col-auto">
                <span class="badge badge-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Pilih POP untuk menampilkan data
                </span>
            </div>
            @endif
        </div>
    </div>
</div>
@once
<script>
function changePop(popId) {
    window.location.href = window.location.pathname + '?pop_id=' + encodeURIComponent(popId);
}
</script>
@endonce
@endif
