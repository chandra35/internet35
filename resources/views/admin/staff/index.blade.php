@extends('layouts.admin')

@section('title', 'Kelola Tim')

@section('page-title', 'Kelola Tim')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Kelola Tim</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users-cog mr-2"></i>
                    Daftar Staff
                </h3>
                <div class="card-tools">
                    @can('staff.create')
                    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i>Tambah Staff
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Cari</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, Email, HP..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control select2">
                                    <option value="">Semua Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control select2">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i>Filter
                                </button>
                                <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo mr-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No. HP</th>
                                <th>Role</th>
                                <th width="100">Status</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $index => $member)
                            <tr>
                                <td>{{ $staff->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $member->avatar_url }}" alt="" class="img-circle mr-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        <span>{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->phone ?? '-' }}</td>
                                <td>
                                    @foreach($member->roles as $role)
                                        <span class="badge badge-{{ $role->name == 'teknisi' ? 'info' : 'warning' }}">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @can('staff.edit')
                                    <button type="button" class="btn btn-sm btn-{{ $member->is_active ? 'success' : 'danger' }}" onclick="toggleStatus('{{ $member->id }}')" title="Klik untuk toggle status">
                                        <i class="fas fa-{{ $member->is_active ? 'check' : 'times' }}"></i>
                                        {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                    @else
                                        <span class="badge badge-{{ $member->is_active ? 'success' : 'danger' }}">
                                            {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    @endcan
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @can('staff.edit')
                                        <a href="{{ route('admin.staff.edit', $member) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('staff.delete')
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteStaff('{{ $member->id }}')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                    Belum ada staff. <a href="{{ route('admin.staff.create') }}">Tambah staff pertama</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Menampilkan {{ $staff->firstItem() ?? 0 }} - {{ $staff->lastItem() ?? 0 }} dari {{ $staff->total() }} data
                    </div>
                    {{ $staff->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function toggleStatus(id) {
    $.post(`{{ url('admin/staff') }}/${id}/toggle-status`, {
        _token: '{{ csrf_token() }}'
    }).done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            location.reload();
        }
    }).fail(function() {
        toastr.error('Gagal mengubah status');
    });
}

function deleteStaff(id) {
    Swal.fire({
        title: 'Hapus Staff?',
        text: 'Staff akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('admin/staff') }}/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('Gagal menghapus staff');
                }
            });
        }
    });
}
</script>
@endpush
