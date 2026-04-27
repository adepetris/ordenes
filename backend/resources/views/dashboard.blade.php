@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header page-header-inline">
        <div>
            <h1>Dashboard operativo</h1>
            <p class="muted">Resumen del flujo de ordenes de compra.</p>
        </div>

        <div class="actions-row compact-actions">
            <a href="{{ route('orders.index') }}" class="btn">Ver ordenes</a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline">Ver proveedores</a>
        </div>
    </div>

    <div class="cards kpi-grid">
        <article class="card kpi-card">
            <p class="muted">Pendientes de aprobacion</p>
            <h2>{{ number_format($kpis['pending_approval']) }}</h2>
        </article>

        <article class="card kpi-card">
            <p class="muted">Aprobadas este mes</p>
            <h2>{{ number_format($kpis['approved_month_count']) }}</h2>
        </article>

        <article class="card kpi-card">
            <p class="muted">Rechazadas</p>
            <h2>{{ number_format($kpis['rejected_count']) }}</h2>
        </article>

        <article class="card kpi-card">
            <p class="muted">Proveedores activos</p>
            <h2>{{ number_format($kpis['active_suppliers']) }}</h2>
        </article>

        <article class="card kpi-card">
            <p class="muted">Ordenes creadas (mes)</p>
            <h2>{{ number_format($kpis['orders_month_count']) }}</h2>
        </article>
    </div>

    <section class="card table-card">
        <div class="card-header">
            <h2>Desglose por estado</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statusBreakdown as $row)
                    <tr>
                        <td>{{ \App\Support\UiLabels::orderStatus($row->status) }}</td>
                        <td>{{ number_format((int) $row->total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="muted">No hay datos de ordenes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="cards dashboard-grid-2">
        <section class="card table-card">
            <div class="card-header">
                <h2>Ultimas ordenes</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="table-link">
                                    {{ $order->order_number ?: ('#'.$order->id) }}
                                </a>
                            </td>
                            <td>{{ $order->supplier?->name ?: '-' }}</td>
                            <td>{{ \App\Support\UiLabels::orderStatus($order->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">Sin ordenes recientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="card table-card">
            <div class="card-header">
                <h2>Top proveedores (mes)</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Ordenes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topSuppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ number_format((int) $supplier->total_orders) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="muted">Sin proveedores con ordenes aprobadas este mes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
