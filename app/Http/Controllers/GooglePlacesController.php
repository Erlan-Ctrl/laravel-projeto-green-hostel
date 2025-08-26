<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GooglePlacesController extends Controller
{

    public function nearby(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (! $lat || ! $lng) {
            return response()->json(['error' => 'Parâmetros lat/lng obrigatórios'], 400);
        }

        $key = config('services.google.places_key', env('GOOGLE_PLACES_API_KEY'));
        if (! $key) {
            return response()->json(['error' => 'GOOGLE_PLACES_API_KEY ausente'], 500);
        }

        $radius = $request->query('radius', 5000);
        $keyword = $request->query('query', null);

        $cacheKey = 'places_nearby:' . md5("{$lat},{$lng},{$radius},{$keyword}");
        $body = Cache::remember($cacheKey, 90, function () use ($lat, $lng, $radius, $keyword, $key) {
            $params = [
                'location' => "{$lat},{$lng}",
                'radius' => $radius,
                'type' => 'lodging',
                'key' => $key,
            ];
            if ($keyword) $params['keyword'] = $keyword;

            $resp = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', $params);
            if (! $resp->ok()) {
                Log::error('Google Places HTTP error', ['status' => $resp->status(), 'body' => substr($resp->body(), 0, 1000)]);
                return null;
            }
            return $resp->json();
        });

        if ($body === null) {
            return response()->json(['error' => 'Erro ao contatar Google Places'], 502);
        }

        $results = $body['results'] ?? [];

        $normalized = [];
        foreach ($results as $r) {
            $placeLat = $r['geometry']['location']['lat'] ?? null;
            $placeLng = $r['geometry']['location']['lng'] ?? null;
            $vicinity = $r['vicinity'] ?? ($r['formatted_address'] ?? '');

            $photoUrl = null;
            if (!empty($r['photos'][0]['photo_reference'])) {
                $photoRef = $r['photos'][0]['photo_reference'];
                $photoUrl = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=800&photoreference={$photoRef}&key={$key}";
            }

            $distanceKm = null;
            if ($placeLat !== null && $placeLng !== null) {
                $distanceKm = $this->haversineDistance($lat, $lng, $placeLat, $placeLng);
            }

            $normalized[] = [
                'id' => $r['place_id'] ?? null,
                'place_id' => $r['place_id'] ?? null,
                'name' => $r['name'] ?? '',
                'city' => $vicinity,
                'neighborhood' => null,
                'image_url' => $photoUrl,
                'price' => null,
                'price_level' => $r['price_level'] ?? null,
                'price_text' => $this->mapPriceLevel($r['price_level'] ?? null),
                'distance' => $distanceKm !== null ? round($distanceKm, 3) : null,
                'description' => ($r['types'] ? implode(', ', $r['types']) . ' — ' : '') . $vicinity,
                'latitude' => $placeLat,
                'longitude' => $placeLng,
                'rating' => $r['rating'] ?? null,
                'user_ratings_total' => $r['user_ratings_total'] ?? 0,
                'raw' => config('app.debug') ? $r : null,
            ];
        }

        return response()->json($normalized, 200);
    }

    public function placeDetails(Request $request)
    {
        $placeId = $request->query('place_id');
        if (! $placeId) {
            return response()->json(['error' => 'place_id obrigatório'], 400);
        }

        $key = config('services.google.places_key', env('GOOGLE_PLACES_API_KEY'));
        if (! $key) {
            return response()->json(['error' => 'GOOGLE_PLACES_API_KEY ausente'], 500);
        }

        $cacheKey = 'place_details:' . $placeId;
        $detail = Cache::remember($cacheKey, 3600, function () use ($placeId, $key) {
            $resp = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'formatted_address,formatted_phone_number,website,price_level,opening_hours,international_phone_number,geometry,name,rating,user_ratings_total',
                'key' => $key,
            ]);

            if (! $resp->ok()) {
                Log::warning('Place Details HTTP error', ['place_id' => $placeId, 'status' => $resp->status(), 'body' => substr($resp->body(), 0, 1000)]);
                return null;
            }

            $body = $resp->json();
            return $body['result'] ?? null;
        });

        if ($detail === null) {
            return response()->json(['error' => 'Não foi possível obter detalhes (veja logs)'], 502);
        }

        return response()->json([
            'place_id' => $placeId,
            'name' => $detail['name'] ?? null,
            'formatted_address' => $detail['formatted_address'] ?? null,
            'phone' => $detail['formatted_phone_number'] ?? $detail['international_phone_number'] ?? null,
            'website' => $detail['website'] ?? null,
            'price_level' => $detail['price_level'] ?? null,
            'price_text' => $this->mapPriceLevel($detail['price_level'] ?? null),
            'opening_hours' => $detail['opening_hours']['weekday_text'] ?? [],
            'rating' => $detail['rating'] ?? null,
            'user_ratings_total' => $detail['user_ratings_total'] ?? null,
            'geometry' => $detail['geometry'] ?? null,
            'raw' => config('app.debug') ? $detail : null,
        ]);
    }

    private function mapPriceLevel($level)
    {
        if ($level === null) return null;
        $map = [
            0 => 'Grátis/Barato',
            1 => 'Econômico ($)',
            2 => 'Médio ($$)',
            3 => 'Caro ($$$)',
            4 => 'Muito caro ($$$$)',
        ];
        return $map[(int)$level] ?? null;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $lat1 = floatval($lat1); $lon1 = floatval($lon1);
        $lat2 = floatval($lat2); $lon2 = floatval($lon2);
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}
