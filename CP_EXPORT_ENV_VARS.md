# Variables d'environnement pour Club Privilèges Export

Ajoutez ces variables à votre fichier `.env` :

```env
# Configuration Club Privilèges Export API
CP_EXPORT_URL=https://clubprivileges.app/api/get-pending-sync-data
CP_EXPORT_TOKEN=cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789
CP_EXPORT_TIMEOUT=300
CP_EXPORT_RETRY_ATTEMPTS=3
CP_EXPORT_RETRY_DELAY=5

# Configuration Club Privilèges Sync (existant)
CP_SYNC_SERVER_USERNAME=BiGHellO
CP_SYNC_SERVER_PASSWORD=EMQLj3EuDrjS22aNkj
CP_SYNC_USERNAME=imed@clubprivileges.app
CP_SYNC_PASSWORD=Taraji1919
```

## Description des variables

- `CP_EXPORT_URL` : Endpoint de l'API d'export incrémental
- `CP_EXPORT_TOKEN` : Token d'authentification pour l'API
- `CP_EXPORT_TIMEOUT` : Timeout des requêtes HTTP (en secondes)
- `CP_EXPORT_RETRY_ATTEMPTS` : Nombre de tentatives en cas d'échec
- `CP_EXPORT_RETRY_DELAY` : Délai entre les tentatives (en secondes)
