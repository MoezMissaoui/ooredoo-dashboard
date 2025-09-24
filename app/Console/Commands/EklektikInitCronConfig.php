<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EklektikCronConfig;

class EklektikInitCronConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eklektik:init-cron-config 
                            {--user-id=1 : ID de l\'utilisateur qui initialise la configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialiser la configuration par défaut du cron Eklektik';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');

        $this->info("🚀 Initialisation de la configuration du cron Eklektik...");
        $this->info("👤 Utilisateur: {$userId}");

        try {
            // Vérifier si des configurations existent déjà
            $existingConfigs = EklektikCronConfig::count();
            
            if ($existingConfigs > 0) {
                $this->warn("⚠️ Des configurations existent déjà ({$existingConfigs} entrées).");
                if (!$this->confirm('Voulez-vous les réinitialiser ?')) {
                    $this->info('❌ Initialisation annulée.');
                    return;
                }
                
                // Supprimer les configurations existantes
                EklektikCronConfig::truncate();
                $this->info("🗑️ Anciennes configurations supprimées.");
            }

            // Initialiser les configurations par défaut
            EklektikCronConfig::initializeDefaultConfigs($userId);

            $this->info("✅ Configuration initialisée avec succès !");

            // Afficher les configurations créées
            $configs = EklektikCronConfig::getAllConfigs();
            $this->table(
                ['Clé', 'Valeur', 'Description'],
                $configs->map(function ($config) {
                    return [
                        $config->config_key,
                        $config->config_value,
                        $config->description
                    ];
                })->toArray()
            );

            $this->info("\n💡 Prochaines étapes:");
            $this->info("1. Accéder à l'interface d'administration: /admin/eklektik-cron");
            $this->info("2. Configurer les paramètres selon vos besoins");
            $this->info("3. Tester la configuration");
            $this->info("4. Configurer le cron système si nécessaire");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'initialisation: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
        }
    }
}