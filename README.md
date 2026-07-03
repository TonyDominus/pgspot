# PG Spot

Mappa collaborativa di Perugia: panorami, servizi igienici, eventi e altro.

**Stack:** Laravel 13 · Vue 3 · Inertia.js · Tailwind CSS · MySQL/SQLite

## Requisiti locali (WAMP)

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL (opzionale, default SQLite per sviluppo)

## Avvio rapido in locale

```bash
# Dipendenze
composer install
npm install

# Ambiente (se non esiste)
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Frontend
npm run dev

# Server (in un altro terminale)
php artisan serve
```

Apri `http://127.0.0.1:8000`.

### WAMP con virtual host

Punta il document root a `public/`:

```
DocumentRoot "c:/wamp64/www/pgspot/public"
```

Imposta in `.env`:

```
APP_URL=http://pgspot.local
```

### Credenziali demo (seeder)

Password per tutti in locale: `password` (configurabile con `SEED_DEMO_PASSWORD`)

| Ruolo        | Email                    |
|--------------|--------------------------|
| superadmin   | superadmin@pgspot.local  |
| admin        | admin@pgspot.local       |
| user         | user@pgspot.local        |

## Ruoli

| Ruolo        | Permessi principali                          |
|--------------|-----------------------------------------------|
| Guest        | Consulta mappa, POI ed eventi                 |
| user         | Contributi, preferiti, notifiche              |
| admin        | Moderazione, eventi, dashboard                |
| superadmin   | Impostazioni globali, gestione admin          |

## Struttura progetto

```
app/
  Enums/          # Ruoli, stati POI, contributi
  Http/
    Controllers/  # Home, Admin
    Middleware/   # EnsureUserHasRole
  Models/         # Poi, Category, Event, Contribution...
database/
  migrations/     # Schema completo
  seeders/        # Categorie e POI demo Perugia
deploy/           # Script e config nginx per VPS
docker/           # Docker per ambiente omogeneo
resources/js/
  Pages/          # Home, Admin/Dashboard
```

## Git — primo setup

Il repository non esiste ancora. Segui questi passi.

### 1. Crea il repo su GitHub

1. Vai su [github.com/new](https://github.com/new)
2. Nome suggerito: `pgspot`
3. **Non** inizializzare con README (esiste già in locale)
4. Crea il repository

### 2. Inizializza Git in locale

```bash
cd c:\wamp64\www\pgspot

git init
git add .
git commit -m "Initial commit: Laravel + PG Spot foundation"

git branch -M main
git remote add origin https://github.com/TUO_USERNAME/pgspot.git
git push -u origin main
```

### 3. File mai committati

Già esclusi da `.gitignore`:

- `.env` (segreti e credenziali)
- `vendor/`, `node_modules/`
- `storage/*.key`

**Non committare mai** password di produzione o `APP_KEY` della VPS.

### 4. Branch consigliati

```
main      → produzione (VPS)
develop   → sviluppo
feature/* → nuove funzionalità
```

```bash
git checkout -b develop
git push -u origin develop
```

## Deploy su VPS

### Prerequisiti server

- Ubuntu 22.04+ / Debian 12+
- Nginx, PHP 8.3-FPM, MySQL 8, Redis (opzionale)
- Composer, Node.js 20+
- Dominio puntato al server (es. `pgspot.it`)

### Prima installazione sulla VPS

```bash
# Sul server
sudo apt update
sudo apt install nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd redis-server git unzip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node (via nvm o nodesource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Clone
sudo mkdir -p /var/www/pgspot
sudo chown $USER:www-data /var/www/pgspot
git clone https://github.com/TUO_USERNAME/pgspot.git /var/www/pgspot
cd /var/www/pgspot

# Ambiente produzione
cp .env.example .env
nano .env   # vedi sezione sotto

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link

sudo chown -R www-data:www-data storage bootstrap/cache
```

### `.env` produzione (esempio)

```env
APP_NAME="PG Spot"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pgspot.it

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgspot
DB_USERNAME=pgspot
DB_PASSWORD=password_sicura_qui

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

SUPERADMIN_EMAIL=admin@tuodominio.it
SUPERADMIN_PASSWORD=password_molto_sicura
```

### Nginx

```bash
sudo cp deploy/nginx/pgspot.conf /etc/nginx/sites-available/pgspot
sudo ln -s /etc/nginx/sites-available/pgspot /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### SSL con Certbot

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d pgspot.it -d www.pgspot.it
```

### Aggiornamenti successivi

```bash
cd /var/www/pgspot
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

Oppure manualmente: `git pull`, `composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`, cache.

### Cron (code e scheduler Laravel)

```bash
crontab -e
```

Aggiungi:

```
* * * * * cd /var/www/pgspot && php artisan schedule:run >> /dev/null 2>&1
```

### Queue worker (systemd)

Per elaborare code in background (notifiche, upload):

```bash
sudo nano /etc/systemd/system/pgspot-worker.service
```

```ini
[Unit]
Description=PG Spot Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/pgspot/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable pgspot-worker
sudo systemctl start pgspot-worker
```

## Docker (alternativa locale / staging)

```bash
cp .env.example .env
# Imposta DB_CONNECTION=mysql, DB_HOST=db, credenziali dal docker-compose

docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm ci && npm run build
```

App su `http://localhost:8080`.

## Prossimi sviluppi

- [ ] Mappa Leaflet interattiva
- [ ] Form contributi utente + moderazione admin
- [ ] CRUD eventi e vetrine
- [ ] Pannello superadmin (impostazioni, ruoli)
- [ ] PWA (manifest + service worker)
- [ ] Upload foto POI

## Licenza

Proprietario — tutti i diritti riservati.
