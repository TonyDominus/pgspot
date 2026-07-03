<?php

namespace App\Http\Controllers;

use App\Models\Poi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $poi = Poi::query()->published()->where('slug', $slug)->firstOrFail();
        $request->user()->favoritePois()->syncWithoutDetaching([$poi->id]);

        return back();
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $poi = Poi::query()->published()->where('slug', $slug)->firstOrFail();
        $request->user()->favoritePois()->detach($poi->id);

        return back();
    }
}
