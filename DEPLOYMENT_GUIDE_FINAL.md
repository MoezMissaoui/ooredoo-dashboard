# 🚀 Guide de Déploiement - Dashboard Eklektik

## 📋 Vue d'Ensemble du Projet

Ce projet est un **Dashboard Analytics Laravel** avec intégration complète **Eklektik** pour la gestion des abonnements, revenus et statistiques opérateurs.

### 🎯 Fonctionnalités Principales
- **Dashboard Multi-Operateurs** : Orange, TT, Taraji, Ooredoo
- **Intégration Eklektik** : API externe avec synchronisation de données
- **Calculs de Revenue Sharing** : Répartition automatique CA Opérateur/Agrégateur/BigDeal
- **Analytics Temps Réel** : KPIs, graphiques Chart.js, statistiques détaillées
- **Système d'Authentification** : Roles (Admin, User), invitations, OTP
- **Cache Optimisé** : Performance améliorée avec cache Laravel

---

## 🛠️ Prérequis Système

### Serveur
- **PHP** : 8.1+ (recommandé 8.2)
- **MySQL/MariaDB** : 8.0+ ou 10.6+
- **Redis** : 6.0+ (pour cache/sessions)
- **Nginx/Apache** : Avec mod_rewrite
- **Composer** : 2.4+
- **Node.js** : 18+ avec NPM

### Extensions PHP Requises
```bash
php-mysql
php-redis
php-curl
php-json
php-mbstring
php-xml
php-zip
php-gd
php-intl
```

---

## 📦 Installation

### 1. Cloner le Projet
```bash
git clone <repository-url> dashboard-cp
cd dashboard-cp
```

### 2. Installation des Dépendances
```bash
# Dépendances PHP
composer install --optimize-autoloader --no-dev

# Dépendances Node.js
npm install
npm run build
```

### 3. Configuration Environnement
```bash
# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate
```

### 4. Configuration Base de Données
Éditer `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dashboard_cp
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Cache Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Configuration Eklektik
EKLEKTIK_API_URL=https://stats.eklectic.tn/getelements.php
EKLEKTIK_USERNAME=your_eklektik_username
EKLEKTIK_PASSWORD=your_eklektik_password
```

### 5. Migrations et Seeders
```bash
# Exécuter les migrations
php artisan migrate

# Insérer les données de base (rôles, admin)
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=SuperAdminSeeder
```

---

## ⚙️ Configuration Eklektik

### 1. Configuration API Eklektik
Dans `.env`, configurez les accès API :
```env
EKLEKTIK_API_URL=https://stats.eklectic.tn/getelements.php
EKLEKTIK_USERNAME=your_username
EKLEKTIK_PASSWORD=your_password
EKLEKTIK_CACHE_DURATION=300
```

### 2. Configuration Revenue Sharing
Les facteurs de calcul sont définis dans `app/Services/EklektikRevenueSharingService.php` :
- **Orange** : TVA 19%, Commission 12%
- **TT** : TVA 19%, Commission 12%  
- **Taraji** : TVA 19%, Remise 17%, Commission 33%

### 3. Synchronisation Initiale des Données
```bash
# Synchroniser les données Eklektik (30 derniers jours)
php artisan eklektik:sync-stats

# Ou pour une période spécifique
php artisan eklektik:sync-stats --start-date=2025-09-01 --end-date=2025-09-24
```

---

## 🔧 Configuration Serveur Web

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/dashboard-cp/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Apache Configuration (.htaccess)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 📊 Configuration Dashboard

### 1. Structure des Données
Le dashboard utilise plusieurs tables principales :
- `eklektik_stats_daily` : Statistiques journalières Eklektik
- `users` : Utilisateurs et rôles
- `user_operators` : Association utilisateurs-opérateurs
- `transaction_histories` : Historique des transactions

### 2. APIs Disponibles
```
GET /api/eklektik-dashboard/kpis
GET /api/eklektik-dashboard/overview-chart
GET /api/eklektik-dashboard/revenue-evolution
GET /api/eklektik-dashboard/revenue-distribution
GET /api/eklektik-dashboard/subs-evolution
```

### 3. Cache et Performance
```bash
# Optimiser le cache
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Vider le cache si nécessaire
php artisan cache:clear
```

---

## 🔄 Tâches de Maintenance

### 1. Synchronisation Automatique
Configurer un cron job pour la synchronisation automatique :
```bash
# Crontab entry - synchronisation quotidienne à 6h
0 6 * * * cd /var/www/dashboard-cp && php artisan eklektik:sync-stats --days=1 >> /var/log/eklektik-sync.log 2>&1
```

### 2. Nettoyage du Cache
```bash
# Nettoyage hebdomadaire du cache
0 2 * * 0 cd /var/www/dashboard-cp && php artisan cache:clear
```

### 3. Sauvegarde Base de Données
```bash
# Sauvegarde quotidienne
mysqldump -u username -p dashboard_cp > backup_$(date +%Y%m%d).sql
```

---

## 🧪 Tests et Validation

### 1. Vérification API Eklektik
```bash
php artisan test:eklektik-api
```

### 2. Test des Calculs Revenue Sharing
```bash
php artisan test:revenue-sharing
```

### 3. Vérification Dashboard
- Accéder à `/dashboard`
- Vérifier l'onglet "Eklektik"
- Tester les filtres par opérateur
- Valider l'affichage des graphiques

---

## 🚨 Résolution de Problèmes

### Erreurs Courantes

#### 1. Erreur 500 sur APIs Eklektik
```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Tester la connectivité API
php artisan eklektik:test-api
```

#### 2. Graphiques qui ne s'affichent pas
- Vérifier que les données sont synchronisées
- S'assurer que Chart.js est chargé
- Contrôler la console JavaScript

#### 3. Problèmes de Cache
```bash
# Vider complètement le cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

#### 4. Erreurs de Permissions
```bash
# Réajuster les permissions
sudo chown -R www-data:www-data /var/www/dashboard-cp
sudo chmod -R 755 /var/www/dashboard-cp
sudo chmod -R 775 /var/www/dashboard-cp/storage
sudo chmod -R 775 /var/www/dashboard-cp/bootstrap/cache
```

---

## 📈 Monitoring et Logs

### 1. Logs Principaux
- `storage/logs/laravel.log` : Logs application
- `/var/log/nginx/error.log` : Logs serveur web
- `/var/log/eklektik-sync.log` : Logs synchronisation

### 2. Monitoring Performance
- Surveiller l'utilisation mémoire PHP
- Contrôler les temps de réponse API
- Monitorer l'espace disque (logs/cache)

---

## 🔐 Sécurité

### 1. Configuration Firewall
```bash
# Ouvrir seulement les ports nécessaires
ufw allow 80
ufw allow 443
ufw allow 22
```

### 2. Configuration SSL (Recommandé)
```bash
# Avec Certbot
certbot --nginx -d your-domain.com
```

### 3. Sécurisation .env
```bash
# Permissions restrictives
chmod 600 .env
```

---

## 📞 Support

### Documentation Technique
- **Laravel** : https://laravel.com/docs
- **Chart.js** : https://www.chartjs.org/docs/
- **API Eklektik** : Documentation fournie séparément

### Logs de Debug
En cas de problème, fournir :
1. Logs Laravel (`storage/logs/laravel.log`)
2. Logs serveur web
3. Console JavaScript (erreurs browser)
4. Configuration `.env` (sans les secrets)

---

**Version** : 1.0.0  
**Dernière mise à jour** : 24 septembre 2025  
**Compatibilité** : Laravel 10+, PHP 8.1+

