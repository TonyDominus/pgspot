<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'duration',
        'poi_ids',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'poi_ids' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }
}
