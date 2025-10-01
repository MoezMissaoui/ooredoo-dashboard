# Script de gestion du scheduler Laravel Eklektik
param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("status", "start", "stop", "restart", "logs", "test")]
    [string]$Action = "status"
)

$TaskName = "Laravel-Eklektik-Scheduler"
$ProjectPath = "D:\Dashboard CP"

function Show-Status {
    Write-Host "=== STATUT DU SCHEDULER EKLEKTIK ===" -ForegroundColor Green
    
    # Info de la tache Windows
    $Task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($Task) {
        $TaskInfo = $Task | Get-ScheduledTaskInfo
        Write-Host "Tache Windows:" -ForegroundColor Yellow
        Write-Host "  Nom: $($Task.TaskName)" -ForegroundColor Cyan
        Write-Host "  Etat: $($Task.State)" -ForegroundColor Cyan
        Write-Host "  Derniere execution: $($TaskInfo.LastRunTime)" -ForegroundColor Cyan
        Write-Host "  Resultat: $($TaskInfo.LastTaskResult)" -ForegroundColor Cyan
        Write-Host "  Prochaine execution: $($TaskInfo.NextRunTime)" -ForegroundColor Cyan
    } else {
        Write-Host "❌ Tache Windows non trouvee!" -ForegroundColor Red
    }
    
    # Taches Laravel
    Write-Host "`nTaches Laravel:" -ForegroundColor Yellow
    cd $ProjectPath
    & php artisan schedule:list
    
    # Configuration Eklektik
    Write-Host "`nConfiguration Eklektik:" -ForegroundColor Yellow
    $config = & php -r "require 'vendor/autoload.php'; `$app = require 'bootstrap/app.php'; `$kernel = `$app->make(Illuminate\Contracts\Console\Kernel::class); `$kernel->bootstrap(); echo 'Enabled: ' . (App\Models\EklektikCronConfig::isCronEnabled() ? 'Yes' : 'No') . PHP_EOL; echo 'Schedule: ' . App\Models\EklektikCronConfig::getConfig('cron_schedule') . PHP_EOL;"
    Write-Host $config -ForegroundColor Cyan
}

function Start-Scheduler {
    Write-Host "Demarrage du scheduler..." -ForegroundColor Yellow
    Start-ScheduledTask -TaskName $TaskName
    Write-Host "✅ Scheduler demarre" -ForegroundColor Green
}

function Stop-Scheduler {
    Write-Host "Arret du scheduler..." -ForegroundColor Yellow
    Stop-ScheduledTask -TaskName $TaskName
    Write-Host "✅ Scheduler arrete" -ForegroundColor Green
}

function Restart-Scheduler {
    Write-Host "Redemarrage du scheduler..." -ForegroundColor Yellow
    Stop-ScheduledTask -TaskName $TaskName
    Start-Sleep -Seconds 2
    Start-ScheduledTask -TaskName $TaskName
    Write-Host "✅ Scheduler redémarre" -ForegroundColor Green
}

function Show-Logs {
    Write-Host "=== LOGS DU SCHEDULER ===" -ForegroundColor Green
    $LogPath = "$ProjectPath\storage\logs\eklektik-sync.log"
    if (Test-Path $LogPath) {
        Write-Host "Logs Eklektik (10 dernieres lignes):" -ForegroundColor Yellow
        Get-Content $LogPath -Tail 10
    } else {
        Write-Host "Fichier de log non trouve: $LogPath" -ForegroundColor Red
    }
    
    # Logs Laravel generaux
    $LaravelLogPath = "$ProjectPath\storage\logs\laravel.log"
    if (Test-Path $LaravelLogPath) {
        Write-Host "`nLogs Laravel (filtres Eklektik):" -ForegroundColor Yellow
        Get-Content $LaravelLogPath | Select-String -Pattern "EKLEKTIK|eklektik" | Select -Last 5
    }
}

function Test-Scheduler {
    Write-Host "=== TEST DU SCHEDULER ===" -ForegroundColor Green
    cd $ProjectPath
    
    Write-Host "Execution manuelle de schedule:run..." -ForegroundColor Yellow
    & php artisan schedule:run
    
    Write-Host "`nTest de synchronisation..." -ForegroundColor Yellow
    & php artisan eklektik:sync-stats --period=1
    
    Write-Host "✅ Tests termines" -ForegroundColor Green
}

# Execution de l'action demandee
switch ($Action) {
    "status" { Show-Status }
    "start" { Start-Scheduler }
    "stop" { Stop-Scheduler }
    "restart" { Restart-Scheduler }
    "logs" { Show-Logs }
    "test" { Test-Scheduler }
    default { Show-Status }
}

Write-Host "`n=== COMMANDES UTILES ===" -ForegroundColor Green
Write-Host ".\manage_scheduler.ps1 status   - Voir le statut" -ForegroundColor Cyan
Write-Host ".\manage_scheduler.ps1 start    - Demarrer" -ForegroundColor Cyan
Write-Host ".\manage_scheduler.ps1 stop     - Arreter" -ForegroundColor Cyan
Write-Host ".\manage_scheduler.ps1 restart  - Redemarrer" -ForegroundColor Cyan
Write-Host ".\manage_scheduler.ps1 logs     - Voir les logs" -ForegroundColor Cyan
Write-Host ".\manage_scheduler.ps1 test     - Tester" -ForegroundColor Cyan
