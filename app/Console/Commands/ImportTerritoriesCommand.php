<?php

namespace App\Console\Commands;

use App\Services\IstatTerritorySyncService;
use App\Services\TerritoryImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTerritoriesCommand extends Command
{
    protected $signature = 'pgspot:import-territories
                            {file? : JSON annidato oppure CSV, non è la fonte canonica}
                            {--istat : Sincronizza il file ufficiale ISTAT/SITUAS}
                            {--source= : Percorso dell\'xlsx ufficiale}
                            {--crosswalk= : CSV di corrispondenza codici, opzionale}
                            {--regioni= : Elenco legacy regioni}
                            {--province= : Elenco legacy province}
                            {--comuni= : Elenco legacy comuni}
                            {--dry-run : Legge e valida senza salvare}';

    protected $description = 'Sincronizza l\'anagrafica. La fonte canonica è --istat. I JSON legacy non sono più l\'anagrafica corrente.';

    public function handle(TerritoryImportService $importer, IstatTerritorySyncService $istat): int
    {
        if ($this->option('istat')) {
            return $this->syncIstat($istat);
        }

        $dryRun = (bool) $this->option('dry-run');
        $regioni = $this->option('regioni');
        $province = $this->option('province');
        $comuni = $this->option('comuni');
        $national = is_string($regioni) || is_string($province) || is_string($comuni);

        if ($national && (! is_string($regioni) || ! is_string($province) || ! is_string($comuni) || $regioni === '' || $province === '' || $comuni === '')) {
            $this->error('Per l\'import legacy servono insieme --regioni, --province e --comuni. La fonte canonica è --istat.');

            return self::FAILURE;
        }

        $file = $this->argument('file');
        if (! $national && (! is_string($file) || $file === '')) {
            $this->error('Usa --istat per la fonte ufficiale, oppure indica un file JSON/CSV.');

            return self::FAILURE;
        }

        try {
            DB::beginTransaction();
            $result = $national
                ? $importer->importItalianFiles($regioni, $province, $comuni)
                : $importer->importFile($file);
            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Entità', 'Creati', 'Aggiornati'],
            [
                ['Regioni', $result['regions_created'], $result['regions_updated']],
                ['Province', $result['provinces_created'], $result['provinces_updated']],
                ['Comuni', $result['municipalities_created'], $result['municipalities_updated']],
            ],
        );

        $anomalies = $result['anomalies'] ?? [];
        $this->line('Anomalie: '.count($anomalies));
        foreach (array_slice($anomalies, 0, 20) as $anomaly) {
            $message = is_array($anomaly)
                ? $anomaly['entity'].' '.$anomaly['id'].': '.$anomaly['message']
                : (string) $anomaly;
            $this->warn($message);
        }
        $this->line('Collisioni slug risolte: '.count($result['slug_collisions'] ?? []));
        $this->comment('I comuni nuovi restano non indicizzabili. intro, is_indexable e slug già presenti non vengono sovrascritti. I POI non vengono modificati.');

        if ($dryRun) {
            $this->warn('Dry-run: nessuna modifica salvata.');
        } else {
            $this->info('Anagrafica territoriale importata.');
        }

        return self::SUCCESS;
    }

    private function syncIstat(IstatTerritorySyncService $istat): int
    {
        $source = $this->option('source');
        $source = is_string($source) && $source !== ''
            ? $source
            : database_path('data/territories/istat/Elenco-comuni-italiani.xlsx');
        $crosswalk = $this->option('crosswalk');
        $crosswalk = is_string($crosswalk) && $crosswalk !== ''
            ? $crosswalk
            : database_path('data/territories/istat/sardegna-2026.csv');
        if (! is_file($crosswalk)) {
            $crosswalk = null;
        }

        $dryRun = (bool) $this->option('dry-run');

        try {
            DB::beginTransaction();
            $result = $istat->syncFromOfficialFile($source, $crosswalk);
            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Entità', 'Creati', 'Aggiornati', 'Disattivati'],
            [
                ['Regioni', $result['regions_created'], $result['regions_updated'], '—'],
                ['Unità sovracomunali', $result['provinces_created'], $result['provinces_updated'], $result['provinces_deactivated']],
                ['Comuni', $result['municipalities_created'], $result['municipalities_updated'], $result['municipalities_deactivated']],
            ],
        );
        $this->line('Codici comunali cambiati: '.$result['codes_changed']);
        $this->line('Comuni trasferiti di unità: '.$result['transferred']);
        $this->line('Anomalie: '.count($result['anomalies']));
        foreach (array_slice($result['anomalies'], 0, 20) as $anomaly) {
            $this->warn($anomaly);
        }
        if ($result['reallocation'] !== []) {
            $this->warn('Territori disattivati ancora referenziati, da riallocare:');
            foreach ($result['reallocation'] as $item) {
                $this->warn($item);
            }
        }
        $this->comment('intro, is_indexable e gli slug esistenti non vengono sovrascritti. I POI non vengono modificati.');

        if ($dryRun) {
            $this->warn('Dry-run: nessuna modifica salvata.');
        } else {
            $this->info('Anagrafica ISTAT sincronizzata.');
        }

        return self::SUCCESS;
    }
}
