@extends('layouts.app')
@section('title', 'Nueva empresa')
@section('content')
    <div class="page-header"><h1>Nueva empresa</h1></div>
    <section class="card form-card">
        <form method="POST" action="{{ route('billing-companies.store') }}" class="form">
            @include('billing_companies._form')
        </form>
    </section>
@endsection
