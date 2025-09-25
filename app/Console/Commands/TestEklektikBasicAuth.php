<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestEklektikBasicAuth extends Command
{
    protected $signature = 'eklektik:test-basic-auth';
    protected $description = 'Test de l\'authentification HTTP Basic Eklektik';

    public function handle()
    {
        $this->info('🔐 Test de l\'authentification HTTP Basic Eklektik');
        $this->info('================================================');
        $this->newLine();

        $statsUrl = 'https://stats.eklectic.tn/getelements.php';
        $username = 'ttclubpriv';
        $password = 'tt22cp**';

        // Test 1: Authentification HTTP Basic
        $this->info('1️⃣ Test avec authentification HTTP Basic...');
        
        $statsParams = [
            'dim' => 'daily',
            'dim2' => 'offre',
            'offreid' => 11,
            'datedeb' => '2024-09-01',
            'datefin' => '2024-09-30',
            '_' => time() * 1000
        ];

        $statsUrlWithParams = $statsUrl . '?' . http_build_query($statsParams);
        $this->info('URL: ' . $statsUrlWithParams);

        $response = Http::timeout(30)
            ->withBasicAuth($username, $password)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])
            ->get($statsUrlWithParams);

        $this->info('Status: ' . $response->status());
        $this->info('Body: ' . $response->body());
        $this->newLine();

        // Test 2: Avec token en paramètre
        $this->info('2️⃣ Test avec token en paramètre...');
        
        $paramsWithAuth = array_merge($statsParams, [
            'username' => $username,
            'password' => $password
        ]);

        $urlWithAuth = $statsUrl . '?' . http_build_query($paramsWithAuth);
        $this->info('URL: ' . $urlWithAuth);

        $response2 = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])
            ->get($urlWithAuth);

        $this->info('Status: ' . $response2->status());
        $this->info('Body: ' . $response2->body());
        $this->newLine();

        // Test 3: Avec différents opérateurs
        $this->info('3️⃣ Test avec différents opérateurs...');
        
        $operators = [
            11 => 'TT',
            82 => 'Orange',
            26 => 'Taraji'
        ];

        foreach ($operators as $offreId => $operatorName) {
            $this->info("Opérateur: $operatorName (ID: $offreId)");
            
            $operatorParams = [
                'dim' => 'daily',
                'dim2' => 'offre',
                'offreid' => $offreId,
                'datedeb' => '2024-09-01',
                'datefin' => '2024-09-30',
                '_' => time() * 1000
            ];

            $operatorUrl = $statsUrl . '?' . http_build_query($operatorParams);
            $operatorResponse = Http::timeout(30)
                ->withBasicAuth($username, $password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])
                ->get($operatorUrl);

            $data = $operatorResponse->json();
            $hasData = !empty($data['data']);
            
            $this->info("  Status: {$operatorResponse->status()}, Données: " . ($hasData ? 'OUI' : 'NON'));
            
            if ($hasData) {
                $this->info("  Data: " . json_encode($data['data'], JSON_PRETTY_PRINT));
            }
            $this->newLine();
        }

        // Test 4: Test avec différentes périodes
        $this->info('4️⃣ Test avec différentes périodes...');
        
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
            $this->info("Période: $label ($start à $end)");
            
            $periodParams = [
                'dim' => 'daily',
                'dim2' => 'offre',
                'offreid' => 11,
                'datedeb' => $start,
                'datefin' => $end,
                '_' => time() * 1000
            ];

            $periodUrl = $statsUrl . '?' . http_build_query($periodParams);
            $periodResponse = Http::timeout(30)
                ->withBasicAuth($username, $password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])
                ->get($periodUrl);

            $data = $periodResponse->json();
            $hasData = !empty($data['data']);
            
            $this->info("  Status: {$periodResponse->status()}, Données: " . ($hasData ? 'OUI' : 'NON'));
            
            if ($hasData) {
                $this->info("  Data: " . json_encode($data['data'], JSON_PRETTY_PRINT));
                break; // Sortir de la boucle si on trouve des données
            }
            $this->newLine();
        }

        $this->info('✅ Test terminé!');
    }
}

