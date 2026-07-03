<?php

namespace App\Models;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    protected $fillable = [
        'user_id',
        'poi_id',
        'type',
        'payload',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContributionType::class,
            'status' => ContributionStatus::class,
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function poi(): BelongsTo
    {
        return $this->belongsTo(Poi::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', ContributionStatus::Pending);
    }
}
