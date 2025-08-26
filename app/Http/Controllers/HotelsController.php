<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HotelsController extends Controller
{
    public function nearby(Request $request)
    {
        // Delegar para o GooglePlacesController (mantém a mesma assinatura)
        return app(GooglePlacesController::class)->nearby($request);
    }
}
