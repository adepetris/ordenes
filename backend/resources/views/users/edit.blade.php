@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <div class="page-header">
        <h1>Editar usuario</h1>
        <p class="muted">Actualiza datos, estado y perfiles del usuario.</p>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('users.update', $user) }}" class="form">
            @method('PUT')
            @include('users._form', ['user' => $user])
        </form>
    </section>

    <section class="card form-card">
        <h2>Restablecer contrasena</h2>
        <form method="POST" action="{{ route('users.password.reset', $user) }}" class="form">
            @csrf
            <label for="new_password">Nueva contrasena temporal</label>
            <input id="new_password" type="password" name="new_password" required>
            @error('new_password')
                <small class="error">{{ $message }}</small>
            @enderror

            <div class="actions-row">
                <button type="submit" class="btn btn-outline">Restablecer contrasena</button>
            </div>
        </form>
    </section>

    @if(! $user->hasRole('administrador'))
        <section class="card form-card">
            <h2>Eliminar usuario</h2>
            <p class="muted">Esta accion elimina el usuario de forma permanente.</p>
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="form">
                @csrf
                @method('DELETE')
                <div class="actions-row">
                    <button type="submit" class="btn btn-outline" onclick="return confirm('Se eliminara el usuario seleccionado.');">Eliminar usuario</button>
                </div>
            </form>
        </section>
    @endif
@endsection
