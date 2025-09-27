# 🚀 Guide de Déploiement - Dashboard CP (Préprod)

## 📋 Prérequis

### Environnement Serveur
- **PHP**: 8.2+ avec extensions (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath)
- **Composer**: 2.0+
- **Node.js**: 16+ (pour les assets)
- **MySQL**: 8.0+
- **Git**: 2.0+

### Accès Serveur
- Accès SSH au serveur préprod
- Accès à la base de données MySQL
- Permissions d'écriture sur le répertoire du projet

## 🔄 Étapes de Déploiement

### 1. Connexion au Serveur
```bash
ssh user@preprod-server
cd /path/to/dashboard-cp
```

### 2. Sauvegarde de Sécurité
```bash
# Sauvegarde de la base de données
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarde des fichiers
cp -r /path/to/dashboard-cp /path/to/backup/dashboard-cp_$(date +%Y%m%d_%H%M%S)
```

### 3. Récupération du Code
```bash
# Récupérer les dernières modifications
git fetch origin
git checkout develop
git pull origin develop

# Vérifier le commit
git log --oneline -1
# Doit afficher: 8e583fd feat: Amélioration complète du système Eklektik et navigation
```

### 4. Installation des Dépendances
```bash
# Dépendances PHP
composer install --no-dev --optimize-autoloader

# Dépendances Node.js (si nécessaire)
npm install --production
npm run build
```

### 5. Configuration de l'Environnement
```bash
# Copier le fichier d'environnement
cp .env.example .env

# Éditer la configuration
nano .env
```

#### Variables d'Environnement Importantes
```env
APP_ENV=preprod
APP_DEBUG=false
APP_URL=https://preprod-dashboard.example.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=preprod_dashboard
DB_USERNAME=preprod_user
DB_PASSWORD=secure_password

# Configuration Eklektik
EKLEKTIK_API_URL=https://api.eklektik.com
EKLEKTIK_API_TOKEN=your_token_here

# Configuration Sync
CP_API_URL=https://api.club-privileges.com
CP_API_TOKEN=your_cp_token_here
```

### 6. Migration de la Base de Données
```bash
# Exécuter les migrations
php artisan migrate --force

# Vérifier les nouvelles tables
php artisan tinker
>>> \DB::select("SHOW TABLES LIKE 'eklektik_%'");
>>> exit
```

### 7. Optimisation de l'Application
```bash
# Cache de configuration
php artisan config:cache

# Cache des routes
php artisan route:cache

# Cache des vues
php artisan view:cache

# Optimisation de l'autoloader
composer dump-autoload --optimize
```

### 8. Permissions et Propriétaire
```bash
# Définir les permissions
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chown -R www-data:www-data storage bootstrap/cache

# Créer le lien symbolique pour le stockage
php artisan storage:link
```

### 9. Configuration du Scheduler Windows
```bash
# Copier les scripts PowerShell
cp manage_scheduler.ps1 /path/to/scripts/
cp run_cron.bat /path/to/scripts/

# Configurer la tâche planifiée Windows
# Utiliser le script manage_scheduler.ps1 pour la gestion
```

### 10. Test de l'Application
```bash
# Test des routes
php artisan route:list --name=admin.eklektik

# Test de la configuration
php artisan config:show

# Test de la base de données
php artisan tinker
>>> \App\Models\User::count();
>>> \App\Models\EklektikSyncTracking::count();
>>> exit
```

## 🔧 Configuration Post-Déploiement

### 1. Configuration Eklektik
1. Accéder à: `https://preprod-dashboard.example.com/admin/eklektik-cron`
2. Configurer le cron:
   - **Activer**: ✅
   - **Fréquence**: `0 3 * * *` (tous les jours à 3h)
   - **Opérateurs**: ALL
   - **Notifications**: ✅

### 2. Test des Fonctionnalités
1. **Dashboard Principal**: `https://preprod-dashboard.example.com/dashboard`
2. **Sub-Stores**: `https://preprod-dashboard.example.com/sub-stores/dashboard`
3. **Configuration Eklektik**: Menu Profil → Configuration Eklektik
4. **Synchronisation**: Menu Profil → Gestion des Synchronisations

### 3. Vérification des Logs
```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs Eklektik
tail -f storage/logs/eklektik-sync.log

# Logs du serveur web
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

## 🚨 Gestion des Erreurs

### Erreurs Communes

#### 1. Erreur de Migration
```bash
# Si migration échoue
php artisan migrate:rollback
php artisan migrate --force
```

#### 2. Erreur de Cache
```bash
# Vider tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 3. Erreur de Permissions
```bash
# Réparer les permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 4. Erreur de Base de Données
```bash
# Vérifier la connexion
php artisan tinker
>>> \DB::connection()->getPdo();
>>> exit
```

## 📊 Monitoring Post-Déploiement

### 1. Vérifications Quotidiennes
- [ ] Dashboard principal accessible
- [ ] Synchronisation Eklektik fonctionnelle
- [ ] Logs sans erreurs critiques
- [ ] Performance acceptable

### 2. Vérifications Hebdomadaires
- [ ] Sauvegarde de la base de données
- [ ] Nettoyage des logs anciens
- [ ] Mise à jour des dépendances
- [ ] Test des fonctionnalités critiques

### 3. Métriques à Surveiller
- Temps de réponse des pages
- Utilisation de la mémoire
- Taille des logs
- Erreurs 500/404

## 🔄 Rollback (En Cas de Problème)

### 1. Rollback Rapide
```bash
# Revenir au commit précédent
git checkout HEAD~1
composer install --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Rollback Complet
```bash
# Restaurer la sauvegarde
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
cp -r /path/to/backup/dashboard-cp_YYYYMMDD_HHMMSS/* /path/to/dashboard-cp/
```

## 📞 Support

### Contacts
- **Développeur**: [Votre nom]
- **Admin Système**: [Admin contact]
- **Base de Données**: [DBA contact]

### Ressources
- **Documentation**: [Lien vers la doc]
- **Monitoring**: [Lien vers le monitoring]
- **Logs**: [Lien vers les logs]

---

## ✅ Checklist de Déploiement

- [ ] Sauvegarde effectuée
- [ ] Code récupéré (commit 8e583fd)
- [ ] Dépendances installées
- [ ] Configuration mise à jour
- [ ] Migrations exécutées
- [ ] Caches optimisés
- [ ] Permissions configurées
- [ ] Scheduler configuré
- [ ] Tests fonctionnels passés
- [ ] Monitoring activé
- [ ] Documentation mise à jour

**Date de déploiement**: ___________  
**Version déployée**: 8e583fd  
**Déployé par**: ___________  
**Validé par**: ___________