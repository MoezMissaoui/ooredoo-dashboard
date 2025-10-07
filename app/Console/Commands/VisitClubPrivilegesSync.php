<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VisitClubPrivilegesSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cp:visit-sync 
                            {--force : Forcer la visite même si elle a déjà été exécutée récemment}
                            {--test : Mode test - ne pas exécuter réellement}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Visiter le lien de synchronisation Club Privilèges';

    private $syncUrl;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Construire l'URL à partir de la configuration
        $baseUrl = config('cp_sync.base_url', 'https://clubprivileges.app');
        $endpoint = config('cp_sync.sync_endpoint', '/api/sync-dashboard-data');
        $this->syncUrl = rtrim($baseUrl, '/') . $endpoint;
    }

    /**
     * Obtenir le token d'authentification API
     */
    private function getApiToken()
    {
        $token = config('cp_sync.api_token');
        
        if (!$token) {
            return null;
        }

        return $token;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Visite du lien de synchronisation Club Privilèges...');

        try {
            // Vérifier si la synchronisation a déjà été exécutée récemment
            if (!$this->option('force') && !$this->option('test')) {
                $lastVisit = Cache::get('cp_sync_last_visit');
                if ($lastVisit) {
                    $lastVisitTime = Carbon::parse($lastVisit);
                    $timeSinceLastVisit = $lastVisitTime->diffInMinutes(Carbon::now());
                    
                    if ($timeSinceLastVisit < 30) { // Moins de 30 minutes
                        $this->warn("⚠️  Visite déjà effectuée il y a {$timeSinceLastVisit} minutes");
                        $this->info("💡 Utilisez --force pour forcer la visite");
                        return 0;
                    }
                }
            }

            if ($this->option('test')) {
                $this->info('🧪 Mode test - Simulation de la visite');
                $this->simulateVisit();
                return 0;
            }

            $this->info("🌐 Visite de l'URL: {$this->syncUrl}");

            // Obtenir le token API
            $apiToken = $this->getApiToken();
            if (!$apiToken) {
                $this->error('❌ Token API non configuré');
                $this->info('💡 Configurez CP_EXPORT_TOKEN dans .env');
                return 1;
            }

            $this->info('🔐 Connexion avec authentification token API...');

            // Effectuer la requête GET avec token API
            $response = Http::timeout(300) // 5 minutes timeout
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($this->syncUrl);

            // Enregistrer le résultat
            $this->logVisitResult($response->successful(), $response->status(), $response->body());

            if ($response->successful()) {
                $this->info("✅ Sync réussie - Statut: {$response->status()}");

                Log::info('[CP SYNC] OK', [
                    'status' => $response->status(),
                    'size' => strlen($response->body())
                ]);

                return 0;

            } else {
                $this->error("❌ Échec - Statut: {$response->status()}");

                // Limiter la taille de la réponse loggée
                $errorBody = substr($response->body(), 0, 500);
                Log::error('[CP SYNC] Échec', [
                    'status' => $response->status(),
                    'error' => $errorBody
                ]);

                return 1;
            }

        } catch (\Exception $e) {
            $this->logVisitResult(false, 500, $e->getMessage());
            
            $this->error('❌ Erreur: ' . $e->getMessage());
            
            Log::error('[CP SYNC] Erreur', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine()
            ]);

            return 1;
        }
    }

    /**
     * Simuler une visite (mode test)
     */
    private function simulateVisit()
    {
        $this->info('🔐 Simulation de la visite...');
        $this->info('📡 Simulation de l\'envoi de la requête GET...');
        $this->info('⏳ Simulation du traitement...');
        
        // Simuler un délai
        sleep(2);
        
        $mockResponse = 'Synchronisation simulée avec succès - Données mises à jour';
        
        $this->logVisitResult(true, 200, $mockResponse);
        
        $this->info('✅ Simulation de visite réussie');
        $this->info("📊 Statut simulé: 200");
        $this->info("📝 Réponse simulée: {$mockResponse}");
    }

    /**
     * Enregistrer le résultat de la visite
     */
    private function logVisitResult($success, $status, $response)
    {
        // Limiter la taille de la réponse stockée
        $limitedResponse = is_string($response) ? substr($response, 0, 200) : $response;
        
        $result = [
            'timestamp' => Carbon::now()->toISOString(),
            'success' => $success,
            'status' => $status,
        ];

        // Mettre à jour le dernier résultat (sans réponse complète)
        Cache::put('cp_sync_last_visit', $result['timestamp'], 86400);
        Cache::put('cp_sync_last_result', $result, 86400);

        // Garder seulement les 20 dernières entrées dans l'historique
        $history = Cache::get('cp_sync_history', []);
        $history[] = $result;
        
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        
        Cache::put('cp_sync_history', $history, 86400 * 7);
    }
}
