<?php

namespace App\Console\Commands;

use App\Services\MvpPrepareService;
use Illuminate\Console\Command;

class PrepareMvpCommand extends Command
{
    protected $signature = 'pgspot:prepare-mvp
                            {--dry-run : Mostra le azioni senza scrivere}
                            {--skip-import : Non importare POI da OpenStreetMap}
                            {--limit=250 : Limite elementi OSM}';

    protected $description = 'Chiude i gap MVP (legal, cleanup, eventi, import OSM) — resta solo caricare le foto';

    public function handle(MvpPrepareService $prepare): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipImport = (bool) $this->option('skip-import');
        $limit = max(1, (int) $this->option('limit'));

        if ($dryRun) {
            $this->warn('Dry-run: nessuna modifica persistita.');
        }

        $this->info('Preparazione MVP PG Spot...');
        $result = $prepare->run($dryRun, $skipImport, $limit);

        $this->newLine();
        $this->line('<fg=cyan>Legal</>');
        $this->line('  Compilati: '.implode(', ', $result['legal']['filled'] ?: ['—']));
        $this->line('  Già presenti: '.implode(', ', $result['legal']['skipped'] ?: ['—']));

        $this->newLine();
        $this->line('<fg=cyan>Cleanup POI</>');
        foreach ($result['cleanup']['archived'] as $row) {
            $this->line("  Archiviato: {$row}");
        }
        foreach ($result['cleanup']['renamed'] as $row) {
            $this->line("  Slug: {$row}");
        }
        if ($result['cleanup']['archived'] === [] && $result['cleanup']['renamed'] === []) {
            $this->line('  Nessuna azione');
        }

        $this->newLine();
        $this->line('<fg=cyan>Eventi</>');
        $this->line($result['events']['refreshed']
            ? '  Evento welcome aggiornato/creato: '.($result['events']['title'] ?? '')
            : '  Evento welcome già valido: '.($result['events']['title'] ?? '—'));

        $this->newLine();
        $this->line('<fg=cyan>OpenStreetMap</>');
        if ($result['osm'] === null) {
            $this->line('  Saltato (--skip-import)');
        } elseif (! empty($result['osm']['error'])) {
            $this->error('  Import fallito: '.$result['osm']['error']);
            $this->comment('  Legal/cleanup/eventi sono comunque applicati. Riprova: php artisan pgspot:import-osm');
        } else {
            $osm = $result['osm'];
            $this->line("  Creati: {$osm['created']} | Aggiornati: {$osm['updated']} | Skip: {$osm['skipped']} | Totale processati: {$osm['total']}");
            $this->comment('  Attribuzione: © OpenStreetMap contributors (ODbL)');
            $this->comment('  I POI importati hanno attributes.needs_photo = true');
        }

        $this->newLine();
        $osmFailed = is_array($result['osm'] ?? null) && ! empty($result['osm']['error']);
        if ($dryRun) {
            $this->info('Dry-run completato. Riesegui senza --dry-run sulla VPS dopo il deploy.');
        } elseif ($osmFailed) {
            $this->warn('MVP parziale: legal/cleanup ok, OSM da ritentare. Poi carica le foto.');
        } else {
            $this->info('MVP preparato. Manca solo caricare le foto sui POI (admin → Luoghi).');
        }

        return $osmFailed ? self::FAILURE : self::SUCCESS;
    }
}
