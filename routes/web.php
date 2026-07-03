<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SponsorshipController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PoiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/lista', [PoiController::class, 'index'])->name('poi.index');
Route::get('/luoghi/{slug}', [PoiController::class, 'show'])->name('poi.show');

Route::middleware('auth')->group(function () {
    Route::post('/luoghi/{slug}/recensioni', [ReviewController::class, 'store'])->name('poi.reviews.store');
    Route::delete('/luoghi/{slug}/recensioni', [ReviewController::class, 'destroy'])->name('poi.reviews.destroy');
    Route::post('/luoghi/{slug}/preferiti', [FavoriteController::class, 'store'])->name('poi.favorites.store');
    Route::delete('/luoghi/{slug}/preferiti', [FavoriteController::class, 'destroy'])->name('poi.favorites.destroy');
});

Route::get('/filtri', [HomeController::class, 'filters'])->name('filters');
Route::get('/itinerari', [HomeController::class, 'routes'])->name('routes');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('sponsorships', SponsorshipController::class)->except(['show']);
});

Route::middleware('auth')->group(function () {
    Route::get('/preferiti', [HomeController::class, 'favorites'])->name('favorites');
    Route::get('/contribuisci', [ContributionController::class, 'create'])->name('contribute.create');
    Route::post('/contribuisci', [ContributionController::class, 'store'])->name('contribute.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
