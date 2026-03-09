<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'failed_login_attempts' => 'integer',
    ];

    /**
     * Get the addresses for the user.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get role change logs where this user was the target
     */
    public function roleChangeLogs(): HasMany
    {
        return $this->hasMany(RoleChangeLog::class, 'target_user_id');
    }

    /**
     * Get role change logs performed by this user
     */
    public function performedRoleChanges(): HasMany
    {
        return $this->hasMany(RoleChangeLog::class, 'performed_by');
    }

    /**
     * Check if account is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Lock account for specified minutes
     */
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock account
     */
    public function unlockAccount(): void
    {
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');

        // Lock account after 5 failed attempts
        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount(30);
        }
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedAttempts(): void
    {
        $this->update(['failed_login_attempts' => 0]);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is admin or super admin
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Get user's primary role name
     */
    public function getPrimaryRole(): string
    {
        return $this->roles->first()?->name ?? 'user';
    }
}
