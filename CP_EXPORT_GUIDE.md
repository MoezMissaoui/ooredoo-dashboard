# Guide d'utilisation - Club Privilèges Export API

## 🚀 Installation et Configuration

### 1. Variables d'environnement

Ajoutez ces variables à votre fichier `.env` :

```env
# Configuration Club Privilèges Export API
CP_EXPORT_URL=https://clubprivileges.app/api/get-pending-sync-data
CP_EXPORT_TOKEN=cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789
CP_EXPORT_TIMEOUT=300
CP_EXPORT_RETRY_ATTEMPTS=3
CP_EXPORT_RETRY_DELAY=5
```

### 2. Migration de la base de données

```bash
php artisan migrate
```

## 📋 Commandes disponibles

### Test de connexion
```bash
php artisan cp:sync --test
```

### Afficher l'état de synchronisation
```bash
php artisan cp:sync --state
```

### Synchronisation normale (1 boucle)
```bash
php artisan cp:sync
```

### Synchronisation avec plusieurs boucles (pour initial load)
```bash
php artisan cp:sync --loop=10
```

### Reset de l'état de synchronisation
```bash
php artisan cp:sync --reset
```

### Synchronisation complète (reset + plusieurs boucles)
```bash
php artisan cp:sync --reset --loop=50
```

## 🔄 Fonctionnement automatique

Le système est configuré pour s'exécuter automatiquement :

- **Toutes les 5 minutes** : Synchronisation incrémentale normale
- **Toutes les heures** : Visite du lien de sync Club Privilèges

## 📊 Tables synchronisées

| Table source | Clé primaire | Table destination |
|--------------|--------------|-------------------|
| client | client_id | clients |
| client_abonnement | client_abonnement_id | client_abonnements |
| history | history_id | histories |
| promotion_pass_orders | id | promotion_pass_orders |
| promotion_pass_vendu | id | promotion_pass_vendus |
| partner | partner_id | partners |
| promotion | promotion_id | promotions |

## 🔍 Monitoring

### Logs
- **Synchronisation export** : `storage/logs/cp-export-sync.log`
- **Visite sync** : `storage/logs/cp-sync.log`

### État de synchronisation
La table `sync_state` contient l'état de chaque table :
- `table_name` : Nom de la table
- `last_inserted_id` : Dernier ID synchronisé
- `last_synced_at` : Date de dernière synchronisation

## 🛠️ Dépannage

### Erreur 401 Unauthorized
- Vérifiez que le token est correct dans `.env`
- L'API peut nécessiter le format `Bearer <token>`

### Erreur 500 Internal Server Error
- Vérifiez que l'endpoint est accessible
- Vérifiez les logs pour plus de détails

### Pas de données synchronisées
- Vérifiez que les tables de destination existent
- Vérifiez les permissions de base de données
- Lancez un test de connexion : `php artisan cp:sync --test`

## 📈 Performance

### Initial Load
Pour une synchronisation complète initiale :
```bash
php artisan cp:sync --reset --loop=100
```

### Synchronisation continue
Le système gère automatiquement la synchronisation incrémentale. Chaque exécution ne récupère que les nouvelles données depuis le dernier ID synchronisé.

## 🔧 Configuration avancée

### Modifier la fréquence de synchronisation
Éditez `app/Console/Kernel.php` :
```php
// Toutes les 5 minutes (actuel)
$schedule->command('cp:sync')->everyFiveMinutes();

// Toutes les 10 minutes
$schedule->command('cp:sync')->everyTenMinutes();

// Toutes les heures
$schedule->command('cp:sync')->hourly();
```

### Ajouter de nouvelles tables
1. Modifiez `config/sync_export.php`
2. Ajoutez l'entrée dans la migration `sync_state`
3. Redéployez la migration

## 📝 Notes importantes

- Le système utilise l'**upsert** pour éviter les doublons
- Les erreurs sont loggées avec retry automatique
- Le système est **idempotent** : peut être relancé sans problème
- Les données sont synchronisées de manière **incrémentale** uniquement
