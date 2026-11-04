<!-- lm3:anfang -->
<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 mb-3">
        <div class="card border mb-0">
            <div class="card-body p-0 position-relative">
                {{-- Admin settings keys (phpVMS settings table / admin):
                     acars.livemap_old_style
                     acars.livemap_show_top_flights_panel
                     acars.livemap_flights_panel (top|bottom|off, 5.1)
                     acars.livemap_default_basemap
                     acars.livemap_show_basemap_switcher
                     acars.livemap_enable_satellite
                     acars.livemap_show_weather_box
                     acars.livemap_weather_proxy_enabled
                     acars.livemap_owm_api_key
                     acars.livemap_weather_default_layer (none|clouds|radar|storms|wind|temp|combo)
                     acars.livemap_weather_default_opacity (0.2 - 1.0)
                     acars.livemap_show_network_box
                     acars.livemap_default_network_vatsim
                     acars.livemap_default_network_ivao
                     acars.livemap_default_show_pilots
                     acars.livemap_default_show_controllers
                     acars.livemap_default_show_sectors
                     acars.livemap_default_follow_flight
                     acars.livemap_mobile_show_flights_button
                     acars.livemap_mobile_flights_open
                     acars.livemap_mobile_weather_open
                     acars.livemap_mobile_network_open
                     acars.livemap_color_flights_header_start
                     acars.livemap_color_flights_header_end
                     acars.livemap_color_weather_header
                     acars.livemap_color_network_header
                     acars.livemap_color_box_background
                     acars.livemap_color_mobile_button
                     acars.livemap_color_mobile_button_active
                --}}
                @php
                    $lmBool = function ($value, $default = false) {
                        if (is_bool($value)) return $value;
                        if ($value === null) return $default;
                        $v = strtolower(trim((string) $value));
                        if ($v === '') return $default;
                        return in_array($v, ['1', 'true', 'yes', 'on'], true);
                    };

                    $lmString = function ($value, $default = '') {
                        if ($value === null) return $default;
                        $v = trim((string) $value);
                        return $v === '' ? $default : $v;
                    };
                    $lmHexColor = function ($value, $default = '#FFFFFF') {
                        $raw = strtoupper(trim((string) ($value ?? '')));
                        if (preg_match('/^#[0-9A-F]{6}$/', $raw) === 1) return $raw;
                        return strtoupper($default);
                    };
                    $lmHexToRgba = function ($hex, $alpha = 1.0) {
                        $hex = ltrim((string) $hex, '#');
                        if (strlen($hex) !== 6) return 'rgba(255,255,255,'.(float) $alpha.')';
                        $r = hexdec(substr($hex, 0, 2));
                        $g = hexdec(substr($hex, 2, 2));
                        $b = hexdec(substr($hex, 4, 2));
                        return 'rgba('.$r.','.$g.','.$b.','.(float) $alpha.')';
                    };
                    $lmSetting = function (string $legacyKey, $default = null) {
                        $sentinel = '__LIVEMAP_MISSING__';
                        $settingValue = setting($legacyKey, $sentinel);
                        if ($settingValue !== $sentinel) {
                            return $settingValue;
                        }

                        // Legacy fall-through for pre-v4.6.4 installs whose values
                        // still sit in storage/app/kvp.json. The admin page promotes
                        // these into the DB on its next load.
                        $suffix = preg_replace('/^acars\.livemap_/', '', $legacyKey);
                        if (!is_string($suffix) || trim($suffix) === '') {
                            $suffix = str_replace('.', '_', $legacyKey);
                        }
                        $kvpValue = kvp('livemap.'.$suffix, $sentinel);
                        if ($kvpValue !== $sentinel) {
                            return $kvpValue;
                        }

                        return $default;
                    };

                    // Sprache der Bedienelemente: Deutsch, wenn die Seite deutsch ist.
                    $lmDe = str_starts_with(strtolower((string) app()->getLocale()), 'de');

                    // 5.1: Lage der Flugtafel (oben ueber der Karte / unten als Tabelle /
                    // aus). Fehlt die Einstellung (Update von 4.7.x), gelten die Altschluessel.
                    $lmTafelLage = strtolower($lmString($lmSetting('acars.livemap_flights_panel', ''), ''));
                    if (!in_array($lmTafelLage, ['top', 'bottom', 'off'], true)) {
                        $lmTafelLage = \Modules\LiveMap\Http\Controllers\Admin\SettingsController::tafelLageAusAlt(
                            $lmSetting('acars.livemap_old_style', null),
                            $lmSetting('acars.livemap_show_top_flights_panel', null)
                        );
                    }
                    $oldStyle = $lmTafelLage === 'off';
                    $showTopFlights = $lmTafelLage !== 'off';
                    $defaultBasemap = strtolower($lmString($lmSetting('acars.livemap_default_basemap', 'positron'), 'positron'));
                    if (!in_array($defaultBasemap, ['positron', 'osm', 'dark', 'satellite'], true)) {
                        $defaultBasemap = 'positron';
                    }

                    $weatherProxyEnabled = $lmBool($lmSetting('acars.livemap_weather_proxy_enabled', true), true);
                    $weatherDefault = strtolower($lmString($lmSetting('acars.livemap_weather_default_layer', 'combo'), 'combo'));
                    if (!in_array($weatherDefault, ['none', 'clouds', 'radar', 'storms', 'wind', 'temp', 'combo'], true)) {
                        $weatherDefault = 'combo';
                    }
                    $weatherOpacity = (float) $lmSetting('acars.livemap_weather_default_opacity', 1);
                    if (!is_finite($weatherOpacity) || $weatherOpacity < 0.2 || $weatherOpacity > 1) $weatherOpacity = 1;
                    $storedOwmApiKey = $lmString($lmSetting('acars.livemap_owm_api_key', env('LIVEMAP_OWM_API_KEY', '')), '');
                    $owmApiKeyForClient = $weatherProxyEnabled ? '' : $storedOwmApiKey;
                    // If no key is configured we must not request any weather tile —
                    // without this flag the frontend still fires hundreds of proxy
                    // requests that just return blank SVGs and slow the whole page down.
                    $weatherAvailable = $storedOwmApiKey !== '' || (!$weatherProxyEnabled && $owmApiKeyForClient !== '');
                    if (!$weatherAvailable) {
                        $weatherDefault = 'none';
                    }
                    $flightsHeaderStart = $lmHexColor($lmSetting('acars.livemap_color_flights_header_start', '#1A2A4A'), '#1A2A4A');
                    $weatherHeaderColor = $lmHexColor($lmSetting('acars.livemap_color_weather_header', '#1A2E4A'), '#1A2E4A');
                    $networkHeaderColor = $lmHexColor($lmSetting('acars.livemap_color_network_header', '#1A2E4A'), '#1A2E4A');
                    $boxBackgroundColor = $lmHexColor($lmSetting('acars.livemap_color_box_background', '#FFFFFF'), '#FFFFFF');
                    $mobileButtonColor = $lmHexColor($lmSetting('acars.livemap_color_mobile_button', '#1A2A4A'), '#1A2A4A');
                    $mobileButtonActiveColor = $lmHexColor($lmSetting('acars.livemap_color_mobile_button_active', '#243B6A'), '#243B6A');
                    // 5.1: Akzent hell (Feld „Accent colour“) und dunkel einstellbar; die
                    // Zeilen-Toenungen und die Schrift auf dem Akzent leiten sich davon ab,
                    // damit z. B. eine gruene VA-Seite kein Blau mehr behaelt.
                    $lmAkzentDunkel = $lmHexColor($lmSetting('acars.livemap_color_accent_dark', '#7CB8F0'), '#7CB8F0');
                    $lmSchriftAuf = function ($hex) {
                        // WCAG-Kontrast: Weiss oder Dunkelblau, was besser lesbar ist.
                        $lin = function ($c) { $c = $c / 255; return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4; };
                        $h = ltrim((string) $hex, '#');
                        $l = 0.2126 * $lin(hexdec(substr($h, 0, 2))) + 0.7152 * $lin(hexdec(substr($h, 2, 2))) + 0.0722 * $lin(hexdec(substr($h, 4, 2)));
                        $gegenWeiss = 1.05 / ($l + 0.05);
                        $gegenDunkel = ($l + 0.05) / (0.0137 + 0.05); // #0B1F3A
                        return $gegenWeiss >= $gegenDunkel ? '#FFFFFF' : '#0B1F3A';
                    };
                    // Helle Zeilen-Toenung: kraeftige Fassung des Akzents (gleicher Farbton,
                    // Saettigung >= 70 %, Helligkeit 55 %). Ein dunkler Akzent wie Marineblau
                    // wuerde sonst grau toenen; so bleibt GSG beim bisherigen Hellblau.
                    $lmToenung = function ($hex) {
                        $h = ltrim((string) $hex, '#');
                        $r = hexdec(substr($h, 0, 2)) / 255; $g = hexdec(substr($h, 2, 2)) / 255; $b = hexdec(substr($h, 4, 2)) / 255;
                        $max = max($r, $g, $b); $min = min($r, $g, $b); $d = $max - $min; $l = ($max + $min) / 2;
                        $s = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));
                        $hue = 0;
                        if ($d != 0) {
                            if ($max == $r) $hue = 60 * fmod((($g - $b) / $d), 6);
                            elseif ($max == $g) $hue = 60 * ((($b - $r) / $d) + 2);
                            else $hue = 60 * ((($r - $g) / $d) + 4);
                        }
                        if ($hue < 0) $hue += 360;
                        $s = $d == 0 ? 0 : max($s, 0.7); $l = 0.55;
                        $c = (1 - abs(2 * $l - 1)) * $s; $x = $c * (1 - abs(fmod($hue / 60, 2) - 1)); $m = $l - $c / 2;
                        [$r1, $g1, $b1] = $hue < 60 ? [$c, $x, 0] : ($hue < 120 ? [$x, $c, 0] : ($hue < 180 ? [0, $c, $x] : ($hue < 240 ? [0, $x, $c] : ($hue < 300 ? [$x, 0, $c] : [$c, 0, $x]))));
                        return sprintf('#%02X%02X%02X', round(($r1 + $m) * 255), round(($g1 + $m) * 255), round(($b1 + $m) * 255));
                    };
                    $boxBackgroundRgba = $lmHexToRgba($boxBackgroundColor, 0.97);
                    $mobileButtonRgba = $lmHexToRgba($mobileButtonColor, 0.92);
                    $mobileButtonActiveRgba = $lmHexToRgba($mobileButtonActiveColor, 0.92);

                    // CARTO verlangt seit dem 26.08.2026 einen Schluessel fuer seine
                    // Basiskarten — frei bis fuenf Millionen Kacheln im Monat. Die
                    // RASTER-Kacheln (und nur die) tragen seither ein Wasserzeichen
                    // "API KEY REQUIRED" quer ueber die Karte; genau die benutzen die
                    // Stile "Carto Light" und "Carto Dark" hier.
                    //
                    // Ohne hinterlegten Schluessel bleibt alles wie bisher — die Karte
                    // laedt, sie traegt nur das Wasserzeichen. Kein Bruch bei einem
                    // Update, keine Pflichtangabe.
                    //
                    // Der Name ist bewusst NICHT `livemap_` vorangestellt: Es ist der
                    // Schluessel des ganzen Auftritts. Andere Bausteine, die CARTO
                    // zeichnen, lesen denselben Wert.
                    $cartoApiKey = $lmString($lmSetting('acars.carto_api_key', env('CARTO_API_KEY', '')), '');
                    // 5.1: Ohne Schluessel traegt jede CARTO-Kachel „API KEY REQUIRED“
                    // (gemessen test.kant.ovh 03.11.2026) — fuer eine frische Installation
                    // die erste Huerde. Dann die freien OSM-Kacheln.
                    if ($cartoApiKey === '' && in_array($defaultBasemap, ['positron', 'dark'], true)) {
                        $defaultBasemap = 'osm';
                    }
                    $lmThemeModus = strtolower($lmString($lmSetting('acars.livemap_theme_mode', 'auto'), 'auto'));
                    if (!in_array($lmThemeModus, ['auto', 'light', 'dark'], true)) {
                        $lmThemeModus = 'auto';
                    }
                    // Hoehe: Fenster minus Seitenkopf, begrenzt. Feste 800 px schnitten
                    // die Karte bei 720 px Fensterhoehe ab.
                    $lmMinHoehe = max(320, min(2000, (int) $lmSetting('acars.livemap_min_height', 420)));
                    $lmMaxHoehe = max($lmMinHoehe, min(3000, (int) $lmSetting('acars.livemap_max_height', 1000)));

                    $liveMapUiConfig = [
                        // Top flights panel ("old style" = hidden)
                        'oldStyle'              => $oldStyle,
                        'showTopFlightsPanel'   => $showTopFlights,
                        'tafelLage'             => $lmTafelLage,
                        'defaultBasemap'        => $defaultBasemap,
                        'cartoApiKey'           => $cartoApiKey,
                        'showBasemapSwitcher'   => $lmBool($lmSetting('acars.livemap_show_basemap_switcher', true), true),
                        'enableSatelliteBasemap'=> $lmBool($lmSetting('acars.livemap_enable_satellite', true), true),
                        // Weather box + defaults
                        'showWeatherBox'        => $lmBool($lmSetting('acars.livemap_show_weather_box', true), true),
                        'weatherProxyEnabled'   => $weatherProxyEnabled,
                        'weatherProxyBaseUrl'   => rtrim(url('/livemap/weather-tile'), '/'),
                        'owmApiKey'             => $owmApiKeyForClient,
                        'weatherDefaultLayer'   => $weatherDefault,
                        'weatherDefaultOpacity' => round($weatherOpacity, 2),
                        'weatherAvailable'      => $weatherAvailable,
                        // Network box + defaults
                        'showNetworkBox'        => $lmBool($lmSetting('acars.livemap_show_network_box', true), true),
                        'defaultVatsimEnabled'  => $lmBool($lmSetting('acars.livemap_default_network_vatsim', true), true),
                        'defaultIvaoEnabled'    => $lmBool($lmSetting('acars.livemap_default_network_ivao', true), true),
                        'defaultShowPilots'     => $lmBool($lmSetting('acars.livemap_default_show_pilots', false), false),
                        'defaultShowControllers'=> $lmBool($lmSetting('acars.livemap_default_show_controllers', true), true),
                        'defaultShowSectors'    => $lmBool($lmSetting('acars.livemap_default_show_sectors', false), false),
                        'defaultFollowFlight'   => $lmBool($lmSetting('acars.livemap_default_follow_flight', true), true),
                        // Mobile behavior
                        'mobileShowFlightsButton' => $lmBool($lmSetting('acars.livemap_mobile_show_flights_button', true), true),
                        'mobileFlightsOpen'       => $lmBool($lmSetting('acars.livemap_mobile_flights_open', false), false),
                        'mobileWeatherOpen'       => $lmBool($lmSetting('acars.livemap_mobile_weather_open', false), false),
                        'mobileNetworkOpen'       => $lmBool($lmSetting('acars.livemap_mobile_network_open', false), false),
                        'mobileButtonInactive'    => $mobileButtonRgba,
                        'mobileButtonActive'      => $mobileButtonActiveRgba,
                        // v5: Fluege kommen ueber das Modul (Quelle laut Einstellung).
                        'flightsApi'              => rtrim(url('/livemap/api/fluege'), '/'),
                        'quelle'                  => class_exists(\Modules\LiveMap\Http\Controllers\LiveFlightsController::class)
                            ? \Modules\LiveMap\Http\Controllers\LiveFlightsController::quelle() : 'phpvms',
                        'refreshMs'               => 10000,
                        'sprache'                 => $lmDe ? 'de' : 'en',
                        'center'                  => [(float) ($center[0] ?? 50), (float) ($center[1] ?? 10)],
                        'zoom'                    => (int) ($zoom ?? 5),
                        // v5.1
                        'themeModus'              => $lmThemeModus,
                        'minHoehe'                => $lmMinHoehe,
                        'maxHoehe'                => $lmMaxHoehe,
                        'rufzeichenZeigen'        => $lmBool($lmSetting('acars.livemap_show_labels', true), true),
                    ];
                @endphp

                @include('livemap::partials.styles')
                @include('livemap::partials.markup')
            </div>
        </div>
    </div>
</div>
@include('livemap::partials.scripts')
<!-- lm3:ende -->
