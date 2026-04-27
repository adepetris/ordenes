@extends('layouts.app')

@section('title', 'Nuevo usuario')

@section('content')
    <div class="page-header">
        <h1>Nuevo usuario</h1>
        <p class="muted">Creacion de usuario con perfiles multiples.</p>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('users.store') }}" class="form">
            @include('users._form')
        </form>
    </section>
@endsection
