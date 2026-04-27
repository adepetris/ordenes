<?php

namespace App\Services;

use App\Models\PurchaseOrder;

class PurchaseOrderNumberService
{
    public function generateNext(): string
    {
        $year = now()->format('Y');
        $prefix = "OC-{$year}-";

        $latestNumber = PurchaseOrder::query()
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $nextSequence = 1;

        if (is_string($latestNumber) && str_starts_with($latestNumber, $prefix)) {
            $latestSequence = (int) substr($latestNumber, strlen($prefix));
            $nextSequence = $latestSequence + 1;
        }

        return sprintf('%s%06d', $prefix, $nextSequence);
    }
}
