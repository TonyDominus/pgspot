<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ContributionController as AdminContributionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\ItineraryController as AdminItineraryController;
use App\Http\Controllers\Admin\PoiController as AdminPoiController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SponsorshipController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\TerritoryController as AdminTerritoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\MapPoiController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PoiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchSuggestionController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TerritoryLookupController;
use App\Support\AuthRedirect;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search/suggestions', SearchSuggestionController::class)->name('search.suggestions');
Route::get('/map/pois', MapPoiController::class)->name('map.pois');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/lista', [PoiController::class, 'index'])->name('poi.index');
Route::get('/luoghi/{slug}', [PoiController::class, 'show'])->name('poi.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/luoghi/{slug}/recensioni', [ReviewController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('poi.reviews.store');
    Route::delete('/luoghi/{slug}/recensioni', [ReviewController::class, 'destroy'])->name('poi.reviews.destroy');
    Route::post('/luoghi/{slug}/preferiti', [FavoriteController::class, 'store'])->name('poi.favorites.store');
    Route::delete('/luoghi/{slug}/preferiti', [FavoriteController::class, 'destroy'])->name('poi.favorites.destroy');
});

Route::get('/filtri', [HomeController::class, 'filters'])->name('filters');
Route::get('/itinerari', [ItineraryController::class, 'index'])->name('routes');
Route::get('/itinerari/{slug}', [ItineraryController::class, 'show'])->name('itineraries.show');
Route::get('/eventi', [EventController::class, 'index'])->name('events.index');

Route::get('/legal/{page}', [PageController::class, 'legal'])->name('legal.show');

Route::get('/dashboard', function () {
    $user = auth()->user();

    return AuthRedirect::intended($user);
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pois', [AdminPoiController::class, 'index'])->name('pois.index');
    Route::get('/pois/create', [AdminPoiController::class, 'create'])->name('pois.create');
    Route::post('/pois', [AdminPoiController::class, 'store'])->name('pois.store');
    Route::get('/pois/{poi}/edit', [AdminPoiController::class, 'edit'])->name('pois.edit');
    Route::put('/pois/{poi}', [AdminPoiController::class, 'update'])->name('pois.update');
    Route::delete('/pois/{poi}', [AdminPoiController::class, 'destroy'])->name('pois.destroy');
    Route::post('/pois/{poi}/photos', [AdminPoiController::class, 'storePhoto'])->name('pois.photos.store');
    Route::delete('/pois/{poi}/photos/{photo}', [AdminPoiController::class, 'destroyPhoto'])->name('pois.photos.destroy');
    Route::post('/pois/{poi}/photos/{photo}/primary', [AdminPoiController::class, 'setPrimaryPhoto'])->name('pois.photos.primary');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{tag}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::get('/territories', [AdminTerritoryController::class, 'index'])->name('territories.index');
    Route::get('/territories/regions/create', [AdminTerritoryController::class, 'createRegion'])->name('territories.regions.create');
    Route::post('/territories/regions', [AdminTerritoryController::class, 'storeRegion'])->name('territories.regions.store');
    Route::get('/territories/regions/{region}/edit', [AdminTerritoryController::class, 'editRegion'])->name('territories.regions.edit');
    Route::put('/territories/regions/{region}', [AdminTerritoryController::class, 'updateRegion'])->name('territories.regions.update');
    Route::get('/territories/provinces/create', [AdminTerritoryController::class, 'createProvince'])->name('territories.provinces.create');
    Route::post('/territories/provinces', [AdminTerritoryController::class, 'storeProvince'])->name('territories.provinces.store');
    Route::get('/territories/provinces/{province}/edit', [AdminTerritoryController::class, 'editProvince'])->name('territories.provinces.edit');
    Route::put('/territories/provinces/{province}', [AdminTerritoryController::class, 'updateProvince'])->name('territories.provinces.update');
    Route::get('/territories/municipalities/create', [AdminTerritoryController::class, 'createMunicipality'])->name('territories.municipalities.create');
    Route::post('/territories/municipalities', [AdminTerritoryController::class, 'storeMunicipality'])->name('territories.municipalities.store');
    Route::get('/territories/municipalities/{municipality}/edit', [AdminTerritoryController::class, 'editMunicipality'])->name('territories.municipalities.edit');
    Route::put('/territories/municipalities/{municipality}', [AdminTerritoryController::class, 'updateMunicipality'])->name('territories.municipalities.update');
    Route::get('/contributions', [AdminContributionController::class, 'index'])->name('contributions.index');
    Route::post('/contributions/{contribution}/approve', [AdminContributionController::class, 'approve'])->name('contributions.approve');
    Route::post('/contributions/{contribution}/reject', [AdminContributionController::class, 'reject'])->name('contributions.reject');
    Route::resource('sponsorships', SponsorshipController::class)->except(['show']);
    Route::resource('events', AdminEventController::class)->except(['show']);
    Route::get('itineraries/pois', [AdminItineraryController::class, 'searchPois'])->name('itineraries.pois');
    Route::resource('itineraries', AdminItineraryController::class)->except(['show']);
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/system', [SystemController::class, 'index'])->name('system.index');
    Route::post('/system/test-mail', [SystemController::class, 'sendTestMail'])->name('system.test-mail');
    Route::post('/system/smoke-test', [SystemController::class, 'runSmokeTest'])->name('system.smoke-test');
});

Route::middleware('auth')->group(function () {
    Route::get('/territories/provinces', [TerritoryLookupController::class, 'provinces'])->name('territories.provinces');
    Route::get('/territories/municipalities', [TerritoryLookupController::class, 'municipalities'])->name('territories.municipalities');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/preferiti', [HomeController::class, 'favorites'])->name('favorites');
    Route::get('/contribuisci', [ContributionController::class, 'create'])->name('contribute.create');
    Route::get('/contribuisci/miei', [ContributionController::class, 'mine'])->name('contribute.mine');
    Route::get('/contribuisci/duplicati', [ContributionController::class, 'duplicates'])->name('contribute.duplicates');
    Route::post('/contribuisci', [ContributionController::class, 'store'])->middleware('throttle:10,1')->name('contribute.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
