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
RUN_USER="$(id -un)"

mkdir -p "$BACKUP_DIR" 2>/dev/null || sudo mkdir -p "$BACKUP_DIR"

if [[ ! -w "$BACKUP_DIR" ]]; then
    sudo chown "$RUN_USER:$RUN_USER" "$BACKUP_DIR"
    sudo chmod 755 "$BACKUP_DIR"
fi

if [[ ! -w "$BACKUP_DIR" ]]; then
    echo "Errore: $BACKUP_DIR non scrivibile per $RUN_USER"
    echo "Esegui: sudo mkdir -p $BACKUP_DIR && sudo chown $RUN_USER:$RUN_USER $BACKUP_DIR"
    exit 1
fi

cd "$APP_DIR"

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
mysqldump --no-tablespaces -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" | gzip > "$DB_FILE"
unset MYSQL_PWD

if [[ -d storage/app/public ]]; then
    tar -czf "$STORAGE_FILE" -C storage/app/public .
else
    echo "Attenzione: storage/app/public assente, salto backup foto"
    STORAGE_FILE=""
fi

ARTISAN="php artisan"
if [[ ! -w storage/logs || ! -w bootstrap/cache ]]; then
    ARTISAN="sudo -u www-data php artisan"
fi

if [[ -n "$STORAGE_FILE" ]]; then
    $ARTISAN pgspot:record-backup "$DB_FILE" --storage="$STORAGE_FILE" --type=cron
else
    $ARTISAN pgspot:record-backup "$DB_FILE" --type=cron
fi

find "$BACKUP_DIR" -type f -mtime +"$KEEP_DAYS" -delete

echo "[$(date -Iseconds)] Backup completato: $DB_FILE"
[[ -n "$STORAGE_FILE" ]] && echo "  Foto: $STORAGE_FILE"
