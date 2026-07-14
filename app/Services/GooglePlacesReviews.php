<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GooglePlacesReviews
{
    /**
     * @return array{name: string|null, rating: float|null, user_rating_count: int, google_maps_uri: string|null, reviews: list<array{author_name: string, author_uri: string|null, author_photo_uri: string|null, quote: string, rating: int, relative_time: string|null, google_maps_uri: string|null}>, attributions: list<array{provider: string, provider_uri: string|null}>}|null
     */
    public function forPlace(?string $placeId): ?array
    {
        $apiKey = config('services.google_places.api_key');

        if (! $apiKey || ! $placeId) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,googleMapsUri,reviews,attributions',
                ])
                ->connectTimeout(2)
                ->timeout(4)
                ->get('https://places.googleapis.com/v1/places/'.rawurlencode($placeId), [
                    'languageCode' => 'pt-BR',
                    'regionCode' => 'BR',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || ! is_array($data = $response->json())) {
            return null;
        }

        $reviews = collect($data['reviews'] ?? [])
            ->filter(fn ($review) => is_array($review)
                && filled(data_get($review, 'authorAttribution.displayName'))
                && filled(data_get($review, 'text.text')))
            ->map(fn (array $review) => [
                'author_name' => (string) data_get($review, 'authorAttribution.displayName'),
                'author_uri' => data_get($review, 'authorAttribution.uri'),
                'author_photo_uri' => data_get($review, 'authorAttribution.photoUri'),
                'quote' => (string) data_get($review, 'text.text'),
                'rating' => max(1, min(5, (int) ($review['rating'] ?? 5))),
                'relative_time' => $review['relativePublishTimeDescription'] ?? null,
                'google_maps_uri' => $review['googleMapsUri'] ?? null,
            ])
            ->values()
            ->all();

        if ($reviews === []) {
            return null;
        }

        return [
            'name' => data_get($data, 'displayName.text'),
            'rating' => isset($data['rating']) ? (float) $data['rating'] : null,
            'user_rating_count' => (int) ($data['userRatingCount'] ?? 0),
            'google_maps_uri' => $data['googleMapsUri'] ?? null,
            'reviews' => $reviews,
            'attributions' => collect($data['attributions'] ?? [])
                ->filter(fn ($attribution) => is_array($attribution) && filled($attribution['provider'] ?? null))
                ->map(fn (array $attribution) => [
                    'provider' => (string) $attribution['provider'],
                    'provider_uri' => $attribution['providerUri'] ?? null,
                ])
                ->values()
                ->all(),
        ];
    }
}
