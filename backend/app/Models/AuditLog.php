<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'entity',
        'entity_id',
        'action',
        'diff_json',
    ];

    protected function casts(): array
    {
        return [
            'diff_json' => 'array',
        ];
    }

    protected function localCreatedAt(): Attribute
    {
        return Attribute::get(
            fn () => $this->created_at?->copy()->setTimezone(config('app.audit_timezone')),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
