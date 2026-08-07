<?php

namespace App\Support;

class LegalDefaults
{
    public static function body(string $page): string
    {
        return match ($page) {
            'privacy' => <<<'TXT'
Informativa privacy — PG Spot

Titolare: PG Spot (contatto: info@pgspot.it).
Questo sito consente di consultare una mappa collaborativa di luoghi a Perugia, creare un account, salvare preferiti, lasciare recensioni e proporre nuovi punti di interesse.

Dati trattati
- Dati di account (nome, email, password hashata) se ti registri
- Contenuti che invii (contributi, foto, recensioni, segnalazioni)
- Dati tecnici di navigazione (IP, log server) e, solo con consenso, cookie analitici (Google Analytics)

Finalità e basi giuridiche
- Erogazione del servizio e sicurezza (esecuzione del contratto / legittimo interesse)
- Moderazione contenuti e prevenzione abusi
- Misurazione audience solo dopo consenso cookie

Diritti
Puoi chiedere accesso, rettifica, cancellazione o limitazione scrivendo a info@pgspot.it. Puoi eliminare l’account dalle impostazioni profilo.
Per reclami: Garante per la protezione dei dati personali.

Aggiornamento: agosto 2026. Il testo può essere modificato dal pannello amministrazione.
TXT,
            'termini' => <<<'TXT'
Termini di utilizzo — PG Spot

Usando PG Spot accetti questi termini.

Il servizio
PG Spot fornisce una mappa collaborativa informativa su Perugia. I contenuti (luoghi, orari, accessibilità, recensioni) possono essere incompleti o non aggiornati: verifica sempre sul posto quando rilevante per sicurezza o accessibilità.

Account e contributi
Sei responsabile di quanto pubblichi. Non caricare contenuti illegali, offensivi, o foto di terzi senza diritti. I contributi sono soggetti a moderazione e possono essere rifiutati o rimossi.

Uso consentito
È vietato abusare della piattaforma, tentare accessi non autorizzati o usare i dati per spam.

Limitazione di responsabilità
PG Spot è fornito “così com’è”. Non rispondiamo di danni derivanti da informazioni inaccurate o indisponibilità del servizio, nei limiti di legge.

Contatti: info@pgspot.it
TXT,
            'cookie' => <<<'TXT'
Cookie Policy — PG Spot

Cookie tecnici
Necessari al funzionamento (sessione, CSRF, preferenze essenziali). Non richiedono consenso.

Cookie analitici
Google Analytics (GA4) viene attivato solo se accetti i cookie non essenziali dal banner. Serve a capire come viene usato il sito in forma aggregata.

Gestione
Puoi rifiutare i cookie analitici dal banner o cancellare i cookie dal browser. Continuando senza accettare, il sito resta utilizzabile con i soli cookie tecnici.

Contatti: info@pgspot.it
TXT,
            'contatti' => <<<'TXT'
Contatti — PG Spot

Email: info@pgspot.it

Per segnalazioni su luoghi, problemi tecnici o richieste privacy usa l’indirizzo sopra.
Se hai un account, puoi anche proporre correzioni dalla funzione “Aggiungi luogo” / contributi.
TXT,
            default => '',
        };
    }

    /** @return array{privacy: string, terms: string, cookies: string, contact: string} */
    public static function all(): array
    {
        return [
            'privacy' => self::body('privacy'),
            'terms' => self::body('termini'),
            'cookies' => self::body('cookie'),
            'contact' => self::body('contatti'),
        ];
    }
}
