# 📋 Checklist Migration - Dashboard Eklektik

## 🎯 Vue d'Ensemble

Cette checklist garantit une migration réussie du Dashboard avec l'intégration Eklektik complète.

---

## ✅ Phase 1 : Préparation

### 📊 Sauvegarde Système
- [ ] **Base de données sauvegardée**
  ```bash
  mysqldump -u username -p dashboard_cp > backup_pre_eklektik_$(date +%Y%m%d).sql
  ```
- [ ] **Fichiers système sauvegardés**
  ```bash
  tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/dashboard/
  ```
- [ ] **Configuration .env sauvegardée**
  ```bash
  cp .env .env.backup.$(date +%Y%m%d)
  ```

### 🔍 Vérification Environnement
- [ ] **PHP Version** : 8.1+ confirmé
  ```bash
  php --version
  ```
- [ ] **MySQL/MariaDB** : Version 8.0+/10.6+ confirmée
  ```bash
  mysql --version
  ```
- [ ] **Redis** : Service actif
  ```bash
  redis-cli ping
  ```
- [ ] **Extensions PHP** : Toutes installées
  ```bash
  php -m | grep -E "(curl|json|mysql|redis|mbstring|xml|zip|gd)"
  ```

---

## ✅ Phase 2 : Déploiement Code

### 📁 Récupération du Code
- [ ] **Git Pull/Clone** réussi
  ```bash
  git pull origin main
  # ou
  git clone <repository-url> dashboard-cp
  ```
- [ ] **Vérification fichiers** : Tous les nouveaux fichiers présents
  ```bash
  ls -la app/Services/Eklektik*
  ls -la app/Http/Controllers/Api/EklektikDashboard*
  ls -la resources/views/components/eklektik-charts.blade.php
  ```

### 📦 Dépendances
- [ ] **Composer** : Installation réussie
  ```bash
  composer install --optimize-autoloader --no-dev
  ```
- [ ] **NPM** : Build assets réussi
  ```bash
  npm install
  npm run build
  ```

---

## ✅ Phase 3 : Configuration Base de Données

### 🗄️ Migrations
- [ ] **Migration EklektikStatsDaily** : Exécutée avec succès
  ```bash
  php artisan migrate --path=database/migrations/2025_09_24_003221_create_eklektik_stats_dailies_table.php
  ```
- [ ] **Vérification table** : Structure correcte
  ```sql
  DESCRIBE eklektik_stats_daily;
  ```
- [ ] **Index performants** : Créés automatiquement
  ```sql
  SHOW INDEX FROM eklektik_stats_daily;
  ```

### 📊 Données de Test
- [ ] **Table vide** : Prête pour synchronisation
  ```sql
  SELECT COUNT(*) FROM eklektik_stats_daily;
  -- Résultat attendu: 0
  ```

---

## ✅ Phase 4 : Configuration Eklektik

### 🔐 Credentials API
- [ ] **Variables .env** : Configurées
  ```env
  EKLEKTIK_API_URL=https://stats.eklectic.tn/getelements.php
  EKLEKTIK_USERNAME=your_username
  EKLEKTIK_PASSWORD=your_password
  EKLEKTIK_CACHE_DURATION=300
  ```
- [ ] **Test connectivité** : API accessible
  ```bash
  curl -u "username:password" "https://stats.eklectic.tn/getelements.php" | head -20
  ```

### 🔄 Synchronisation Initiale
- [ ] **Commande disponible** : Eklektik sync opérationnelle
  ```bash
  php artisan list | grep eklektik
  ```
- [ ] **Synchronisation test** : 1 jour de données
  ```bash
  php artisan eklektik:sync-stats --start-date=2025-09-23 --end-date=2025-09-23
  ```
- [ ] **Vérification données** : Enregistrements créés
  ```sql
  SELECT * FROM eklektik_stats_daily WHERE date = '2025-09-23' LIMIT 5;
  ```
- [ ] **Synchronisation complète** : Période complète
  ```bash
  php artisan eklektik:sync-stats --start-date=2025-09-01 --end-date=2025-09-24 --force
  ```

---

## ✅ Phase 5 : Tests APIs

### 🔗 Endpoints Eklektik
- [ ] **API KPIs** : Réponse 200 OK
  ```bash
  curl "http://localhost/api/eklektik-dashboard/kpis?start_date=2025-09-11&end_date=2025-09-24"
  ```
- [ ] **API Overview Chart** : Données graphique présentes
  ```bash
  curl "http://localhost/api/eklektik-dashboard/overview-chart?start_date=2025-09-11&end_date=2025-09-24"
  ```
- [ ] **API Revenue Evolution** : Données par opérateur
  ```bash
  curl "http://localhost/api/eklektik-dashboard/revenue-evolution?start_date=2025-09-11&end_date=2025-09-24"
  ```
- [ ] **API Revenue Distribution** : Distribution pie/bar charts
  ```bash
  curl "http://localhost/api/eklektik-dashboard/revenue-distribution?start_date=2025-09-11&end_date=2025-09-24"
  ```
- [ ] **API Subs Evolution** : Évolution abonnements
  ```bash
  curl "http://localhost/api/eklektik-dashboard/subs-evolution?start_date=2025-09-11&end_date=2025-09-24"
  ```

