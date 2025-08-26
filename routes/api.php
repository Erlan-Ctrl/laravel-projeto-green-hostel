<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotelsController;
use App\Http\Controllers\GooglePlacesController;

Route::get('places/nearby', [GooglePlacesController::class, 'nearby']);
Route::get('hotels/nearby', [GooglePlacesController::class, 'nearby']);
Route::get('place/details', [GooglePlacesController::class, 'placeDetails']);


Route::get('/__ping', function (Request $request) {
    return response()->json([
        'ok' => true,
        'time' => now()->toDateTimeString(),
    ]);
});
