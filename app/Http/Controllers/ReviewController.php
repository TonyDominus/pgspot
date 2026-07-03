<?php

namespace App\Http\Controllers;

use App\Models\Poi;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $poi = Poi::query()->published()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'poi_id' => $poi->id],
            $validated,
        );

        $this->syncPoiRating($poi);

        return back()->with('success', 'Recensione salvata!');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $poi = Poi::query()->published()->where('slug', $slug)->firstOrFail();

        Review::query()
            ->where('user_id', $request->user()->id)
            ->where('poi_id', $poi->id)
            ->delete();

        $this->syncPoiRating($poi);

        return back()->with('success', 'Recensione rimossa.');
    }

    private function syncPoiRating(Poi $poi): void
    {
        $poi->update([
            'rating' => round((float) Review::query()->where('poi_id', $poi->id)->avg('rating'), 2) ?: 0,
            'review_count' => Review::query()->where('poi_id', $poi->id)->count(),
        ]);
    }
}
