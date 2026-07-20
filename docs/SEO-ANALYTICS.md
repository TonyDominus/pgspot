# SEO, Search Console e Google Analytics — PG Spot

Guida passo passo per indicizzare pgspot.it e attivare GA4 in modo conforme al GDPR.

---

## Parte A — Verifiche tecniche (già nel codice)

Dopo il deploy, controlla che funzionino:

| URL | Cosa fa |
|-----|---------|
| `https://pgspot.it/sitemap.xml` | Sitemap dinamica (POI, itinerari, legal) |
| `https://pgspot.it/robots.txt` | Indica la sitemap, blocca `/admin` |
| Pagine POI | Meta description, Open Graph, JSON-LD Place |
| Banner cookie | Compare al primo visit — GA si attiva solo dopo “Accetta tutti” |

In **Admin → Impostazioni → Analytics** inserisci il Measurement ID GA4 (`G-XXXXXXXXXX`).

---

## Parte B — Google Search Console

### 1. Crea la proprietà

1. Vai su [search.google.com/search-console](https://search.google.com/search-console)
2. **Aggiungi proprietà** → scegli **Prefisso URL**: `https://pgspot.it`
3. Verifica di essere loggato con l’account Google del sito

### 2. Verifica il dominio

Metodo consigliato: **record DNS TXT**

1. Search Console ti dà un record tipo `google-site-verification=xxxxx`
2. Nel pannello DNS del dominio (Aruba, Cloudflare, ecc.) aggiungi un record **TXT**:
   - Nome: `@` (o `pgspot.it`)
   - Valore: quello fornito da Google
3. Attendi 5–30 minuti, poi clic **Verifica** in Search Console

Alternativa: file HTML o meta tag (più laborioso con Laravel).

### 3. Invia la sitemap

1. Search Console → **Sitemap** (menu sinistra)
2. In “Aggiungi una nuova sitemap” inserisci: `sitemap.xml`
3. Invia — stato dovrebbe diventare **Operazione riuscita** (può richiedere ore)

### 4. Controllo indicizzazione

- **Ispezione URL**: incolla `https://pgspot.it` → **Richiedi indicizzazione**
- Ripeti per 2–3 POI importanti
- Dopo qualche giorno: **Copertura** / **Pagine** per vedere quante URL sono indicizzate

---

## Parte C — Google Analytics 4

### 1. Crea la proprietà GA4

1. Vai su [analytics.google.com](https://analytics.google.com)
2. **Amministrazione** (ingranaggio) → **Crea** → **Proprietà**
3. Nome: `PG Spot`
4. Fuso orario: `Italia`
5. Settore: viaggi / intrattenimento (a piacere)
6. Crea un **Flusso di dati Web**:
   - URL: `https://pgspot.it`
   - Nome flusso: `PG Spot Web`

### 2. Copia il Measurement ID

Dopo la creazione vedrai **`G-XXXXXXXXXX`**. Copialo.

### 3. Inseriscilo in PG Spot

1. Login superadmin → **Admin → Impostazioni → Analytics**
2. Incolla il Measurement ID → **Salva impostazioni**
3. `git pull` + build non serve se modifichi solo da admin

### 4. Test del consenso cookie

1. Apri il sito in finestra anonima
2. Compare il banner cookie
3. Clic **Accetta tutti**
4. In GA4 → **Amministrazione → DebugView** (opzionale) o **Rapporti → Tempo reale**
5. Naviga qualche pagina — dovresti vedere 1 utente attivo

Se clic **Solo necessari**, GA **non** deve caricarsi (corretto per GDPR).

### 5. Collegare Search Console ad Analytics (consigliato)

1. GA4 → **Amministrazione → Collegamenti Search Console**
2. Collega la proprietà Search Console creata in Parte B
3. Così vedi query di ricerca anche in Analytics

---

## Parte D — Tag Google (alternativa avanzata)

Se in futuro preferisci **Google Tag Manager** invece del tag diretto:

1. Crea contenitore GTM su [tagmanager.google.com](https://tagmanager.google.com)
2. Aggiungi tag GA4 Configuration con il tuo `G-XXXXXXXXXX`
3. Trigger: solo dopo consenso analytics (richiede integrazione GTM nel codice — oggi PG Spot usa gtag diretto post-consenso)

Per il go-live il Measurement ID in Impostazioni è sufficiente.

---

## Checklist rapida

- [ ] `https://pgspot.it/sitemap.xml` risponde 200
- [ ] Search Console: dominio verificato
- [ ] Sitemap inviata in Search Console
- [ ] GA4: flusso web creato, ID in Admin → Impostazioni
- [ ] Banner cookie testato (accetta / rifiuta)
- [ ] Tempo reale GA4 mostra visite dopo consenso
- [ ] Cookie policy aggiornata in Admin → Impostazioni (menziona GA)

---

## Note legali

- Aggiorna la **Cookie Policy** spiegando cookie tecnici + Google Analytics (solo con consenso)
- GA4 con `anonymize_ip` è già attivo nel codice
- Per cookie analytics in UE serve consenso esplicito prima del caricamento — il banner PG Spot lo gestisce
