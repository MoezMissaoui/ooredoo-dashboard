<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EklektikCronConfig extends Model
{
    use HasFactory;

    protected $table = 'eklektik_cron_config';

    protected $fillable = [
        'config_key',
        'config_value',
        'description',
        'is_active',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    // Constantes pour les clés de configuration
    const CRON_ENABLED = 'cron_enabled';
    const CRON_SCHEDULE = 'cron_schedule';
    const CRON_OPERATORS = 'cron_operators';
    const CRON_RETENTION_DAYS = 'cron_retention_days';
    const CRON_NOTIFICATION_EMAIL = 'cron_notification_email';
    const CRON_ERROR_EMAIL = 'cron_error_email';
    const CRON_BATCH_SIZE = 'cron_batch_size';
    const CRON_TIMEOUT = 'cron_timeout';

    /**
     * Récupérer une configuration par clé
     */
    public static function getConfig($key, $default = null)
    {
        $config = self::where('config_key', $key)
            ->where('is_active', true)
            ->first();
        
        return $config ? $config->config_value : $default;
    }

    /**
     * Définir une configuration
     */
    public static function setConfig($key, $value, $description = null, $userId = null)
    {
        return self::updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $value,
                'description' => $description,
                'is_active' => true,
                'updated_by' => $userId,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Récupérer toutes les configurations
     */
    public static function getAllConfigs()
    {
        return self::orderBy('config_key')
            ->get()
            ->keyBy('config_key');
    }

    /**
     * Initialiser les configurations par défaut
     */
    public static function initializeDefaultConfigs($userId = null)
    {
        $defaultConfigs = [
            [
                'key' => self::CRON_ENABLED,
                'value' => 'true',
                'description' => 'Activer/désactiver le cron Eklektik'
            ],
            [
                'key' => self::CRON_SCHEDULE,
                'value' => '0 2 * * *',
                'description' => 'Planification du cron (format cron)'
            ],
            [
                'key' => self::CRON_OPERATORS,
                'value' => '["ALL", "TT", "Orange", "Taraji", "Timwe"]',
                'description' => 'Opérateurs à traiter par le cron'
            ],
            [
                'key' => self::CRON_RETENTION_DAYS,
                'value' => '90',
                'description' => 'Nombre de jours de rétention des données de cache'
            ],
            [
                'key' => self::CRON_NOTIFICATION_EMAIL,
                'value' => '',
                'description' => 'Email pour les notifications de succès'
            ],
            [
                'key' => self::CRON_ERROR_EMAIL,
                'value' => '',
                'description' => 'Email pour les notifications d\'erreur'
            ],
            [
                'key' => self::CRON_BATCH_SIZE,
                'value' => '1000',
                'description' => 'Taille des lots de traitement'
            ],
            [
                'key' => self::CRON_TIMEOUT,
                'value' => '300',
                'description' => 'Timeout en secondes pour le traitement'
            ]
        ];

        foreach ($defaultConfigs as $config) {
            self::setConfig(
                $config['key'],
                $config['value'],
                $config['description'],
                $userId
            );
        }
    }

    /**
     * Obtenir la planification du cron formatée
     */
    public static function getCronSchedule()
    {
        $schedule = self::getConfig(self::CRON_SCHEDULE, '0 2 * * *');
        return self::formatCronSchedule($schedule);
    }

    /**
     * Formater la planification du cron pour l'affichage
     */
    public static function formatCronSchedule($schedule)
    {
        $parts = explode(' ', $schedule);
        if (count($parts) !== 5) {
            return 'Format invalide';
        }

        $descriptions = [
            'minute' => $parts[0],
            'hour' => $parts[1],
            'day' => $parts[2],
            'month' => $parts[3],
            'weekday' => $parts[4]
        ];

        $formatted = [];
        if ($descriptions['minute'] !== '*') {
            $formatted[] = "Minute: {$descriptions['minute']}";
        }
        if ($descriptions['hour'] !== '*') {
            $formatted[] = "Heure: {$descriptions['hour']}";
        }
        if ($descriptions['day'] !== '*') {
            $formatted[] = "Jour: {$descriptions['day']}";
        }
        if ($descriptions['month'] !== '*') {
            $formatted[] = "Mois: {$descriptions['month']}";
        }
        if ($descriptions['weekday'] !== '*') {
            $formatted[] = "Jour de la semaine: {$descriptions['weekday']}";
        }

        return empty($formatted) ? 'Tous les jours à 02:00' : implode(', ', $formatted);
    }

    /**
     * Valider une planification cron
     */
    public static function validateCronSchedule($schedule)
    {
        $parts = explode(' ', $schedule);
        if (count($parts) !== 5) {
            return false;
        }

        // Validation basique des champs cron
        foreach ($parts as $part) {
            if (!preg_match('/^(\*|[0-5]?\d)(,(\*|[0-5]?\d))*$/', $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtenir les opérateurs configurés
     */
    public static function getCronOperators()
    {
        $operators = self::getConfig(self::CRON_OPERATORS, '["ALL"]');
        return json_decode($operators, true) ?: ['ALL'];
    }

    /**
     * Vérifier si le cron est activé
     */
    public static function isCronEnabled()
    {
        return self::getConfig(self::CRON_ENABLED, 'true') === 'true';
    }
}