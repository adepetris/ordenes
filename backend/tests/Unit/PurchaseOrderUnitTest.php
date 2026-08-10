<?php

namespace Tests\Unit;

use App\Enums\PurchaseOrderUnit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseOrderUnitTest extends TestCase
{
    #[Test]
    public function it_exposes_the_supported_units_with_their_labels(): void
    {
        $units = collect(PurchaseOrderUnit::cases())
            ->mapWithKeys(fn (PurchaseOrderUnit $unit) => [$unit->value => $unit->label()])
            ->all();

        $this->assertSame([
            'Un' => 'Unidad/es',
            'Kg' => 'Kilogramo/s',
            'L' => 'Litro/s',
            'M' => 'Metro/s',
            'M2' => 'Metro/s cuadrado/s',
            'M3' => 'Metro/s cubico/s',
            'Caj' => 'Caja/s',
            'Bol' => 'Bolsa/s',
            'Rol' => 'Rollo/s',
            'Serv' => 'Servicio/s',
        ], $units);
    }
}
