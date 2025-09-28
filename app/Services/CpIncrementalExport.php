<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CpIncrementalExport
{
    protected $endpoint;
    protected $token;
    protected $timeout;
    protected $retryAttempts;
    protected $retryDelay;
    protected $tables;
    protected $destinationTables;

    public function __construct()
    {
        $this->endpoint = config('sync_export.endpoint');
        $this->token = config('sync_export.token');
        $this->timeout = config('sync_export.timeout', 300);
        $this->retryAttempts = config('sync_export.retry_attempts', 3);
        $this->retryDelay = config('sync_export.retry_delay', 10); // Augmenté à 10 secondes
        $this->tables = config('sync_export.tables', []);
        $this->destinationTables = config('sync_export.destination_tables', []);
    }

    /**
     * Effectue une synchronisation complète
     */
    public function pullOnce(): array
    {
        Log::info('🔄 [CP Export] Début de la synchronisation incrémentale');

        $allResponses = [];
        $state = DB::table('sync_state')->pluck('last_inserted_id', 'table_name')->all();

        // Traiter chaque table individuellement pour éviter le dépassement de mémoire
        foreach ($this->tables as $table => $pk) {
            Log::info("📊 [CP Export] Synchronisation de la table: {$table}");

            $tablePayload = [
                $table => [
                    'colonne_id_name' => $pk,
                    'last_inserted_id' => (int)($state[$table] ?? 0),
                ]
            ];

            try {
                // 2) Appel HTTP avec retry pour cette table
                $response = $this->makeHttpRequest($tablePayload);

                if (isset($response['tables'][$table])) {
                    $allResponses['tables'][$table] = $response['tables'][$table];
                }

                Log::info("✅ [CP Export] Table {$table} synchronisée", [
                    'rows_count' => count($response['tables'][$table]['rows'] ?? []),
                    'max_id' => $response['tables'][$table]['max_id'] ?? 'N/A'
                ]);

                // Petite pause entre les tables pour éviter la surcharge
                sleep(2);

            } catch (Exception $e) {
                Log::error("❌ [CP Export] Erreur pour la table {$table}: " . $e->getMessage());
                // Continuer avec les autres tables même si une échoue
            }
        }

        // Retourner un format compatible
        $allResponses['status'] = true;
        $allResponses['tables'] = $allResponses['tables'] ?? [];

        Log::info('✅ [CP Export] Synchronisation terminée', [
            'tables_processed' => count($allResponses['tables'])
        ]);

        return $allResponses;
    }

    /**
     * Construit le payload des tables à synchroniser
     */
    protected function buildTablesPayload(): array
    {
        $state = DB::table('sync_state')
            ->pluck('last_inserted_id', 'table_name')
            ->all();

        // Commencer avec une seule table pour éviter le dépassement de mémoire côté serveur
        $tablesPayload = [];
        $firstTable = array_key_first($this->tables);
        $firstPk = $this->tables[$firstTable];
        
        $tablesPayload[$firstTable] = [
            'colonne_id_name' => $firstPk,
            'last_inserted_id' => (int)($state[$firstTable] ?? 0),
        ];

        return $tablesPayload;
    }

    /**
     * Effectue l'appel HTTP avec retry automatique
     */
    protected function makeHttpRequest(array $payload): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                Log::info("🌐 [CP Export] Tentative {$attempt}/{$this->retryAttempts}");

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => $this->token, // Token direct ou Bearer selon l'API
                    ])
                    ->post($this->endpoint, [
                        'tables' => $payload
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['status']) || !$data['status']) {
                        throw new Exception('API returned error status: ' . json_encode($data));
                    }

                    return $data;
                }

                // Log détaillé de l'erreur
                Log::error("❌ [CP Export] Erreur HTTP {$response->status()}", [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'endpoint' => $this->endpoint,
                    'token_length' => strlen($this->token)
                ]);

                // Si 401, essayer avec Bearer
                if ($response->status() === 401 && !str_contains($this->token, 'Bearer')) {
                    Log::info('🔐 [CP Export] Tentative avec Bearer token');
                    $response = Http::timeout($this->timeout)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $this->token,
                        ])
                        ->post($this->endpoint, [
                            'tables' => $payload
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['status']) && $data['status']) {
                            return $data;
                        }
                    }
                }

                // Si 500, essayer avec un payload minimal
                if ($response->status() === 500) {
                    Log::info('🔧 [CP Export] Erreur 500 - Tentative avec payload minimal');
                    $minimalPayload = [
                        'client' => [
                            'colonne_id_name' => 'client_id',
                            'last_inserted_id' => 0
                        ]
                    ];
                    
                    $response = Http::timeout($this->timeout)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => $this->token,
                        ])
                        ->post($this->endpoint, [
                            'tables' => $minimalPayload
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['status']) && $data['status']) {
                            Log::info('✅ [CP Export] Connexion réussie avec payload minimal');
                            return $data;
                        }
                    }
                }

                // Si 429 (Too Many Requests), attendre plus longtemps
                if ($response->status() === 429) {
                    Log::warning('⏳ [CP Export] Rate limit atteint (429) - Attente prolongée');
                    $delay = $this->retryDelay * 3; // Attendre 3x plus longtemps
                    sleep($delay);
                    continue; // Relancer la boucle
                }

                throw new Exception("HTTP {$response->status()}: {$response->body()}");

            } catch (Exception $e) {
                $lastException = $e;
                Log::warning("⚠️ [CP Export] Tentative {$attempt} échouée: " . $e->getMessage());

                if ($attempt < $this->retryAttempts) {
                    $delay = $this->retryDelay * $attempt; // Exponential backoff
                    Log::info("⏳ [CP Export] Attente de {$delay}s avant retry...");
                    sleep($delay);
                }
            }
        }

        throw new Exception("Toutes les tentatives ont échoué. Dernière erreur: " . $lastException->getMessage());
    }

    /**
     * Traite la réponse et met à jour les données
     */
    public function upsertAndAdvance(array $response): void
    {
        $tables = $response['tables'] ?? [];
        $totalRows = 0;

        foreach ($tables as $table => $block) {
            $rows = $block['rows'] ?? [];
            $maxId = $block['max_id'] ?? null;
            $count = $block['count'] ?? count($rows);
            $hasMore = $block['has_more'] ?? false;

            Log::info("📋 [CP Export] Traitement table {$table}", [
                'rows_count' => $count,
                'max_id' => $maxId,
                'has_more' => $hasMore
            ]);

            if (empty($rows)) {
                // Même sans données, on avance le curseur si fourni
                if ($maxId !== null) {
                    $this->advanceCursor($table, $maxId);
                }
                continue;
            }

            // Upsert des données
            $this->upsertTableData($table, $rows);
            $totalRows += count($rows);

            // Avancer le curseur
            if ($maxId !== null) {
                $this->advanceCursor($table, $maxId);
            }
        }

        Log::info("✅ [CP Export] Synchronisation terminée", [
            'total_rows_processed' => $totalRows,
            'tables_processed' => count($tables)
        ]);
    }

    /**
     * Effectue l'upsert des données d'une table
     */
    protected function upsertTableData(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $destinationTable = $this->destinationTables[$table] ?? $table;
        $pk = $this->tables[$table] ?? 'id';

        try {
            // Préparer les colonnes pour l'upsert
            $columns = array_keys($rows[0]);
            $updateColumns = array_values(array_diff($columns, [$pk]));

            // Effectuer l'upsert
            DB::table($destinationTable)->upsert($rows, [$pk], $updateColumns);

            Log::info("💾 [CP Export] Upsert réussi", [
                'table' => $destinationTable,
                'rows' => count($rows),
                'pk' => $pk
            ]);

        } catch (Exception $e) {
            Log::error("❌ [CP Export] Erreur upsert table {$destinationTable}", [
                'error' => $e->getMessage(),
                'rows_count' => count($rows)
            ]);
            throw $e;
        }
    }

    /**
     * Avance le curseur d'une table
     */
    protected function advanceCursor(string $table, int $maxId): void
    {
        DB::table('sync_state')->updateOrInsert(
            ['table_name' => $table],
            [
                'last_inserted_id' => $maxId,
                'last_synced_at' => now(),
                'updated_at' => now()
            ]
        );

        Log::info("📈 [CP Export] Curseur avancé", [
            'table' => $table,
            'new_max_id' => $maxId
        ]);
    }

    /**
     * Obtient l'état de synchronisation
     */
    public function getSyncState(): array
    {
        return DB::table('sync_state')
            ->orderBy('table_name')
            ->get()
            ->toArray();
    }

    /**
     * Reset l'état de synchronisation (pour re-sync complet)
     */
    public function resetSyncState(): void
    {
        DB::table('sync_state')->update([
            'last_inserted_id' => 0,
            'last_synced_at' => null,
            'updated_at' => now()
        ]);

        Log::info('🔄 [CP Export] État de synchronisation réinitialisé');
    }

    /**
     * Teste la connexion à l'API
     */
    public function testConnection(): array
    {
        try {
            $payload = $this->buildTablesPayload();
            $response = $this->makeHttpRequest($payload);
            
            return [
                'success' => true,
                'message' => 'Connexion réussie',
                'response' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
