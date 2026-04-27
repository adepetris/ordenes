@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="page-header page-header-inline">
        <div>
            <h1>Usuarios</h1>
            <p class="muted">Administracion de accesos y perfiles.</p>
        </div>

        <div class="actions-row compact-actions">
            <a href="{{ route('users.create') }}" class="btn">Nuevo usuario</a>
        </div>
    </div>

    <section class="card form-card">
        <form method="GET" action="{{ route('users.index') }}" class="search-form">
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nombre, usuario o correo">
            <button type="submit" class="btn">Buscar</button>
            @if($search !== '')
                <a href="{{ route('users.index') }}" class="btn btn-outline">Limpiar</a>
            @endif
        </form>

        @if($errors->has('users'))
            <small class="error">{{ $errors->first('users') }}</small>
        @endif
    </section>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Perfiles</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email ?: '-' }}</td>
                        <td>{{ $user->is_active ? 'Activo' : 'Inactivo' }}</td>
                        <td>{{ $user->roles->pluck('description')->filter()->implode(', ') ?: $user->roles->pluck('name')->implode(', ') }}</td>
                        <td>
                            <a href="{{ route('users.edit', $user) }}" class="table-link">Editar</a>
                            @if(! $user->hasRole('administrador'))
                                <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="table-link" onclick="return confirm('Se eliminara el usuario seleccionado.');">Eliminar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $users->links() }}
        </div>
    </section>
@endsection
