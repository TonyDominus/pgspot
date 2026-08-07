<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\PoiStatus;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Poi;
use App\Models\User;
use App\Support\LegalDefaults;

class MvpPrepareService
{
    public function __construct(private OsmImportService $osm) {}

    /**
     * @return array{
     *   legal: array{filled: list<string>, skipped: list<string>},
     *   cleanup: array{archived: list<string>, renamed: list<string>},
     *   events: array{refreshed: bool, title: ?string},
     *   osm: ?array{created: int, updated: int, skipped: int, total: int}
     * }
     */
    public function run(bool $dryRun = false, bool $skipImport = false, int $osmLimit = 250): array
    {
        return [
            'legal' => $this->ensureLegal($dryRun),
            'cleanup' => $this->cleanupJunkPois($dryRun),
            'events' => $this->ensureWelcomeEvent($dryRun),
            'osm' => $skipImport ? null : $this->importOsm($dryRun, $osmLimit),
        ];
    }

    /** @return array{filled: list<string>, skipped: list<string>} */
    public function ensureLegal(bool $dryRun = false): array
    {
        $map = [
            'legal.privacy' => LegalDefaults::body('privacy'),
            'legal.terms' => LegalDefaults::body('termini'),
            'legal.cookies' => LegalDefaults::body('cookie'),
            'legal.contact' => LegalDefaults::body('contatti'),
        ];

        $filled = [];
        $skipped = [];

        foreach ($map as $key => $body) {
            $raw = AppSetting::getValue($key);
            $current = is_array($raw) ? trim((string) ($raw['body'] ?? '')) : trim((string) ($raw ?? ''));

            if ($current !== '') {
                $skipped[] = $key;

                continue;
            }

            $filled[] = $key;
            if (! $dryRun) {
                AppSetting::setValue($key, ['body' => $body]);
            }
        }

        return compact('filled', 'skipped');
    }

    /** @return array{archived: list<string>, renamed: list<string>} */
    public function cleanupJunkPois(bool $dryRun = false): array
    {
        $archived = [];
        $renamed = [];

        $junk = Poi::query()
            ->where(function ($q) {
                $q->where('name', 'like', 'Casa mia%')
                    ->orWhere('slug', 'like', 'casa-mia%')
                    ->orWhere('name', 'like', '%test%')
                    ->orWhere('name', 'like', '%prova%');
            })
            ->where('status', '!=', PoiStatus::Archived)
            ->get();

        foreach ($junk as $poi) {
            $archived[] = "{$poi->name} ({$poi->slug})";
            if (! $dryRun) {
                $poi->update(['status' => PoiStatus::Archived]);
            }
        }

        $typo = Poi::query()->where('slug', 'scalianta-ercolano')->first();
        if ($typo) {
            $newSlug = 'scalinata-ercolano';
            if (! Poi::query()->where('slug', $newSlug)->where('id', '!=', $typo->id)->exists()) {
                $renamed[] = "{$typo->slug} -> {$newSlug}";
                if (! $dryRun) {
                    $typo->update([
                        'slug' => $newSlug,
                        'name' => "Scalinata di Sant'Ercolano",
                    ]);
                }
            }
        }

        return compact('archived', 'renamed');
    }

    /** @return array{refreshed: bool, title: ?string} */
    public function ensureWelcomeEvent(bool $dryRun = false): array
    {
        $event = Event::query()->where('slug', 'benvenuto-pgspot')->first();

        if (! $event) {
            if ($dryRun) {
                return ['refreshed' => true, 'title' => 'Benvenuto su PG Spot'];
            }

            $actor = User::query()->where('role', UserRole::SuperAdmin)->first();
            Event::query()->create([
                'title' => 'Benvenuto su PG Spot',
                'slug' => 'benvenuto-pgspot',
                'description' => 'Scopri panorami, servizi e itinerari di Perugia. Registrati per contribuire alla mappa!',
                'starts_at' => now(),
                'ends_at' => now()->addMonths(6),
                'is_featured' => true,
                'status' => EventStatus::Published,
                'created_by' => $actor?->id,
            ]);

            return ['refreshed' => true, 'title' => 'Benvenuto su PG Spot'];
        }

        $needsRefresh = $event->status !== EventStatus::Published
            || ($event->ends_at && $event->ends_at->lt(now()))
            || $event->starts_at->lt(now()->subMonths(2));

        if ($needsRefresh && ! $dryRun) {
            $event->update([
                'status' => EventStatus::Published,
                'is_featured' => true,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(6),
                'description' => $event->description ?: 'Scopri panorami, servizi e itinerari di Perugia.',
            ]);
        }

        return [
            'refreshed' => $needsRefresh,
            'title' => $event->title,
        ];
    }

    /** @return array{created: int, updated: int, skipped: int, total: int} */
    public function importOsm(bool $dryRun = false, int $limit = 250): array
    {
        $center = AppSetting::getValue('app.default_center', [
            'lat' => 43.1107,
            'lng' => 12.3908,
            'zoom' => 14,
        ]);

        $lat = (float) ($center['lat'] ?? 43.1107);
        $lng = (float) ($center['lng'] ?? 12.3908);
        // ~6–7 km box around Perugia centro
        $delta = 0.055;

        $actor = User::query()->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])->first();

        $result = $this->osm->import(
            [
                'south' => $lat - $delta,
                'west' => $lng - $delta,
                'north' => $lat + $delta,
                'east' => $lng + $delta,
            ],
            $dryRun,
            $limit,
            $actor,
        );

        return [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
        ];
    }
}
