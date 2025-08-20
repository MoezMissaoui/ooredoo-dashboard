# 🚀 Guide de Déploiement - Ooredoo Dashboard

## 📋 Prérequis Serveur

### Environnement Requis
- **PHP**: 8.1 ou supérieur
- **MySQL**: 8.0 ou supérieur 
- **Apache/Nginx**: avec mod_rewrite activé
- **Composer**: pour la gestion des dépendances PHP
- **Node.js**: (optionnel, pour les assets)

### Extensions PHP Requises
```bash
php-mysql
php-mbstring
php-xml
php-curl
php-zip
php-gd
php-json
php-tokenizer
php-fileinfo
```

## 📦 Contenu du Package

```
ooredoo-dashboard/
├── app/                    # Code application Laravel
├── database/              # Migrations et seeders
├── resources/             # Vues et assets
├── public/               # Point d'entrée web
├── .env.example          # Configuration d'exemple
├── composer.json         # Dépendances PHP
├── DEPLOYMENT_GUIDE.md   # Ce guide
├── PRODUCTION_CONFIG.md  # Configuration production
└── deploy.sh            # Script de déploiement
```

## 🔧 Instructions de Déploiement

### Étape 1: Préparer l'Environnement

1. **Créer le répertoire du projet**:
```bash
cd /var/www/html
sudo mkdir ooredoo-dashboard
sudo chown www-data:www-data ooredoo-dashboard
```

2. **Uploader les fichiers**:
   - Extraire l'archive dans `/var/www/html/ooredoo-dashboard/`
   - Vérifier que tous les fichiers sont présents

### Étape 2: Configuration de Base

1. **Copier la configuration**:
```bash
cd /var/www/html/ooredoo-dashboard
cp .env.example .env
```

2. **Éditer le fichier .env** avec les paramètres du serveur:
```bash
nano .env
```

**Variables importantes à configurer**:
```env
APP_NAME="Ooredoo Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ooredoo_dashboard
DB_USERNAME=votre_user_mysql
DB_PASSWORD=votre_password_mysql

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=assistant@clubprivileges.app
MAIL_PASSWORD="nltk qbof szsp qopq"
MAIL_FROM_ADDRESS=assistant@clubprivileges.app
```

### Étape 3: Installation des Dépendances

```bash
# Installer les dépendances PHP
composer install --optimize-autoloader --no-dev

# Générer la clé d'application
php artisan key:generate

# Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Étape 4: Configuration de la Base de Données

1. **Créer la base de données**:
```sql
CREATE DATABASE ooredoo_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ooredoo_user'@'localhost' IDENTIFIED BY 'password_securise';
GRANT ALL PRIVILEGES ON ooredoo_dashboard.* TO 'ooredoo_user'@'localhost';
FLUSH PRIVILEGES;
```

2. **Exécuter les migrations**:
```bash
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=RolesSeeder
```

### Étape 5: Configuration des Permissions

```bash
# Permissions des dossiers
sudo chown -R www-data:www-data /var/www/html/ooredoo-dashboard
sudo chmod -R 755 /var/www/html/ooredoo-dashboard
sudo chmod -R 775 /var/www/html/ooredoo-dashboard/storage
sudo chmod -R 775 /var/www/html/ooredoo-dashboard/bootstrap/cache
```

### Étape 6: Configuration Apache/Nginx

#### Pour Apache (.htaccess inclus):
```apache
<VirtualHost *:80>
    ServerName dashboard.ooredoo.com
    DocumentRoot /var/www/html/ooredoo-dashboard/public
    
    <Directory /var/www/html/ooredoo-dashboard/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ooredoo_error.log
    CustomLog ${APACHE_LOG_DIR}/ooredoo_access.log combined
</VirtualHost>
```

#### Pour Nginx:
```nginx
server {
    listen 80;
    server_name dashboard.ooredoo.com;
    root /var/www/html/ooredoo-dashboard/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔐 Sécurité et SSL

### Configuration SSL (Recommandé)
```bash
# Installer Certbot pour Let's Encrypt
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d dashboard.ooredoo.com
```

### Sécurisation Supplémentaire
```bash
# Masquer la version de serveur
echo "ServerTokens Prod" >> /etc/apache2/apache2.conf

# Désactiver les fonctions PHP dangereuses
# Dans php.ini: disable_functions = exec,passthru,shell_exec,system
```

## ✅ Tests de Vérification

### 1. Test de Connexion
- Accéder à `https://votre-domaine.com`
- Vérifier que la page de connexion s'affiche

### 2. Test de Connexion Super Admin
- **Email**: `superadmin@clubprivileges.app`
- **Mot de passe**: `SuperAdmin2024!`
- Vérifier l'accès au dashboard

### 3. Test des Fonctionnalités
- ✅ Affichage des données globales
- ✅ Sélection d'opérateurs
- ✅ Filtres de dates
- ✅ Graphiques interactifs
- ✅ Export de données
- ✅ Gestion des utilisateurs
- ✅ Système d'invitations

## 🚨 Dépannage

### Erreurs Communes

1. **Erreur 500**:
   - Vérifier les logs: `tail -f storage/logs/laravel.log`
   - Vérifier les permissions
   - Vérifier la configuration .env

2. **Erreur de Base de Données**:
   - Vérifier les credentials dans .env
   - Tester la connexion: `php artisan tinker` puis `DB::connection()->getPdo()`

3. **Erreur d'Email**:
   - Vérifier la configuration SMTP
   - Tester: `php artisan tinker` puis `Mail::raw('Test', function($msg) { $msg->to('test@test.com'); })`

### Commandes Utiles
```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Vérifier l'état de l'application
php artisan about
```

## 📞 Support

**Contact Technique**: 
- Pour les problèmes de déploiement, contacter l'équipe de développement
- Logs disponibles dans `storage/logs/laravel.log`
- Base de données accessible via phpMyAdmin ou ligne de commande

## 🔄 Mises à Jour Futures

Pour les mises à jour futures:
1. Sauvegarder la base de données
2. Sauvegarder le fichier .env
3. Remplacer les fichiers du code
4. Exécuter `composer install --no-dev`
5. Exécuter `php artisan migrate`
6. Vider les caches

---
**Version**: 1.0  
**Date**: $(date '+%Y-%m-%d')  
**Environnement**: Production
