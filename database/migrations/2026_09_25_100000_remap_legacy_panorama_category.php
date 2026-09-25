<?php

use App\Services\LegacyCategoryRemap;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LegacyCategoryRemap::class)->remapPanorama();
    }

    public function down(): void
    {
        // Il pivot legacy non viene ricostruito: la categoria `panorama` resta nel database.
    }
};
