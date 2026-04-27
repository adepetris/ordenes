@extends('layouts.app')

@section('title', 'Editar orden')

@section('content')
    <div class="page-header">
        <h1>Editar orden #{{ $order->id }}</h1>
        <p class="muted">Solo disponible para ordenes en borrador.</p>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('orders.update', $order) }}" class="form">
            @method('PUT')
            @include('orders._form', ['order' => $order])
        </form>
    </section>
@endsection
