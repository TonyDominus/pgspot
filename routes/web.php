<?php

use App\Http\Controllers\Admin\ContributionController as AdminContributionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PoiController as AdminPoiController;
use App\Http\Controllers\Admin\SponsorshipController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PoiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Support\AuthRedirect;
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
Route::get('/itinerari', [ItineraryController::class, 'index'])->name('routes');
Route::get('/itinerari/{slug}', [ItineraryController::class, 'show'])->name('itineraries.show');

Route::get('/legal/{page}', [PageController::class, 'legal'])->name('legal.show');

Route::get('/dashboard', function () {
    $user = auth()->user();

    return AuthRedirect::intended($user);
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pois', [AdminPoiController::class, 'index'])->name('pois.index');
    Route::get('/pois/{poi}/edit', [AdminPoiController::class, 'edit'])->name('pois.edit');
    Route::put('/pois/{poi}', [AdminPoiController::class, 'update'])->name('pois.update');
    Route::delete('/pois/{poi}', [AdminPoiController::class, 'destroy'])->name('pois.destroy');
    Route::post('/pois/{poi}/photos', [AdminPoiController::class, 'storePhoto'])->name('pois.photos.store');
    Route::delete('/pois/{poi}/photos/{photo}', [AdminPoiController::class, 'destroyPhoto'])->name('pois.photos.destroy');
    Route::post('/pois/{poi}/photos/{photo}/primary', [AdminPoiController::class, 'setPrimaryPhoto'])->name('pois.photos.primary');
    Route::get('/contributions', [AdminContributionController::class, 'index'])->name('contributions.index');
    Route::post('/contributions/{contribution}/approve', [AdminContributionController::class, 'approve'])->name('contributions.approve');
    Route::post('/contributions/{contribution}/reject', [AdminContributionController::class, 'reject'])->name('contributions.reject');
    Route::resource('sponsorships', SponsorshipController::class)->except(['show']);
});

Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
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
