<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubStoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperatorsController extends Controller
{
    protected $subStoreService;

    public function __construct(SubStoreService $subStoreService)
    {
        $this->subStoreService = $subStoreService;
    }

    /**
     * Récupérer la liste des opérateurs disponibles
     */
    public function getOperators(Request $request)
    {
        try {
            $user = auth()->user();
            
            Log::info("=== DÉBUT API Operators getOperators ===");
            Log::info("Utilisateur: {$user->email} (Rôle: {$user->role->name})");
            
            // Récupérer les opérateurs selon le rôle de l'utilisateur
            $operators = $this->getAvailableOperatorsForUser($user);
            $defaultOperator = $this->getDefaultOperatorForUser($user);
            
            Log::info("Opérateurs récupérés: " . count($operators));
            Log::info("Opérateur par défaut: " . $defaultOperator);
            
            return response()->json([
                'operators' => $operators,
                'default_operator' => $defaultOperator,
                'user_role' => $user->role->name ?? 'collaborator'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur dans Operators getOperators: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'operators' => $this->getFallbackOperators(),
                'default_operator' => 'ALL',
                'user_role' => 'collaborator',
                'error' => 'Erreur lors du chargement des opérateurs'
            ], 500);
        }
    }
    
    /**
     * Obtenir les opérateurs disponibles pour un utilisateur
     */
    private function getAvailableOperatorsForUser($user)
    {
        // Super Admin : tous les opérateurs
        if ($user->isSuperAdmin()) {
            return $this->getAllOperators();
        }
        
        // Admin : opérateurs assignés
        if ($user->isAdmin()) {
            return $this->getUserAssignedOperators($user);
        }
        
        // Collaborateur : opérateurs assignés
        if ($user->isCollaborator()) {
            return $this->getUserAssignedOperators($user);
        }
        
        // Fallback : opérateurs de base
        return $this->getBasicOperators();
    }
    
    /**
     * Récupérer tous les opérateurs
     */
    private function getAllOperators()
    {
        // Utiliser le service centralisé
        $allOperators = $this->subStoreService->getAllOperators();
        
        // Ajouter "Tous les opérateurs" en premier
        return array_merge(['ALL' => 'Tous les opérateurs'], $allOperators);
    }
    
    /**
     * Récupérer les opérateurs assignés à un utilisateur
     */
    private function getUserAssignedOperators($user)
    {
        $operators = ['ALL' => 'Tous les opérateurs'];
        
        // Récupérer les opérateurs assignés via user_operators
        $assignedOperators = DB::table('user_operators')
            ->join('operators', 'user_operators.operator_id', '=', 'operators.operator_id')
            ->where('user_operators.user_id', $user->id)
            ->pluck('operators.operator_name', 'operators.operator_name')
            ->toArray();
        
        // Récupérer les sub-stores assignés
        $assignedSubStores = DB::table('user_operators')
            ->join('stores', 'user_operators.operator_id', '=', 'stores.store_id')
            ->where('user_operators.user_id', $user->id)
            ->where('stores.is_sub_store', 1)
            ->where('stores.store_active', 1)
            ->pluck('stores.store_name', 'stores.store_name')
            ->toArray();
        
        // Combiner les opérateurs assignés
        $userOperators = array_merge($assignedOperators, $assignedSubStores);
        
        if (!empty($userOperators)) {
            $operators = array_merge($operators, $userOperators);
        } else {
            // Fallback : opérateurs de base si aucun assigné
            $operators = array_merge($operators, $this->getBasicOperators());
        }
        
        return $operators;
    }
    
    /**
     * Récupérer les opérateurs de base
     */
    private function getBasicOperators()
    {
        return $this->subStoreService->getClassicOperators();
    }
    
    /**
     * Obtenir l'opérateur par défaut pour un utilisateur
     */
    private function getDefaultOperatorForUser($user)
    {
        // Super Admin : ALL par défaut
        if ($user->isSuperAdmin()) {
            return 'ALL';
        }
        
        // Admin/Collaborateur : premier opérateur assigné
        $primaryOperator = $user->primaryOperator();
        if ($primaryOperator) {
            return $primaryOperator->operator_name;
        }
        
        return 'ALL';
    }
    
    /**
     * Opérateurs de fallback en cas d'erreur
     */
    private function getFallbackOperators()
    {
        $classicOperators = $this->subStoreService->getClassicOperators();
        return array_merge(['ALL' => 'Tous les opérateurs'], $classicOperators);
    }
}
