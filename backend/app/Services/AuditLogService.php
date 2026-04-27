<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function log(?int $userId, string $entity, int $entityId, string $action, ?array $diff = null): void
    {
        AuditLog::query()->create([
            'user_id' => $userId,
            'entity' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'diff_json' => $diff,
        ]);
    }
}
