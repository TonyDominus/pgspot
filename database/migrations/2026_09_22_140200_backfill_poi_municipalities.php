<?php

use App\Services\TerritoryBackfillService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $backfill = app(TerritoryBackfillService::class);
        $backfill->ensureCoreTerritory();
        $result = $backfill->assignOrphanPoisToPerugia();
        $backfill->ensureDefaultSettings();

        if ($result['skipped'] !== []) {
            Log::warning('Backfill territoriale: POI non assegnati al Comune di Perugia', $result['skipped']);
        }
    }

    public function down(): void
    {
        // Non si azzera municipality_id: un rollback cancellerebbe anche assegnazioni fatte dopo il backfill.
    }
};
