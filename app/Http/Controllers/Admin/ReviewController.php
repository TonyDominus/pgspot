<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $reviews = Review::query()
            ->with(['user:id,name,email', 'poi:id,name,slug'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($builder) use ($term) {
                    $builder
                        ->where('comment', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('poi', fn ($poi) => $poi->where('name', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only('q'),
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $poi = $review->poi;
        $review->delete();

        if ($poi) {
            $this->syncPoiRating($poi);
        }

        return back()->with('success', 'Recensione eliminata.');
    }

    private function syncPoiRating(Poi $poi): void
    {
        $poi->update([
            'rating' => round((float) $poi->reviews()->avg('rating'), 2) ?: 0,
            'review_count' => $poi->reviews()->count(),
        ]);
    }
}
