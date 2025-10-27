# 🚀 Installation Guide - Notification API

## 📋 Prérequis

- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Redis (pour les queues)
- Node.js (pour les tests web push)

---

## 🔧 Installation

### 1. Installer les dépendances

```bash
composer install
```

### 2. Configuration

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate
```

### 3. Configurer la base de données

Éditer `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=notification_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Exécuter les migrations

```bash
php artisan migrate
```

### 5. Configurer Redis (Queue)

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

### 6. Générer les clés VAPID (Web Push)

```bash
php artisan webpush:vapid
```

Copier les clés générées dans `.env` :
```env
WEBPUSH_SUBJECT="mailto:your-email@example.com"
WEBPUSH_PUBLIC_KEY=votre_clé_publique
WEBPUSH_PRIVATE_KEY=votre_clé_privée
```

### 7. Configurer WhatsApp Business API

Obtenir vos credentials sur [Meta for Developers](https://developers.facebook.com)

```env
WHATSAPP_ACCESS_TOKEN=votre_token
WHATSAPP_PHONE_NUMBER_ID=votre_phone_id
```

### 8. Configurer l'Email

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

---

## ⚡ Démarrage

### 1. Lancer le serveur

```bash
php artisan serve
```

### 2. Lancer les workers (queues)

```bash
# Terminal 1
php artisan queue:work --queue=default --tries=3

# Terminal 2 (optionnel, pour plus de throughput)
php artisan queue:work --queue=default --tries=3
```

### 3. Monitorer les queues (optionnel)

```bash
# Installer Horizon (recommandé pour production)
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

---

## 🔑 Génération de clés API

### Via API

```bash
curl -X POST http://localhost:8000/api/v1/api-keys/generate \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mon Application",
    "permissions": ["web_push", "email", "whatsapp"],
    "rate_limit": 5000
  }'
```

### Réponse

```json
{
  "success": true,
  "data": {
    "key": "nk_abc123...",
    "secret": "xyz789...",
    "permissions": ["web_push", "email", "whatsapp"]
  },
  "warning": "Save the secret securely. It will not be displayed again."
}
```

⚠️ **IMPORTANT** : Sauvegarder le `secret`, il ne sera plus jamais affiché !

---

## 📝 Exemples d'utilisation

### 1. Envoyer une notification Web Push

```bash
curl -X POST http://localhost:8000/api/v1/notifications/web-push/send \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "title": "Nouvelle notification",
    "body": "Ceci est un test",
    "icon": "https://example.com/icon.png",
    "async": true
  }'
```

### 2. Envoyer un Email

```bash
curl -X POST http://localhost:8000/api/v1/notifications/email/send \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "user@example.com",
    "subject": "Test Email",
    "body": "<h1>Hello</h1><p>This is a test email</p>",
    "async": true
  }'
```

### 3. Envoyer un message WhatsApp

```bash
curl -X POST http://localhost:8000/api/v1/notifications/whatsapp/send \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+22997123456",
    "body": "Bonjour, ceci est un test WhatsApp!",
    "async": true
  }'
```

### 4. Envoi en masse

```bash
curl -X POST http://localhost:8000/api/v1/notifications/bulk-send \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "email",
    "recipients": [
      "user1@example.com",
      "user2@example.com",
      "user3@example.com"
    ],
    "payload": {
      "subject": "Annonce importante",
      "body": "<p>Message pour tous</p>"
    }
  }'
```

### 5. S'abonner aux notifications Web Push

```bash
curl -X POST http://localhost:8000/api/v1/notifications/web-push/subscribe \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
      "p256dh": "key_value",
      "auth": "auth_value"
    },
    "metadata": {
      "user_id": "123",
      "device": "Chrome/Windows"
    }
  }'
```

### 6. Voir les statistiques

```bash
curl -X GET http://localhost:8000/api/v1/notifications/stats \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret"
```

### 7. Voir les logs

```bash
curl -X GET http://localhost:8000/api/v1/notifications/logs \
  -H "X-API-Key: nk_your_key" \
  -H "X-API-Secret: your_secret"
```

---

## 🧪 Tests

```bash
# Créer un test
php artisan make:test NotificationTest

# Lancer les tests
php artisan test
```

---

## 🐛 Debugging

### Vérifier les jobs en échec

```bash
php artisan queue:failed
```

### Relancer les jobs échoués

```bash
php artisan queue:retry all
```

### Vider les queues

```bash
php artisan queue:flush
```

---

## 📊 Production

### Supervisor pour les workers

Créer `/etc/supervisor/conf.d/notification-worker.conf` :

```ini
[program:notification-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start notification-worker:*
```

### Optimisations

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔒 Sécurité

1. **Protéger la route de génération de clés API** en production
2. **Utiliser HTTPS** en production
3. **Configurer des rate limits** appropriés
4. **Monitorer les logs** régulièrement
5. **Sauvegarder** les clés API en lieu sûr
6. **Régénérer les secrets** compromis immédiatement

---

## 📚 Documentation API complète

Importer la collection Postman : [Collection disponible ici]

Ou accéder à la documentation Swagger (à configurer) : `http://localhost:8000/api/documentation`