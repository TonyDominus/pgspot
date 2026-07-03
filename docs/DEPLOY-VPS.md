# Deploy PG Spot su VPS (multi-sito)

Guida per caricare PG Spot su una VPS che ospita già altri siti, con dominio già puntato e SSL Let's Encrypt.

## Prerequisiti sulla VPS

- Ubuntu 22.04+ o Debian 12+
- Nginx già installato e funzionante
- PHP 8.3 + PHP-FPM
- MySQL/MariaDB
- Composer, Node.js 20+
- Git
- Certbot (per SSL)

Verifica versioni:

```bash
php -v
nginx -v
mysql --version
composer -V
node -v
```

---

## 1. Crea database MySQL

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE pgspot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pgspot'@'localhost' IDENTIFIED BY 'PASSWORD_SICURA_QUI';
GRANT ALL PRIVILEGES ON pgspot.* TO 'pgspot'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 2. Clone del progetto

Scegli una directory dedicata (es. come gli altri siti):

```bash
sudo mkdir -p /var/www/pgspot
sudo chown $USER:www-data /var/www/pgspot
cd /var/www/pgspot

git clone https://github.com/TUO_USERNAME/pgspot.git .
```

---

## 3. Configura ambiente

```bash
cp .env.example .env
nano .env
```

Impostazioni principali:

```env
APP_NAME="PG Spot"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tuodominio.it

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgspot
DB_USERNAME=pgspot
DB_PASSWORD=PASSWORD_SICURA_QUI

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Cambia in produzione!
SUPERADMIN_EMAIL=admin@tuodominio.it
SUPERADMIN_PASSWORD=password_molto_sicura
```

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
```

---

## 4. Permessi

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 5. Configurazione Nginx (nuovo sito)

Crea un file separato per il dominio (non toccare gli altri siti):

```bash
sudo nano /etc/nginx/sites-available/pgspot
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tuodominio.it www.tuodominio.it;

    root /var/www/pgspot/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> **Nota:** il path del socket PHP-FPM può variare. Controlla con:
> `ls /run/php/`

Abilita il sito:

```bash
sudo ln -s /etc/nginx/sites-available/pgspot /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. SSL con Let's Encrypt (Certbot)

Se Certbot non è installato:

```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx
```

Ottieni il certificato (Nginx configura automaticamente HTTPS):

```bash
sudo certbot --nginx -d tuodominio.it -d www.tuodominio.it
```

Certbot:
1. Verifica che il dominio punti alla VPS
2. Aggiunge il blocco `listen 443 ssl`
3. Configura il redirect HTTP → HTTPS
4. Imposta il rinnovo automatico

Verifica rinnovo automatico:

```bash
sudo certbot renew --dry-run
```

---

## 7. Cron e code (opzionale ma consigliato)

Scheduler Laravel:

```bash
crontab -e
```

```
* * * * * cd /var/www/pgspot && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Aggiornamenti futuri

Dopo ogni `git pull`:

```bash
cd /var/www/pgspot
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

Oppure usa lo script:

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

---

## 9. Collegare Git (prima volta da locale)

Sul tuo PC:

```bash
cd c:\wamp64\www\pgspot
git remote add origin https://github.com/TUO_USERNAME/pgspot.git
git push -u origin main
```

Sulla VPS il clone è già fatto al passo 2.

---

## 10. Troubleshooting

| Problema | Soluzione |
|----------|-----------|
| 502 Bad Gateway | Verifica PHP-FPM: `sudo systemctl status php8.3-fpm` |
| 500 errore Laravel | Controlla `storage/logs/laravel.log` |
| Permessi negati | `chown www-data:www-data storage bootstrap/cache` |
| Asset non caricati | Esegui `npm run build`, verifica `public/build/` |
| SSL non funziona | Dominio deve puntare alla VPS; `certbot certificates` |
| Altri siti rotti | Non modificare il default; usa solo `sites-available/pgspot` |

---

## Checklist rapida

- [ ] Database creato
- [ ] `.env` configurato con `APP_DEBUG=false`
- [ ] `composer install --no-dev`
- [ ] `npm run build`
- [ ] `php artisan migrate --seed`
- [ ] Nginx site abilitato
- [ ] Certbot SSL attivo
- [ ] Cron configurato
- [ ] Login admin funzionante
