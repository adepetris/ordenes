<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierNumberService
{
    public function generateNext(): string
    {
        $prefix = 'PRV-';

        $latestNumber = Supplier::query()
            ->where('tax_id', 'like', $prefix.'%')
            ->orderByDesc('tax_id')
            ->value('tax_id');

        $nextSequence = 1;

        if (is_string($latestNumber) && str_starts_with($latestNumber, $prefix)) {
            $latestSequence = (int) substr($latestNumber, strlen($prefix));
            $nextSequence = $latestSequence + 1;
        }

        return sprintf('%s%06d', $prefix, $nextSequence);
    }
}
