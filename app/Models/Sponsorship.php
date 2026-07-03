<?php

namespace App\Models;

use App\Enums\SponsorshipPlacement;
use App\Enums\SponsorshipType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sponsorship extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'partner_name',
        'amount_paid',
        'starts_at',
        'ends_at',
        'is_active',
        'poi_id',
        'external_url',
        'image',
        'placement',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => SponsorshipType::class,
            'placement' => SponsorshipPlacement::class,
            'amount_paid' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }
}
