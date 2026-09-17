@extends('layouts.app')

@section('title', 'Ordenes')

@section('content')
    <div class="page-header page-header-inline">
        <div>
            <h1>Ordenes de compra</h1>
            <p class="muted">Listado de ordenes registradas.</p>
        </div>

        <div class="actions-row compact-actions">
            <a href="{{ route('orders.export', array_filter(['status' => $status], fn ($value) => $value !== '')) }}" class="btn btn-outline" target="_blank" rel="noopener">
                Exportar PDF
            </a>

            @if(auth()->user()->hasAnyRole(['administrador', 'usuario_solicitante', 'usuario_autorizado']))
                <a href="{{ route('orders.create') }}" class="btn">Nueva orden</a>
            @endif
        </div>
    </div>

    <section class="card form-card">
        <form method="GET" action="{{ route('orders.index') }}" class="search-form">
            <select name="status">
                <option value="">Todos los estados</option>
                @foreach(['draft' => 'Borrador', 'rejected' => 'Rechazada', 'pending_approval' => 'Pendiente', 'approved' => 'Aprobada', 'cancelled' => 'Anulada'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn">Filtrar</button>
            @if($status !== '')
                <a href="{{ route('orders.index') }}" class="btn btn-outline">Limpiar</a>
            @endif
        </form>
    </section>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Numero</th>
                    <th>Proveedor</th>
                    <th>Solicitante</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number ?: ('#'.$order->id) }}</td>
                        <td>{{ $order->supplier->name }}</td>
                        <td>{{ $order->requester->name }}</td>
                        <td><span class="badge {{ $order->status === 'cancelled' ? 'badge-cancelled' : (in_array($order->status, ['draft', 'approved'], true) ? 'badge-ok' : 'badge-off') }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span></td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="table-link">Ver</a>
                            @if(in_array($order->status, ['draft', 'rejected'], true) && auth()->user()->hasAnyRole(['administrador', 'usuario_solicitante', 'usuario_autorizado']))
                                <a href="{{ route('orders.edit', $order) }}" class="table-link">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No hay ordenes registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $orders->links() }}
        </div>
    </section>
@endsection
