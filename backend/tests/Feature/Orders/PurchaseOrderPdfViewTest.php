<?php

namespace Tests\Feature\Orders;

use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PurchaseOrderPdfViewTest extends TestCase
{
    public function test_notes_are_rendered_below_items_preserving_line_breaks(): void
    {
        $order = $this->orderForPdf("Emanuel reparaciones planta 2.\nRetira Caldez <urgente>");

        $html = view('exports.order_single', [
            'order' => $order,
            'generatedAt' => Carbon::parse('2026-08-11 10:00:00'),
        ])->render();

        $itemsTableEnd = strpos($html, '</table>', strpos($html, '<p class="section-title">Items</p>'));
        $notesTitle = strpos($html, '<p class="section-title">Notas</p>');

        $this->assertNotFalse($itemsTableEnd);
        $this->assertNotFalse($notesTitle);
        $this->assertGreaterThan($itemsTableEnd, $notesTitle);
        $this->assertStringContainsString('Emanuel reparaciones planta 2.<br', $html);
        $this->assertStringContainsString('Retira Caldez &lt;urgente&gt;', $html);
        $this->assertStringNotContainsString('Retira Caldez <urgente>', $html);
    }

    public function test_notes_block_is_not_rendered_when_notes_are_empty(): void
    {
        foreach ([null, '', '   '] as $notes) {
            $html = view('exports.order_single', [
                'order' => $this->orderForPdf($notes),
                'generatedAt' => Carbon::parse('2026-08-11 10:00:00'),
            ])->render();

            $this->assertStringNotContainsString('<p class="section-title">Notas</p>', $html);
        }
    }

    private function orderForPdf(?string $notes): PurchaseOrder
    {
        $order = new PurchaseOrder([
            'order_number' => 'OC-2026-001500',
            'status' => 'approved',
            'notes' => $notes,
        ]);
        $order->created_at = Carbon::parse('2026-08-10 09:00:00');
        $order->setRelation('supplier', null);
        $order->setRelation('requester', null);
        $order->setRelation('items', collect());

        return $order;
    }
}
