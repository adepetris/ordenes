@extends('layouts.app')

@section('title', 'Detalle orden')

@section('content')
    <div class="page-header page-header-inline">
        <div>
            <h1>Orden {{ $order->order_number ?: ('#'.$order->id) }}</h1>
            <p class="muted">Estado: {{ \App\Support\UiLabels::orderStatus($order->status) }}</p>
        </div>

        <div class="actions-row compact-actions">
            @if(in_array($order->status, ['draft', 'rejected'], true) && auth()->user()->hasAnyRole(['administrador', 'usuario_solicitante', 'usuario_autorizado']))
                <a href="{{ route('orders.edit', $order) }}" class="btn">Editar borrador</a>
                <form action="{{ route('orders.submit', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline">Enviar a aprobacion</button>
                </form>
            @endif

            @if($order->status === 'pending_approval' && auth()->user()->hasAnyRole(['administrador', 'usuario_autorizado']))
                <form action="{{ route('orders.approve', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn">Aprobar</button>
                </form>

                <form action="{{ route('orders.reject', $order) }}" method="POST" class="reject-form">
                    @csrf
                    <input type="text" name="comment" placeholder="Motivo de rechazo" required>
                    <button type="submit" class="btn btn-outline">Rechazar</button>
                </form>
            @endif

            @if($order->status === 'approved')
                <a href="{{ route('orders.export.single', $order) }}" class="btn btn-outline" target="_blank" rel="noopener">Exportar PDF</a>
            @endif
        </div>
    </div>

    <div class="cards">
        <article class="card">
            <h2>Proveedor</h2>
            <p>{{ $order->supplier->name }}</p>
            <p class="muted">{{ $order->supplier->tax_id }}</p>
        </article>

        <article class="card">
            <h2>Solicitante</h2>
            <p>{{ $order->requester->name }}</p>
            <p class="muted">{{ $order->requester->email }}</p>
        </article>

    </div>

    @if($order->notes)
        <section class="card form-card">
            <h2>Notas</h2>
            <p>{{ $order->notes }}</p>
        </section>
    @endif

    @if($order->rejected_reason)
        <section class="card form-card">
            <h2>Motivo de rechazo</h2>
            <p>{{ $order->rejected_reason }}</p>
        </section>
    @endif

    <section class="card form-card">
        <div class="page-header page-header-inline">
            <div>
                <h2>Adjuntos</h2>
                <p class="muted">Documentos de respaldo de la orden.</p>
            </div>
        </div>

        @if(auth()->user()->hasAnyRole(['administrador', 'usuario_solicitante', 'usuario_autorizado']))
            <form action="{{ route('orders.attachments.store', $order) }}" method="POST" enctype="multipart/form-data" class="upload-form">
                @csrf
                <input type="file" name="file" required>
                <button type="submit" class="btn">Subir archivo</button>
            </form>
            @error('file')
                <small class="error">{{ $message }}</small>
            @enderror
        @endif

        <table>
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tipo</th>
                    <th>Tamano</th>
                    <th>Subido por</th>
                    <th>Fecha</th>
                    <th>Accion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->attachments as $attachment)
                    <tr>
                        <td>{{ $attachment->file_name }}</td>
                        <td>{{ $attachment->mime_type ?: '-' }}</td>
                        <td>{{ number_format($attachment->size / 1024, 2) }} KB</td>
                        <td>{{ $attachment->uploader?->name ?: 'Sistema' }}</td>
                        <td>{{ $attachment->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('orders.attachments.download', [$order, $attachment]) }}" class="table-link">Descargar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No hay adjuntos cargados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Descripcion</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format((float) $item->qty, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Accion</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs as $log)
                    <tr>
                        <td>{{ \App\Support\UiLabels::auditAction($log->action) }}</td>
                        <td>{{ $log->user?->name ?: 'Sistema' }}</td>
                        <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="muted">Sin trazabilidad registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>Decision</th>
                    <th>Aprobador</th>
                    <th>Comentario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->approvals as $approval)
                    <tr>
                        <td>{{ $approval->decision === 'approved' ? 'Aprobada' : ($approval->decision === 'rejected' ? 'Rechazada' : ($approval->decision === 'auto_approved' ? 'Autoaprobada' : $approval->decision)) }}</td>
                        <td>{{ $approval->approver?->name ?: 'Sistema' }}</td>
                        <td>{{ $approval->comment ?: '-' }}</td>
                        <td>{{ $approval->decided_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">Sin historial de aprobacion.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
