<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoiPhoto extends Model
{
    protected $fillable = [
        'poi_id',
        'path',
        'caption',
        'sort_order',
        'uploaded_by',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function poi(): BelongsTo
    {
        return $this->belongsTo(Poi::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
