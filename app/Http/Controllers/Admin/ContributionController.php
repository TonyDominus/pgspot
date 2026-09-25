<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Enums\PoiStatus;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Tag;
use App\Notifications\ContributionApprovedNotification;
use App\Notifications\ContributionRejectedNotification;
use App\Services\PoiPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function __construct(private PoiPhotoService $photos) {}

    public function index(Request $request): Response
    {
        $contributions = Contribution::query()
            ->with(['user:id,name,email,is_trusted_contributor', 'poi:id,name,slug'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('user'), function ($q) use ($request) {
                $term = '%'.$request->string('user').'%';
                $q->whereHas('user', fn ($user) => $user->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('municipality'), function ($q) use ($request) {
                $ids = Municipality::query()->where('name', 'like', '%'.$request->string('municipality').'%')->pluck('id');
                $q->where(function ($inner) use ($ids) {
                    foreach ($ids as $id) {
                        $inner->orWhere('payload', 'like', '%"municipality_id":'.$id.'%');
                    }
                    if ($ids->isEmpty()) {
                        $inner->whereRaw('1 = 0');
                    }
                });
            })
            ->when($request->boolean('duplicates'), fn ($q) => $q->where('payload', 'like', '%possible_duplicate_ids%'))
            ->orderBy('created_at', $request->string('status')->toString() === 'pending' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $municipalityIds = $contributions->getCollection()
            ->map(fn (Contribution $contribution) => $contribution->payload['municipality_id'] ?? null)
            ->filter()
            ->unique()
            ->values();
        $municipalityNames = Municipality::query()->whereIn('id', $municipalityIds)->pluck('name', 'id');

        $contributions->getCollection()->transform(function (Contribution $contribution) use ($municipalityNames) {
            $payload = $contribution->payload ?? [];
            $contribution->photo_preview_url = PoiPhotoService::publicUrl($payload['photo_path'] ?? $payload['photo_paths'][0] ?? null);
            $contribution->has_duplicates = ! empty($payload['possible_duplicate_ids']);
            $municipalityId = $payload['municipality_id'] ?? null;
            $contribution->municipality_name = $municipalityId ? ($municipalityNames[$municipalityId] ?? null) : null;

            return $contribution;
        });

        return Inertia::render('Admin/Contributions/Index', [
            'contributions' => $contributions,
            'filters' => $request->only(['status', 'type', 'user', 'duplicates', 'from', 'to', 'municipality']),
            'pendingCount' => Contribution::query()->pending()->count(),
        ]);
    }

    public function approve(Request $request, Contribution $contribution): RedirectResponse
    {
        if ($contribution->status !== ContributionStatus::Pending) {
            return back()->with('error', 'Contributo già elaborato.');
        }

        $payload = $contribution->payload;
        $poi = null;

        if ($contribution->type === ContributionType::NewPoi) {
            $poi = $this->approveNewPoi($request, $contribution, $payload);
            if (! $poi instanceof Poi) {
                return $poi;
            }
        }

        if ($contribution->type === ContributionType::Edit) {
            $poi = $this->approveEdit($contribution, $payload);
        }

        if ($contribution->type === ContributionType::Photo) {
            $poi = $this->approvePhoto($contribution, $payload);
        }

        $contribution->update([
            'status' => ContributionStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $contribution->load('user');
        if ($poi && $poi->status === PoiStatus::Published && $contribution->user?->wantsContributionNotifications()) {
            $contribution->user->notify(new ContributionApprovedNotification($poi));
        }

        if ($contribution->type === ContributionType::NewPoi && $poi) {
            return redirect()
                ->route('admin.pois.edit', $poi)
                ->with('success', 'Bozza creata. Controlla i dati e pubblicala quando è pronta.');
        }

        return back()->with('success', 'Contributo approvato.');
    }

    public function reject(Request $request, Contribution $contribution): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $payload = $contribution->payload ?? [];
        foreach (array_filter([$payload['photo_path'] ?? null, ...($payload['extra_photo_paths'] ?? []), ...($payload['photo_paths'] ?? [])]) as $path) {
            Storage::disk('public')->delete($path);
        }

        $poiName = $payload['name'] ?? 'Proposta';

        $contribution->update([
            'status' => ContributionStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $contribution->load('user');
        if ($contribution->user?->wantsContributionNotifications()) {
            $contribution->user->notify(new ContributionRejectedNotification(
                $poiName,
                $request->rejection_reason,
            ));
        }

        return back()->with('success', 'Contributo rifiutato.');
    }

    private function approveNewPoi(Request $request, Contribution $contribution, array $payload): Poi|RedirectResponse
    {
        $municipalityId = $request->input('municipality_id', $payload['municipality_id'] ?? null);
        if (! $municipalityId || ! Municipality::query()->whereKey($municipalityId)->where('is_active', true)->exists()) {
            return back()->with('error', 'Impossibile approvare: manca un comune valido.');
        }

        $categoryId = (int) $request->input('category_id', $payload['category_id'] ?? 0);
        $name = (string) $request->input('name', $payload['name'] ?? '');
        if ($name === '' || $categoryId === 0) {
            return back()->with('error', 'Impossibile approvare: mancano nome o categoria.');
        }

        $poi = Poi::query()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => $request->input('description', $payload['description'] ?? null),
            'latitude' => $request->input('latitude', $payload['latitude']),
            'longitude' => $request->input('longitude', $payload['longitude']),
            'municipality_id' => $municipalityId,
            'is_free' => array_key_exists('is_free', $payload) ? $payload['is_free'] : null,
            'accessibility' => $payload['accessibility'] ?? 'unknown',
            'parking' => $payload['parking'] ?? 'unknown',
            'status' => PoiStatus::Draft,
            'source_type' => 'community',
            'created_by' => $contribution->user_id,
            'approved_by' => $request->user()->id,
        ]);

        $poi->syncPlaceCategories($categoryId, []);
        $tagIds = array_values(array_filter((array) ($payload['tag_ids'] ?? [])));
        if ($tagIds !== []) {
            $poi->tags()->sync(Tag::query()->active()->where('is_filterable', true)->whereIn('id', $tagIds)->pluck('id'));
        }

        if (! empty($payload['photo_path'])) {
            $this->photos->attachFromPath($poi, $payload['photo_path'], true, $contribution->user_id);
        }
        foreach ($payload['extra_photo_paths'] ?? [] as $extraPath) {
            $this->photos->attachFromPath($poi, $extraPath, false, $contribution->user_id);
        }

        $contribution->poi_id = $poi->id;

        return $poi;
    }

    private function approveEdit(Contribution $contribution, array $payload): ?Poi
    {
        $poi = $contribution->poi;
        if (! $poi) {
            return null;
        }

        foreach ($payload['changes'] ?? [] as $field => $change) {
            $value = is_array($change) ? ($change['to'] ?? null) : $change;
            if (in_array($field, ['name', 'description', 'address', 'latitude', 'longitude', 'is_free', 'accessibility', 'parking'], true)) {
                $poi->{$field} = $value;
            }
            if ($field === 'category_id' && $value) {
                $secondary = $poi->categories()->pluck('categories.id')->reject(fn ($id) => (int) $id === (int) $poi->primary_category_id)->all();
                $poi->syncPlaceCategories((int) $value, $secondary);
            }
            if ($field === 'tag_ids') {
                $poi->tags()->sync(array_values(array_filter((array) $value)));
            }
        }

        $poi->save();

        return $poi;
    }

    private function approvePhoto(Contribution $contribution, array $payload): ?Poi
    {
        $poi = $contribution->poi;
        if (! $poi) {
            return null;
        }

        $paths = array_filter([$payload['photo_path'] ?? null, ...($payload['photo_paths'] ?? [])]);
        foreach ($paths as $path) {
            $this->photos->attachFromPath($poi, $path, false, $contribution->user_id, false, $payload['caption'] ?? null);
        }

        return $poi;
    }
}
