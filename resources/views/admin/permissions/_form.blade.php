<form id="permissionForm" action="{{ $permission ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}" method="POST">
    @csrf
    @if($permission)
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="name">Nama Permission <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" 
               value="{{ $permission->name ?? '' }}" {{ $permission ? 'readonly' : 'required' }}>
        @if(!$permission)
            <small class="text-muted">Format: module.action (contoh: users.create)</small>
        @endif
    </div>

    <div class="form-group">
        <label for="group">Group</label>
        <input type="text" class="form-control" id="group" name="group" 
               value="{{ $permission->group ?? '' }}" list="groupList">
        <datalist id="groupList">
            @foreach($groups as $g)
                <option value="{{ $g }}">
            @endforeach
        </datalist>
    </div>

    <div class="form-group">
        <label for="description">Deskripsi</label>
        <textarea class="form-control" id="description" name="description" rows="2">{{ $permission->description ?? '' }}</textarea>
    </div>

    <div class="modal-footer px-0 pb-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
    </div>
</form>
