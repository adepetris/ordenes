@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
    <div class="page-header page-header-inline">
        <div>
            <h1>Empresas</h1>
            <p class="muted">Empresas disponibles para «Facturar a» en las órdenes.</p>
        </div>
        @if(auth()->user()->hasAnyRole(['administrador', 'usuario_autorizado']))
            <a href="{{ route('billing-companies.create') }}" class="btn">Nueva empresa</a>
        @endif
    </div>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Nº Empresa</th>
                    <th>Nombre de Empresa</th>
                    <th>CUIT</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td>{{ $company->company_number }}</td>
                        <td>{{ $company->name }}</td>
                        <td>{{ $company->cuit }}</td>
                        <td>
                            @if(auth()->user()->hasAnyRole(['administrador', 'usuario_autorizado']))
                                <a href="{{ route('billing-companies.edit', $company) }}" class="table-link">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No hay empresas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $companies->links() }}</div>
    </section>
@endsection
