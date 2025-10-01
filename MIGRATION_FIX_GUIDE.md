# Guide de correction des migrations Eklektik

## 🚨 Problème identifié

Le fichier de migration `2025_09_23_011532_create_eklektik_notifications_tracking_table.php` a été modifié pour créer la table `eklektik_transactions_tracking` au lieu de `eklektik_notifications_tracking`, causant des problèmes lors du déploiement sur la préprod.

## ✅ Corrections apportées

### 1. Migration corrigée
- **Fichier** : `database/migrations/2025_09_23_011532_create_eklektik_notifications_tracking_table.php`
- **Correction** : Maintenant crée correctement la table `eklektik_notifications_tracking`
- **Structure** : 
  - `id` (bigint, primary key)
  - `notification_id` (bigint, index)
  - `processed_at` (timestamp)
  - `kpi_updated` (boolean)
  - `processing_batch_id` (varchar(50), nullable)
  - `processing_metadata` (json, nullable)
  - `created_at`, `updated_at` (timestamps)
  - Index de performance

### 2. Table transactions_tracking
- **Fichier** : `database/migrations/2025_09_23_120134_create_eklektik_transactions_tracking_table.php`
- **Table** : `eklektik_transactions_tracking`
- **Structure** : Similaire mais avec `transaction_id` et clé étrangère vers `transactions_history`

## 🔧 Actions à effectuer sur la préprod

### Option 1 : Reset et re-migration (Recommandé)

```bash
# 1. Se connecter à la préprod
ssh user@preprod-server

# 2. Aller dans le répertoire du projet
cd /path/to/dashboard

# 3. Sauvegarder la base de données (sécurité)
mysqldump -u username -p preprod_dashboard > backup_$(date +%Y%m%d_%H%M%S).sql

# 4. Vérifier l'état des migrations
php artisan migrate:status

# 5. Rollback de la migration problématique
php artisan migrate:rollback --step=1

# 6. Supprimer manuellement la table si elle existe
mysql -u username -p preprod_dashboard -e "DROP TABLE IF EXISTS eklektik_transactions_tracking;"

# 7. Exécuter les migrations corrigées
php artisan migrate

# 8. Vérifier que les tables sont créées
php artisan migrate:status
```

### Option 2 : Correction manuelle (Si rollback impossible)

```bash
# 1. Se connecter à MySQL
mysql -u username -p preprod_dashboard

# 2. Créer la table notifications_tracking manuellement
CREATE TABLE `eklektik_notifications_tracking` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint unsigned NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `kpi_updated` tinyint(1) NOT NULL DEFAULT '0',
  `processing_batch_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processing_metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eklektik_notifications_tracking_notification_id_index` (`notification_id`),
  KEY `idx_processed_kpi` (`processed_at`,`kpi_updated`),
  KEY `idx_batch_processed` (`processing_batch_id`,`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# 3. Marquer la migration comme exécutée
INSERT INTO migrations (migration, batch) VALUES ('2025_09_23_011532_create_eklektik_notifications_tracking_table', 77);

# 4. Vérifier
SELECT * FROM migrations WHERE migration LIKE '%eklektik%';
```

## 📋 Vérification post-déploiement

### 1. Vérifier les tables
```sql
SHOW TABLES LIKE '%eklektik%';
```

### 2. Vérifier la structure
```sql
DESCRIBE eklektik_notifications_tracking;
DESCRIBE eklektik_transactions_tracking;
```

### 3. Vérifier les migrations
```bash
php artisan migrate:status | grep eklektik
```

## 🎯 Résultat attendu

Après correction, vous devriez avoir :

1. **Table `eklektik_notifications_tracking`** : Pour suivre les notifications
2. **Table `eklektik_transactions_tracking`** : Pour suivre les transactions
3. **Migrations cohérentes** : Noms de fichiers correspondant aux tables créées
4. **Déploiement réussi** : Plus d'erreurs lors des `php artisan migrate`

## ⚠️ Notes importantes

- **Sauvegardez toujours** avant de faire des modifications sur la préprod
- **Testez d'abord** sur un environnement de test si possible
- **Vérifiez les dépendances** : certaines tables peuvent avoir des clés étrangères
- **Documentez** les changements effectués pour l'équipe

## 🔍 Dépannage

Si vous rencontrez des erreurs :

1. **Table already exists** : Supprimez la table manuellement
2. **Foreign key constraint** : Vérifiez les tables référencées
3. **Migration not found** : Vérifiez que le fichier existe sur le serveur
4. **Permission denied** : Vérifiez les droits MySQL de l'utilisateur

## 📞 Support

En cas de problème, contactez l'équipe de développement avec :
- Les logs d'erreur complets
- L'état des migrations (`php artisan migrate:status`)
- La structure des tables (`SHOW TABLES`)
