<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\OsmImportService;
use Illuminate\Console\Command;

class ImportOsmCommand extends Command
{
    protected $signature = 'pgspot:import-osm
                            {--dry-run : Elenca senza salvare}
                            {--limit=250 : Max elementi}
                            {--south= : BBox south}
                            {--west= : BBox west}
                            {--north= : BBox north}
                            {--east= : BBox east}';

    protected $description = 'Importa bagni, fontanelle e parcheggi da OpenStreetMap (Overpass)';

    public function handle(OsmImportService $osm): int
    {
        $center = AppSetting::getValue('app.default_center', [
            'lat' => 43.1107,
            'lng' => 12.3908,
        ]);
        $lat = (float) ($center['lat'] ?? 43.1107);
        $lng = (float) ($center['lng'] ?? 12.3908);
        $delta = 0.055;

        $bbox = [
            'south' => (float) ($this->option('south') ?: $lat - $delta),
            'west' => (float) ($this->option('west') ?: $lng - $delta),
            'north' => (float) ($this->option('north') ?: $lat + $delta),
            'east' => (float) ($this->option('east') ?: $lng + $delta),
        ];

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $actor = User::query()->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])->first();

        $this->info(sprintf(
            'Overpass bbox [%.4f,%.4f – %.4f,%.4f]%s',
            $bbox['south'],
            $bbox['west'],
            $bbox['north'],
            $bbox['east'],
            $dryRun ? ' (dry-run)' : '',
        ));

        try {
            $result = $osm->import($bbox, $dryRun, $limit, $actor);
        } catch (\Throwable $e) {
            $this->error('Import fallito: '.$e->getMessage());
            if (str_contains($e->getMessage(), 'SSL certificate')) {
                $this->comment('Su WAMP: imposta OVERPASS_VERIFY_SSL=false nel .env locale, oppure esegui il comando sulla VPS.');
            }

            return self::FAILURE;
        }

        $this->table(
            ['Azione', 'Nome', 'OSM'],
            collect($result['items'])->take(30)->map(fn ($i) => [$i['action'], $i['name'], $i['osm_id']])->all(),
        );

        if ($result['total'] > 30) {
            $this->line('… e altri '.($result['total'] - 30).' elementi');
        }

        $this->info("Creati: {$result['created']} | Aggiornati: {$result['updated']} | Skip: {$result['skipped']}");
        $this->comment('Dati © OpenStreetMap contributors — licenza ODbL. Aggiungi foto proprie prima di promuovere marketing.');

        return self::SUCCESS;
    }
}
