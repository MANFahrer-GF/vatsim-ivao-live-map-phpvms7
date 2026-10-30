<!-- lm3:anfang -->
<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 mb-3">
        <div class="card border mb-0">
            <div class="card-body p-0 position-relative">
                {{-- Admin settings keys (phpVMS settings table / admin):
                     acars.livemap_old_style
                     acars.livemap_show_top_flights_panel
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

                    $oldStyle = $lmBool($lmSetting('acars.livemap_old_style', false), false);
                    $showTopFlights = $oldStyle
                        ? false
                        : $lmBool($lmSetting('acars.livemap_show_top_flights_panel', true), true);
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

                    $liveMapUiConfig = [
                        // Top flights panel ("old style" = hidden)
                        'oldStyle'              => $oldStyle,
                        'showTopFlightsPanel'   => $showTopFlights,
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
                    ];
                @endphp

                @php
                    $lmTheme = trim((string) setting('general.theme', 'SPTheme'));
                    if ($lmTheme === '') {
                        $lmTheme = 'SPTheme';
                    }

                    $lmStylesCandidates = [
                        'layouts.'.$lmTheme.'.widgets.live_map_styles',
                        'layouts.SPTheme.widgets.live_map_styles',
                        'layouts.Disposable_v3.widgets.live_map_styles',
                    ];
                    $lmScriptsCandidates = [
                        'layouts.'.$lmTheme.'.widgets.live_map_scripts',
                        'layouts.SPTheme.widgets.live_map_scripts',
                        'layouts.Disposable_v3.widgets.live_map_scripts',
                    ];

                    $lmStylesView = null;
                    foreach ($lmStylesCandidates as $lmCandidate) {
                        if (\Illuminate\Support\Facades\View::exists($lmCandidate)) {
                            $lmStylesView = $lmCandidate;
                            break;
                        }
                    }

                    $lmScriptsView = null;
                    foreach ($lmScriptsCandidates as $lmCandidate) {
                        if (\Illuminate\Support\Facades\View::exists($lmCandidate)) {
                            $lmScriptsView = $lmCandidate;
                            break;
                        }
                    }
                @endphp

                @if($lmStylesView)
                    @include($lmStylesView)
                @endif

                {{-- ══════════════════════════════════════════════════════════
                     LIVE-KARTE v5 (AeroACARS 3.0, Pflichtenheft P-11.11)
                     Links: Live-Fluege (Felder wie die Flugtafel der Webapp)
                     Rechts: Flugdetail (Felder wie das Flugdetail der Webapp)
                     Oben rechts: Werkzeuge (Netz, Wetter, Folgen)
                     Schmal (< 760 px Widget-Breite): Liste und Detail als
                     Blatt von unten. Die Breite misst das Skript am Widget,
                     nicht am Fenster — die Seitenleiste des Themes zaehlt mit.
                ══════════════════════════════════════════════════════════ --}}
                <div class="live-map-wrapper lm3" id="lm3" data-quelle="{{ $liveMapUiConfig['quelle'] }}">
                    <div id="map" role="region" aria-label="{{ $lmDe ? 'Live-Karte' : 'Live map' }}"></div>

                    {{-- ══ Live-Fluege ══ --}}
                    <section class="lm3-panel" id="lm3-panel" aria-labelledby="lm3-titel">
                        <header class="lm3-panel-kopf">
                            <h2 class="lm3-titel" id="lm3-titel">
                                <span class="lm3-punkt" id="lm3-status-punkt" aria-hidden="true"></span>
                                {{ $lmDe ? 'Live-Flüge' : 'Live flights' }}
                                <span class="lm3-zahl" id="lm3-zahl">—</span>
                            </h2>
                            <button type="button" class="lm3-ikon" id="lm3-panel-zu" aria-expanded="true" aria-controls="lm3-panel-inhalt"
                                    title="{{ $lmDe ? 'Liste ein-/ausklappen' : 'Collapse / expand list' }}">
                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M3 10l5-5 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </header>
                        <div class="lm3-panel-inhalt" id="lm3-panel-inhalt">
                            <div class="lm3-tabs" role="tablist">
                                <button type="button" role="tab" id="lm3-tab-aktiv" aria-selected="true">
                                    {{ $lmDe ? 'Aktiv' : 'Active' }} <span class="lm3-tabzahl" id="lm3-n-aktiv">0</span>
                                </button>
                                <button type="button" role="tab" id="lm3-tab-geplant" aria-selected="false">
                                    {{ $lmDe ? 'Geplant' : 'Planned' }} <span class="lm3-tabzahl" id="lm3-n-geplant">0</span>
                                </button>
                            </div>
                            <div class="lm3-filter" id="lm3-filter">
                                <label for="lm3-phase">{{ $lmDe ? 'Phase' : 'Phase' }}</label>
                                <select id="lm3-phase"></select>
                            </div>
                            <p class="lm3-meldung" id="lm3-meldung" role="status" hidden></p>
                            <div class="lm3-liste" id="lm3-liste" role="list"></div>
                        </div>
                    </section>

                    {{-- ══ Flugdetail (vom Skript gefuellt) ══ --}}
                    <aside class="lm3-detail" id="lm3-detail" aria-live="polite" hidden></aside>

                    {{-- ══ Werkzeuge ══ --}}
                    <div class="lm3-werkzeuge" role="toolbar" aria-label="{{ $lmDe ? 'Kartenwerkzeuge' : 'Map tools' }}">
                        <button type="button" class="lm3-wz lm3-wz-fluege" id="mob-toggle-panel" aria-pressed="false" aria-controls="lm3-panel">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" fill="currentColor"/></svg>
                            <span>{{ $lmDe ? 'Flüge' : 'Flights' }}</span>
                        </button>
                        <button type="button" class="lm3-wz" id="lm3-btn-netz" aria-expanded="false" aria-controls="vatsim-box">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 12h18M12 3c2.8 3 2.8 15 0 18M12 3c-2.8 3-2.8 15 0 18" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                            <span>{{ $lmDe ? 'Netzwerk' : 'Network' }}</span>
                            <span class="vatsim-dot" id="vatsimDot" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="lm3-wz" id="lm3-btn-wetter" aria-expanded="false" aria-controls="weather-box">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M7 18a4 4 0 0 1-.6-7.96A6 6 0 0 1 18 9a4.5 4.5 0 0 1-.5 9z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                            <span>{{ $lmDe ? 'Wetter' : 'Weather' }}</span>
                        </button>
                        <button type="button" class="lm3-wz" id="lm3-btn-karte" aria-expanded="false" aria-controls="lm3-karte-box">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 3l9 5-9 5-9-5z M3 13l9 5 9-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                            <span>{{ $lmDe ? 'Karte' : 'Map' }}</span>
                        </button>
                        <button type="button" class="lm3-wz vatsim-btn-follow" id="btnFollowFlight" aria-pressed="false">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.5" fill="currentColor"/><path d="M12 1v4M12 19v4M1 12h4M19 12h4" stroke="currentColor" stroke-width="2"/></svg>
                            <span>{{ $lmDe ? 'Folgen' : 'Follow' }}</span>
                        </button>
                    </div>

                    {{-- ══ Netzwerk (VATSIM / IVAO) ══ --}}
                    <div class="lm3-box map-vatsim-box" id="vatsim-box" hidden>
                        <div class="lm3-box-titel">{{ $lmDe ? 'Netzwerk' : 'Network' }}</div>
                        <div id="vatsim-content">
                            <div class="lm3-reihe">
                                <button id="btnNetVatsim" type="button" class="lm3-schalter map-network-toggle-btn map-network-toggle-btn-vatsim">
                                    <span id="vatsimNetDot" class="map-network-dot"></span> VATSIM
                                </button>
                                <button id="btnNetIvao" type="button" class="lm3-schalter map-network-toggle-btn map-network-toggle-btn-ivao">
                                    <span id="ivaoNetDot" class="map-network-dot"></span> IVAO
                                </button>
                            </div>
                            <div class="lm3-raster">
                                <button id="btnVatsimPilots" type="button" class="lm3-schalter vatsim-btn">{{ $lmDe ? 'Piloten' : 'Pilots' }}</button>
                                <button id="btnVatsimCtrl" type="button" class="lm3-schalter vatsim-btn">{{ $lmDe ? 'Lotsen' : 'Controllers' }}</button>
                                <button id="btnVatsimSectors" type="button" class="lm3-schalter vatsim-btn lm3-breit">{{ $lmDe ? 'FIR-Sektoren' : 'FIR sectors' }}</button>
                            </div>
                            <div class="lm3-statistik">
                                <div id="vatsimStats" class="map-network-stat map-network-stat-vatsim">—</div>
                                <div id="ivaoStats" class="map-network-stat map-network-stat-ivao">…</div>
                            </div>
                            <div class="map-network-legend">
                                <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-delivery">D</span><span class="map-network-legend-label">Delivery</span></div>
                                <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-ground">G</span><span class="map-network-legend-label">Ground</span></div>
                                <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-tower">T</span><span class="map-network-legend-label">Tower</span></div>
                                <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-appatis">A<span class="map-network-legend-ai-suffix">i</span></span><span class="map-network-legend-label">App / ATIS</span></div>
                                <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-center">C</span><span class="map-network-legend-label">Center</span></div>
                                <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-atis">i</span><span class="map-network-legend-label">ATIS only</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- ══ Grundkarte ══ --}}
                    <div class="lm3-box" id="lm3-karte-box" hidden>
                        <div class="lm3-box-titel">{{ $lmDe ? 'Grundkarte' : 'Base map' }}</div>
                        <div class="lm3-raster" id="lm3-karten-liste"></div>
                    </div>

                    {{-- ══ Wetter ══ --}}
                    <div class="lm3-box map-weather-box-left" id="weather-box" hidden>
                        <div class="lm3-box-titel">{{ $lmDe ? 'Wetter' : 'Weather' }}</div>
                        <div id="weather-content">
                            <div class="lm3-raster map-weather-buttons">
                                <button id="btnClouds" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Wolken' : 'Clouds' }}</button>
                                <button id="btnRadar" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Niederschlag' : 'Radar' }}</button>
                                <button id="btnStorms" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Luftdruck' : 'Pressure' }}</button>
                                <button id="btnWind" type="button" class="lm3-schalter weather-btn">Wind</button>
                                <button id="btnTemp" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Temperatur' : 'Temp' }}</button>
                                <button id="btnCombined" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Kombiniert' : 'Combo' }}</button>
                                <button id="btnDarkMap" type="button" class="lm3-schalter weather-btn lm3-breit">{{ $lmDe ? 'Karte abdunkeln' : 'Dim map' }}</button>
                            </div>
                            <div class="weather-slider-wrapper lm3-regler">
                                <label for="weatherOpacity">{{ $lmDe ? 'Deckkraft' : 'Opacity' }}</label>
                                <input type="range" id="weatherOpacity" min="0.2" max="1" step="0.05" value="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- lm3:ende -->
@if($lmScriptsView)
    @include($lmScriptsView)
@endif
