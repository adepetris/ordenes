@csrf

<div class="grid-2">
    <div>
        <label for="name">Nombre</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="username">Usuario</label>
        <input id="username" type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required>
        @error('username')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="email">Correo (opcional)</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}">
        @error('email')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="is_active">Estado</label>
        @php($active = old('is_active', isset($user) ? (int) $user->is_active : 1))
        <select id="is_active" name="is_active">
            <option value="1" @selected((string) $active === '1')>Activo</option>
            <option value="0" @selected((string) $active === '0')>Inactivo</option>
        </select>
        @error('is_active')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    @if(!isset($user))
        <div>
            <label for="password">Contrasena inicial</label>
            <input id="password" type="password" name="password" required>
            @error('password')
                <small class="error">{{ $message }}</small>
            @enderror
        </div>
    @endif
</div>

<div>
    <label>Perfiles</label>
    @php($selectedRoles = collect(old('roles', isset($user) ? $user->roles->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all())
    <div class="role-grid">
        @foreach($roles as $role)
            <label class="remember">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoles, true))>
                {{ $role->description ?: $role->name }}
            </label>
        @endforeach
    </div>
    @error('roles')
        <small class="error">{{ $message }}</small>
    @enderror
    @error('roles.*')
        <small class="error">{{ $message }}</small>
    @enderror
</div>

<div class="actions-row">
    <button type="submit" class="btn">Guardar</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline">Cancelar</a>
</div>
