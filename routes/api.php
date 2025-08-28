<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GooglePlacesController;
use App\Http\Controllers\HotelsController;

Route::get('places', [GooglePlacesController::class, 'nearby']);
Route::get('places/nearby', [GooglePlacesController::class, 'nearby']);
Route::get('places/details', [GooglePlacesController::class, 'details']);
Route::get('hotels', [HotelsController::class, 'nearby']);
Route::get('hotels/details', [HotelsController::class, 'details']);
Route::get('places/nearby', [GooglePlacesController::class, 'nearby']);
Route::get('hotels/nearby', [GooglePlacesController::class, 'nearby']);

Route::get('/__ping', function (Request $request) {
    return response()->json([
        'ok' => true,
        'time' => now()->toDateTimeString(),
    ]);
});
