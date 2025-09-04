<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Services\Sync\SyncService;

class SyncPullCommand extends Command
{
    protected $signature = 'sync:pull {table?} {--full}';
    protected $description = 'Pull incremental data from Club Privilèges API into dashboard DB';

    public function handle(SyncService $service)
    {
        $tables = Config::get('sync.tables');
        $target = $this->argument('table');

        if ($target) {
            if (!isset($tables[$target])) {
                $this->error("Unknown table $target");
                return 1;
            }
            $this->syncOne($service, $target);
            return 0;
        }

        // ordre des dépendances
        $order = ['partner','promotion','client','client_abonnement','promotion_pass_orders','promotion_pass_vendu','history'];
        foreach ($order as $t) {
            if (!isset($tables[$t])) continue;
            $this->syncOne($service, $t);
        }
        return 0;
    }

    private function syncOne(SyncService $service, string $table): void
    {
        $this->info("Sync $table...");
        $res = $service->pullTable($table);
        $this->info("$table: received={$res['received']} upserted={$res['upserted']} ms={$res['ms']}");
    }
}



