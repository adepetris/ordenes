<?php

namespace App\Support;

class UiLabels
{
    public static function orderStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Borrador',
            'pending_approval' => 'Pendiente de aprobacion',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'cancelled' => 'Cancelada',
            default => $status,
        };
    }

    public static function auditAction(string $action): string
    {
        return match ($action) {
            'order_created' => 'Orden creada',
            'order_updated' => 'Orden actualizada',
            'order_submitted' => 'Orden enviada a aprobacion',
            'order_auto_approved' => 'Orden autoaprobada',
            'order_approved' => 'Orden aprobada',
            'order_rejected' => 'Orden rechazada',
            'attachment_uploaded' => 'Adjunto cargado',
            'supplier_created' => 'Proveedor creado',
            'supplier_updated' => 'Proveedor actualizado',
            default => $action,
        };
    }

    public static function auditEntity(string $entity): string
    {
        return match ($entity) {
            'purchase_order' => 'Orden de compra',
            'purchase_order_supplier' => 'Proveedor',
            default => $entity,
        };
    }

    public static function roleName(string $role): string
    {
        return match ($role) {
            'administrador' => 'Administrador',
            'usuario_solicitante' => 'Usuario solicitante',
            'usuario_autorizado' => 'Usuario autorizado',
            default => $role,
        };
    }
}
