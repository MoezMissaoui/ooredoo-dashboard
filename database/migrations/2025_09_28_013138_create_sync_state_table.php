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
        Schema::create('sync_state', function (Blueprint $table) {
            $table->string('table_name')->primary();
            $table->unsignedBigInteger('last_inserted_id')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // Préremplir les tables supportées
        DB::table('sync_state')->insert([
            ['table_name' => 'client', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'client_abonnement', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'history', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'promotion_pass_orders', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'promotion_pass_vendu', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'partner', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'promotion', 'last_inserted_id' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_state');
    }
};
