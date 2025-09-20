<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EklektikTestController extends Controller
{
    public function testAllEndpoints()
    {
        $results = [];
        
        try {
            // 1. Test d'authentification
            Log::info('🔐 Test d\'authentification Eklektik');
            
            $authResponse = Http::timeout(30)
                ->asForm()
                ->post('https://payment.eklectic.tn/API/oauth/token', [
                    'client_id' => '0a2e605d-88f6-11ec-9feb-fa163e3dd8b3',
                    'client_secret' => 'ee60bb148a0e468a5053f9db41008780',
                    'grant_type' => 'client_credentials'
                ]);
            
            $results['auth'] = [
                'status' => $authResponse->status(),
                'success' => $authResponse->successful(),
                'body' => $authResponse->body(),
                'token_received' => false,
                'token_length' => 0
            ];
            
            if ($authResponse->successful()) {
                $authData = $authResponse->json();
                $token = $authData['access_token'] ?? null;
                
                if ($token) {
                    $results['auth']['token_received'] = true;
                    $results['auth']['token_length'] = strlen($token);
                    
                    // 2. Test des vrais endpoints selon la documentation OpenAPI
                    $endpoints = [
                        'oauth_token' => 'https://payment.eklectic.tn/API/oauth/token',
                        'subscription_sendmt' => 'https://payment.eklectic.tn/API/subscription/sendmt',
                        'subscription_oneshot' => 'https://payment.eklectic.tn/API/subscription/oneshot',
                        'subscription_find' => 'https://payment.eklectic.tn/API/subscription/find',
                        'subscription_otp' => 'https://payment.eklectic.tn/API/subscription/otp',
                        'subscription_confirm' => 'https://payment.eklectic.tn/API/subscription/confirm',
                        'subscription_oneClick' => 'https://payment.eklectic.tn/API/subscription/oneClick',
                        'subscription_token' => 'https://payment.eklectic.tn/API/subscription/token'
                    ];
                    
                    $results['endpoints'] = [];
                    
                    foreach ($endpoints as $name => $url) {
                        try {
                            Log::info("📡 Test endpoint: {$name} - {$url}");
                            
                            $response = Http::timeout(30)
                                ->withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Accept' => 'application/json',
                                    'Content-Type' => 'application/json'
                                ])
                                ->get($url);
                            
                            $results['endpoints'][$name] = [
                                'url' => $url,
                                'status' => $response->status(),
                                'success' => $response->successful(),
                                'body' => $response->body(),
                                'headers' => $response->headers(),
                                'has_data' => false,
                                'data_count' => 0
                            ];
                            
                            if ($response->successful()) {
                                $data = $response->json();
                                if (is_array($data)) {
                                    $results['endpoints'][$name]['has_data'] = true;
                                    $results['endpoints'][$name]['data_count'] = count($data);
                                }
                            }
                            
                        } catch (\Exception $e) {
                            $results['endpoints'][$name] = [
                                'url' => $url,
                                'error' => $e->getMessage(),
                                'success' => false
                            ];
                        }
                    }
                    
                    // 3. Test avec des numéros spécifiques selon la documentation
                    $results['parameter_tests'] = [];
                    
                    // Numéros de test tunisiens
                    $testNumbers = [
                        '21612345678', // Format international
                        '12345678',    // Format local
                        '21698765432'  // Autre numéro de test
                    ];
                    
                    $paramTests = [];
                    
                    // Test des endpoints avec paramètres spécifiques
                    foreach ($testNumbers as $index => $msisdn) {
                        $paramTests["subscriber_tt_{$index}"] = [
                            'url' => "https://payment.eklectic.tn/API/subscription/subscribers/11/{$msisdn}",
                            'description' => "Subscriber TT pour MSISDN {$msisdn}",
                            'method' => 'GET'
                        ];
                        
                        $paramTests["subscriber_orange_{$index}"] = [
                            'url' => "https://payment.eklectic.tn/API/subscription/subscribers/12/{$msisdn}",
                            'description' => "Subscriber Orange pour MSISDN {$msisdn}",
                            'method' => 'GET'
                        ];
                    }
                    
                    // Test des endpoints POST avec données
                    $paramTests['subscription_find_tt'] = [
                        'url' => 'https://payment.eklectic.tn/API/subscription/find',
                        'description' => 'Trouver abonnement TT',
                        'method' => 'POST',
                        'data' => [
                            'msisdn' => '21612345678',
                            'offreid' => 11
                        ]
                    ];
                    
                    $paramTests['subscription_find_orange'] = [
                        'url' => 'https://payment.eklectic.tn/API/subscription/find',
                        'description' => 'Trouver abonnement Orange',
                        'method' => 'POST',
                        'data' => [
                            'msisdn' => '21612345678',
                            'offreid' => 12
                        ]
                    ];
                    
                    foreach ($paramTests as $name => $test) {
                        try {
                            $headers = [
                                'Authorization' => 'Bearer ' . $token,
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json'
                            ];
                            
                            if ($test['method'] === 'POST') {
                                $response = Http::timeout(30)
                                    ->withHeaders($headers)
                                    ->post($test['url'], $test['data'] ?? []);
                            } else {
                                $response = Http::timeout(30)
                                    ->withHeaders($headers)
                                    ->get($test['url']);
                            }
                            
                            $results['parameter_tests'][$name] = [
                                'description' => $test['description'],
                                'url' => $test['url'],
                                'method' => $test['method'],
                                'status' => $response->status(),
                                'success' => $response->successful(),
                                'body' => $response->body(),
                                'has_data' => false,
                                'data_count' => 0,
                                'response_type' => 'unknown'
                            ];
                            
                            if ($response->successful()) {
                                $data = $response->json();
                                if (is_array($data)) {
                                    $results['parameter_tests'][$name]['has_data'] = true;
                                    $results['parameter_tests'][$name]['data_count'] = count($data);
                                    $results['parameter_tests'][$name]['response_type'] = 'array';
                                } elseif (is_object($data)) {
                                    $results['parameter_tests'][$name]['has_data'] = true;
                                    $results['parameter_tests'][$name]['data_count'] = 1;
                                    $results['parameter_tests'][$name]['response_type'] = 'object';
                                }
                            }
                            
                        } catch (\Exception $e) {
                            $results['parameter_tests'][$name] = [
                                'description' => $test['description'],
                                'url' => $test['url'],
                                'method' => $test['method'] ?? 'GET',
                                'error' => $e->getMessage(),
                                'success' => false
                            ];
                        }
                    }
                }
            }
            
            $results['summary'] = [
                'total_endpoints_tested' => count($endpoints ?? []),
                'successful_endpoints' => 0,
                'endpoints_with_data' => 0,
                'total_parameter_tests' => count($paramTests ?? []),
                'successful_parameter_tests' => 0,
                'parameter_tests_with_data' => 0
            ];
            
            // Calculer les statistiques
            if (isset($results['endpoints'])) {
                foreach ($results['endpoints'] as $endpoint) {
                    if ($endpoint['success'] ?? false) {
                        $results['summary']['successful_endpoints']++;
                    }
                    if ($endpoint['has_data'] ?? false) {
                        $results['summary']['endpoints_with_data']++;
                    }
                }
            }
            
            if (isset($results['parameter_tests'])) {
                foreach ($results['parameter_tests'] as $test) {
                    if ($test['success'] ?? false) {
                        $results['summary']['successful_parameter_tests']++;
                    }
                    if ($test['has_data'] ?? false) {
                        $results['summary']['parameter_tests_with_data']++;
                    }
                }
            }
            
            $results['timestamp'] = now()->toISOString();
            
            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('Erreur test Eklektik complet', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }
}