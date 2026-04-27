<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\SupplierNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SupplierNumberService $numberService,
    )
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $suppliers = $this->applySearchFilter(Supplier::query(), $search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'search' => $search,
        ]);
    }

    public function export(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $fileName = 'suppliers_'.now()->format('Ymd_His').'.pdf';

        $suppliers = $this->applySearchFilter(Supplier::query(), $search)
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('exports.suppliers', [
            'suppliers' => $suppliers,
            'filters' => [
                'q' => $search,
            ],
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = null;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $supplierNumber = $this->numberService->generateNext();
                $supplier = Supplier::query()->create([
                    ...$request->validated(),
                    'tax_id' => $supplierNumber,
                ]);

                break;
            } catch (QueryException $exception) {
                if (! $this->isSupplierNumberCollision($exception) || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        $this->auditLogService->log(
            userId: $request->user()?->id,
            entity: 'purchase_order_supplier',
            entityId: $supplier->id,
            action: 'supplier_created',
            diff: [
                'name' => $supplier->name,
                'tax_id' => $supplier->tax_id,
                'status' => $supplier->status,
            ],
        );

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $before = $supplier->only(['tax_id', 'name', 'email', 'phone', 'status']);
        $supplier->update($request->validated());

        $this->auditLogService->log(
            userId: $request->user()?->id,
            entity: 'purchase_order_supplier',
            entityId: $supplier->id,
            action: 'supplier_updated',
            diff: [
                'before' => $before,
                'after' => $supplier->only(['tax_id', 'name', 'email', 'phone', 'status']),
            ],
        );

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    private function applySearchFilter(Builder $query, string $search): Builder
    {
        $searchTerm = mb_strtolower($search);

        return $query->when($search !== '', function ($builder) use ($searchTerm): void {
            $builder->where(function ($inner) use ($searchTerm): void {
                $like = "%{$searchTerm}%";

                $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(tax_id) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$like]);
            });
        });
    }

    private function isSupplierNumberCollision(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'tax_id')
            && (str_contains($message, 'unique') || str_contains($message, 'duplicate'));
    }
}
