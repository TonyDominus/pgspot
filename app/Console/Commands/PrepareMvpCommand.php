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
        } else {
            $osm = $result['osm'];
            $this->line("  Creati: {$osm['created']} | Aggiornati: {$osm['updated']} | Skip: {$osm['skipped']} | Totale processati: {$osm['total']}");
            $this->comment('  Attribuzione: © OpenStreetMap contributors (ODbL)');
            $this->comment('  I POI importati hanno attributes.needs_photo = true');
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry-run completato. Riesegui senza --dry-run sulla VPS dopo il deploy.'
            : 'MVP preparato. Manca solo caricare le foto sui POI (admin → Luoghi).');

        return self::SUCCESS;
    }
}
