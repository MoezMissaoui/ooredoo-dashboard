<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Incrémental toutes les 15 minutes
        $schedule->command('sync:pull')->everyFifteenMinutes()->withoutOverlapping();
        // Rattrapage quotidien (fenêtre J-7 si on ajoute la logique ultérieurement)
        $schedule->command('sync:pull')->dailyAt('02:00')->withoutOverlapping();
        
        // Synchronisation Eklektik - Quotidienne à 02:30
        $schedule->command('eklektik:sync-stats --period=1')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/eklektik-sync.log'));
            
        // Synchronisation Eklektik - Hebdomadaire (7 jours) le dimanche à 03:00
        $schedule->command('eklektik:sync-stats --period=7')
            ->weeklyOn(0, '03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/eklektik-sync-weekly.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
