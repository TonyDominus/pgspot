#!/usr/bin/env bash
# Backup database + foto POI per PG Spot
# Uso: ./deploy/backup.sh
# Cron esempio (ogni giorno alle 3:00):
#   0 3 * * * /var/www/pgspot/deploy/backup.sh >> /var/log/pgspot-backup.log 2>&1

set -euo pipefail

APP_DIR="${PGSPOT_DIR:-/var/www/pgspot}"
BACKUP_DIR="${PGSPOT_BACKUP_DIR:-/var/backups/pgspot}"
KEEP_DAYS="${PGSPOT_BACKUP_KEEP_DAYS:-14}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

mkdir -p "$BACKUP_DIR"

cd "$APP_DIR"

# Carica credenziali DB da .env
if [[ ! -f .env ]]; then
    echo "Errore: .env non trovato in $APP_DIR"
    exit 1
fi

DB_DATABASE="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')"
DB_USERNAME="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')"
DB_PASSWORD="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')"
DB_HOST="$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"' || echo '127.0.0.1')"

DB_FILE="$BACKUP_DIR/db_${TIMESTAMP}.sql.gz"
STORAGE_FILE="$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz"

echo "[$(date -Iseconds)] Avvio backup PG Spot"

export MYSQL_PWD="$DB_PASSWORD"
mysqldump -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" | gzip > "$DB_FILE"
unset MYSQL_PWD

if [[ -d storage/app/public ]]; then
    tar -czf "$STORAGE_FILE" -C storage/app/public .
else
    echo "Attenzione: storage/app/public assente, salto backup foto"
    STORAGE_FILE=""
fi

if [[ -n "$STORAGE_FILE" ]]; then
    php artisan pgspot:record-backup "$DB_FILE" --storage="$STORAGE_FILE" --type=cron
else
    php artisan pgspot:record-backup "$DB_FILE" --type=cron
fi

find "$BACKUP_DIR" -type f -mtime +"$KEEP_DAYS" -delete

echo "[$(date -Iseconds)] Backup completato: $DB_FILE"
[[ -n "$STORAGE_FILE" ]] && echo "  Foto: $STORAGE_FILE"
