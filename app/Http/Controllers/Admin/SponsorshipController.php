<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SponsorshipPlacement;
use App\Enums\SponsorshipType;
use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\Sponsorship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SponsorshipController extends Controller
{
    public function index(): Response
    {
        $sponsorships = Sponsorship::query()
            ->with(['poi:id,name,slug', 'creator:id,name'])
            ->orderByDesc('starts_at')
            ->get();

        return Inertia::render('Admin/Sponsorships/Index', [
            'sponsorships' => $sponsorships,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Sponsorships/Form', [
            'sponsorship' => null,
            'pois' => Poi::query()->published()->orderBy('name')->get(['id', 'name', 'slug']),
            'types' => collect(SponsorshipType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'placements' => collect(SponsorshipPlacement::cases())->map(fn ($p) => ['value' => $p->value, 'label' => $p->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSponsorship($request);

        Sponsorship::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.sponsorships.index')->with('success', 'Sponsorizzazione creata.');
    }

    public function edit(Sponsorship $sponsorship): Response
    {
        return Inertia::render('Admin/Sponsorships/Form', [
            'sponsorship' => $sponsorship->load('poi:id,name,slug'),
            'pois' => Poi::query()->published()->orderBy('name')->get(['id', 'name', 'slug']),
            'types' => collect(SponsorshipType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'placements' => collect(SponsorshipPlacement::cases())->map(fn ($p) => ['value' => $p->value, 'label' => $p->label()]),
        ]);
    }

    public function update(Request $request, Sponsorship $sponsorship): RedirectResponse
    {
        $sponsorship->update($this->validateSponsorship($request));

        return redirect()->route('admin.sponsorships.index')->with('success', 'Sponsorizzazione aggiornata.');
    }

    public function destroy(Sponsorship $sponsorship): RedirectResponse
    {
        $sponsorship->delete();

        return redirect()->route('admin.sponsorships.index')->with('success', 'Sponsorizzazione eliminata.');
    }

    private function validateSponsorship(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:'.implode(',', array_column(SponsorshipType::cases(), 'value')),
            'partner_name' => 'required|string|max:255',
            'amount_paid' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'poi_id' => 'nullable|exists:pois,id',
            'external_url' => 'nullable|url|max:500',
            'placement' => 'required|in:'.implode(',', array_column(SponsorshipPlacement::cases(), 'value')),
            'notes' => 'nullable|string|max:2000',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['poi_id'] = $request->input('poi_id') ?: null;

        return $data;
    }
}
