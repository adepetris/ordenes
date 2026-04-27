@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
    <div class="page-header page-header-inline">
        <div>
            <h1>Proveedores</h1>
            <p class="muted">Gestion de proveedores para ordenes de compra.</p>
        </div>

        <div class="actions-row compact-actions">
            <a href="{{ route('suppliers.export', array_filter(['q' => $search], fn ($value) => $value !== '')) }}" class="btn btn-outline" target="_blank" rel="noopener">
                Exportar PDF
            </a>

            @if(auth()->user()->hasAnyRole(['administrador', 'usuario_solicitante', 'usuario_autorizado']))
                <a href="{{ route('suppliers.create') }}" class="btn">Nuevo proveedor</a>
            @endif
        </div>
    </div>

    <section class="card form-card">
        <form method="GET" action="{{ route('suppliers.index') }}" class="search-form">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Buscar por nombre, Nº Proveedor o correo"
            >
            <button type="submit" class="btn">Buscar</button>
            @if($search !== '')
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline">Limpiar</a>
            @endif
        </form>
    </section>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Nº Proveedor</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Telefono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->tax_id }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->email ?: '-' }}</td>
                        <td>{{ $supplier->phone ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $supplier->status === 'active' ? 'badge-ok' : 'badge-off' }}">
                                {{ $supplier->status === 'active' ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            @if(auth()->user()->hasAnyRole(['administrador', 'usuario_autorizado']))
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="table-link">Editar</a>
                            @else
                                <span class="muted">Sin permisos</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No hay proveedores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $suppliers->links() }}
        </div>
    </section>
@endsection
