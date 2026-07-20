<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Poi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->with('poi:id,name')
            ->orderByDesc('starts_at')
            ->paginate(20);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Form', [
            'event' => null,
            'pois' => Poi::query()->published()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(EventStatus::cases())->map(fn ($s) => $s->value),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Event::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['title']).'-'.Str::random(4),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.events.index')->with('success', 'Evento creato.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Form', [
            'event' => $event,
            'pois' => Poi::query()->published()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(EventStatus::cases())->map(fn ($s) => $s->value),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $event->update($this->validated($request));

        return redirect()->route('admin.events.index')->with('success', 'Evento aggiornato.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Evento eliminato.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'poi_id' => 'nullable|exists:pois,id',
            'external_url' => 'nullable|url|max:500',
            'is_featured' => 'boolean',
            'status' => 'required|in:'.implode(',', array_column(EventStatus::cases(), 'value')),
        ]);
    }
}
