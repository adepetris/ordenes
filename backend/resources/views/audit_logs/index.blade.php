@extends('layouts.app')

@section('title', 'Auditoria')

@section('content')
    <div class="page-header">
        <h1>Auditoria del sistema</h1>
        <p class="muted">Filtra eventos clave por accion, usuario, entidad y fecha.</p>
        <a href="{{ route('audit-logs.export', array_filter($filters, fn ($value) => $value !== '')) }}" class="btn btn-outline" target="_blank" rel="noopener">
            Exportar PDF
        </a>
    </div>

    <section class="card form-card">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="audit-filters">
            <select name="action">
                <option value="">Todas las acciones</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ \App\Support\UiLabels::auditAction($action) }}</option>
                @endforeach
            </select>

            <select name="entity">
                <option value="">Todas las entidades</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity }}" @selected($filters['entity'] === $entity)>{{ \App\Support\UiLabels::auditEntity($entity) }}</option>
                @endforeach
            </select>

            <select name="user_id">
                <option value="">Todos los usuarios</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected($filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}">

            <button type="submit" class="btn">Filtrar</button>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-outline">Limpiar</a>
        </form>
    </section>

    <section class="card table-card">
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
                        <td>{{ $log->local_created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->name ?: 'Sistema' }}</td>
                        <td>{{ \App\Support\UiLabels::auditAction($log->action) }}</td>
                        <td>{{ \App\Support\UiLabels::auditEntity($log->entity) }}</td>
                        <td>{{ $log->entity_id }}</td>
                        <td>
                            @if(is_array($log->diff_json) && count($log->diff_json) > 0)
                                <details>
                                    <summary>Ver detalle</summary>
                                    <pre class="audit-json">{{ json_encode($log->diff_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No hay eventos para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $logs->links() }}
        </div>
    </section>
@endsection
