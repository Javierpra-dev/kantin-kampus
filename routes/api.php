<?php

use App\Http\Controllers\Api\V1\CanteenController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SuggestionController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Kantin Kampus (Phase 12a)
|--------------------------------------------------------------------------
|
| Versioned REST API endpoints for the Flutter buyer mobile application.
| All endpoints return JSON and are grouped under /api/v1.
|
*/

Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    // Canteens
    Route::get('/canteens', [CanteenController::class, 'index'])->name('api.v1.canteens.index');
    Route::get('/canteens/{id}/menus', [CanteenController::class, 'menus'])->name('api.v1.canteens.menus');

    // Menus
    Route::get('/menus/popular', [MenuController::class, 'popular'])->name('api.v1.menus.popular');
    Route::get('/menus/mood', [MenuController::class, 'mood'])->name('api.v1.menus.mood');

    // Search
    Route::get('/search', [SearchController::class, 'search'])->name('api.v1.search');

    // Zones
    Route::get('/zones', [ZoneController::class, 'index'])->name('api.v1.zones.index');

    // Suggestions ("Pilihin Aku")
    Route::post('/suggestions', [SuggestionController::class, 'suggestions'])->name('api.v1.suggestions');

    // Orders
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware(['throttle:10,1'])
        ->name('api.v1.orders.store');

    Route::get('/orders/{order_code}', [OrderController::class, 'show'])->name('api.v1.orders.show');
    Route::post('/orders/{order_code}/payment', [OrderController::class, 'payment'])->name('api.v1.orders.payment');
    Route::post('/orders/{order_code}/simulate-pay', [OrderController::class, 'simulatePay'])->name('api.v1.orders.simulate-pay');
    Route::post('/orders/{order_code}/reorder-check', [OrderController::class, 'reorderCheck'])->name('api.v1.orders.reorder-check');
});

