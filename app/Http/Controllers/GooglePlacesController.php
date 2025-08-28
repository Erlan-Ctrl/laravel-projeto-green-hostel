<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GooglePlacesController extends Controller
{
    protected $nearbyUrl = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';
    protected $detailsUrl = 'https://maps.googleapis.com/maps/api/place/details/json';
    protected $geocodeUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
    protected $photoUrl = 'https://maps.googleapis.com/maps/api/place/photo';

    /**
     * Search nearby lodging using Google Places API.
     * Accepts query params:
     *  - q (string): free-text keyword / hotel name
     *  - location (string): neighborhood/address OR "lat,lng"
     *  - price_filter (cheap|mid|premium) optional
     *  - radius (meters) optional
     *
     * Returns JSON: { data: [ { place_id, name, vicinity, lat, lng, rating, user_ratings_total, price_level, photo } ] }
     */
    public function nearby(Request $request)
    {
        $apiKey = env('GOOGLE_PLACES_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Google Places API key not set'], 500);
        }

        $q = $request->query('q');
        $locationQuery = $request->query('location');
        $price_filter = $request->query('price_filter');
        $radius = intval($request->query('radius', 8000));

        $defaultLat = '-1.4558';
        $defaultLng = '-48.5039';

        $lat = $defaultLat;
        $lng = $defaultLng;

        if ($locationQuery) {
            if (preg_match('/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/', $locationQuery)) {
                [$lat, $lng] = array_map('trim', explode(',', $locationQuery));
            } else {
                $geoRes = $this->geocodeLocation($locationQuery, $apiKey);
                if ($geoRes && isset($geoRes['lat']) && isset($geoRes['lng'])) {
                    $lat = $geoRes['lat'];
                    $lng = $geoRes['lng'];
                }
            }
        }

        $cacheKey = 'places_nearby:' . md5(sprintf('%s|%s|%s|%d', $q, $lat . ',' . $lng, $price_filter, $radius));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json(['data' => $cached]);
        }

        $params = [
            'key' => $apiKey,
            'location' => $lat . ',' . $lng,
            'radius' => $radius,
            'type' => 'lodging',
        ];

        if ($q) {
            $params['keyword'] = $q;
        }

        try {
            $resp = Http::timeout(10)->get($this->nearbyUrl, $params);
            $body = $resp->json();
            $results = $body['results'] ?? [];

            $mapped = array_map(function ($r) use ($apiKey) {
                $lat = $r['geometry']['location']['lat'] ?? null;
                $lng = $r['geometry']['location']['lng'] ?? null;

                $photo = null;
                if (!empty($r['photos'][0]['photo_reference'])) {
                    $ref = $r['photos'][0]['photo_reference'];
                    $photo = $this->buildPhotoUrl($ref, $apiKey);
                }

                return [
                    'place_id' => $r['place_id'] ?? null,
                    'name' => $r['name'] ?? null,
                    'vicinity' => $r['vicinity'] ?? ($r['formatted_address'] ?? null),
                    'lat' => $lat,
                    'lng' => $lng,
                    'rating' => $r['rating'] ?? null,
                    'user_ratings_total' => $r['user_ratings_total'] ?? null,
                    'price_level' => $r['price_level'] ?? null,
                    'photo' => $photo,
                ];
            }, $results);

            if ($price_filter) {
                $mapped = array_values(array_filter($mapped, function ($i) use ($price_filter) {
                    $pl = $i['price_level'] ?? null;
                    if ($price_filter === 'cheap') return $pl === 0 || $pl === 1 || $pl === null;
                    if ($price_filter === 'mid') return $pl === 2;
                    if ($price_filter === 'premium') return $pl >= 3;
                    return true;
                }));
            }

            Cache::put($cacheKey, $mapped, 30);

            return response()->json(['data' => $mapped]);
        } catch (\Exception $e) {
            Log::error("Google Places Nearby error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to query Google Places: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get place details by place_id and return simplified shape (used by frontend if needed).
     */
    public function details(Request $request)
    {
        $apiKey = env('GOOGLE_PLACES_API_KEY');
        $placeId = $request->query('place_id');

        if (!$apiKey || !$placeId) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        $cacheKey = 'places_details:' . md5($placeId);
        $cached = Cache::get($cacheKey);
        if ($cached) return response()->json(['data' => $cached]);

        try {
            $resp = Http::timeout(10)->get($this->detailsUrl, [
                'place_id' => $placeId,
                'key' => $apiKey,
                'fields' => 'name,formatted_address,geometry,formatted_phone_number,website,opening_hours,photos,rating,user_ratings_total,price_level,review'
            ]);
            $body = $resp->json();
            $r = $body['result'] ?? null;
            if (!$r) {
                return response()->json(['error' => 'Place not found'], 404);
            }
            $photo = null;
            if (!empty($r['photos'][0]['photo_reference'])) {
                $photo = $this->buildPhotoUrl($r['photos'][0]['photo_reference'], $apiKey);
            }

            $mapped = [
                'place_id' => $r['place_id'] ?? $placeId,
                'name' => $r['name'] ?? null,
                'address' => $r['formatted_address'] ?? null,
                'lat' => $r['geometry']['location']['lat'] ?? null,
                'lng' => $r['geometry']['location']['lng'] ?? null,
                'phone' => $r['formatted_phone_number'] ?? null,
                'website' => $r['website'] ?? null,
                'opening_hours' => $r['opening_hours'] ?? null,
                'rating' => $r['rating'] ?? null,
                'user_ratings_total' => $r['user_ratings_total'] ?? null,
                'price_level' => $r['price_level'] ?? null,
                'photo' => $photo,
            ];

            // cache details a bit longer
            Cache::put($cacheKey, $mapped, 300);

            return response()->json(['data' => $mapped]);
        } catch (\Exception $e) {
            Log::error("Google Places Details error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to query Google Places details: ' . $e->getMessage()], 500);
        }
    }

    private function geocodeLocation($text, $apiKey)
    {
        try {
            $resp = Http::timeout(8)->get($this->geocodeUrl, [
                'address' => $text,
                'key' => $apiKey,
            ]);
            $body = $resp->json();
            $first = $body['results'][0] ?? null;
            if (!$first) return null;
            return [
                'lat' => $first['geometry']['location']['lat'] ?? null,
                'lng' => $first['geometry']['location']['lng'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning("Geocode failed for '{$text}': " . $e->getMessage());
            return null;
        }
    }

    private function buildPhotoUrl($photoReference, $apiKey, $maxwidth = 800)
    {
        return $this->photoUrl . '?maxwidth=' . intval($maxwidth) . '&photoreference=' . urlencode($photoReference) . '&key=' . $apiKey;
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
