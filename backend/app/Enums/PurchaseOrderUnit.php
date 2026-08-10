<?php

namespace App\Enums;

enum PurchaseOrderUnit: string
{
    case Unit = 'Un';
    case Kilogram = 'Kg';
    case Liter = 'L';
    case Meter = 'M';
    case SquareMeter = 'M2';
    case CubicMeter = 'M3';
    case Box = 'Caj';
    case Bag = 'Bol';
    case Roll = 'Rol';
    case Service = 'Serv';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unidad/es',
            self::Kilogram => 'Kilogramo/s',
            self::Liter => 'Litro/s',
            self::Meter => 'Metro/s',
            self::SquareMeter => 'Metro/s cuadrado/s',
            self::CubicMeter => 'Metro/s cubico/s',
            self::Box => 'Caja/s',
            self::Bag => 'Bolsa/s',
            self::Roll => 'Rollo/s',
            self::Service => 'Servicio/s',
        };
    }
}
