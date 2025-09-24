<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestEklektikWebAuth extends Command
{
    protected $signature = 'eklektik:test-web-auth';
    protected $description = 'Test de l\'authentification web Eklektik avec login/mot de passe';

    public function handle()
    {
        $this->info('🔐 Test de l\'authentification web Eklektik');
        $this->info('==========================================');
        $this->newLine();

        $loginUrl = 'https://stats.eklectic.tn/en/admin/login';
        $statsUrl = 'https://stats.eklectic.tn/getelements.php';
        
        $username = 'ttclubpriv';
        $password = 'tt22cp**';

        // Étape 1: Récupérer la page de login pour obtenir les tokens CSRF
        $this->info('1️⃣ Récupération de la page de login...');
        
        $loginPageResponse = Http::timeout(30)->get($loginUrl);
        $this->info('Status: ' . $loginPageResponse->status());
        
        if (!$loginPageResponse->successful()) {
            $this->error('❌ Impossible d\'accéder à la page de login');
            return;
        }

        // Extraire le token CSRF si présent
        $loginPageContent = $loginPageResponse->body();
        $csrfToken = $this->extractCsrfToken($loginPageContent);
        
        if ($csrfToken) {
            $this->info('Token CSRF trouvé: ' . substr($csrfToken, 0, 20) . '...');
        } else {
            $this->info('Aucun token CSRF trouvé');
        }

        // Étape 2: Tentative de login
        $this->info('2️⃣ Tentative de login...');
        
        $loginData = [
            'email' => $username,
            'password' => $password,
        ];

        if ($csrfToken) {
            $loginData['_token'] = $csrfToken;
        }

        $loginResponse = Http::timeout(30)
            ->withHeaders([
                'Referer' => $loginUrl,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])
            ->asForm()
            ->post($loginUrl, $loginData);

        $this->info('Status: ' . $loginResponse->status());
        $this->info('Headers: ' . json_encode($loginResponse->headers(), JSON_PRETTY_PRINT));
        $this->info('Body: ' . substr($loginResponse->body(), 0, 500) . '...');
        $this->newLine();

        // Étape 3: Tester l'accès aux stats avec les cookies de session
        if ($loginResponse->successful()) {
            $this->info('3️⃣ Test d\'accès aux stats avec session...');
            
            $cookies = $this->extractCookies($loginResponse->headers());
            $this->info('Cookies extraits: ' . count($cookies));
            
            foreach ($cookies as $cookie) {
                $this->info('Cookie: ' . substr($cookie, 0, 50) . '...');
            }
            $this->newLine();

            // Test avec les cookies
            $statsParams = [
                'dim' => 'daily',
                'dim2' => 'offre',
                'offreid' => 11,
                'datedeb' => '2024-09-01',
                'datefin' => '2024-09-30',
                '_' => time() * 1000
            ];

            $statsUrlWithParams = $statsUrl . '?' . http_build_query($statsParams);
            $this->info('URL Stats: ' . $statsUrlWithParams);

            $statsResponse = Http::timeout(30)
                ->withHeaders([
                    'Cookie' => implode('; ', $cookies),
                    'Referer' => $loginUrl,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])
                ->get($statsUrlWithParams);

            $this->info('Status: ' . $statsResponse->status());
            $this->info('Body: ' . $statsResponse->body());
            $this->newLine();

            // Test avec différents opérateurs
            $this->info('4️⃣ Test avec différents opérateurs...');
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
                    ->withHeaders([
                        'Cookie' => implode('; ', $cookies),
                        'Referer' => $loginUrl,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])
                    ->get($operatorUrl);

                $this->info("  Status: {$operatorResponse->status()}");
                $data = $operatorResponse->json();
                $hasData = !empty($data['data']);
                $this->info("  Données: " . ($hasData ? 'OUI' : 'NON'));
                
                if ($hasData) {
                    $this->info("  Data: " . json_encode($data['data'], JSON_PRETTY_PRINT));
                }
                $this->newLine();
            }

        } else {
            $this->error('❌ Échec de la connexion');
        }

        $this->info('✅ Test terminé!');
    }

    private function extractCsrfToken($content)
    {
        // Chercher le token CSRF dans différents formats
        $patterns = [
            '/name="_token"\s+value="([^"]+)"/',
            '/csrf-token.*?content="([^"]+)"/',
            '/_token.*?value="([^"]+)"/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function extractCookies($headers)
    {
        $cookies = [];
        
        if (isset($headers['Set-Cookie'])) {
            $setCookieHeaders = is_array($headers['Set-Cookie']) ? $headers['Set-Cookie'] : [$headers['Set-Cookie']];
            
            foreach ($setCookieHeaders as $cookieHeader) {
                // Extraire le nom et la valeur du cookie
                if (preg_match('/([^=]+)=([^;]+)/', $cookieHeader, $matches)) {
                    $cookies[] = $matches[1] . '=' . $matches[2];
                }
            }
        }

        return $cookies;
    }
}

