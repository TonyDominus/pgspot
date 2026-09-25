<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tags/Index', [
            'tags' => Tag::query()->withCount('pois')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tags/Form', ['tag' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tag::query()->create($this->validated($request));

        return redirect()->route('admin.tags.index')->with('success', 'Caratteristica creata.');
    }

    public function edit(Tag $tag): Response
    {
        $tag->loadCount('pois');

        return Inertia::render('Admin/Tags/Form', ['tag' => $tag]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($this->validated($request, $tag));

        return redirect()->route('admin.tags.index')->with('success', 'Caratteristica aggiornata.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Tag $tag = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug'.($tag ? ','.$tag->id : ''),
            'is_active' => 'boolean',
            'is_filterable' => 'boolean',
            'is_indexable' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $validated['slug'] = Str::slug(($validated['slug'] ?? '') ?: $validated['name']) ?: 'caratteristica';
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_filterable'] = $request->boolean('is_filterable');
        $validated['is_indexable'] = $request->boolean('is_indexable');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }
}
