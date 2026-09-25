<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'is_active',
        'is_filterable',
        'is_indexable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_filterable' => 'boolean',
            'is_indexable' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function pois(): BelongsToMany
    {
        return $this->belongsToMany(Poi::class);
    }
}
