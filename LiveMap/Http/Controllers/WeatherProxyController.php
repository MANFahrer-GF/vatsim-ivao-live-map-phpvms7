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
        'thunder_new',
        'wind_new',
        'temp_new',
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

        $cacheKey = 'livemap:owm:'.$layer.':'.$z.':'.$x.':'.$y;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['body'], $cached['type'])) {
            return response(base64_decode((string) $cached['body']), 200)
                ->header('Content-Type', (string) $cached['type'])
                ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
                ->header('X-LiveMap-Proxy', '1')
                ->header('X-LiveMap-Cache', 'HIT');
        }

        $url = sprintf('https://tile.openweathermap.org/map/%s/%d/%d/%d.png', $layer, $z, $x, $y);
        try {
            $upstream = Http::timeout(10)
                ->retry(1, 200)
                ->accept('image/png')
                ->get($url, ['appid' => $apiKey]);
        } catch (\Throwable $e) {
            Log::warning('[LiveMap] OWM proxy request failed', ['message' => $e->getMessage()]);
            Cache::put(self::CACHE_UPSTREAM_FAILED, 1, now()->addSeconds(60));
            $this->rememberUpstreamError('NETWORK', $e->getMessage());

            return $this->blankTileResponse('upstream-exception');
        }

        if (!$upstream->ok()) {
            Log::warning('[LiveMap] OWM proxy non-200 response', [
                'status' => $upstream->status(),
                'layer'  => $layer,
                'z'      => $z,
                'x'      => $x,
                'y'      => $y,
            ]);
            Cache::put(self::CACHE_UPSTREAM_FAILED, 1, now()->addSeconds(60));
            $this->rememberUpstreamError((string) $upstream->status(), 'OWM returned HTTP '.$upstream->status().' for '.$layer);

            return $this->blankTileResponse('upstream-'.$upstream->status());
        }

        Cache::forget(self::CACHE_UPSTREAM_FAILED);
        Cache::put(self::CACHE_LAST_SUCCESS_AT, now()->toDateTimeString(), now()->addDay());

        $body = $upstream->body();
        $contentType = $upstream->header('Content-Type', 'image/png');

        Cache::put($cacheKey, [
            'body' => base64_encode($body),
            'type' => $contentType,
        ], now()->addMinutes(5));

        return response($body, 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
            ->header('X-LiveMap-Proxy', '1')
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

    private function lmGet(string $legacyKey, $default = null)
    {
        $sentinel = '__LIVEMAP_MISSING__';
        $suffix = preg_replace('/^acars\.livemap_/', '', $legacyKey);
        if (!is_string($suffix) || trim($suffix) === '') {
            $suffix = str_replace('.', '_', $legacyKey);
        }

        $kvpValue = kvp(self::KVP_PREFIX.$suffix, $sentinel);
        if ($kvpValue !== $sentinel) {
            return $kvpValue;
        }

        return setting($legacyKey, $default);
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
