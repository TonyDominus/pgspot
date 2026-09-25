<?php

namespace App\Models;

use App\Enums\PoiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poi extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'latitude',
        'longitude',
        'address',
        'municipality_id',
        'primary_category_id',
        'status',
        'price',
        'is_free',
        'accessibility',
        'parking',
        'website',
        'phone',
        'source_type',
        'source_ref',
        'last_verified_at',
        'opening_hours',
        'attributes',
        'created_by',
        'approved_by',
        'approved_at',
        'rating',
        'review_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => PoiStatus::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'opening_hours' => 'array',
            'attributes' => 'array',
            'approved_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'rating' => 'decimal:2',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    protected static function booted(): void
    {
        static::saved(function (Poi $poi): void {
            if (! $poi->primary_category_id) {
                return;
            }

            $attached = $poi->categories()->where('categories.id', $poi->primary_category_id)->exists();
            if (! $attached) {
                $poi->categories()->attach($poi->primary_category_id);
            }
        });
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /**
     * @param  list<int|string>  $secondaryIds
     */
    public function syncPlaceCategories(?int $primaryId, array $secondaryIds): void
    {
        $ids = collect($secondaryIds)
            ->map(fn ($id) => (int) $id)
            ->when($primaryId, fn ($ids) => $ids->push($primaryId))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->categories()->sync($ids);
        $this->primary_category_id = $primaryId;
        $this->save();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PoiPhoto::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function getPrimaryPhotoUrlAttribute(): ?string
    {
        if (! $this->relationLoaded('photos')) {
            return null;
        }

        $photo = $this->photos->firstWhere('is_primary', true) ?? $this->photos->first();

        return $photo?->url;
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function itineraries(): BelongsToMany
    {
        return $this->belongsToMany(Itinerary::class, 'itinerary_poi')
            ->withPivot(['position', 'note'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function scopePublished($query)
    {
        return $query->where('status', PoiStatus::Published);
    }
}
