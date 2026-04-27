@extends('layouts.app')

@section('title', 'Nueva orden')

@section('content')
    <div class="page-header">
        <h1>Nueva orden de compra</h1>
        <p class="muted">Se guardara en estado borrador.</p>
    </div>

    <section class="card form-card">
        <form method="POST" action="{{ route('orders.store') }}" class="form">
            @include('orders._form')
        </form>
    </section>
@endsection
