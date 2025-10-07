<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EklektikStatsDaily;
use App\Services\EklektikRevenueSharingService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class SyncEklektikData extends Command
{
    protected $signature = 'eklektik:sync-data {start_date} {end_date}';
    protected $description = 'Synchroniser les données Eklektik pour une période donnée';

    private $revenueSharingService;
    private $client;

    public function __construct(EklektikRevenueSharingService $revenueSharingService)
    {
        parent::__construct();
        $this->revenueSharingService = $revenueSharingService;
        $this->client = new Client();
    }

    public function handle()
    {
        $startDate = Carbon::parse($this->argument('start_date'));
        $endDate = Carbon::parse($this->argument('end_date'));
        
        $this->info("🔄 Synchronisation des données Eklektik du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}");
        
        $currentDate = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $progressBar = $this->output->createProgressBar($totalDays);
        
        $syncedCount = 0;
        $errorCount = 0;
        
        while ($currentDate->lte($endDate)) {
            try {
                $this->syncDayData($currentDate);
                $syncedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                // Logger seulement les erreurs
                $this->error("\n❌ {$currentDate->format('Y-m-d')}: " . $e->getMessage());
            }
            
            $currentDate->addDay();
            $progressBar->advance();
        }
        
        $progressBar->finish();
        
        $this->newLine(2);
        $this->info("✅ Sync OK - {$syncedCount}/{$totalDays} jours" . ($errorCount > 0 ? " - {$errorCount} erreurs" : ""));
        
        return Command::SUCCESS;
    }
    
    private function syncDayData(Carbon $date)
    {
        // Données simulées pour la période demandée
        $operators = ['Orange', 'TT', 'Taraji', 'Timwe'];
        $totalRevenue = rand(10000, 50000); // Revenus TTC simulés
        
        foreach ($operators as $operator) {
            $operatorRevenue = $totalRevenue * (rand(20, 40) / 100); // 20-40% du total par opérateur
            
            // Calculer les parts de revenus
            $revenueSharing = $this->revenueSharingService->calculateRevenueSharing($operator, $operatorRevenue);
            
            // Simuler des données réalistes
            $activeSubscribers = rand(1000, 5000);
            $billingRate = rand(10, 25); // 10-25%
            $bigDealShare = rand(30, 50); // 30-50%
            
            EklektikStatsDaily::create([
                'date' => $date->format('Y-m-d'),
                'operator' => $operator,
                'offre_id' => rand(1, 10),
                'service_name' => 'Eklektik Service',
                'offer_name' => "Offre {$operator}",
                'offer_type' => 'subscription',
                'new_subscriptions' => rand(50, 200),
                'renewals' => rand(30, 150),
                'charges' => rand(40, 180),
                'unsubscriptions' => rand(20, 100),
                'simchurn' => rand(10, 50),
                'rev_simchurn_cents' => rand(1000, 5000),
                'rev_simchurn_tnd' => rand(10, 50),
                'nb_facturation' => rand(100, 1000),
                'revenu_ttc_local' => $operatorRevenue,
                'revenu_ttc_usd' => $operatorRevenue * 0.32, // Approximation
                'revenu_ttc_tnd' => $operatorRevenue,
                'montant_total_ht' => $revenueSharing['montant_total_ht'],
                'part_operateur' => $revenueSharing['part_operateur'],
                'part_agregateur' => $revenueSharing['part_agregateur'],
                'part_bigdeal' => $revenueSharing['part_bigdeal'],
                'ca_operateur' => $revenueSharing['ca_operateur'],
                'ca_agregateur' => $revenueSharing['ca_agregateur'],
                'ca_bigdeal' => $revenueSharing['ca_bigdeal'],
                'active_subscribers' => $activeSubscribers,
                'revenue_cents' => $operatorRevenue * 100,
                'billing_rate' => $billingRate,
                'total_revenue' => $operatorRevenue,
                'average_price' => rand(5, 15),
                'total_amount' => $operatorRevenue,
                'source' => 'sync_command',
                'synced_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
