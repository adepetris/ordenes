<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $kpis = [
            'pending_approval' => PurchaseOrder::query()->where('status', 'pending_approval')->count(),
            'approved_month_count' => PurchaseOrder::query()
                ->where('status', 'approved')
                ->where('approved_at', '>=', $monthStart)
                ->count(),
            'rejected_count' => PurchaseOrder::query()->where('status', 'rejected')->count(),
            'active_suppliers' => Supplier::query()->where('status', 'active')->count(),
            'orders_month_count' => PurchaseOrder::query()->where('created_at', '>=', $monthStart)->count(),
        ];

        $statusBreakdown = PurchaseOrder::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $recentOrders = PurchaseOrder::query()
            ->with(['supplier', 'requester'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $topSuppliers = PurchaseOrder::query()
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.status', 'approved')
            ->where('purchase_orders.approved_at', '>=', $monthStart)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->select('suppliers.name')
            ->selectRaw('COUNT(purchase_orders.id) as total_orders')
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'kpis' => $kpis,
            'statusBreakdown' => $statusBreakdown,
            'recentOrders' => $recentOrders,
            'topSuppliers' => $topSuppliers,
        ]);
    }
}
