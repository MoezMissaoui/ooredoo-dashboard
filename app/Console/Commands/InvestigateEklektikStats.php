<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class InvestigateEklektikStats extends Command
{
    protected $signature = 'eklektik:investigate-stats';
    protected $description = 'Investigation approfondie de l\'API Eklektik Stats avec différentes périodes et paramètres';

    public function handle()
    {
        $this->info('🔍 Investigation de l\'API Eklektik Stats');
        $this->info('==========================================');
        $this->newLine();

        $eklektikStatsUrl = 'https://stats.eklectic.tn/getelements.php';
        $token = $this->getEklektikToken();

        if (!$token) {
            $this->error('❌ Impossible d\'obtenir le token');
            return;
        }

        // Test 1: Différentes périodes
        $this->info('1️⃣ Test avec différentes périodes...');
        $periods = [
            ['2024-01-01', '2024-01-31', 'Janvier 2024'],
            ['2024-06-01', '2024-06-30', 'Juin 2024'],
            ['2024-09-01', '2024-09-30', 'Septembre 2024'],
            ['2024-12-01', '2024-12-31', 'Décembre 2024'],
            ['2025-01-01', '2025-01-31', 'Janvier 2025'],
            ['2025-06-01', '2025-06-30', 'Juin 2025'],
            ['2025-08-01', '2025-08-31', 'Août 2025'],
        ];

        foreach ($periods as [$start, $end, $label]) {
            $this->testPeriod($eklektikStatsUrl, $token, $start, $end, $label);
        }

        // Test 2: Différents paramètres
        $this->info('2️⃣ Test avec différents paramètres...');
        $this->testDifferentParams($eklektikStatsUrl, $token);

        // Test 3: Test avec d'autres dimensions
        $this->info('3️⃣ Test avec d\'autres dimensions...');
        $this->testDifferentDimensions($eklektikStatsUrl, $token);

        $this->info('✅ Investigation terminée!');
    }

    private function getEklektikToken()
    {
        try {
            $response = Http::timeout(30)
                ->asForm()
                ->post('https://payment.eklectic.tn/API/oauth/token', [
                    'client_id' => '0a2e605d-88f6-11ec-9feb-fa163e3dd8b3',
                    'client_secret' => 'ee60bb148a0e468a5053f9db41008780',
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }
        } catch (\Exception $e) {
            $this->error('Erreur token: ' . $e->getMessage());
        }
        return null;
    }

    private function testPeriod($baseUrl, $token, $start, $end, $label)
    {
        $this->info("Période: $label ($start à $end)");
        
        $params = [
            'dim' => 'daily',
            'dim2' => 'offre',
            'offreid' => 11, // TT
            'datedeb' => $start,
            'datefin' => $end,
            '_' => time() * 1000
        ];

        $url = $baseUrl . '?' . http_build_query($params);
        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get($url);

        $data = $response->json();
        $hasData = !empty($data['data']);
        
        $this->info("  Status: {$response->status()}, Données: " . ($hasData ? 'OUI' : 'NON'));
        
        if ($hasData) {
            $this->info("  Data: " . json_encode($data['data'], JSON_PRETTY_PRINT));
        }
        
        $this->newLine();
    }

    private function testDifferentParams($baseUrl, $token)
    {
        $testParams = [
            // Test sans dim2
            ['dim' => 'daily', 'offreid' => 11, 'datedeb' => '2024-09-01', 'datefin' => '2024-09-30'],
            // Test avec dim2 différent
            ['dim' => 'daily', 'dim2' => 'operator', 'offreid' => 11, 'datedeb' => '2024-09-01', 'datefin' => '2024-09-30'],
            ['dim' => 'daily', 'dim2' => 'service', 'offreid' => 11, 'datedeb' => '2024-09-01', 'datefin' => '2024-09-30'],
            // Test avec dim différent
            ['dim' => 'monthly', 'dim2' => 'offre', 'offreid' => 11, 'datedeb' => '2024-09-01', 'datefin' => '2024-09-30'],
            ['dim' => 'yearly', 'dim2' => 'offre', 'offreid' => 11, 'datedeb' => '2024-01-01', 'datefin' => '2024-12-31'],
        ];

        foreach ($testParams as $i => $params) {
            $this->info("Test " . ($i + 1) . ": " . json_encode($params));
            
            $url = $baseUrl . '?' . http_build_query($params);
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get($url);

            $data = $response->json();
            $hasData = !empty($data['data']);
            
            $this->info("  Status: {$response->status()}, Données: " . ($hasData ? 'OUI' : 'NON'));
            
            if ($hasData) {
                $this->info("  Data: " . json_encode($data['data'], JSON_PRETTY_PRINT));
            }
            
            $this->newLine();
        }
    }

    private function testDifferentDimensions($baseUrl, $token)
    {
        $dimensions = [
            ['dim' => 'daily', 'dim2' => 'offre'],
            ['dim' => 'monthly', 'dim2' => 'offre'],
            ['dim' => 'yearly', 'dim2' => 'offre'],
            ['dim' => 'daily', 'dim2' => 'operator'],
            ['dim' => 'daily', 'dim2' => 'service'],
            ['dim' => 'daily', 'dim2' => 'country'],
        ];

        foreach ($dimensions as $i => $dim) {
            $this->info("Dimension " . ($i + 1) . ": " . json_encode($dim));
            
            $params = array_merge($dim, [
                'offreid' => 11,
                'datedeb' => '2024-09-01',
                'datefin' => '2024-09-30',
                '_' => time() * 1000
            ]);

            $url = $baseUrl . '?' . http_build_query($params);
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get($url);

            $data = $response->json();
            $hasData = !empty($data['data']);
            
            $this->info("  Status: {$response->status()}, Données: " . ($hasData ? 'OUI' : 'NON'));
            
            if ($hasData) {
                $this->info("  Data: " . json_encode($data['data'], JSON_PRETTY_PRINT));
            }
            
            $this->newLine();
        }
    }
}

