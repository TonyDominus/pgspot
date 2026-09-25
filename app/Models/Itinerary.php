<?php

namespace App\Models;

use App\Enums\ItineraryStatus;
use App\Enums\PoiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Itinerary extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'excerpt',
        'duration',
        'cover_path',
        'estimated_duration_minutes',
        'estimated_distance_km',
        'difficulty',
        'municipality_id',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ItineraryStatus::class,
            'estimated_duration_minutes' => 'integer',
            'estimated_distance_km' => 'decimal:2',
        ];
    }

    public function pois(): BelongsToMany
    {
        return $this->belongsToMany(Poi::class, 'itinerary_poi')
            ->withPivot(['position', 'note'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', ItineraryStatus::Published)->orderBy('sort_order');
    }

    public function scopePublic($query)
    {
        return $query->published()->whereHas('pois', function ($pois) {
            $pois->where('status', PoiStatus::Published);
        }, '>=', 2);
    }
}
