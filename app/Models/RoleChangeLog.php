<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleChangeLog extends Model
{
    protected $fillable = [
        'performed_by',
        'target_user_id',
        'old_role',
        'new_role',
        'ip_address',
        'user_agent',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Log a role change
     */
    public static function logChange(
        int $performedBy,
        int $targetUserId,
        string $oldRole,
        string $newRole,
        string $ipAddress,
        ?string $userAgent = null,
        ?string $reason = null
    ): self {
        return self::create([
            'performed_by' => $performedBy,
            'target_user_id' => $targetUserId,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
        ]);
    }
}
