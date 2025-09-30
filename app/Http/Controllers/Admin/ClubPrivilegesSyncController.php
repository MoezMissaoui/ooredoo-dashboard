<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ClubPrivilegesSyncController extends Controller
{
    private $syncUrl = 'https://clubprivileges.app/sync-dashboard-data';
    
    /**
     * Obtenir les identifiants de connexion
     */
    private function getCredentials()
    {
        $serverUsername = config('cp_sync.server_username');
        $serverPassword = config('cp_sync.server_password');
        $backendUsername = config('cp_sync.username');
        $backendPassword = config('cp_sync.password');

        if (!$serverUsername || !$serverPassword || !$backendUsername || !$backendPassword) {
            return null;
        }

        return [
            'server' => [
                'username' => $serverUsername,
                'password' => $serverPassword
            ],
            'backend' => [
                'username' => $backendUsername,
                'password' => $backendPassword
            ]
        ];
    }
    
    public function index()
    {
        return view('admin.club-privileges-sync');
    }

    /**
     * Visiter le lien de synchronisation Club Privilèges
     */
    public function visitSync(Request $request)
    {
        try {
            Log::info('🔄 [CP SYNC] Début de la visite du lien de synchronisation');

            // Obtenir les identifiants
            $credentials = $this->getCredentials();
            if (!$credentials) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants de connexion non configurés'
                ], 400);
            }

            // Effectuer la requête GET avec double authentification
            $response = Http::timeout(300) // 5 minutes timeout
                ->withBasicAuth($credentials['server']['username'], $credentials['server']['password'])
                ->withHeaders([
                    'X-Backend-Username' => $credentials['backend']['username'],
                    'X-Backend-Password' => $credentials['backend']['password']
                ])
                ->get($this->syncUrl);

            // Enregistrer le résultat
            $this->logVisitResult($response->successful(), $response->status(), $response->body());

            if ($response->successful()) {
                Log::info('✅ [CP SYNC] Visite du lien de synchronisation réussie', [
                    'status' => $response->status(),
                    'response_length' => strlen($response->body())
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Visite du lien de synchronisation réussie',
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

            } else {
                Log::error('❌ [CP SYNC] Échec de la visite du lien de synchronisation', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la visite du lien de synchronisation',
                    'status' => $response->status(),
                    'response' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            $this->logVisitResult(false, 500, $e->getMessage());
            
            Log::error('💥 [CP SYNC] Erreur lors de la visite du lien de synchronisation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la visite: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut de la dernière visite
     */
    public function status()
    {
        try {
            $lastResult = Cache::get('cp_sync_last_result');
            $syncHistory = Cache::get('cp_sync_history', []);

            return response()->json([
                'success' => true,
                'data' => [
                    'last_visit' => $lastResult,
                    'history' => array_slice($syncHistory, -10), // 10 dernières visites
                    'next_scheduled' => $this->getNextScheduledVisit()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [CP SYNC] Erreur lors de la récupération du statut', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des visites
     */
    public function history(Request $request)
    {
        try {
            $limit = $request->get('limit', 50);
            $syncHistory = Cache::get('cp_sync_history', []);

            return response()->json([
                'success' => true,
                'data' => array_slice($syncHistory, -$limit)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [CP SYNC] Erreur lors de la récupération de l\'historique', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion au lien de synchronisation
     */
    public function testConnection()
    {
        try {
            // Obtenir les identifiants
            $credentials = $this->getCredentials();
            if (!$credentials) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants de connexion non configurés'
                ], 400);
            }

            // Test de connexion avec double authentification
            $response = Http::timeout(30)
                ->withBasicAuth($credentials['server']['username'], $credentials['server']['password'])
                ->withHeaders([
                    'X-Backend-Username' => $credentials['backend']['username'],
                    'X-Backend-Password' => $credentials['backend']['password']
                ])
                ->get($this->syncUrl);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion au lien de synchronisation réussie',
                    'status' => $response->status(),
                    'response_length' => strlen($response->body())
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la connexion au lien de synchronisation',
                    'status' => $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('❌ [CP SYNC] Erreur lors du test de connexion', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer le résultat de la visite
     */
    private function logVisitResult($success, $status, $response)
    {
        $result = [
            'timestamp' => Carbon::now()->toISOString(),
            'success' => $success,
            'status' => $status,
            'response' => $response,
            'url' => $this->syncUrl
        ];

        // Mettre à jour le dernier résultat
        Cache::put('cp_sync_last_visit', $result['timestamp'], 86400); // 24h
        Cache::put('cp_sync_last_result', $result, 86400); // 24h

        // Ajouter à l'historique
        $history = Cache::get('cp_sync_history', []);
        $history[] = $result;
        
        // Garder seulement les 100 dernières entrées
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }
        
        Cache::put('cp_sync_history', $history, 86400 * 7); // 7 jours
    }

    /**
     * Obtenir la prochaine visite programmée
     */
    private function getNextScheduledVisit()
    {
        // Calculer la prochaine heure
        $now = Carbon::now();
        $nextHour = $now->copy()->addHour()->startOfHour();
        
        return $nextHour->toISOString();
    }
}