@extends('layouts.app')

@section('title', 'Editar proveedor')

@section('content')
    <div class="page-header">
        <h1>Editar proveedor</h1>
        <p class="muted">Actualiza los datos del proveedor.</p>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="form">
            @method('PUT')
            @include('suppliers._form', ['supplier' => $supplier])
        </form>
    </section>
@endsection
