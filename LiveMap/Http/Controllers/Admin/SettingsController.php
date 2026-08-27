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
    private const KVP_PREFIX = 'livemap.';

    public function index()
    {
        $this->ensureDurableBackup();
        $this->altbestandVerstecken();

        return view('livemap::admin.index', [
            'settings'      => $this->currentSettings(),
            'layerOptions'  => $this->layerOptions(),
            'basemapOptions' => $this->basemapOptions(),
            'weatherProxyStatus' => $this->weatherProxyStatus(),
            'cartoStatus'        => $this->cartoStatus(),
            'acarsLiveTimeStatus' => $this->acarsLiveTimeStatus(),
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
            'carto_api_key'           => 'nullable|string|max:128',
            'ui_primary_color'           => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_accent_color'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_box_background'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $proxyEnabled = $request->boolean('weather_proxy_enabled');
        $currentOwmApiKey = trim((string) $this->lmGet('acars.livemap_owm_api_key', env('LIVEMAP_OWM_API_KEY', '')));
        $submittedOwmApiKey = trim((string) ($validated['owm_api_key'] ?? ''));
        $clearOwmApiKey = $request->boolean('owm_api_key_clear');
        $owmApiKey = $submittedOwmApiKey !== ''
            ? $submittedOwmApiKey
            : ($clearOwmApiKey ? '' : $currentOwmApiKey);

        if ($proxyEnabled && $owmApiKey === '') {
            return back()->withInput()->withErrors([
                'owm_api_key' => 'OpenWeatherMap API key is required when weather proxy is enabled.',
            ]);
        }

        // CARTO-Schluessel — dieselbe Mechanik wie beim Wetter-Schluessel:
        // leer heisst "behalten", nur das Haekchen loescht.
        $currentCartoKey = trim((string) $this->lmGet('acars.carto_api_key', env('CARTO_API_KEY', '')));
        $submittedCartoKey = trim((string) ($validated['carto_api_key'] ?? ''));
        $clearCartoKey = $request->boolean('carto_api_key_clear');
        $cartoKey = $submittedCartoKey !== ''
            ? $submittedCartoKey
            : ($clearCartoKey ? '' : $currentCartoKey);

        if ($cartoKey !== '' && $cartoKey !== $currentCartoKey) {
            $pruefung = $this->verifyCartoApiKey($cartoKey);
            if ($pruefung['valid'] === false) {
                return back()->withInput()->withErrors(['carto_api_key' => $pruefung['message']]);
            }
        }

        // ⚠ Ein abgelehnter Wetter-Schluessel darf das Speichern NICHT
        // verhindern.
        //
        // OpenWeatherMap gibt fuer einen frisch erzeugten Schluessel bis
        // zu zwei Stunden lang HTTP 401 zurueck — er ist richtig, nur noch
        // nicht freigeschaltet. Vorher brach das Speichern hier ab, und
        // der Betreiber konnte seinen korrekten Schluessel ueberhaupt
        // nicht hinterlegen. Am 27.08.2026 hat das eine fremde VA eine
        // Stunde gekostet: „keeps saying openweathermap rejected the key,
        // but it's definitely right" — zwanzig Minuten spaeter lief er.
        //
        // Also speichern und warnen, statt zu blockieren. Ob der
        // Schluessel wirkt, sagt ohnehin die Statusbox — und sie prueft
        // erneut, sobald sich der hinterlegte Wert aendert.
        $owmApiKeyChanged = $owmApiKey !== $currentOwmApiKey;
        $owmWarnung = '';
        if ($owmApiKey !== '' && $owmApiKeyChanged) {
            $verification = $this->verifyOwmApiKey($owmApiKey);
            if (!$verification['valid']) {
                $owmWarnung = $verification['message'];
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
            'acars.carto_api_key'                       => $cartoKey,
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
            $this->persistLiveMapSetting(
                $key,
                $payload[$key] ?? ($definition['default'] ?? ''),
                $definition['type'] ?? 'string',
                $definition,
            );
        }

        $meldung = 'Live Map settings saved.';
        if ($owmWarnung !== '') {
            $meldung .= ' — '.$owmWarnung;
        }

        return redirect('/admin/livemap')->with('status', $meldung);
    }

    private function currentSettings(): array
    {
        $values = [];
        foreach ($this->definitions() as $key => $definition) {
            $values[$key] = $this->lmGet($key, $definition['default'] ?? null);
        }

        return $values;
    }

    /**
     * Prueft, ob CARTO den Schluessel wirklich annimmt.
     *
     * # Warum das nicht ueber den Statuscode geht
     *
     * CARTO antwortet auf eine Kachelanfrage IMMER mit 200 — ohne
     * Schluessel, mit falschem Schluessel, mit leerem Schluessel. Auch die
     * Kopfzeilen unterscheiden sich nicht. Ein Tippfehler waere damit
     * unsichtbar, bis jemand die Karte aufmacht und das Wasserzeichen
     * sieht.
     *
     * Was sich unterscheidet, ist das BILD. Gemessen am 26.08.2026 an
     * derselben Kachel:
     *
     *     ohne Schluessel    18.917 B   md5 c4ff8359e505
     *     falscher Schluessel 18.917 B   md5 c4ff8359e505   (identisch)
     *     leerer Schluessel  18.917 B   md5 c4ff8359e505   (identisch)
     *     echter Schluessel  20.251 B   md5 78f84118df46   (anders)
     *
     * Ein nicht angenommener Schluessel liefert also byteweise dieselbe
     * Kachel wie gar keiner. Genau daran erkennt man ihn.
     *
     * # Wann NICHT geurteilt wird
     *
     * Zuerst wird die Kachel ohne Schluessel ZWEIMAL geholt. Sind schon
     * diese beiden verschieden, taugt der Vergleich nicht (CDN-Knoten,
     * geaenderte Kachel) — dann wird das Speichern nicht blockiert. Lieber
     * kein Urteil als ein falsches.
     */
    private function verifyCartoApiKey(string $schluessel): array
    {
        $adresse = 'https://a.basemaps.cartocdn.com/light_all/6/33/21.png';

        try {
            $ohne1 = Http::timeout(8)->get($adresse);
            $ohne2 = Http::timeout(8)->get($adresse);
            if (!$ohne1->successful() || !$ohne2->successful()) {
                return ['valid' => null, 'message' => ''];
            }
            $referenz = md5($ohne1->body());
            if ($referenz !== md5($ohne2->body())) {
                // Die Vergleichskachel ist nicht stabil — nicht urteilen.
                return ['valid' => null, 'message' => ''];
            }

            $mit = Http::timeout(8)->get($adresse, ['key' => $schluessel]);
            if (!$mit->successful()) {
                return ['valid' => null, 'message' => ''];
            }

            if (md5($mit->body()) === $referenz) {
                return [
                    'valid'   => false,
                    'message' => 'CARTO liefert mit diesem Schlüssel dieselbe Kachel wie ohne — '
                        .'er wird also nicht angenommen. Bitte den Wert aus der CARTO-Mail prüfen '
                        .'(Format: cb1_…). Die Karte trägt sonst weiterhin das Wasserzeichen.',
                ];
            }

            return ['valid' => true, 'message' => ''];
        } catch (\Throwable $e) {
            // CARTO nicht erreichbar: kein Grund, das Speichern zu verhindern.
            return ['valid' => null, 'message' => ''];
        }
    }

    /**
     * Ob ein CARTO-Schluessel hinterlegt ist — mehr braucht die Ansicht nicht.
     *
     * Der Wert selbst wird NICHT zurueckgegeben. Ein Schluessel, der im
     * Eingabefeld vorausgefuellt steht, landet in jedem Browser-Cache und
     * in jedem Bildschirmfoto; das Feld bleibt deshalb leer und "leer"
     * heisst "behalten".
     */
    private function cartoStatus(): array
    {
        // Woher der Schluessel kommt, gehoert in die Anzeige.
        //
        // Vorher stand dort nur "API Key: SET" — und genau das war
        // irrefuehrend: Der Schluessel lag in der `.env`, das Feld war leer,
        // und warum das Loeschen-Haekchen scheinbar nichts tat, war von der
        // Seite aus nicht zu erkennen. Ein Status, der den Zustand nennt aber
        // nicht die Quelle, beantwortet die naechste Frage nicht.
        //
        // Der Wachwert trennt "keine Zeile" von "Zeile, aber leer". Nur das
        // erste ist der Erstzustand, in dem die `.env` greift; das zweite ist
        // ein bewusstes Loeschen und schlaegt sie.
        $wachwert = '__LIVEMAP_KEINE_ZEILE__';
        $ausEinstellung = $this->lmGet('acars.carto_api_key', $wachwert);
        $ausEnv = trim((string) env('CARTO_API_KEY', ''));

        if ($ausEinstellung === $wachwert) {
            $key = $ausEnv;
            $quelle = $ausEnv !== '' ? 'env' : 'none';
        } else {
            $key = trim((string) $ausEinstellung);
            $quelle = $key !== '' ? 'setting' : 'none';
        }

        // Ein geleerter Eintrag, waehrend die `.env` noch etwas enthaelt: Das
        // ist kein Fehler, aber es gehoert gesagt — sonst sucht jemand die
        // `.env`-Zeile und wundert sich, dass sie wirkungslos ist.
        $envUeberstimmt = $key === '' && $ausEnv !== '' && $ausEinstellung !== $wachwert;

        $basemap = (string) $this->lmGet('acars.livemap_default_basemap', 'positron');
        $cartoInUse = in_array($basemap, ['positron', 'dark'], true);

        if ($key === '') {
            return [
                'hasApiKey'      => false,
                'accepted'       => null,
                'checkedAt'      => null,
                'quelle'         => $quelle,
                'envUeberstimmt' => $envUeberstimmt,
                'cartoInUse'     => $cartoInUse,
                'badgeClass' => $cartoInUse ? 'danger' : 'info',
                'title'      => $cartoInUse ? 'CARTO Key Missing' : 'No CARTO Key (not needed)',
                'message'    => $cartoInUse
                    ? 'The default basemap is a CARTO style, so every tile carries the '
                        .'"API KEY REQUIRED" watermark. Paste a key below — free up to 5 million '
                        .'tiles per month at carto.com/basemaps/apikey.'
                    : 'The default basemap is not a CARTO style, so no key is required. '
                        .'You still need one if users switch to Carto Light or Carto Dark.',
            ];
        }

        // Das Urteil wird gespeichert, nicht bei jedem Seitenaufruf neu geholt.
        //
        // Eine Pruefung kostet drei Anfragen an CARTO. Auf Installationen ohne
        // Anwendungs-Cache (`CACHE_DRIVER=null`, bei phpVMS nicht selten)
        // liefe das bei JEDEM Oeffnen der Seite — dafuer ist es zu teuer.
        //
        // Der Fingerabdruck haengt am Schluessel: Wird ein anderer eingetragen
        // — auch ueber die `.env`, an der Oberflaeche vorbei — passt er nicht
        // mehr, und es wird neu geprueft.
        $fingerabdruck = substr(hash('sha256', $key), 0, 16);
        $abgelegt = json_decode((string) $this->lmGet('acars.carto_api_key_checked', ''), true);
        $passt = is_array($abgelegt) && ($abgelegt['fp'] ?? null) === $fingerabdruck;

        if (!$passt) {
            $pruefung = $this->verifyCartoApiKey($key);
            $abgelegt = [
                'fp' => $fingerabdruck,
                'ok' => $pruefung['valid'],
                'at' => now()->toIso8601String(),
            ];
            // ⚠ NICHT `Setting::updateOrCreate(['id' => …])`.
            //
            // Die Tabelle schluesselt auf einen ABGELEITETEN Wert
            // (`Setting::formatKey()`), und `id` ist nicht fuellbar. Ein
            // `updateOrCreate` auf den rohen Namen legt deshalb eine Zeile
            // OHNE id an — und die zweite davon scheitert am
            // Primaerschluessel:
            //
            //     Duplicate entry '' for key 'PRIMARY'
            //
            // Sichtbar wurde das erst, als Thomas den Schluessel entfernte:
            // Beim naechsten Aufruf der Einstellungsseite kam ein 500er.
            // Der Modul-eigene Weg macht es richtig.
            $this->persistDurableSetting(
                'acars.carto_api_key_checked',
                (string) json_encode($abgelegt),
                'text',
                [
                    'name'        => 'CARTO key check result',
                    'description' => 'Cached verdict of the last CARTO key verification.',
                ]
            );
        }

        $ok = $abgelegt['ok'] ?? null;

        if ($ok === true) {
            return [
                'hasApiKey'  => true,
                'accepted'   => true,
                'checkedAt'  => $abgelegt['at'] ?? null,
                'quelle'         => $quelle,
                'envUeberstimmt' => $envUeberstimmt,
                'cartoInUse' => $cartoInUse,
                'badgeClass' => 'success',
                'title'      => 'CARTO Key OK',
                'message'    => 'CARTO returns a clean tile for this key — the watermark is gone.',
            ];
        }

        if ($ok === false) {
            return [
                'hasApiKey'  => true,
                'accepted'   => false,
                'checkedAt'  => $abgelegt['at'] ?? null,
                'quelle'         => $quelle,
                'envUeberstimmt' => $envUeberstimmt,
                'cartoInUse' => $cartoInUse,
                'badgeClass' => 'danger',
                'title'      => 'CARTO Key Not Accepted',
                'message'    => 'CARTO returns the same tile as without a key, so it is being ignored. '
                    .'Check the value against the CARTO email (format: cb1_…). '
                    .'The map still carries the watermark.',
            ];
        }

        return [
            'hasApiKey'      => true,
            'accepted'       => null,
            'checkedAt'      => $abgelegt['at'] ?? null,
            'quelle'         => $quelle,
            'envUeberstimmt' => $envUeberstimmt,
            'cartoInUse'     => $cartoInUse,
            'badgeClass'     => 'warning',
            'title'      => 'CARTO Key Set — Not Verified',
            'message'    => 'A key is stored, but CARTO could not be reached to confirm it is accepted. '
                .'This says nothing about the key itself; it will be checked again later.',
        ];
    }

    private function layerOptions(): array
    {
        return [
            'none'   => 'None',
            'clouds' => 'Clouds',
            'radar'  => 'Radar / Precipitation',
            'storms' => 'Pressure (storm proxy)',
            'wind'   => 'Wind',
            'temp'   => 'Temperature',
            'combo'  => 'Combo (Clouds + Radar + Pressure)',
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
        $proxyEnabled = (bool) $this->lmGet('acars.livemap_weather_proxy_enabled', true);
        $apiKey = trim((string) $this->lmGet('acars.livemap_owm_api_key', env('LIVEMAP_OWM_API_KEY', '')));
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

    private function acarsLiveTimeStatus(): array
    {
        $raw = setting('acars.live_time', 0);
        $value = is_numeric($raw) ? (int) $raw : 0;

        return [
            'value' => $value,
            'isSafe' => $value >= 1,
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
            $action = 'Use supported layers (clouds, radar, pressure, wind, temp) and verify URL format.';
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
                'message' => 'OpenWeatherMap did not accept this key yet (HTTP '.$status.'). '
                    .'The key was saved anyway: a newly created OpenWeatherMap key can take up to two hours '
                    .'to activate, and returns 401 until then. If it still fails after that, check the key.',
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
            // ⚠ Ohne diesen Eintrag wird der Schluessel NIE gespeichert.
            //
            // `update()` legt zwar jeden Wert in `$payload` ab, geschrieben
            // wird danach aber ueber `foreach ($this->definitions() …)` —
            // was hier fehlt, faellt bei jedem Speichern lautlos heraus.
            //
            // Genau das ist Thomas am 26.08.2026 aufgefallen: Das Haekchen
            // „Remove stored key" hatte keine Wirkung. Der Wert kam nie in
            // die Datenbank, also las jede Seite weiter die `.env` — der
            // Schluessel liess sich nicht loeschen, und ein neuer liesse
            // sich genauso wenig eintragen.
            'acars.carto_api_key' => [
                'name'        => 'Live Map: CARTO Basemaps API Key',
                'description' => 'Required since 26 Aug 2026 for the CARTO light/dark basemaps',
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

    private function persistLiveMapSetting(string $legacyKey, $value, string $type, ?array $definition = null): void
    {
        if ($type === 'bool' || $type === 'boolean') {
            $value = $value ? '1' : '0';
        } elseif ($type === 'float') {
            $value = number_format((float) $value, 2, '.', '');
        } else {
            $value = (string) $value;
        }

        $stringValue = (string) $value;

        // Durable DB-backed storage is the single source of truth. The phpVMS
        // `settings` table lives in the database and is backed up with the rest
        // of the site, unlike storage/app/kvp.json which gets wiped by deploys,
        // permission resets, or Spatie Valuestore concurrent-write races.
        $this->persistDurableSetting($legacyKey, $stringValue, $type, $definition ?? ($this->definitions()[$legacyKey] ?? []));
    }

    private function lmGet(string $legacyKey, $default = null)
    {
        $sentinel = '__LIVEMAP_MISSING__';
        $settingValue = setting($legacyKey, $sentinel);
        if ($settingValue !== $sentinel) {
            return $settingValue;
        }

        // One-time fall-through for users upgrading from v4.6.1-4.6.3 whose
        // values still live in storage/app/kvp.json. ensureDurableBackup() will
        // promote them into the DB on the next admin page load.
        $kvpValue = kvp($this->toKvpKey($legacyKey), $sentinel);
        if ($kvpValue !== $sentinel) {
            return $kvpValue;
        }

        return $default;
    }

    private function persistDurableSetting(string $legacyKey, string $value, string $type, array $definition): void
    {
        try {
            $id = Setting::formatKey($legacyKey);
            $name = (string) ($definition['name'] ?? $legacyKey);
            $description = (string) ($definition['description'] ?? '');
            $default = (string) ($definition['default'] ?? '');

            $setting = Setting::query()->where('id', $id)->first();
            if ($setting) {
                $setting->value = $value;
                $setting->type = $this->mapDefinitionTypeToSettingType($type);
                $setting->save();
            } else {
                Setting::query()->insert([
                    'id'          => $id,
                    'key'         => $legacyKey,
                    'value'       => $value,
                    'default'     => $default,
                    'name'        => $name,
                    'description' => $description,
                    'type'        => $this->mapDefinitionTypeToSettingType($type),
                    'group'       => 'livemap_module',
                    'options'     => '',
                    'order'       => 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $this->forgetSettingCache($legacyKey);
        } catch (\Throwable $e) {
            // Swallow: worst case is the DB row is missing; kvp still holds the value.
        }
    }

    /**
     * Der `type` der phpVMS-Zeile — immer `hidden`.
     *
     * # Warum die Einstellungen doppelt auftauchten
     *
     * Die Einstellungsseite des phpVMS-Kerns zeigt ALLES, was nicht
     * `hidden` ist:
     *
     * ```php
     * $settings = Setting::where('type', '!=', 'hidden')->orderBy('order')->get();
     * $settings = $settings->groupBy('group');
     * ```
     *
     * Sie filtert nach dem TYP, nicht nach der Gruppe. Unsere 30 Zeilen
     * standen damit als eigener Abschnitt „livemap_module" mitten in den
     * Kern-Einstellungen — und zusaetzlich auf unserer eigenen Seite.
     * Zwei Formulare fuer dieselben Werte, und das im Kern ohne unsere
     * Pruefungen: Ein CARTO- oder Wetter-Schluessel, dort eingetragen,
     * wird nie gegen den Anbieter geprueft.
     *
     * Gemeldet von zwei VAs am 27.08.2026; es bestand vermutlich seit
     * v4.6.5, als die Einstellungen in die Datenbank wanderten.
     *
     * # Warum `hidden` hier gefahrlos ist
     *
     * `SettingRepository::retrieve()` wandelt anhand des Typs um und
     * gibt bei `hidden` den ROHEN Text zurueck. Das waere gefaehrlich,
     * wenn wir „true"/„false" speicherten — beides sind wahre
     * Zeichenketten.
     *
     * Wir speichern aber normalisiert (siehe `persistLiveMapSetting`):
     * Wahrheitswerte als `'1'`/`'0'`, Kommazahlen ueber `number_format`.
     * `'0'` ist in PHP falsch, `'1'` wahr, `'0.60'` rechnet sich wie
     * eine Zahl — der rohe Text verhaelt sich also genau wie der
     * umgewandelte Wert. Am Bestand nachgesehen: 11 Zeilen `'1'`,
     * 6 Zeilen `'0'`, keine andere Schreibweise.
     *
     * ⚠ Wer hier je etwas anderes als `'1'`/`'0'` speichert, muss diese
     * Entscheidung neu pruefen.
     */
    private function mapDefinitionTypeToSettingType(string $type): string
    {
        unset($type);

        return 'hidden';
    }

    /**
     * Altbestand einsammeln: Zeilen dieses Moduls auf `hidden` setzen.
     *
     * Ohne das blieben bestehende Installationen doppelt, bis jede
     * einzelne Einstellung einmal neu gespeichert wird. Laeuft beim
     * Oeffnen der Modulseite, ist billig (ein UPDATE ueber hoechstens 30
     * Zeilen) und idempotent.
     */
    private function altbestandVerstecken(): void
    {
        try {
            Setting::query()
                ->where('group', 'livemap_module')
                ->where('type', '!=', 'hidden')
                ->update(['type' => 'hidden']);
        } catch (\Throwable $e) {
            // Kosmetik. Schlaegt es fehl, stehen die Werte weiterhin
            // doppelt — aber die Seite muss deshalb nicht scheitern.
        }
    }

    private function toKvpKey(string $legacyKey): string
    {
        $suffix = preg_replace('/^acars\.livemap_/', '', $legacyKey);
        if (!is_string($suffix) || trim($suffix) === '') {
            $suffix = str_replace('.', '_', $legacyKey);
        }

        return self::KVP_PREFIX.$suffix;
    }

    /**
     * One-way migration: if a value only exists in the legacy kvp.json, copy it
     * into the durable DB row so future reads go through the settings table.
     * Runs on every admin page load — idempotent, no destructive cleanup.
     */
    private function ensureDurableBackup(): void
    {
        $sentinel = '__LIVEMAP_MISSING__';

        foreach ($this->definitions() as $legacyKey => $definition) {
            $settingValue = setting($legacyKey, $sentinel);
            if ($settingValue !== $sentinel) {
                continue;
            }

            $kvpValue = kvp($this->toKvpKey($legacyKey), $sentinel);
            if ($kvpValue === $sentinel) {
                continue;
            }

            $this->persistDurableSetting(
                $legacyKey,
                (string) $kvpValue,
                $definition['type'] ?? 'string',
                $definition,
            );
        }
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
