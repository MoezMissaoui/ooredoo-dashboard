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
