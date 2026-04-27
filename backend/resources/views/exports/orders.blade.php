<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ordenes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-table td { border: none; padding: 0; vertical-align: middle; }
        .brand-left { width: 70%; }
        .brand-left img { height: 56px; }
        .brand-name { margin-top: 2px; font-size: 16px; font-weight: 700; letter-spacing: 0.5px; }
        .header-right { width: 30%; text-align: right; font-size: 10px; line-height: 1.4; }
        .header-divider { border: 0; border-top: 2px solid #111827; margin: 0 0 12px; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        p { margin: 0 0 6px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="brand-left">
                <img src="{{ public_path('images/grupo-depetris-logo.svg') }}" alt="Logo GRUPO DEPETRIS">
                <div class="brand-name">GRUPO DEPETRIS</div>
            </td>
            <td class="header-right">
                <strong>Fecha de emision</strong><br>
                {{ $generatedAt->format('Y-m-d H:i:s') }}
            </td>
        </tr>
    </table>
    <hr class="header-divider">

    <h1>Reporte de Ordenes de Compra</h1>
    <div class="meta">
        <p>Estado: {{ $filters['status'] !== '' ? $filters['status'] : 'Todos' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Numero</th>
                <th>Estado</th>
                <th>Proveedor</th>
                <th>Solicitante</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->order_number ?: ('#'.$order->id) }}</td>
                    <td>{{ \App\Support\UiLabels::orderStatus($order->status) }}</td>
                    <td>{{ $order->supplier?->name ?: '-' }}</td>
                    <td>{{ $order->requester?->name ?: '-' }}</td>
                    <td>{{ $order->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin datos para exportar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
