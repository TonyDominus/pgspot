<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'poi_id',
        'latitude',
        'longitude',
        'image',
        'external_url',
        'is_featured',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_featured' => 'boolean',
            'status' => EventStatus::class,
        ];
    }

    public function poi(): BelongsTo
    {
        return $this->belongsTo(Poi::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', EventStatus::Published);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
