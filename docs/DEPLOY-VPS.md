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

# Email transazionali (Resend — riusa la stessa API key dell'altro sito)
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx
MAIL_FROM_ADDRESS="noreply@tuodominio.it"
MAIL_FROM_NAME="${APP_NAME}"

# Cambia in produzione!
SUPERADMIN_EMAIL=admin@tuodominio.it
SUPERADMIN_PASSWORD=password_molto_sicura
```

Verifica il dominio su [resend.com/domains](https://resend.com/domains) e aggiungi i record DNS richiesti. Puoi usare la stessa API key dell'altro progetto sulla VPS; l'importante è che `MAIL_FROM_ADDRESS` sia un indirizzo del dominio verificato (es. `noreply@pgspot.it`).

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

# 1. Permessi PRIMA di tutto (composer chiama artisan → scrive log e bootstrap/cache)
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

# 2. Aggiorna codice e dipendenze
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Migrazioni e cache
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache

# 4. Permessi finali per Nginx/PHP-FPM
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

> **Nota Composer:** l'opzione corretta è `--optimize-autoloader` (con **-er** finale), non `--optimize-autoload`.

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
| `The "--optimize-autoload" option does not exist` | Usa `--optimize-autoloader` (con **-er**): `composer install --no-dev --optimize-autoloader` |
| Permission denied su `storage/logs` o `bootstrap/cache` | Esegui `chown`/`chmod` **prima** dei comandi artisan (vedi sezione 8) |
| `route:cache` / `CompiledRouteCollection` | `php artisan route:clear` poi riprova; se persiste, salta `route:cache` (il sito funziona ugualmente) |
| 502 Bad Gateway | Verifica PHP-FPM: `sudo systemctl status php8.3-fpm` |
| 500 errore Laravel | Controlla `storage/logs/laravel.log` |
| Permessi negati | `sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache` |
| Asset non caricati | Esegui `npm run build`, verifica `public/build/` |
| SSL non funziona | Dominio deve puntare alla VPS; `certbot certificates` |
| Altri siti rotti | Non modificare il default; usa solo `sites-available/pgspot` |

### Ripristino rapido dopo errori di deploy

Se il deploy è andato in errore a metà:

```bash
cd /var/www/pgspot
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
sudo rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

---

## 11. Backup

Script incluso in `deploy/backup.sh` — dump MySQL compresso + archivio foto (`storage/app/public`).

```bash
chmod +x deploy/backup.sh
sudo mkdir -p /var/backups/pgspot
./deploy/backup.sh
```

Cron giornaliero (es. 3:00):

```
0 3 * * * /var/www/pgspot/deploy/backup.sh >> /var/log/pgspot-backup.log 2>&1
```

**Cosa salvare:**

| Cosa | Come |
|------|------|
| Database | `deploy/backup.sh` (automatico) |
| Foto POI | incluso nello script |
| File `.env` | copia manuale fuori dal server (password, API key) |
| Codice | già su GitHub |

Conserva almeno 14 giorni di backup locali; idealmente copia periodica su storage esterno (S3, altro server, NAS).

L'ultimo backup eseguito compare nel pannello **Admin → Monitoraggio** (superadmin).

---

## 12. Email transazionali

PG Spot invia automaticamente:

| Evento | Quando |
|--------|--------|
| Verifica account | Registrazione |
| Benvenuto | Dopo verifica email |
| Reset password | Richiesta recupero password |
| Contributo approvato/rifiutato | Moderazione (se l'utente ha dato consenso) |
| POI aggiornato | Modifica POI collegato (se consenso) |

Test rapido: **Admin → Monitoraggio → Invia email di test**.

Email marketing (report luoghi più visitati, newsletter): previste in una fase successiva.

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
- [ ] Resend configurato (`MAIL_MAILER=resend`, dominio verificato)
- [ ] Backup cron attivo
- [ ] Login admin funzionante
