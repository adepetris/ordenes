<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Auditoria</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-table td { border: none; padding: 0; vertical-align: middle; }
        .brand-left { width: 70%; }
        .brand-left img { height: 56px; }
        .brand-name { margin-top: 2px; font-size: 16px; font-weight: 700; letter-spacing: 0.5px; }
        .header-right { width: 30%; text-align: right; font-size: 10px; line-height: 1.4; }
        .header-divider { border: 0; border-top: 2px solid #111827; margin: 0 0 12px; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        p { margin: 0 0 4px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px; vertical-align: top; }
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

    <h1>Reporte de Auditoria</h1>
    <div class="meta">
        <p>Accion: {{ $filters['action'] !== '' ? \App\Support\UiLabels::auditAction($filters['action']) : 'Todas' }}</p>
        <p>Entidad: {{ $filters['entity'] !== '' ? \App\Support\UiLabels::auditEntity($filters['entity']) : 'Todas' }}</p>
        <p>Usuario ID: {{ $filters['user_id'] !== '' ? $filters['user_id'] : 'Todos' }}</p>
        <p>Desde: {{ $filters['date_from'] !== '' ? $filters['date_from'] : '-' }} / Hasta: {{ $filters['date_to'] !== '' ? $filters['date_to'] : '-' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Accion</th>
                <th>Entidad</th>
                <th>ID entidad</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->local_created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->user?->name ?: 'Sistema' }}</td>
                    <td>{{ \App\Support\UiLabels::auditAction($log->action) }}</td>
                    <td>{{ \App\Support\UiLabels::auditEntity($log->entity) }}</td>
                    <td>{{ $log->entity_id }}</td>
                    <td>{{ is_array($log->diff_json) ? json_encode($log->diff_json, JSON_UNESCAPED_UNICODE) : '-' }}</td>
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
