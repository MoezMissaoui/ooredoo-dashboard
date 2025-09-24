<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index pour les requêtes Eklektik sur transactions_history
        try {
            DB::statement('
                CREATE INDEX idx_eklektik_transactions_created_status 
                ON transactions_history (created_at, status)
            ');
        } catch (\Exception $e) {
            // Index existe déjà, continuer
        }
        
        // Index pour les transactions Eklektik par opérateur
        try {
            DB::statement('
                CREATE INDEX idx_eklektik_transactions_status_created 
                ON transactions_history (status, created_at)
            ');
        } catch (\Exception $e) {
            // Index existe déjà, continuer
        }
        
        // Index pour les transactions avec résultat JSON
        try {
            DB::statement('
                CREATE INDEX idx_eklektik_transactions_result 
                ON transactions_history (transaction_history_id, created_at, result(100))
            ');
        } catch (\Exception $e) {
            // Index existe déjà, continuer
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP INDEX idx_eklektik_transactions_created_status ON transactions_history');
        } catch (\Exception $e) {
            // Index n'existe pas, continuer
        }
        
        try {
            DB::statement('DROP INDEX idx_eklektik_transactions_status_created ON transactions_history');
        } catch (\Exception $e) {
            // Index n'existe pas, continuer
        }
        
        try {
            DB::statement('DROP INDEX idx_eklektik_transactions_result ON transactions_history');
        } catch (\Exception $e) {
            // Index n'existe pas, continuer
        }
    }
};