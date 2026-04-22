<?php

namespace Modules\LiveMap\Http\Controllers;

use App\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WeatherProxyController extends Controller
{
    private const KVP_PREFIX = 'livemap.';
    private const ALLOWED_LAYERS = [
        'clouds_new',
        'precipitation_new',
        'pressure_new',
        // Legacy aliases kept for backward-compatible requests from older JS.
        'thunder_new',
        'weather_new',
        'wind_new',
        'temp_new',
    ];
    private const LAYER_ALIASES = [
        'thunder_new' => 'pressure_new',
        'weather_new' => 'precipitation_new',
    ];
    private const UPSTREAM_LAYER_FALLBACKS = [
        'pressure_new' => ['precipitation_new', 'clouds_new'],
        'precipitation_new' => ['clouds_new'],
    ];

    private const BLANK_TILE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"></svg>';
    private const CACHE_UPSTREAM_FAILED = 'livemap:owm:upstream-failed';
    private const CACHE_LAST_ERROR_CODE = 'livemap:owm:last_error_code';
    private const CACHE_LAST_ERROR_REASON = 'livemap:owm:last_error_reason';
    private const CACHE_LAST_ERROR_AT = 'livemap:owm:last_error_at';
    private const CACHE_LAST_SUCCESS_AT = 'livemap:owm:last_success_at';

    public function tile(Request $request, string $layer, int $z, int $x, int $y): Response
    {
        if (!in_array($layer, self::ALLOWED_LAYERS, true)) {
            return $this->blankTileResponse('invalid-layer');
        }
        $resolvedLayer = self::LAYER_ALIASES[$layer] ?? $layer;

        if (!$this->lmBool('acars.livemap_weather_proxy_enabled', true)) {
            return $this->blankTileResponse('proxy-disabled');
        }

        if ($z < 0 || $z > 18 || $x < 0 || $y < 0) {
            return $this->blankTileResponse('invalid-coordinates');
        }

        $maxTileIndex = 2 ** $z;
        if ($x >= $maxTileIndex || $y >= $maxTileIndex) {
            return $this->blankTileResponse('tile-out-of-range');
        }

        $rateKey = 'livemap:tile:'.sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 600)) {
            return $this->blankTileResponse('rate-limited');
        }
        RateLimiter::hit($rateKey, 60);

        $apiKey = trim((string) $this->lmGet('acars.livemap_owm_api_key', env('LIVEMAP_OWM_API_KEY', '')));
        if ($apiKey === '') {
            return $this->blankTileResponse('api-key-missing');
        }

        if (Cache::get(self::CACHE_UPSTREAM_FAILED)) {
            return $this->blankTileResponse('upstream-cooldown');
        }

        $cacheKey = 'livemap:owm:'.$resolvedLayer.':'.$z.':'.$x.':'.$y;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['body'], $cached['type'])) {
            $cachedLayer = (string) ($cached['upstream_layer'] ?? $resolvedLayer);
            return response(base64_decode((string) $cached['body']), 200)
                ->header('Content-Type', (string) $cached['type'])
                ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
                ->header('X-LiveMap-Proxy', '1')
                ->header('X-LiveMap-Upstream-Layer', $cachedLayer)
                ->header('X-LiveMap-Fallback', $cachedLayer !== $resolvedLayer ? '1' : '0')
                ->header('X-LiveMap-Cache', 'HIT');
        }

        $upstreamCandidates = $this->upstreamLayerCandidates($resolvedLayer);
        $attemptedLayers = [];
        $upstreamLayer = $resolvedLayer;
        $upstream = null;
        $lastExceptionMessage = null;

        foreach ($upstreamCandidates as $candidateLayer) {
            $attemptedLayers[] = $candidateLayer;
            $url = sprintf('https://tile.openweathermap.org/map/%s/%d/%d/%d.png', $candidateLayer, $z, $x, $y);
            try {
                $candidateResponse = Http::timeout(10)
                    ->retry(1, 200)
                    ->accept('image/png')
                    ->get($url, ['appid' => $apiKey]);
            } catch (\Throwable $e) {
                $lastExceptionMessage = $e->getMessage();
                continue;
            }

            $upstream = $candidateResponse;
            $upstreamLayer = $candidateLayer;
            if ($candidateResponse->ok()) {
                break;
            }
        }

        if ($upstream === null) {
            Log::warning('[LiveMap] OWM proxy request failed', [
                'message'          => $lastExceptionMessage ?? 'All upstream attempts failed',
                'layer'            => $layer,
                'resolved_layer'   => $resolvedLayer,
                'attempted_layers' => $attemptedLayers,
                'z'                => $z,
                'x'                => $x,
                'y'                => $y,
            ]);
            Cache::put(self::CACHE_UPSTREAM_FAILED, 1, now()->addSeconds(60));
            $this->rememberUpstreamError('NETWORK', (string) ($lastExceptionMessage ?? 'All upstream attempts failed'));

            return $this->blankTileResponse('upstream-exception');
        }

        if (!$upstream->ok()) {
            Log::warning('[LiveMap] OWM proxy non-200 response', [
                'status'           => $upstream->status(),
                'layer'            => $layer,
                'resolved_layer'   => $resolvedLayer,
                'upstream_layer'   => $upstreamLayer,
                'attempted_layers' => $attemptedLayers,
                'z'                => $z,
                'x'                => $x,
                'y'                => $y,
            ]);
            Cache::put(self::CACHE_UPSTREAM_FAILED, 1, now()->addSeconds(60));
            $this->rememberUpstreamError(
                (string) $upstream->status(),
                'OWM returned HTTP '.$upstream->status().' for '.$layer.
                ($resolvedLayer !== $layer ? ' (resolved: '.$resolvedLayer.')' : '').
                ' (attempted: '.implode(' -> ', $attemptedLayers).')'
            );

            return $this->blankTileResponse('upstream-'.$upstream->status());
        }

        Cache::forget(self::CACHE_UPSTREAM_FAILED);
        Cache::put(self::CACHE_LAST_SUCCESS_AT, now()->toDateTimeString(), now()->addDay());

        $body = $upstream->body();
        $contentType = $upstream->header('Content-Type', 'image/png');

        Cache::put($cacheKey, [
            'body'           => base64_encode($body),
            'type'           => $contentType,
            'upstream_layer' => $upstreamLayer,
        ], now()->addMinutes(5));

        return response($body, 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
            ->header('X-LiveMap-Proxy', '1')
            ->header('X-LiveMap-Upstream-Layer', $upstreamLayer)
            ->header('X-LiveMap-Fallback', $upstreamLayer !== $resolvedLayer ? '1' : '0')
            ->header('X-LiveMap-Cache', 'MISS');
    }

    private function blankTileResponse(string $reason): Response
    {
        return response(self::BLANK_TILE_SVG, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=60, s-maxage=60')
            ->header('X-LiveMap-Proxy', '1')
            ->header('X-LiveMap-Weather', 'unavailable')
            ->header('X-LiveMap-Reason', $reason);
    }

    private function rememberUpstreamError(string $code, string $reason): void
    {
        Cache::put(self::CACHE_LAST_ERROR_CODE, strtoupper(trim($code)), now()->addDay());
        Cache::put(self::CACHE_LAST_ERROR_REASON, trim($reason), now()->addDay());
        Cache::put(self::CACHE_LAST_ERROR_AT, now()->toDateTimeString(), now()->addDay());
    }

    private function upstreamLayerCandidates(string $resolvedLayer): array
    {
        $candidates = [$resolvedLayer];
        foreach (self::UPSTREAM_LAYER_FALLBACKS[$resolvedLayer] ?? [] as $fallbackLayer) {
            if (!in_array($fallbackLayer, $candidates, true)) {
                $candidates[] = $fallbackLayer;
            }
        }

        return $candidates;
    }

    private function lmGet(string $legacyKey, $default = null)
    {
        $sentinel = '__LIVEMAP_MISSING__';
        $suffix = preg_replace('/^acars\.livemap_/', '', $legacyKey);
        if (!is_string($suffix) || trim($suffix) === '') {
            $suffix = str_replace('.', '_', $legacyKey);
        }

        $kvpKey = self::KVP_PREFIX.$suffix;
        $kvpValue = kvp($kvpKey, $sentinel);
        if ($kvpValue !== $sentinel) {
            return $kvpValue;
        }

        // kvp.json may have been wiped (deploy, cache flush, permission reset,
        // Spatie Valuestore race). Fall back to the durable DB row and re-seed kvp.
        $settingValue = setting($legacyKey, $sentinel);
        if ($settingValue !== $sentinel) {
            try {
                kvp_save($kvpKey, (string) $settingValue);
            } catch (\Throwable $e) {
                // Non-fatal: DB already answered this read.
            }

            return $settingValue;
        }

        return $default;
    }

    private function lmBool(string $legacyKey, bool $default = false): bool
    {
        $value = $this->lmGet($legacyKey, $default);
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return $default;
        }

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
