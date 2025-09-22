<?php

namespace App\Http\Middleware;

use App\Services\SubStoreService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardAccessMiddleware
{
    protected $subStoreService;

    public function __construct(SubStoreService $subStoreService)
    {
        $this->subStoreService = $subStoreService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $dashboardType = null): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('auth.login');
        }
        
        // Vérifier l'accès selon le type de dashboard
        switch ($dashboardType) {
            case 'admin':
                // Seuls les super admin et admin peuvent accéder au dashboard admin
                if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                    abort(403, 'Vous n\'avez pas les permissions pour accéder au dashboard administrateur.');
                }
                break;
                
            case 'sub-store':
                // Seuls les super admin, admin sub-store, collaborateurs sub-store et admins avec opérateurs sub-stores peuvent accéder
                if (!$user->isSuperAdmin() && !$user->isAdminSubStore() && !$this->isCollaboratorSubStore($user) && !$this->isAdminWithSubStoreOperator($user)) {
                    abort(403, 'Vous n\'avez pas les permissions pour accéder au dashboard sub-stores.');
                }
                break;
                
            case 'operator':
                // Seuls les super admin, admin opérateur et collaborateurs opérateur peuvent accéder
                if (!$user->isSuperAdmin() && !$user->isAdminOperator() && !$this->isCollaboratorOperator($user)) {
                    abort(403, 'Vous n\'avez pas les permissions pour accéder au dashboard opérateur.');
                }
                break;
        }
        
        return $next($request);
    }
    
    /**
     * Vérifier si l'utilisateur est un collaborateur sub-store
     */
    private function isCollaboratorSubStore($user): bool
    {
        if (!$user->isCollaborator()) {
            return false;
        }
        
        $primaryOperator = $user->primaryOperator();
        if (!$primaryOperator) {
            return false;
        }
        
        // Utiliser le service centralisé pour vérifier si c'est un sub-store
        return $this->subStoreService->isSubStoreOperator($primaryOperator->operator_name);
    }
    
    /**
     * Vérifier si l'utilisateur est un admin avec un opérateur sub-store
     */
    private function isAdminWithSubStoreOperator($user): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }
        
        $primaryOperator = $user->primaryOperator();
        if (!$primaryOperator) {
            return false;
        }
        
        // Utiliser le service centralisé pour vérifier si c'est un sub-store
        return $this->subStoreService->isSubStoreOperator($primaryOperator->operator_name);
    }
    
    /**
     * Vérifier si l'utilisateur est un collaborateur opérateur
     */
    private function isCollaboratorOperator($user): bool
    {
        if (!$user->isCollaborator()) {
            return false;
        }
        
        $primaryOperator = $user->primaryOperator();
        if (!$primaryOperator) {
            return false;
        }
        
        // Liste des opérateurs "classiques" (non sub-store)
        $operatorTypes = [
            'S\'abonner via Timwe', 'S\'abonner via Orange', 'S\'abonner via TT',
            'Timwe', 'Ooredoo', 'MTN', 'Orange', 'Moov', 'Wave', 'PayPal',
            'Visa', 'Mastercard', 'Mobile Money', 'Bank Transfer',
            'Paiement par carte bancaire', 'Carte cadeaux'
        ];
        
        return in_array($primaryOperator->operator_name, $operatorTypes);
    }
}
