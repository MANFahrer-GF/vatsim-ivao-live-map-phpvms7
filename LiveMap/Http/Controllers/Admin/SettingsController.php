<?php

namespace Modules\LiveMap\Http\Controllers\Admin;

use App\Contracts\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    private const GROUP = 'acars';

    public function index()
    {
        return view('livemap::admin.index', [
            'settings'      => $this->currentSettings(),
            'layerOptions'  => $this->layerOptions(),
            'basemapOptions' => $this->basemapOptions(),
            'weatherProxyStatus' => $this->weatherProxyStatus(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layout_mode'             => 'required|in:modern,old_style',
            'default_basemap'         => 'required|in:positron,osm,dark,satellite',
            'weather_default_layer'   => 'required|in:none,clouds,radar,storms,wind,temp,combo',
            'weather_default_opacity' => 'required|numeric|min:0.2|max:1',
            'owm_api_key'             => 'nullable|string|max:128',
            'ui_primary_color'           => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_accent_color'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_box_background'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $proxyEnabled = $request->boolean('weather_proxy_enabled');
        $owmApiKey = trim((string) ($validated['owm_api_key'] ?? ''));
        if ($proxyEnabled && $owmApiKey === '') {
            return back()->withInput()->withErrors([
                'owm_api_key' => 'OpenWeatherMap API key is required when weather proxy is enabled.',
            ]);
        }

        if ($owmApiKey !== '') {
            $verification = $this->verifyOwmApiKey($owmApiKey);
            if (!$verification['valid']) {
                return back()->withInput()->withErrors([
                    'owm_api_key' => $verification['message'],
                ]);
            }
        }

        $layoutMode = $validated['layout_mode'];
        $primaryColor = $this->normalizeHexColor($validated['ui_primary_color'] ?? '', '#1A2A4A');
        $accentColor = $this->normalizeHexColor($validated['ui_accent_color'] ?? '', '#243B6A');
        $boxBackgroundColor = $this->normalizeHexColor($validated['color_box_background'] ?? '', '#FFFFFF');

        $payload = [
            'acars.livemap_old_style'                   => $layoutMode === 'old_style',
            'acars.livemap_show_top_flights_panel'      => $layoutMode === 'modern',
            'acars.livemap_default_basemap'             => $validated['default_basemap'],
            'acars.livemap_show_basemap_switcher'       => $request->boolean('show_basemap_switcher'),
            'acars.livemap_enable_satellite'            => $request->boolean('enable_satellite'),
            'acars.livemap_show_weather_box'            => $request->boolean('show_weather_box'),
            'acars.livemap_weather_proxy_enabled'       => $request->boolean('weather_proxy_enabled'),
            'acars.livemap_weather_default_layer'       => $validated['weather_default_layer'],
            'acars.livemap_weather_default_opacity'     => $validated['weather_default_opacity'],
            'acars.livemap_owm_api_key'                 => $owmApiKey,
            'acars.livemap_show_network_box'            => $request->boolean('show_network_box'),
            'acars.livemap_default_network_vatsim'      => $request->boolean('default_network_vatsim'),
            'acars.livemap_default_network_ivao'        => $request->boolean('default_network_ivao'),
            'acars.livemap_default_show_pilots'         => $request->boolean('default_show_pilots'),
            'acars.livemap_default_show_controllers'    => $request->boolean('default_show_controllers'),
            'acars.livemap_default_show_sectors'        => $request->boolean('default_show_sectors'),
            'acars.livemap_default_follow_flight'       => $request->boolean('default_follow_flight'),
            'acars.livemap_mobile_show_flights_button'  => $request->boolean('mobile_show_flights_button'),
            'acars.livemap_mobile_flights_open'         => $request->boolean('mobile_flights_open'),
            'acars.livemap_mobile_weather_open'         => $request->boolean('mobile_weather_open'),
            'acars.livemap_mobile_network_open'         => $request->boolean('mobile_network_open'),
            'acars.livemap_color_flights_header_start'  => $primaryColor,
            'acars.livemap_color_flights_header_end'    => $accentColor,
            'acars.livemap_color_weather_header'        => $primaryColor,
            'acars.livemap_color_network_header'        => $primaryColor,
            'acars.livemap_color_box_background'        => $boxBackgroundColor,
            'acars.livemap_color_mobile_button'         => $primaryColor,
            'acars.livemap_color_mobile_button_active'  => $accentColor,
        ];

        foreach ($this->definitions() as $key => $definition) {
            $this->persistSetting(
                $key,
                $payload[$key] ?? ($definition['default'] ?? ''),
                $definition['type'] ?? 'string',
                $definition['name'] ?? $key,
                $definition['description'] ?? ''
            );
        }

        return redirect('/admin/livemap')->with('status', 'Live Map settings saved.');
    }

    private function currentSettings(): array
    {
        $values = [];
        foreach ($this->definitions() as $key => $definition) {
            $values[$key] = setting($key, $definition['default'] ?? null);
        }

        return $values;
    }

    private function layerOptions(): array
    {
        return [
            'none'   => 'None',
            'clouds' => 'Clouds',
            'radar'  => 'Radar / Precipitation',
            'storms' => 'Storms / Thunder',
            'wind'   => 'Wind',
            'temp'   => 'Temperature',
            'combo'  => 'Combo (Clouds + Radar + Storms)',
        ];
    }

    private function basemapOptions(): array
    {
        return [
            'positron'  => 'Carto Light (default style)',
            'osm'       => 'OpenStreetMap Standard',
            'dark'      => 'Carto Dark',
            'satellite' => 'Esri World Imagery (Satellite)',
        ];
    }

    private function weatherProxyStatus(): array
    {
        $proxyEnabled = (bool) setting('acars.livemap_weather_proxy_enabled', true);
        $apiKey = trim((string) setting('acars.livemap_owm_api_key', env('LIVEMAP_OWM_API_KEY', '')));
        $hasApiKey = $apiKey !== '';
        $fallbackActive = (bool) Cache::get('livemap:owm:upstream-failed');
        $lastErrorCode = Cache::get('livemap:owm:last_error_code');
        $lastErrorReason = Cache::get('livemap:owm:last_error_reason');
        $lastErrorAt = Cache::get('livemap:owm:last_error_at');
        $lastSuccessAt = Cache::get('livemap:owm:last_success_at');
        $errorInfo = $this->explainWeatherError($lastErrorCode, $lastErrorReason);

        $state = 'ok';
        $badgeClass = 'success';
        $title = 'Weather Proxy OK';
        $message = 'Proxy is active and no temporary upstream fallback is currently detected.';

        if (!$proxyEnabled) {
            $state = 'disabled';
            $badgeClass = 'info';
            $title = 'Weather Proxy Disabled';
            $message = 'Proxy is turned off in settings. Browser will call OpenWeather directly (key may be visible).';
        } elseif (!$hasApiKey) {
            $state = 'missing_key';
            $badgeClass = 'danger';
            $title = 'API Key Missing';
            $message = 'No OpenWeatherMap API key configured. Weather tiles cannot be loaded.';
        } elseif ($fallbackActive) {
            $state = 'fallback';
            $badgeClass = 'warning';
            $title = 'Fallback Active';
            $message = 'OpenWeather upstream recently failed. Proxy serves blank tiles temporarily to prevent browser error spam.';
        }

        return [
            'state' => $state,
            'badgeClass' => $badgeClass,
            'title' => $title,
            'message' => $message,
            'proxyEnabled' => $proxyEnabled,
            'hasApiKey' => $hasApiKey,
            'fallbackActive' => $fallbackActive,
            'lastErrorCode' => $lastErrorCode,
            'lastErrorReason' => $lastErrorReason,
            'lastErrorAt' => $lastErrorAt,
            'lastSuccessAt' => $lastSuccessAt,
            'errorInfo' => $errorInfo,
        ];
    }

    private function explainWeatherError($code, ?string $reason = null): array
    {
        $normalized = strtoupper(trim((string) ($code ?? '')));
        $title = 'No upstream error recorded';
        $meaning = 'No recent OpenWeatherMap error has been stored by the proxy.';
        $action = 'No action needed.';

        if ($normalized === '') {
            return [
                'code' => null,
                'title' => $title,
                'meaning' => $meaning,
                'action' => $action,
                'reason' => $reason ?: null,
            ];
        }

        if ($normalized === 'NETWORK') {
            $title = 'Network/Connection error';
            $meaning = 'The server could not reach OpenWeatherMap (timeout, DNS, firewall, or temporary network outage).';
            $action = 'Check server outbound HTTPS access to tile.openweathermap.org and retry.';
        } elseif ($normalized === '401') {
            $title = 'Unauthorized (401)';
            $meaning = 'OpenWeatherMap rejected the API key.';
            $action = 'Verify the exact API key in admin settings and ensure it is active.';
        } elseif ($normalized === '403') {
            $title = 'Forbidden (403)';
            $meaning = 'Key is valid but access is not allowed (plan/billing/domain restriction).';
            $action = 'Check OWM plan permissions and account/billing status.';
        } elseif ($normalized === '404') {
            $title = 'Not Found (404)';
            $meaning = 'Requested tile/layer path was not accepted by upstream.';
            $action = 'Use supported layers (clouds, radar, storms, wind, temp) and verify URL format.';
        } elseif ($normalized === '429') {
            $title = 'Rate limit reached (429)';
            $meaning = 'Too many requests were sent to OpenWeatherMap.';
            $action = 'Reduce active weather layers (avoid combo), wait, or upgrade plan limits.';
        } elseif (in_array($normalized, ['500', '502', '503', '504'], true)) {
            $title = 'Upstream service unavailable ('.$normalized.')';
            $meaning = 'OpenWeatherMap had a server-side issue.';
            $action = 'Usually temporary. Wait and test again later.';
        } else {
            $title = 'Upstream error ('.$normalized.')';
            $meaning = 'OpenWeatherMap returned an unclassified error code.';
            $action = 'Check OWM dashboard/logs and test a tile URL directly.';
        }

        return [
            'code' => $normalized,
            'title' => $title,
            'meaning' => $meaning,
            'action' => $action,
            'reason' => $reason ?: null,
        ];
    }

    private function verifyOwmApiKey(string $apiKey): array
    {
        $url = 'https://tile.openweathermap.org/map/clouds_new/1/1/1.png';
        try {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->accept('image/png')
                ->get($url, ['appid' => $apiKey]);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'message' => 'Could not verify OpenWeatherMap key (network/timeout): '.$e->getMessage(),
            ];
        }

        if ($response->ok()) {
            return ['valid' => true, 'message' => ''];
        }

        $status = $response->status();
        if ($status === 401 || $status === 403) {
            return [
                'valid' => false,
                'message' => 'OpenWeatherMap rejected this API key (HTTP '.$status.'). Please check the key.',
            ];
        }

        if ($status === 429) {
            return [
                'valid' => false,
                'message' => 'OpenWeatherMap rate limit reached (HTTP 429). Please wait and try again.',
            ];
        }

        return [
            'valid' => false,
            'message' => 'OpenWeatherMap key validation failed (HTTP '.$status.').',
        ];
    }

    private function definitions(): array
    {
        return [
            'acars.livemap_old_style' => [
                'name'        => 'Live Map: Old Style',
                'description' => 'Hide the top flights table overlay',
                'type'        => 'bool',
                'default'     => false,
            ],
            'acars.livemap_show_top_flights_panel' => [
                'name'        => 'Live Map: Show Top Flights Panel',
                'description' => 'Enable/disable the top flights panel',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_default_basemap' => [
                'name'        => 'Live Map: Default Basemap',
                'description' => 'Default base map style at load',
                'type'        => 'string',
                'default'     => 'positron',
            ],
            'acars.livemap_show_basemap_switcher' => [
                'name'        => 'Live Map: Show Basemap Switcher',
                'description' => 'Show map style switcher control on the map',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_enable_satellite' => [
                'name'        => 'Live Map: Enable Satellite Basemap',
                'description' => 'Allow Esri satellite map in switcher',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_show_weather_box' => [
                'name'        => 'Live Map: Show Weather Box',
                'description' => 'Show weather controls on map',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_weather_proxy_enabled' => [
                'name'        => 'Live Map: Weather Proxy Enabled',
                'description' => 'Serve weather tiles through phpVMS proxy',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_owm_api_key' => [
                'name'        => 'Live Map: OpenWeatherMap API Key',
                'description' => 'Used by weather proxy, kept server-side when proxy is enabled',
                'type'        => 'string',
                'default'     => '',
            ],
            'acars.livemap_weather_default_layer' => [
                'name'        => 'Live Map: Default Weather Layer',
                'description' => 'Default active weather layer at map load',
                'type'        => 'string',
                'default'     => 'combo',
            ],
            'acars.livemap_weather_default_opacity' => [
                'name'        => 'Live Map: Default Weather Opacity',
                'description' => 'Default weather opacity (0.2 - 1.0)',
                'type'        => 'float',
                'default'     => 1,
            ],
            'acars.livemap_show_network_box' => [
                'name'        => 'Live Map: Show Network Box',
                'description' => 'Show network controls on map',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_default_network_vatsim' => [
                'name'        => 'Live Map: VATSIM Enabled by Default',
                'description' => 'Enable VATSIM network at load',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_default_network_ivao' => [
                'name'        => 'Live Map: IVAO Enabled by Default',
                'description' => 'Enable IVAO network at load',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_default_show_pilots' => [
                'name'        => 'Live Map: Show Pilots by Default',
                'description' => 'Enable pilot layer at load',
                'type'        => 'bool',
                'default'     => false,
            ],
            'acars.livemap_default_show_controllers' => [
                'name'        => 'Live Map: Show Controllers by Default',
                'description' => 'Enable controller layer at load',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_default_show_sectors' => [
                'name'        => 'Live Map: Show Sectors by Default',
                'description' => 'Enable sector layer at load',
                'type'        => 'bool',
                'default'     => false,
            ],
            'acars.livemap_default_follow_flight' => [
                'name'        => 'Live Map: Follow Flight by Default',
                'description' => 'Enable follow-flight mode at load',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_mobile_show_flights_button' => [
                'name'        => 'Live Map: Mobile Show Flights Button',
                'description' => 'Show the mobile flights toggle button',
                'type'        => 'bool',
                'default'     => true,
            ],
            'acars.livemap_mobile_flights_open' => [
                'name'        => 'Live Map: Mobile Flights Panel Open',
                'description' => 'Open flights panel by default on mobile',
                'type'        => 'bool',
                'default'     => false,
            ],
            'acars.livemap_mobile_weather_open' => [
                'name'        => 'Live Map: Mobile Weather Open',
                'description' => 'Open weather panel by default on mobile',
                'type'        => 'bool',
                'default'     => false,
            ],
            'acars.livemap_mobile_network_open' => [
                'name'        => 'Live Map: Mobile Network Open',
                'description' => 'Open network panel by default on mobile',
                'type'        => 'bool',
                'default'     => false,
            ],
            'acars.livemap_color_flights_header_start' => [
                'name'        => 'Live Map: Flights Header Gradient Start',
                'description' => 'Top flights panel header gradient start color',
                'type'        => 'string',
                'default'     => '#1A2A4A',
            ],
            'acars.livemap_color_flights_header_end' => [
                'name'        => 'Live Map: Flights Header Gradient End',
                'description' => 'Top flights panel header gradient end color',
                'type'        => 'string',
                'default'     => '#243B6A',
            ],
            'acars.livemap_color_weather_header' => [
                'name'        => 'Live Map: Weather Header Color',
                'description' => 'Weather box title background color',
                'type'        => 'string',
                'default'     => '#1A2E4A',
            ],
            'acars.livemap_color_network_header' => [
                'name'        => 'Live Map: Network Header Color',
                'description' => 'Network box title background color',
                'type'        => 'string',
                'default'     => '#1A2E4A',
            ],
            'acars.livemap_color_box_background' => [
                'name'        => 'Live Map: Box Background Color',
                'description' => 'Background color for weather/network/flights body',
                'type'        => 'string',
                'default'     => '#FFFFFF',
            ],
            'acars.livemap_color_mobile_button' => [
                'name'        => 'Live Map: Mobile Flights Button Color',
                'description' => 'Background color for the mobile Flights toggle button',
                'type'        => 'string',
                'default'     => '#1A2A4A',
            ],
            'acars.livemap_color_mobile_button_active' => [
                'name'        => 'Live Map: Mobile Flights Button Active Color',
                'description' => 'Background color for active mobile Flights toggle button',
                'type'        => 'string',
                'default'     => '#243B6A',
            ],
        ];
    }

    private function normalizeHexColor(?string $value, string $default): string
    {
        $candidate = strtoupper(trim((string) $value));
        if (preg_match('/^#[0-9A-F]{6}$/', $candidate) === 1) {
            return $candidate;
        }

        return strtoupper($default);
    }

    private function persistSetting(
        string $key,
        $value,
        string $type,
        string $name,
        string $description = ''
    ): void {
        $id = Setting::formatKey($key);
        $setting = Setting::query()->where('id', $id)->first();
        if (!$setting) {
            $setting = new Setting();
            $setting->id = $id;
            $setting->key = $key;
            $setting->group = self::GROUP;
            $setting->order = 0;
            $setting->options = '';
        }

        if ($type === 'bool' || $type === 'boolean') {
            $type = 'bool';
            $value = $value ? '1' : '0';
        } elseif ($type === 'float') {
            $value = number_format((float) $value, 2, '.', '');
        } else {
            $value = (string) $value;
        }

        $setting->name = $name;
        $setting->description = $description;
        $setting->type = $type;
        $setting->value = $value;
        $setting->save();

        $this->forgetSettingCache($key);
    }

    private function forgetSettingCache(string $key): void
    {
        $cache = config('cache.keys.SETTINGS');
        if (!is_array($cache) || empty($cache['key'])) {
            return;
        }

        Cache::forget($cache['key'].Setting::formatKey($key));
    }
}
