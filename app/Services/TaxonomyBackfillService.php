<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Poi;
use App\Models\Tag;
use Illuminate\Support\Str;

class TaxonomyBackfillService
{
    /** @var array<string, array{slug: string, name: string}> */
    private const TAGS = [
        'vista citta' => ['slug' => 'vista-citta', 'name' => 'Vista città'],
        'vista città' => ['slug' => 'vista-citta', 'name' => 'Vista città'],
        'foto' => ['slug' => 'fotografico', 'name' => 'Fotografico'],
        'fotografico' => ['slug' => 'fotografico', 'name' => 'Fotografico'],
        'tramonto' => ['slug' => 'tramonto', 'name' => 'Tramonto'],
        'romantico' => ['slug' => 'romantico', 'name' => 'Romantico'],
    ];

    /**
     * @return array{ambiguous: list<array{id: int, name: string, slug: string, reason: string}>, unmapped_tags: list<array{id: int, name: string, value: string}>}
     */
    public function run(): array
    {
        $report = ['ambiguous' => [], 'unmapped_tags' => []];
        $instagram = Category::query()->where('slug', 'instagram-spot')->first();

        foreach (Poi::query()->with('categories')->orderBy('id')->get() as $poi) {
            $this->migratePoi($poi, $instagram, $report);
        }

        if ($instagram && ! $instagram->pois()->exists()) {
            $instagram->update(['is_active' => false]);
        }

        return $report;
    }

    /**
     * @param  array{ambiguous: list<array{id: int, name: string, slug: string, reason: string}>, unmapped_tags: list<array{id: int, name: string, value: string}>}  $report
     */
    private function migratePoi(Poi $poi, ?Category $instagram, array &$report): void
    {
        $categories = $poi->categories;
        $real = $categories->filter(fn (Category $category) => $category->slug !== 'instagram-spot')->values();
        $hasInstagram = $instagram && $categories->contains('id', $instagram->id);

        if ($poi->primary_category_id === null && $real->count() === 1) {
            $poi->primary_category_id = $real->first()->id;
        } elseif ($poi->primary_category_id === null && $real->count() === 0 && $hasInstagram) {
            $report['ambiguous'][] = [
                'id' => $poi->id,
                'name' => $poi->name,
                'slug' => $poi->slug,
                'reason' => 'Solo Instagram Spot: categoria primaria non assegnata.',
            ];
        } elseif ($poi->primary_category_id === null && $real->count() > 1) {
            $report['ambiguous'][] = [
                'id' => $poi->id,
                'name' => $poi->name,
                'slug' => $poi->slug,
                'reason' => 'Più categorie di tipo luogo: categoria primaria non assegnata.',
            ];
        }

        $tagSlugs = [];
        if ($hasInstagram) {
            $tagSlugs[] = 'fotografico';
        }

        $attributes = $poi->attributes ?? [];
        $unmapped = array_values(array_filter((array) ($attributes['legacy_unmapped_tags'] ?? [])));

        foreach ((array) ($attributes['tags'] ?? []) as $raw) {
            $label = trim((string) $raw);
            $key = $this->normalize($label);
            if (isset(self::TAGS[$key])) {
                $tagSlugs[] = self::TAGS[$key]['slug'];

                continue;
            }
            if ($key === 'accessibile') {
                if ($poi->accessibility === 'unknown') {
                    $poi->accessibility = 'yes';
                }

                continue;
            }
            if ($key === 'gratuito') {
                continue;
            }
            if (! in_array($label, $unmapped, true)) {
                $unmapped[] = $label;
                $report['unmapped_tags'][] = ['id' => $poi->id, 'name' => $poi->name, 'value' => $label];
            }
        }

        if (($attributes['sunset'] ?? false) === true) {
            $tagSlugs[] = 'tramonto';
        }
        if (($attributes['city_view'] ?? false) === true) {
            $tagSlugs[] = 'vista-citta';
        }
        if (($attributes['accessible'] ?? false) === true && $poi->accessibility === 'unknown') {
            $poi->accessibility = 'yes';
        }
        if (array_key_exists('free', $attributes)) {
            $poi->is_free = (bool) $attributes['free'];
        }

        unset($attributes['tags'], $attributes['sunset'], $attributes['city_view'], $attributes['accessible'], $attributes['free']);
        if ($unmapped !== []) {
            $attributes['legacy_unmapped_tags'] = array_values(array_unique($unmapped));
        }
        $poi->attributes = $attributes === [] ? null : $attributes;

        if ($poi->isDirty()) {
            $poi->save();
        }

        foreach (array_unique($tagSlugs) as $slug) {
            $definition = collect(self::TAGS)->firstWhere('slug', $slug);
            $tag = Tag::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'] ?? Str::headline($slug),
                    'is_active' => true,
                    'is_filterable' => true,
                    'is_indexable' => false,
                    'sort_order' => 0,
                ],
            );
            $poi->tags()->syncWithoutDetaching([$tag->id]);
        }

        if ($hasInstagram && $real->isNotEmpty()) {
            $poi->categories()->detach($instagram->id);
        }
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