### 🧪 Validation Réponses
Chaque API doit retourner :
- [ ] **success: true**
- [ ] **data: {...}** avec contenu
- [ ] **Pas d'erreurs 500**
- [ ] **Temps de réponse < 2s**

---

## ✅ Phase 6 : Tests Frontend

### 🖥️ Dashboard Principal
- [ ] **Accès dashboard** : URL accessible sans erreur
  ```
  http://your-domain.com/dashboard
  ```
- [ ] **Onglet Eklektik** : Visible et cliquable
- [ ] **Chargement onglet** : Sans erreur JavaScript

### 📊 Graphiques Eklektik
- [ ] **Vue d'ensemble Multi-Axes** : Affiché avec données
  - Active Subs (barres rouges)
  - CA BigDeal (barres jaunes)
- [ ] **Revenus par Opérateur** : Lignes par opérateur
  - TT (bleu)
  - Taraji (orange)
  - Orange (rouge)
  - CA BigDeal Total (vert)
- [ ] **Répartition par Opérateur** : Graphique doughnut
- [ ] **Évolution Active Subs** : Lignes temporelles

### 🎨 Interface Utilisateur
- [ ] **Couleurs cohérentes** : Palette Distribution by Category appliquée
- [ ] **KPIs affichés** : Valeurs numériques correctes
- [ ] **Sélecteur opérateur** : Filtre fonctionnel
- [ ] **Sélection période** : Dates du dashboard principal utilisées

---

## ✅ Phase 7 : Performance

### ⚡ Cache et Optimisation
- [ ] **Cache Laravel** : Configuration optimale
  ```bash
  php artisan optimize
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] **Cache Redis** : Fonctionnel
  ```bash
  redis-cli keys "eklektik_*"
  ```
- [ ] **Temps de réponse** : Dashboard < 3s
- [ ] **Mémoire PHP** : Usage acceptable (<256MB)

### 📈 Monitoring Initial
- [ ] **Logs Laravel** : Pas d'erreurs critiques
  ```bash
  tail -f storage/logs/laravel.log
  ```
- [ ] **Logs serveur** : Pas d'erreurs 500
  ```bash
  tail -f /var/log/nginx/error.log
  ```

---

## ✅ Phase 8 : Configuration Production

### 🔐 Sécurité
- [ ] **Permissions fichiers** : Correctes
  ```bash
  sudo chown -R www-data:www-data /var/www/dashboard/
  sudo chmod -R 755 /var/www/dashboard/
  sudo chmod -R 775 /var/www/dashboard/storage/
  sudo chmod -R 775 /var/www/dashboard/bootstrap/cache/
  ```
- [ ] **Fichier .env** : Sécurisé
  ```bash
  chmod 600 .env
  ```

### ⚙️ Services Système
- [ ] **Nginx/Apache** : Configuration mise à jour
- [ ] **PHP-FPM** : Redémarré
  ```bash
  sudo systemctl restart php8.2-fpm nginx
  ```
- [ ] **Redis** : Service actif et configuré

---

## ✅ Phase 9 : Automatisation

### 🕒 Tâches Programmées
- [ ] **Crontab configuré** : Synchronisation automatique
  ```bash
  # Ajouter dans crontab
  0 6 * * * cd /var/www/dashboard && php artisan eklektik:sync-stats --days=1 >> /var/log/eklektik-sync.log 2>&1
  ```
- [ ] **Logs rotation** : Configuration logrotate
- [ ] **Sauvegarde automatique** : Script configuré

### 📊 Monitoring
- [ ] **Alertes système** : Configurées si applicable
- [ ] **Monitoring ressources** : CPU/Mémoire/Disque
- [ ] **Surveillance API** : Temps de réponse Eklektik

---

## ✅ Phase 10 : Validation Finale

### 🎯 Tests Utilisateur Final
- [ ] **Navigation dashboard** : Fluide
- [ ] **Tous les onglets** : Fonctionnels
- [ ] **Filtres opérateurs** : Réactifs
- [ ] **Graphiques interactifs** : Pas de sautillements
- [ ] **Données cohérentes** : Entre graphiques et KPIs

### 📋 Checklist Finale
- [ ] **Toutes les phases complétées**
- [ ] **Documentation accessible** : DEPLOYMENT_GUIDE_FINAL.md
- [ ] **Contacts support** : Disponibles
- [ ] **Procédures rollback** : Documentées

---

## 🚨 Procédure de Rollback

En cas de problème, procédure de retour arrière :

```bash
# 1. Arrêter les services
sudo systemctl stop nginx php8.2-fpm

# 2. Restaurer les fichiers
rm -rf /var/www/dashboard/
tar -xzf backup_files_YYYYMMDD.tar.gz -C /

# 3. Restaurer la base de données
mysql -u username -p dashboard_cp < backup_pre_eklektik_YYYYMMDD.sql

# 4. Restaurer .env
cp .env.backup.YYYYMMDD .env

# 5. Redémarrer les services
sudo systemctl start php8.2-fpm nginx
```

---

**⚠️ Important** : Cette checklist doit être suivie dans l'ordre pour garantir une migration réussie.

**📞 Support** : En cas de problème, consulter `DEPLOYMENT_GUIDE_FINAL.md` pour la résolution de problèmes.

---

**Version** : 1.0.0  
**Date** : 24 septembre 2025  
**Validé par** : Assistant IA

