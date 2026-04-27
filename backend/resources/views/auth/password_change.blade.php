@extends('layouts.app')

@section('title', 'Cambiar contrasena')

@section('content')
    <div class="page-header">
        <h1>Cambiar contrasena</h1>
        <p class="muted">Debes actualizar tu clave para continuar.</p>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('password.change.update') }}" class="form">
            @csrf
            @method('PUT')

            <label for="current_password">Contrasena actual</label>
            <input id="current_password" type="password" name="current_password" required>
            @error('current_password')
                <small class="error">{{ $message }}</small>
            @enderror

            <label for="password">Nueva contrasena</label>
            <input id="password" type="password" name="password" required>
            @error('password')
                <small class="error">{{ $message }}</small>
            @enderror

            <label for="password_confirmation">Confirmar nueva contrasena</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <div class="actions-row">
                <button type="submit" class="btn">Actualizar contrasena</button>
            </div>
        </form>
    </section>
@endsection
