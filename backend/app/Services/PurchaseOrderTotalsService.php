<?php

namespace App\Services;

class PurchaseOrderTotalsService
{
    public function calculate(array $items): array
    {
        $subtotal = 0;
        $taxTotal = 0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineSubtotal = round($qty * $unitPrice, 2);
            $lineTax = round($lineSubtotal * ($taxRate / 100), 2);
            $lineTotal = round($lineSubtotal + $lineTax, 2);

            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;

            $normalizedItems[] = [
                'description' => $item['description'],
                'qty' => $qty,
                'unit' => $item['unit'],
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => round($subtotal + $taxTotal, 2),
            'items' => $normalizedItems,
        ];
    }
}
