@extends('layouts.app')

@section('title', 'Bandeja de aprobaciones')

@section('content')
    <div class="page-header">
        <h1>Bandeja de aprobaciones</h1>
        <p class="muted">Ordenes pendientes por aprobar o rechazar.</p>
    </div>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Numero</th>
                    <th>Proveedor</th>
                    <th>Solicitante</th>
                    <th>Enviada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number ?: ('#'.$order->id) }}</td>
                        <td>{{ $order->supplier->name }}</td>
                        <td>{{ $order->requester->name }}</td>
                        <td>{{ optional($order->submitted_at)->format('Y-m-d H:i') ?: '-' }}</td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="table-link">Revisar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">No hay ordenes pendientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $orders->links() }}
        </div>
    </section>
@endsection
