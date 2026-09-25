<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lo slug è univoco insieme a province_id, non in tutta Italia.
 * Comuni omonimi in province diverse possono convivere senza cambiare schema.
 */
class Municipality extends Model
{
    protected $fillable = [
        'province_id',
        'name',
        'slug',
        'istat_code',
        'latitude',
        'longitude',
        'intro',
        'is_indexable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_indexable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function pois(): HasMany
    {
        return $this->hasMany(Poi::class);
    }
}
