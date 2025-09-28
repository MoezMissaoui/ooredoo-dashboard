<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CpIncrementalExport;
use Exception;

class CpSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cp:sync 
                            {--loop=1 : Nombre de boucles à exécuter (utile pour initial load)}
                            {--reset : Reset l\'état de synchronisation avant de commencer}
                            {--test : Teste seulement la connexion sans synchroniser}
                            {--state : Affiche l\'état actuel de synchronisation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les données Club Privilèges via l\'API get-pending-sync-data';

    protected $cpExport;

    public function __construct(CpIncrementalExport $cpExport)
    {
        parent::__construct();
        $this->cpExport = $cpExport;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 [CP Sync] Démarrage de la synchronisation Club Privilèges');

        // Afficher l'état si demandé
        if ($this->option('state')) {
            $this->showSyncState();
            return self::SUCCESS;
        }

        // Test de connexion si demandé
        if ($this->option('test')) {
            return $this->testConnection();
        }

        // Reset si demandé
        if ($this->option('reset')) {
            $this->warn('⚠️ Reset de l\'état de synchronisation...');
            $this->cpExport->resetSyncState();
            $this->info('✅ État réinitialisé');
        }

        $loops = (int) $this->option('loop');
        $totalRows = 0;
        $startTime = microtime(true);

        try {
            for ($i = 1; $i <= $loops; $i++) {
                $this->info("🔄 [CP Sync] Boucle {$i}/{$loops}");

                // Récupérer les données
                $response = $this->cpExport->pullOnce();
                
                // Traiter et sauvegarder
                $this->cpExport->upsertAndAdvance($response);

                // Compter les lignes traitées
                $rowsInLoop = $this->countRowsInResponse($response);
                $totalRows += $rowsInLoop;

                $this->info("✅ [CP Sync] Boucle {$i} terminée - {$rowsInLoop} lignes traitées");

                // Vérifier s'il y a encore des données à traiter
                if (!$this->hasMoreData($response)) {
                    $this->info("🏁 [CP Sync] Toutes les données ont été synchronisées");
                    break;
                }

                // Petite pause entre les boucles pour éviter la surcharge
                if ($i < $loops) {
                    sleep(2);
                }
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("🎉 [CP Sync] Synchronisation terminée avec succès");
            $this->info("📊 Statistiques: {$totalRows} lignes traitées en {$duration}s");

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ [CP Sync] Erreur: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Teste la connexion à l'API
     */
    protected function testConnection(): int
    {
        $this->info('🔍 [CP Sync] Test de connexion...');

        try {
            $result = $this->cpExport->testConnection();

            if ($result['success']) {
                $this->info('✅ [CP Sync] Connexion réussie');
                $this->line('📡 Endpoint: ' . config('sync_export.endpoint'));
                $this->line('🔑 Token configuré: ' . (config('sync_export.token') ? 'Oui' : 'Non'));
                return self::SUCCESS;
            } else {
                $this->error('❌ [CP Sync] Connexion échouée: ' . $result['message']);
                return self::FAILURE;
            }
        } catch (Exception $e) {
            $this->error('❌ [CP Sync] Erreur de test: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Affiche l'état actuel de synchronisation
     */
    protected function showSyncState(): void
    {
        $this->info('📊 [CP Sync] État de synchronisation:');
        $this->newLine();

        $state = $this->cpExport->getSyncState();

        $headers = ['Table', 'Dernier ID', 'Dernière sync'];
        $rows = [];

        foreach ($state as $item) {
            $rows[] = [
                $item->table_name,
                number_format($item->last_inserted_id),
                $item->last_synced_at ? $item->last_synced_at : 'Jamais'
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Compte le nombre de lignes dans la réponse
     */
    protected function countRowsInResponse(array $response): int
    {
        $total = 0;
        $tables = $response['tables'] ?? [];

        foreach ($tables as $table => $block) {
            $total += count($block['rows'] ?? []);
        }

        return $total;
    }

    /**
     * Vérifie s'il y a encore des données à traiter
     */
    protected function hasMoreData(array $response): bool
    {
        $tables = $response['tables'] ?? [];

        foreach ($tables as $table => $block) {
            if (($block['has_more'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }
}
