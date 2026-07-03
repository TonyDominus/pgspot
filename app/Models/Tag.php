<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['slug', 'name'];

    public function pois(): BelongsToMany
    {
        return $this->belongsToMany(Poi::class);
    }
}
