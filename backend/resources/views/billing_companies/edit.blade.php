@extends('layouts.app')
@section('title', 'Editar empresa')
@section('content')
    <div class="page-header"><h1>Editar empresa</h1></div>
    <section class="card form-card">
        <form method="POST" action="{{ route('billing-companies.update', $company) }}" class="form">
            @method('PUT')
            @include('billing_companies._form')
        </form>
    </section>
@endsection
