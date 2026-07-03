<?php

namespace App\Services;

use App\Models\Sponsorship;
use Illuminate\Support\Collection;

class SponsorshipService
{
    public function activeForPlacement(string $placement): Collection
    {
        return Sponsorship::query()
            ->currentlyActive()
            ->where('placement', $placement)
            ->with(['poi.categories:id,slug,name,color,icon'])
            ->orderByDesc('amount_paid')
            ->get();
    }

    public function activeAll(): Collection
    {
        return Sponsorship::query()
            ->currentlyActive()
            ->with(['poi.categories:id,slug,name,color,icon'])
            ->orderByDesc('starts_at')
            ->get();
    }
}
