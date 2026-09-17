<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\PurchaseOrderNumberService;
use App\Services\PurchaseOrderTotalsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderTotalsService $totalsService,
        private readonly PurchaseOrderNumberService $numberService,
        private readonly AuditLogService $auditLogService,
    )
    {
    }

    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));

        $orders = $this->applyStatusFilter(
            PurchaseOrder::query()->with(['supplier', 'requester']),
            $status,
        )
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'status' => $status,
        ]);
    }

    public function export(Request $request): Response
    {
        $status = trim((string) $request->query('status', ''));
        $fileName = 'purchase_orders_'.now()->format('Ymd_His').'.pdf';

        $orders = $this->applyStatusFilter(PurchaseOrder::query()->with(['supplier', 'requester']), $status)
            ->orderByDesc('id')
            ->get();

        $pdf = Pdf::loadView('exports.orders', [
            'orders' => $orders,
            'filters' => [
                'status' => $status,
            ],
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function exportSingle(PurchaseOrder $order): Response
    {
        if ($order->status !== 'approved') {
            abort(422, 'Solo se permite exportar una orden aprobada.');
        }

        $order->load(['supplier', 'requester', 'items', 'approvals.approver']);
        $fileName = 'purchase_order_'.($order->order_number ?: $order->id).'.pdf';

        $pdf = Pdf::loadView('exports.order_single', [
            'order' => $order,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function create(): View
    {
        return view('orders.create', [
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $totals = $this->totalsService->calculate($validated['items']);

        $order = null;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $orderNumber = $this->numberService->generateNext();

                $order = DB::transaction(function () use ($request, $validated, $totals, $orderNumber) {
                    $order = PurchaseOrder::query()->create([
                        'order_number' => $orderNumber,
                        'supplier_id' => $validated['supplier_id'],
                        'requester_id' => $request->user()->id,
                        'status' => 'draft',
                        'currency' => strtoupper($validated['currency']),
                        'notes' => $validated['notes'] ?? null,
                        'subtotal' => $totals['subtotal'],
                        'tax_total' => $totals['tax_total'],
                        'grand_total' => $totals['grand_total'],
                    ]);

                    $order->items()->createMany($totals['items']);

                    $this->auditLogService->log(
                        userId: $request->user()?->id,
                        entity: 'purchase_order',
                        entityId: $order->id,
                        action: 'order_created',
                        diff: [
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'supplier_id' => $order->supplier_id,
                            'currency' => $order->currency,
                            'subtotal' => $order->subtotal,
                            'tax_total' => $order->tax_total,
                            'grand_total' => $order->grand_total,
                            'items_count' => count($totals['items']),
                        ],
                    );

                    return $order;
                });

                break;
            } catch (QueryException $exception) {
                if (! $this->isOrderNumberCollision($exception) || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        if (! $order) {
            throw new RuntimeException('No se pudo generar numero de orden.');
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Orden creada en borrador.');
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['supplier', 'requester', 'items', 'approvals.approver', 'attachments.uploader']);
        $auditLogs = AuditLog::query()
            ->where('entity', 'purchase_order')
            ->where('entity_id', $order->id)
            ->with('user')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('orders.show', [
            'order' => $order,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function edit(PurchaseOrder $order): View
    {
        if (! in_array($order->status, ['draft', 'rejected'], true)) {
            abort(422, 'Solo se pueden editar ordenes en borrador.');
        }

        return view('orders.edit', [
            'order' => $order->load('items'),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $order): RedirectResponse
    {
        if (! in_array($order->status, ['draft', 'rejected'], true)) {
            abort(422, 'Solo se pueden editar ordenes en borrador.');
        }

        $validated = $request->validated();
        $totals = $this->totalsService->calculate($validated['items']);
        $before = $order->only(['status', 'supplier_id', 'currency', 'subtotal', 'tax_total', 'grand_total']);

        DB::transaction(function () use ($order, $validated, $totals, $before): void {
            $order->update([
                'supplier_id' => $validated['supplier_id'],
                'currency' => strtoupper($validated['currency']),
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
            ]);

            $order->items()->delete();
            $order->items()->createMany($totals['items']);

            $this->auditLogService->log(
                userId: auth()->id(),
                entity: 'purchase_order',
                entityId: $order->id,
                action: 'order_updated',
                diff: [
                    'before' => $before,
                    'after' => $order->only(['status', 'supplier_id', 'currency', 'subtotal', 'tax_total', 'grand_total']),
                    'items_count' => count($totals['items']),
                ],
            );
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Orden actualizada correctamente.');
    }

    public function submit(PurchaseOrder $order): RedirectResponse
    {
        if (! in_array($order->status, ['draft', 'rejected'], true)) {
            abort(422, 'Solo se pueden enviar ordenes en borrador o rechazadas.');
        }

        $user = auth()->user();
        $canAutoApprove = (bool) $user?->hasAnyRole(['administrador', 'usuario_autorizado']);

        if (! $canAutoApprove) {
            $order->update([
                'status' => 'pending_approval',
                'submitted_at' => now(),
                'approved_at' => null,
                'approved_by' => null,
                'rejected_reason' => null,
            ]);

            $this->auditLogService->log(
                userId: auth()->id(),
                entity: 'purchase_order',
                entityId: $order->id,
                action: 'order_submitted',
                diff: [
                    'status' => $order->status,
                    'grand_total' => $order->grand_total,
                    'auto_approval_allowed' => false,
                    'approval_rule' => 'role_level',
                ],
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Orden enviada a aprobacion.');
        }

        DB::transaction(function () use ($order): void {
            $order->update([
                'status' => 'approved',
                'submitted_at' => now(),
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejected_reason' => null,
            ]);

            Approval::query()->create([
                'purchase_order_id' => $order->id,
                'approver_id' => auth()->id(),
                'level' => 1,
                'decision' => 'auto_approved',
                'comment' => 'Autoaprobada por nivel de usuario.',
                'decided_at' => now(),
            ]);

            $this->auditLogService->log(
                userId: auth()->id(),
                entity: 'purchase_order',
                entityId: $order->id,
                action: 'order_auto_approved',
                diff: [
                    'status' => $order->status,
                    'grand_total' => $order->grand_total,
                    'auto_approval_allowed' => true,
                    'approval_rule' => 'role_level',
                ],
            );
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Orden autoaprobada por nivel de usuario.');
    }

    public function approvalsInbox(): View
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'requester'])
            ->where('status', 'pending_approval')
            ->orderBy('submitted_at')
            ->paginate(10);

        return view('orders.approvals', [
            'orders' => $orders,
        ]);
    }

    public function approve(PurchaseOrder $order): RedirectResponse
    {
        if ($order->status !== 'pending_approval') {
            abort(422, 'La orden no esta pendiente de aprobacion.');
        }

        DB::transaction(function () use ($order): void {
            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejected_reason' => null,
            ]);

            Approval::query()->create([
                'purchase_order_id' => $order->id,
                'approver_id' => auth()->id(),
                'level' => 1,
                'decision' => 'approved',
                'comment' => null,
                'decided_at' => now(),
            ]);

            $this->auditLogService->log(
                userId: auth()->id(),
                entity: 'purchase_order',
                entityId: $order->id,
                action: 'order_approved',
                diff: [
                    'status' => $order->status,
                ],
            );
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Orden aprobada correctamente.');
    }

    public function reject(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if ($order->status !== 'pending_approval') {
            abort(422, 'La orden no esta pendiente de aprobacion.');
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $order->update([
                'status' => 'rejected',
                'approved_at' => null,
                'approved_by' => null,
                'rejected_reason' => $validated['comment'],
            ]);

            Approval::query()->create([
                'purchase_order_id' => $order->id,
                'approver_id' => auth()->id(),
                'level' => 1,
                'decision' => 'rejected',
                'comment' => $validated['comment'],
                'decided_at' => now(),
            ]);

            $this->auditLogService->log(
                userId: auth()->id(),
                entity: 'purchase_order',
                entityId: $order->id,
                action: 'order_rejected',
                diff: [
                    'status' => $order->status,
                    'comment' => $validated['comment'],
                ],
            );
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Orden rechazada.');
    }

    public function cancel(PurchaseOrder $order): RedirectResponse
    {
        if ($order->status === 'cancelled') {
            abort(422, 'La orden ya esta anulada.');
        }

        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $previousStatus): void {
            $order->update([
                'status' => 'cancelled',
            ]);

            $this->auditLogService->log(
                userId: auth()->id(),
                entity: 'purchase_order',
                entityId: $order->id,
                action: 'order_cancelled',
                diff: [
                    'previous_status' => $previousStatus,
                    'status' => $order->status,
                ],
            );
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Orden anulada correctamente.');
    }

    public function uploadAttachment(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv,txt'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $storedName = Str::uuid()->toString().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('order_attachments/'.$order->id, $storedName, 'local');

        $attachment = $order->attachments()->create([
            'uploaded_by' => $request->user()?->id,
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        $this->auditLogService->log(
            userId: $request->user()?->id,
            entity: 'purchase_order',
            entityId: $order->id,
            action: 'attachment_uploaded',
            diff: [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'size' => $attachment->size,
            ],
        );

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Adjunto cargado correctamente.');
    }

    public function downloadAttachment(PurchaseOrder $order, PurchaseOrderAttachment $attachment): BinaryFileResponse
    {
        if ($attachment->purchase_order_id !== $order->id) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'El archivo no existe en almacenamiento.');
        }

        return response()->download(Storage::disk('local')->path($attachment->path), $attachment->file_name);
    }

    private function isOrderNumberCollision(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'order_number')
            && (str_contains($message, 'unique') || str_contains($message, 'duplicate'));
    }

    private function applyStatusFilter(Builder $query, string $status): Builder
    {
        return $query->when($status !== '', fn ($builder) => $builder->where('status', $status));
    }
}
