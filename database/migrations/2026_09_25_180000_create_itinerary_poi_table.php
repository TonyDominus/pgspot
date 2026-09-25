<?php

use App\Enums\ItineraryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_poi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poi_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['itinerary_id', 'poi_id']);
            $table->index(['itinerary_id', 'position']);
        });

        Schema::table('itineraries', function (Blueprint $table) {
            $table->string('excerpt')->nullable()->after('description');
            $table->string('cover_path')->nullable()->after('excerpt');
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable()->after('duration');
            $table->decimal('estimated_distance_km', 6, 2)->nullable()->after('estimated_duration_minutes');
            $table->string('difficulty', 16)->nullable()->after('estimated_distance_km');
            $table->foreignId('municipality_id')->nullable()->after('difficulty')->constrained()->nullOnDelete();
            $table->string('status', 16)->default(ItineraryStatus::Draft->value)->after('municipality_id');
        });

        $poiIds = DB::table('pois')->pluck('id')->flip();
        $missing = [];
        $now = now();

        foreach (DB::table('itineraries')->orderBy('id')->get() as $row) {
            $ids = is_array($row->poi_ids) ? $row->poi_ids : json_decode((string) $row->poi_ids, true);
            $ids = is_array($ids) ? $ids : [];

            DB::table('itineraries')->where('id', $row->id)->update([
                'status' => $row->is_published ? ItineraryStatus::Published->value : ItineraryStatus::Draft->value,
                'estimated_duration_minutes' => $this->minutesFromDuration($row->duration ?? null),
            ]);

            $position = 1;
            $seen = [];
            foreach ($ids as $poiId) {
                $poiId = (int) $poiId;
                if ($poiId < 1 || isset($seen[$poiId])) {
                    continue;
                }
                if (! isset($poiIds[$poiId])) {
                    $missing[] = ['itinerary_id' => $row->id, 'poi_id' => $poiId];

                    continue;
                }
                DB::table('itinerary_poi')->insert([
                    'itinerary_id' => $row->id,
                    'poi_id' => $poiId,
                    'position' => $position,
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $seen[$poiId] = true;
                $position++;
            }
        }

        if ($missing !== []) {
            logger()->warning('itinerary_poi backfill skipped missing POIs', $missing);
        }
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('municipality_id');
            $table->dropColumn([
                'excerpt',
                'cover_path',
                'estimated_duration_minutes',
                'estimated_distance_km',
                'difficulty',
                'status',
            ]);
        });
        Schema::dropIfExists('itinerary_poi');
    }

    private function minutesFromDuration(?string $duration): ?int
    {
        if (! $duration) {
            return null;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*h/i', $duration, $match)) {
            return (int) round((float) str_replace(',', '.', $match[1]) * 60);
        }
        if (preg_match('/(\d+)\s*m/i', $duration, $match)) {
            return (int) $match[1];
        }

        return null;
    }
};
