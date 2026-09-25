<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Poi;
use App\Models\Region;
use App\Models\Tag;
use App\Services\ContributionDuplicateService;
use App\Services\PoiPhotoService;
use App\Support\TerritoryDefaults;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function __construct(
        private PoiPhotoService $photos,
        private ContributionDuplicateService $duplicates,
    ) {}

    public function create(Request $request): Response
    {
        $intent = $request->query('intent', 'new_poi');
        $poi = null;
        $slug = $request->string('poi')->toString();
        if ($slug !== '' && in_array($intent, ['report', 'edit', 'photo'], true)) {
            $poi = Poi::query()
                ->published()
                ->where('slug', $slug)
                ->with(['municipality.province', 'tags:id,name', 'primaryCategory:id,name'])
                ->first();
        }

        return Inertia::render('Contribute/Create', [
            'categories' => Category::query()->active()->where('slug', '!=', 'instagram-spot')->get(['id', 'slug', 'name', 'color', 'icon']),
            'tags' => Tag::query()->active()->where('is_filterable', true)->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'territory' => TerritoryDefaults::get(),
            'mapCenter' => AppSetting::getValue('app.default_center', ['lat' => 43.1107, 'lng' => 12.3908, 'zoom' => 14]),
            'reportPoi' => ($poi && $intent === 'report') ? $poi : null,
            'targetPoi' => $poi ? [
                'id' => $poi->id,
                'name' => $poi->name,
                'slug' => $poi->slug,
                'description' => $poi->description,
                'address' => $poi->address,
                'latitude' => $poi->latitude,
                'longitude' => $poi->longitude,
                'primary_category_id' => $poi->primary_category_id,
                'municipality_id' => $poi->municipality_id,
                'region_id' => $poi->municipality?->province?->region_id,
                'province_id' => $poi->municipality?->province_id,
                'is_free' => $poi->is_free,
                'accessibility' => $poi->accessibility,
                'parking' => $poi->parking,
                'tag_ids' => $poi->tags->pluck('id'),
            ] : null,
            'intent' => $poi ? $intent : 'new_poi',
        ]);
    }

    public function duplicates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'municipality_id' => 'nullable|integer',
        ]);

        $matches = $this->duplicates->find(
            $validated['name'],
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['municipality_id']) ? (int) $validated['municipality_id'] : null,
        );

        return response()->json([
            'pois' => $matches->map(fn (Poi $poi) => [
                'id' => $poi->id,
                'name' => $poi->name,
                'slug' => $poi->slug,
            ])->values(),
        ]);
    }

    public function mine(Request $request): Response
    {
        $items = $request->user()
            ->contributions()
            ->with('poi:id,name,slug')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Contribution $contribution) => [
                'id' => $contribution->id,
                'type' => $contribution->type->value,
                'status' => $contribution->status->value,
                'spot' => $contribution->payload['name'] ?? $contribution->poi?->name,
                'slug' => $contribution->poi?->slug,
                'created_at' => $contribution->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Contribute/Mine', [
            'contributions' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->contributions()->where('created_at', '>=', now()->subDay())->count() >= 20) {
            return back()->withErrors(['name' => 'Hai inviato troppi contributi oggi. Riprova domani.'])->withInput();
        }

        $type = $request->input('type', 'new_poi');

        $validated = $request->validate([
            'name' => [Rule::requiredIf($type === 'new_poi'), 'nullable', 'string', 'max:255'],
            'category_id' => [
                Rule::requiredIf($type === 'new_poi'),
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('is_active', true)->where('slug', '!=', 'instagram-spot')),
            ],
            'poi_id' => [Rule::requiredIf(in_array($type, ['edit', 'photo', 'report'], true) && $request->filled('poi_id') === false && $type !== 'report'), 'nullable', 'integer', 'exists:pois,id'],
            'description' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:255',
            'latitude' => [Rule::requiredIf($type === 'new_poi'), 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [Rule::requiredIf($type === 'new_poi'), 'nullable', 'numeric', 'between:-180,180'],
            'notes' => [Rule::requiredIf($request->input('reason') === 'other'), 'nullable', 'string', 'max:1000'],
            'reason' => 'nullable|in:wrong_info,closed,wrong_position,duplicate,inappropriate,other',
            'duplicate_poi_id' => 'nullable|integer|exists:pois,id',
            'type' => 'nullable|in:new_poi,edit,photo,report',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'extra_photos' => 'nullable|array|max:5',
            'extra_photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'caption' => 'nullable|string|max:255',
            'tag_ids' => 'nullable|array|max:5',
            'tag_ids.*' => 'integer|exists:tags,id',
            'is_free' => 'nullable|in:yes,no,unknown',
            'accessibility' => 'nullable|in:yes,partial,no,unknown',
            'parking' => 'nullable|in:on_site,nearby,paid,none,unknown',
            'changes' => 'nullable|array',
            'possible_duplicate_ids' => 'nullable|array|max:5',
            'possible_duplicate_ids.*' => 'integer',
            'municipality_id' => [
                Rule::requiredIf($type === 'new_poi'),
                'nullable',
                'integer',
                Rule::exists('municipalities', 'id')->where('is_active', true),
            ],
        ], [
            'municipality_id.required' => 'Seleziona il comune del luogo.',
            'notes.required' => 'Aggiungi una nota per la segnalazione.',
        ]);

        if (in_array($type, ['edit', 'photo'], true) && empty($validated['poi_id'])) {
            return back()->withErrors(['poi_id' => 'Manca il luogo da aggiornare.'])->withInput();
        }

        if ($type === 'photo' && ! $request->hasFile('photo')) {
            return back()->withErrors(['photo' => 'Aggiungi una foto.'])->withInput();
        }

        $poiId = $validated['poi_id'] ?? null;
        $payload = collect($validated)->except(['photo', 'extra_photos', 'poi_id'])->all();

        if ($type === 'new_poi') {
            $payload['is_free'] = match ($validated['is_free'] ?? 'unknown') {
                'yes' => true,
                'no' => false,
                default => null,
            };
            $matches = $this->duplicates->find(
                (string) $validated['name'],
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (int) $validated['municipality_id'],
            );
            if ($matches->isNotEmpty()) {
                $payload['possible_duplicate_ids'] = $matches->pluck('id')->all();
            }
        }

        if ($type === 'report') {
            unset($payload['municipality_id']);
        }

        if ($request->hasFile('photo')) {
            $payload['photo_path'] = $this->photos->storePendingContribution($request->file('photo'));
        }

        if ($request->hasFile('extra_photos')) {
            $payload['extra_photo_paths'] = collect($request->file('extra_photos'))
                ->map(fn ($file) => $this->photos->storePendingContribution($file))
                ->all();
        }

        Contribution::query()->create([
            'user_id' => $request->user()->id,
            'poi_id' => in_array($type, ['report', 'edit', 'photo'], true) ? $poiId : null,
            'type' => ContributionType::from($type),
            'status' => ContributionStatus::Pending,
            'payload' => $payload,
        ]);

        return redirect()->route('contribute.mine')->with('success', 'Grazie! Controlleremo lo spot prima di pubblicarlo.');
    }
}
