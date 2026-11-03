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
                            $lmSetting('acars.livemap_old_style', false),
                            $lmSetting('acars.livemap_show_top_flights_panel', true)
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
                    $flightsHeaderEnd = $lmHexColor($lmSetting('acars.livemap_color_flights_header_end', '#243B6A'), '#243B6A');
                    $weatherHeaderColor = $lmHexColor($lmSetting('acars.livemap_color_weather_header', '#1A2E4A'), '#1A2E4A');
                    $networkHeaderColor = $lmHexColor($lmSetting('acars.livemap_color_network_header', '#1A2E4A'), '#1A2E4A');
                    $boxBackgroundColor = $lmHexColor($lmSetting('acars.livemap_color_box_background', '#FFFFFF'), '#FFFFFF');
                    $mobileButtonColor = $lmHexColor($lmSetting('acars.livemap_color_mobile_button', '#1A2A4A'), '#1A2A4A');
                    $mobileButtonActiveColor = $lmHexColor($lmSetting('acars.livemap_color_mobile_button_active', '#243B6A'), '#243B6A');
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
