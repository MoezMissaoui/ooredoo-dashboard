<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SyncClubPrivilegesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cp:sync-data 
                            {--force : Forcer la synchronisation même si elle a déjà été exécutée récemment}
                            {--test : Mode test - ne pas exécuter réellement la synchronisation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les données avec Club Privilèges';

    private $baseUrl = 'https://clubprivileges.app';
    private $syncEndpoint = '/sync-dashboard-data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Début de la synchronisation Club Privilèges...');

        try {
            // Vérifier si la synchronisation a déjà été exécutée récemment
            if (!$this->option('force') && !$this->option('test')) {
                $lastSync = Cache::get('cp_sync_last_result');
                if ($lastSync && isset($lastSync['timestamp'])) {
                    $lastSyncTime = Carbon::parse($lastSync['timestamp']);
                    $timeSinceLastSync = $lastSyncTime->diffInMinutes(Carbon::now());
                    
                    if ($timeSinceLastSync < 30) { // Moins de 30 minutes
                        $this->warn("⚠️  Synchronisation déjà exécutée il y a {$timeSinceLastSync} minutes");
                        $this->info("💡 Utilisez --force pour forcer l'exécution");
                        return 0;
                    }
                }
            }

            if ($this->option('test')) {
                $this->info('🧪 Mode test - Simulation de la synchronisation');
                $this->simulateSync();
                return 0;
            }

            // Obtenir les identifiants
            $credentials = $this->getCredentials();
            if (!$credentials) {
                $this->error('❌ Identifiants de connexion non configurés');
                $this->info('💡 Configurez CP_SYNC_USERNAME et CP_SYNC_PASSWORD dans .env');
                return 1;
            }

            $this->info('🔐 Connexion avec les identifiants configurés...');

            // Effectuer la synchronisation
            $response = Http::timeout(300) // 5 minutes timeout
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->post($this->baseUrl . $this->syncEndpoint);

            if ($response->successful()) {
                $data = $response->json();
                
                // Enregistrer le résultat
                $this->logSyncResult(true, $data, $response->status());
                
                $this->info('✅ Synchronisation Club Privilèges réussie');
                $this->info("📊 Statut HTTP: {$response->status()}");
                
                if (isset($data['message'])) {
                    $this->info("📝 Message: {$data['message']}");
                }
                
                if (isset($data['data'])) {
                    $this->info('📈 Données reçues: ' . json_encode($data['data'], JSON_PRETTY_PRINT));
                }

                Log::info('✅ [CP SYNC] Synchronisation Club Privilèges réussie via commande', [
                    'status' => $response->status(),
                    'data' => $data
                ]);

                return 0;

            } else {
                $errorData = $response->json();
                $this->logSyncResult(false, $errorData, $response->status());
                
                $this->error('❌ Échec de la synchronisation Club Privilèges');
                $this->error("📊 Statut HTTP: {$response->status()}");
                
                if (isset($errorData['message'])) {
                    $this->error("📝 Message d'erreur: {$errorData['message']}");
                }
                
                if (isset($errorData['error'])) {
                    $this->error("💥 Erreur: {$errorData['error']}");
                }

                Log::error('❌ [CP SYNC] Échec de la synchronisation Club Privilèges via commande', [
                    'status' => $response->status(),
                    'error' => $errorData
                ]);

                return 1;
            }

        } catch (\Exception $e) {
            $this->logSyncResult(false, ['error' => $e->getMessage()], 500);
            
            $this->error('💥 Erreur lors de la synchronisation: ' . $e->getMessage());
            
            Log::error('💥 [CP SYNC] Erreur lors de la synchronisation Club Privilèges via commande', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Simuler une synchronisation (mode test)
     */
    private function simulateSync()
    {
        $this->info('🔐 Simulation de la connexion...');
        $this->info('📡 Simulation de l\'envoi de la requête...');
        $this->info('⏳ Simulation du traitement...');
        
        // Simuler un délai
        sleep(2);
        
        $mockData = [
            'message' => 'Synchronisation simulée avec succès',
            'data' => [
                'records_processed' => 150,
                'records_updated' => 45,
                'records_created' => 12,
                'execution_time' => '2.3s'
            ]
        ];
        
        $this->logSyncResult(true, $mockData, 200);
        
        $this->info('✅ Simulation de synchronisation réussie');
        $this->info('📊 Données simulées: ' . json_encode($mockData, JSON_PRETTY_PRINT));
    }

    /**
     * Obtenir les identifiants de connexion
     */
    private function getCredentials()
    {
        $username = config('cp_sync.username');
        $password = config('cp_sync.password');

        if (!$username || !$password) {
            return null;
        }

        return [
            'username' => $username,
            'password' => $password
        ];
    }

    /**
     * Enregistrer le résultat de la synchronisation
     */
    private function logSyncResult($success, $data, $status)
    {
        $result = [
            'timestamp' => Carbon::now()->toISOString(),
            'success' => $success,
            'status' => $status,
            'data' => $data,
            'source' => 'command'
        ];

        // Mettre à jour le dernier résultat
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
}

