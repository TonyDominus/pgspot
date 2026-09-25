<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()->withCount('pois')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::query()->create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'Categoria creata.');
    }

    public function edit(Category $category): Response
    {
        $category->loadCount('pois');

        return Inertia::render('Admin/Categories/Form', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'Categoria aggiornata.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug'.($category ? ','.$category->id : ''),
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
            'is_indexable' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $validated['slug'] = Str::slug(($validated['slug'] ?? '') ?: $validated['name']) ?: 'categoria';
        $validated['icon'] = $validated['icon'] ?? null;
        $validated['color'] = ($validated['color'] ?? null) ?: '#2E7D32';
        $validated['description'] = $validated['description'] ?? null;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_indexable'] = $request->boolean('is_indexable');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }
}
