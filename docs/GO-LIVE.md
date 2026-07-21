# Go-live — backup cron e monitoraggio

## Smoke test

Verifica automatica prima del lancio (config, DB, pagine pubbliche, SEO).

**Da pannello:** Admin → Monitoraggio → **Esegui smoke test**

**Da VPS/CLI:**

```bash
cd /var/www/pgspot
sudo -u www-data php artisan pgspot:smoke-test
```

Exit code `0` = ok (eventuali avvisi gialli non bloccano). Exit code `1` = almeno un controllo fallito.

---

## UptimeRobot (gratis)

Monitor esterno indipendente dal server — ti avvisa se il sito non risponde.

1. Registrati su [uptimerobot.com](https://uptimerobot.com)
2. **Add New Monitor**
3. Tipo: **HTTP(s)**
4. Friendly name: `PG Spot`
5. URL: `https://pgspot.it/up`
6. Monitoring interval: **5 minutes**
7. **Create monitor**
8. Menu **My Settings → Alert Contacts** → aggiungi la tua email e abilita alert per il monitor

Risposta attesa: HTTP **200** con body `{"status":"ok"}` (health check Laravel).

---

## Backup automatico (cron)

### 1. Prepara cartella e permessi (una tantum)

```bash
sudo mkdir -p /var/backups/pgspot
sudo chown asher:asher /var/backups/pgspot
chmod 755 /var/backups/pgspot

sudo touch /var/log/pgspot-backup.log
sudo chown asher:asher /var/log/pgspot-backup.log
```

(Sostituisci `asher` con il tuo utente VPS se diverso.)

### 2. Test manuale

```bash
cd /var/www/pgspot
chmod +x deploy/backup.sh
./deploy/backup.sh
```

Verifica file creati:

```bash
ls -lh /var/backups/pgspot/
```

E in **Admin → Monitoraggio → Ultimo backup** compare data e ora.

### 3. Attiva cron giornaliero

```bash
crontab -e
```

Aggiungi questa riga (backup ogni giorno alle 3:00):

```cron
0 3 * * * /var/www/pgspot/deploy/backup.sh >> /var/log/pgspot-backup.log 2>&1
```

Salva e esci. Verifica:

```bash
crontab -l
```

### 4. Controlla log (opzionale)

```bash
tail -f /var/log/pgspot-backup.log
```

---

## Scheduler Laravel (opzionale)

Se in futuro aggiungi task programmati Laravel:

```cron
* * * * * cd /var/www/pgspot && sudo -u www-data php artisan schedule:run >> /dev/null 2>&1
```

Oggi non è obbligatorio per il go-live.

---

## Checklist finale

- [ ] Smoke test ok da Monitoraggio
- [ ] UptimeRobot attivo su `https://pgspot.it/up`
- [ ] Cron backup configurato (`crontab -l`)
- [ ] Password admin cambiate
- [ ] Testi legali compilati
- [ ] Search Console: sitemap inviata
