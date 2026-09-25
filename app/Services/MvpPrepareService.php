<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\PoiStatus;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\User;
use App\Support\LegalDefaults;
use App\Support\MunicipalityResolver;
use App\Support\TerritoryDefaults;

class MvpPrepareService
{
    public function __construct(private OsmImportService $osm) {}

    /**
     * @return array{
     *   legal: array{filled: list<string>, skipped: list<string>},
     *   cleanup: array{archived: list<string>, renamed: list<string>},
     *   events: array{refreshed: bool, title: ?string},
     *   osm: ?array{created: int, updated: int, skipped: int, total: int, error?: string}
     * }
     */
    public function run(bool $dryRun = false, bool $skipImport = false, int $osmLimit = 250, ?string $municipalitySlug = null): array
    {
        return [
            'legal' => $this->ensureLegal($dryRun),
            'cleanup' => $this->cleanupJunkPois($dryRun),
            'events' => $this->ensureWelcomeEvent($dryRun),
            'osm' => $skipImport ? null : $this->importOsm($dryRun, $osmLimit, $municipalitySlug),
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

    /** Archivia l'evento placeholder "Benvenuto" così non occupa la home. */
    public function ensureWelcomeEvent(bool $dryRun = false): array
    {
        $event = Event::query()->where('slug', 'benvenuto-pgspot')->first();

        if (! $event) {
            return ['refreshed' => false, 'title' => null];
        }

        $needsHide = $event->status === EventStatus::Published || $event->is_featured;

        if ($needsHide && ! $dryRun) {
            $event->update([
                'status' => EventStatus::Draft,
                'is_featured' => false,
            ]);
        }

        return [
            'refreshed' => $needsHide,
            'title' => $event->title,
        ];
    }

    /** @return array{created: int, updated: int, skipped: int, total: int, error?: string} */
    public function importOsm(bool $dryRun = false, int $limit = 250, ?string $municipalitySlug = null): array
    {
        $center = AppSetting::getValue('app.default_center', [
            'lat' => 43.1107,
            'lng' => 12.3908,
            'zoom' => 14,
        ]);

        $lat = (float) ($center['lat'] ?? 43.1107);
        $lng = (float) ($center['lng'] ?? 12.3908);
        // Riquadro operativo intorno al centro mappa configurato (focus Umbria/Perugia).
        $delta = 0.055;

        $actor = User::query()->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])->first();

        try {
            $municipality = $this->resolveImportMunicipality($municipalitySlug);
            $result = $this->osm->import(
                [
                    'south' => $lat - $delta,
                    'west' => $lng - $delta,
                    'north' => $lat + $delta,
                    'east' => $lng + $delta,
                ],
                $municipality,
                $dryRun,
                $limit,
                $actor,
            );
        } catch (\Throwable $e) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
        ];
    }

    private function resolveImportMunicipality(?string $slug): Municipality
    {
        if (is_string($slug) && trim($slug) !== '') {
            return MunicipalityResolver::find($slug);
        }

        $id = TerritoryDefaults::get()['municipality_id'];
        $municipality = $id ? Municipality::query()->find($id) : null;
        if ($municipality) {
            return $municipality;
        }

        throw new \InvalidArgumentException('Nessun comune predefinito. Esegui il backfill territoriale oppure passa --municipality=.');
    }
}
