<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'first_name',
        'last_name',
        'phone',
        'status',
        'last_login_at',
        'last_login_ip',
        'preferences',
        'is_otp_enabled',
        'password_changed_at',
        'created_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
        'is_otp_enabled' => 'boolean',
        'password_changed_at' => 'datetime'
    ];

    // Relations
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function operators(): HasMany
    {
        return $this->hasMany(UserOperator::class);
    }

    public function activeOperators(): HasMany
    {
        return $this->operators()->active();
    }

    public function primaryOperator(): HasMany
    {
        return $this->operators()->primary();
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    // Méthodes utilitaires
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->isSuperAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->role && $this->role->isAdmin();
    }

    public function isCollaborator(): bool
    {
        return $this->role && $this->role->isCollaborator();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    public function canAccessOperator(string $operatorName): bool
    {
        if ($this->isSuperAdmin()) {
            return true; // Super admin peut accéder à tous les opérateurs
        }

        return $this->activeOperators()
                   ->where('operator_name', $operatorName)
                   ->exists();
    }

    public function getOperatorsList(): array
    {
        if ($this->isSuperAdmin()) {
            // Retourner tous les opérateurs disponibles
            return $this->getAllOperators();
        }

        return $this->activeOperators()
                   ->pluck('operator_name')
                   ->toArray();
    }

    public function getPrimaryOperatorName(): ?string
    {
        $primary = $this->primaryOperator()->first();
        return $primary ? $primary->operator_name : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    public function scopeForOperator($query, string $operatorName)
    {
        return $query->whereHas('operators', function($q) use ($operatorName) {
            $q->where('operator_name', $operatorName)->active();
        });
    }

    // Méthodes privées
    private function getAllOperators(): array
    {
        // Cette méthode devrait récupérer tous les opérateurs disponibles
        // depuis la base de données (table country_payments_methods par exemple)
        return \DB::table('country_payments_methods')
                  ->distinct()
                  ->pluck('country_payments_methods_name')
                  ->toArray();
    }

}