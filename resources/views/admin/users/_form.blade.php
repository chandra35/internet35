<form id="userForm" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
    @csrf
    @if($user)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name">Nama <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="{{ $user->name ?? '' }}" required placeholder="Masukkan nama lengkap">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="{{ $user->email ?? '' }}" required placeholder="contoh@email.com">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="phone">No. Telepon</label>
                <input type="text" class="form-control" id="phone" name="phone" 
                       value="{{ $user->phone ?? '' }}" placeholder="08xxxxxxxxxx">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="roles">Roles <span class="text-danger">*</span></label>
                <select class="form-control select2" id="roles" name="roles[]" multiple required style="width:100%;">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" 
                                {{ $user && $user->hasRole($role->name) ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="password">Password @if(!$user)<span class="text-danger">*</span>@endif</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" 
                           {{ $user ? '' : 'required' }} minlength="8" placeholder="Minimal 8 karakter">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                @if($user)
                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password @if(!$user)<span class="text-danger">*</span>@endif</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" 
                           {{ $user ? '' : 'required' }} minlength="8" placeholder="Ulangi password">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="password_confirmation">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                   {{ !$user || $user->is_active ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">User Aktif</label>
        </div>
    </div>

    <div class="modal-footer px-0 pb-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
    </div>
</form>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-pw').on('click', function() {
        const target = $(this).data('target');
        const input = $(`#${target}`);
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
</script>
