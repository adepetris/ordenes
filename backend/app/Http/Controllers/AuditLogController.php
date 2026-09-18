<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $logs = $this->applyFilters(AuditLog::query()->with('user'), $filters)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $entities = AuditLog::query()->select('entity')->distinct()->orderBy('entity')->pluck('entity');
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('audit_logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'entities' => $entities,
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): Response
    {
        $filters = $this->resolveFilters($request);
        $generatedAt = now()->setTimezone(config('app.audit_timezone'));
        $fileName = 'audit_logs_'.$generatedAt->format('Ymd_His').'.pdf';

        $logs = $this->applyFilters(AuditLog::query()->with('user'), $filters)
            ->orderByDesc('id')
            ->get();

        $pdf = Pdf::loadView('exports.audit_logs', [
            'logs' => $logs,
            'filters' => $filters,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'action' => trim((string) $request->query('action', '')),
            'entity' => trim((string) $request->query('entity', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $timezone = config('app.audit_timezone');
        $dateFrom = $filters['date_from'] !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $filters['date_from'], $timezone)->startOfDay()->utc()
            : null;
        $dateTo = $filters['date_to'] !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $filters['date_to'], $timezone)->endOfDay()->utc()
            : null;

        return $query
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['entity'] !== '', fn ($q) => $q->where('entity', $filters['entity']))
            ->when($filters['user_id'] !== '', fn ($q) => $q->where('user_id', (int) $filters['user_id']))
            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('created_at', '<=', $dateTo));
    }
}
