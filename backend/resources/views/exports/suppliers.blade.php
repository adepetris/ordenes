<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Proveedores</title>
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

    <h1>Reporte de Proveedores</h1>
    <div class="meta">
        <p>Busqueda: {{ $filters['q'] !== '' ? $filters['q'] : 'Sin filtro' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nº Proveedor</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Telefono</th>
                <th>Estado</th>
                <th>Fecha alta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->tax_id }}</td>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->email ?: '-' }}</td>
                    <td>{{ $supplier->phone ?: '-' }}</td>
                    <td>{{ $supplier->status }}</td>
                    <td>{{ $supplier->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Sin datos para exportar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
