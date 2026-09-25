<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Services\TerritoryBackfillService;
use App\Services\TerritoryImportService;
use Illuminate\Database\Seeder;

class TerritorySeeder extends Seeder
{
    public function run(): void
    {
        app(TerritoryImportService::class)->importFile(
            database_path('data/territories/umbria-core.json'),
        );

        app(TerritoryBackfillService::class)->ensureDefaultSettings();

        if (! Municipality::query()->where('slug', 'perugia')->whereHas('province', fn ($q) => $q->where('code', 'PG'))->exists()) {
            throw new \RuntimeException('Seed territoriale incompleto: manca il Comune di Perugia.');
        }
    }
}
