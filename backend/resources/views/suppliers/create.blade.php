@extends('layouts.app')

@section('title', 'Nuevo proveedor')

@section('content')
    <div class="page-header">
        <h1>Nuevo proveedor</h1>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('suppliers.store') }}" class="form">
            @include('suppliers._form')
        </form>
    </section>
@endsection
