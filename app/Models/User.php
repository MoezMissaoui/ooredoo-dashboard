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

    public function primaryOperator()
    {
        return $this->operators()->where('is_primary', true)->first();
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
        $primary = $this->primaryOperator();
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

    /**
     * Vérifier si l'utilisateur peut accéder à un sub-store spécifique
     */
    public function canAccessSubStore(string $subStoreName): bool
    {
        if ($this->isSuperAdmin()) {
            return true; // Super Admin peut accéder à tous les sub-stores
        }

        // Pour les autres rôles, vérifier les permissions selon les opérateurs assignés
        if ($subStoreName === 'ALL') {
            return $this->isSuperAdmin(); // Seul Super Admin peut voir "ALL"
        }

        // Logique d'autorisation basée sur les opérateurs ou autres critères
        // À étendre selon les besoins spécifiques
        return true; // Pour le moment, accès autorisé pour tous les utilisateurs authentifiés
    }

    /**
     * Récupérer les sub-stores accessibles pour cet utilisateur
     */
    public function getAccessibleSubStores(): array
    {
        if ($this->isSuperAdmin()) {
            // Super Admin voit tous les sub-stores
            return ['ALL' => 'Tous les sub-stores'];
        }

        // Logique pour récupérer les sub-stores selon les permissions
        // À implémenter selon les besoins
        return ['ALL' => 'Sub-stores autorisés'];
    }

    /**
     * Déterminer le dashboard préféré selon le rôle et les permissions de l'utilisateur
     */
    public function getPreferredDashboard(): string
    {
        // Super Admin : Dashboard principal avec vue globale
        if ($this->isSuperAdmin()) {
            return route('dashboard');
        }

        // Admin : Vérifier si orienté sub-stores ou dashboard principal
        if ($this->isAdmin()) {
            $primaryOperator = $this->primaryOperator();
            
            // Si l'admin est orienté sub-stores, rediriger vers sub-stores dashboard
            if ($primaryOperator && in_array($primaryOperator->operator_name, ['Sub-Stores', 'Retail', 'Partnership'])) {
                return route('sub-stores.dashboard');
            }
            
            // Sinon, dashboard principal avec vue filtrée par opérateur
            return route('dashboard');
        }

        // Collaborator : Selon les permissions et le contexte
        if ($this->isCollaborator()) {
            // Si l'utilisateur a accès aux sub-stores uniquement
            $primaryOperator = $this->primaryOperator();
            
            // Si l'opérateur principal est lié aux sub-stores, rediriger vers sub-stores dashboard
            if ($primaryOperator && in_array($primaryOperator->operator_name, ['Sub-Stores', 'Retail', 'Partnership'])) {
                return route('sub-stores.dashboard');
            }
            
            // Sinon, dashboard principal
            return route('dashboard');
        }

        // Par défaut, dashboard principal
        return route('dashboard');
    }

    /**
     * Vérifier si l'utilisateur est principalement orienté sub-stores
     */
    public function isPrimarySubStoreUser(): bool
    {
        if ($this->isSuperAdmin()) {
            return false; // Super Admin a accès à tout
        }

        $primaryOperator = $this->primaryOperator();
        
        if (!$primaryOperator) {
            return false;
        }

        // Liste des opérateurs considérés comme "sub-stores"
        $subStoreOperators = [
            'Sub-Stores',
            'Retail', 
            'Partnership',
            'White Mark',
            'Magasins'
        ];

        return in_array($primaryOperator->operator_name, $subStoreOperators);
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