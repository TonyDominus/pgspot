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
        'status',
        'price',
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
            'opening_hours' => 'array',
            'attributes' => 'array',
            'approved_at' => 'datetime',
            'rating' => 'decimal:2',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PoiPhoto::class)->orderBy('sort_order');
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

    public function scopePublished($query)
    {
        return $query->where('status', PoiStatus::Published);
    }
}
