<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TerritoryController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab', 'municipalities')->toString();
        if (! in_array($tab, ['regions', 'provinces', 'municipalities'], true)) {
            $tab = 'municipalities';
        }

        $activeOnly = ! $request->boolean('include_inactive');

        $regions = Region::query()->withCount(['provinces' => fn ($query) => $query->where('is_active', true)])->orderBy('name')->get();
        $provinces = Province::query()
            ->with('region:id,name')
            ->withCount(['municipalities' => fn ($query) => $query->where('is_active', true)])
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        $municipalities = Municipality::query()
            ->with(['province:id,region_id,name,code', 'province.region:id,name'])
            ->withCount('pois')
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('region_id'), function ($q) use ($request) {
                $q->whereHas('province', fn ($province) => $province->where('region_id', $request->integer('region_id')));
            })
            ->when($request->filled('province_id'), fn ($q) => $q->where('province_id', $request->integer('province_id')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Territories/Index', [
            'tab' => $tab,
            'regions' => $regions,
            'provinces' => $provinces,
            'municipalities' => $municipalities,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'region_id' => $request->input('region_id'),
                'province_id' => $request->input('province_id'),
                'include_inactive' => $request->boolean('include_inactive'),
            ],
        ]);
    }

    public function createRegion(): Response
    {
        return Inertia::render('Admin/Territories/RegionForm', ['region' => null]);
    }

    public function storeRegion(Request $request): RedirectResponse
    {
        Region::query()->create($this->validateRegion($request));

        return redirect()->route('admin.territories.index', ['tab' => 'regions'])->with('success', 'Regione creata.');
    }

    public function editRegion(Region $region): Response
    {
        return Inertia::render('Admin/Territories/RegionForm', ['region' => $region]);
    }

    public function updateRegion(Request $request, Region $region): RedirectResponse
    {
        $region->update($this->validateRegion($request, $region));

        return redirect()->route('admin.territories.index', ['tab' => 'regions'])->with('success', 'Regione aggiornata.');
    }

    public function createProvince(): Response
    {
        return Inertia::render('Admin/Territories/ProvinceForm', [
            'province' => null,
            'regions' => $this->regionOptions(),
        ]);
    }

    public function storeProvince(Request $request): RedirectResponse
    {
        Province::query()->create($this->validateProvince($request));

        return redirect()->route('admin.territories.index', ['tab' => 'provinces'])->with('success', 'Provincia creata.');
    }

    public function editProvince(Province $province): Response
    {
        return Inertia::render('Admin/Territories/ProvinceForm', [
            'province' => $province,
            'regions' => $this->regionOptions(),
        ]);
    }

    public function updateProvince(Request $request, Province $province): RedirectResponse
    {
        $province->update($this->validateProvince($request, $province));

        return redirect()->route('admin.territories.index', ['tab' => 'provinces'])->with('success', 'Provincia aggiornata.');
    }

    public function createMunicipality(): Response
    {
        return Inertia::render('Admin/Territories/MunicipalityForm', [
            'municipality' => null,
            'provinces' => $this->provinceOptions(),
        ]);
    }

    public function storeMunicipality(Request $request): RedirectResponse
    {
        Municipality::query()->create($this->validateMunicipality($request));

        return redirect()->route('admin.territories.index', ['tab' => 'municipalities'])->with('success', 'Comune creato.');
    }

    public function editMunicipality(Municipality $municipality): Response
    {
        return Inertia::render('Admin/Territories/MunicipalityForm', [
            'municipality' => $municipality,
            'provinces' => $this->provinceOptions($municipality->province_id),
        ]);
    }

    public function updateMunicipality(Request $request, Municipality $municipality): RedirectResponse
    {
        $municipality->update($this->validateMunicipality($request, $municipality));

        return redirect()->route('admin.territories.index', ['tab' => 'municipalities'])->with('success', 'Comune aggiornato.');
    }

    /** @return array{name: string, slug: string, istat_code: ?string} */
    private function validateRegion(Request $request, ?Region $region = null): array
    {
        $this->normalizeSlug($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('regions', 'slug')->ignore($region?->id)],
            'istat_code' => ['nullable', 'string', 'max:10'],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $this->resolveSlug('regions', $validated['name'], $validated['slug'] ?? null, [], $region?->id),
            'istat_code' => $this->blankToNull($validated['istat_code'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    private function validateProvince(Request $request, ?Province $province = null): array
    {
        $this->normalizeSlug($request);
        $this->blankFieldsToNull($request, ['code', 'istat_code', 'latitude', 'longitude']);
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('provinces', 'slug')->where('region_id', $request->integer('region_id'))->ignore($province?->id),
            ],
            'code' => ['nullable', 'string', 'max:8', Rule::unique('provinces', 'code')->ignore($province?->id)],
            'istat_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        return [
            'region_id' => (int) $validated['region_id'],
            'name' => $validated['name'],
            'slug' => $this->resolveSlug(
                'provinces',
                $validated['name'],
                $validated['slug'] ?? null,
                ['region_id' => (int) $validated['region_id']],
                $province?->id,
            ),
            'code' => $this->blankToNull($validated['code'] ?? null),
            'istat_code' => $this->blankToNull($validated['istat_code'] ?? null),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function validateMunicipality(Request $request, ?Municipality $municipality = null): array
    {
        $this->normalizeSlug($request);
        $this->blankFieldsToNull($request, ['istat_code', 'latitude', 'longitude', 'intro']);

        $validated = $request->validate([
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('municipalities', 'slug')->where('province_id', $request->integer('province_id'))->ignore($municipality?->id),
            ],
            'istat_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'intro' => ['nullable', 'string', 'max:5000'],
            'is_indexable' => ['boolean'],
        ]);

        return [
            'province_id' => (int) $validated['province_id'],
            'name' => $validated['name'],
            'slug' => $this->resolveSlug(
                'municipalities',
                $validated['name'],
                $validated['slug'] ?? null,
                ['province_id' => (int) $validated['province_id']],
                $municipality?->id,
            ),
            'istat_code' => $this->blankToNull($validated['istat_code'] ?? null),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'intro' => $this->blankToNull($validated['intro'] ?? null),
            'is_indexable' => $request->boolean('is_indexable'),
        ];
    }

    private function normalizeSlug(Request $request): void
    {
        $slug = trim((string) $request->input('slug', ''));
        $request->merge([
            'slug' => $slug === '' ? null : (Str::slug($slug) ?: null),
        ]);
    }

    /** @param  list<string>  $fields */
    private function blankFieldsToNull(Request $request, array $fields): void
    {
        $merged = [];
        foreach ($fields as $field) {
            if ($request->input($field) === '' || $request->input($field) === null) {
                $merged[$field] = null;
            }
        }

        if ($merged !== []) {
            $request->merge($merged);
        }
    }

    /** @param  array<string, int|string>  $scope */
    private function resolveSlug(string $table, string $name, ?string $requested, array $scope, ?int $ignoreId): string
    {
        $base = $requested ?: (Str::slug($name) ?: 'territorio');
        if ($requested) {
            return $base;
        }

        $slug = $base;
        $i = 2;
        while ($this->slugExists($table, $slug, $scope, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /** @param  array<string, int|string>  $scope */
    private function slugExists(string $table, string $slug, array $scope, ?int $ignoreId): bool
    {
        return DB::table($table)
            ->where('slug', $slug)
            ->where($scope)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /** @return Collection<int, Region> */
    private function regionOptions()
    {
        return Region::query()->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, Province> */
    private function provinceOptions(?int $includeId = null)
    {
        return Province::query()
            ->with('region:id,name')
            ->where(function ($query) use ($includeId) {
                $query->where('is_active', true);
                if ($includeId) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'region_id', 'name', 'code']);
    }
}
