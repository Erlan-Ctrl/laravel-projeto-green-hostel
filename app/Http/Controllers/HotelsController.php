<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HotelsController extends Controller
{
    /**
     * Keep the HotelsController as a thin delegator to GooglePlacesController so your
     * existing routes/controllers calling HotelsController::nearby continue to work.
     */
    public function nearby(Request $request)
    {
        return app(GooglePlacesController::class)->nearby($request);
    }

    public function details(Request $request)
    {
        return app(GooglePlacesController::class)->details($request);
    }
}
