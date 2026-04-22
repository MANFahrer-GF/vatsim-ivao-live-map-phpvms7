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

                    $liveMapUiConfig = [
                        // Top flights panel ("old style" = hidden)
                        'oldStyle'              => $oldStyle,
                        'showTopFlightsPanel'   => $showTopFlights,
                        'defaultBasemap'        => $defaultBasemap,
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
                     VA ACTIVE FLIGHTS PANEL (TOP-CENTER) — neues Design
                     Header: dunkelblau, zugeklappt/aufgeklappt
                     Tabs: Active Flights | Planned Flights
                     Scroll ab 5 Einträgen mit Fade-Effekt
                ══════════════════════════════════════════════════════════ --}}

                <div class="live-map-wrapper">
                    <div id="map"></div>

                    {{-- ══ VA FLIGHTS PANEL (TOP-CENTER) ══ --}}
                    <div id="va-flights-panel">

                        {{-- Collapsed Header (sichtbar wenn zugeklappt) --}}
                        <div id="va-header-collapsed">
                            <div class="va-header-left">
                                <div class="va-header-stat va-stat-active">
                                    <span class="va-hdr-icon">✈</span>
                                    <span>Active Flights</span>
                                    <span class="va-hdr-num" id="va-count-active-hdr">—</span>
                                </div>
                                <div class="va-header-divider"></div>
                                <div class="va-header-stat va-stat-planned">
                                    <span class="va-hdr-icon">📋</span>
                                    <span>Planned Flights</span>
                                    <span class="va-hdr-num" id="va-count-planned-hdr">—</span>
                                </div>
                            </div>
                            <div class="va-header-chevron" id="va-chevron-collapsed">▼</div>
                        </div>

                        {{-- Expanded Body (sichtbar wenn aufgeklappt) --}}
                        <div id="va-flights-body">

                            {{-- Expanded Header (klickbar zum Schließen) --}}
                            <div class="va-header-expanded" id="va-header-expanded">
                                <div class="va-header-left">
                                    <div class="va-header-stat va-stat-active">
                                        <span class="va-hdr-icon">✈</span>
                                        <span>Active Flights</span>
                                        <span class="va-hdr-num" id="va-count-active-exp">—</span>
                                    </div>
                                    <div class="va-header-divider"></div>
                                    <div class="va-header-stat va-stat-planned">
                                        <span class="va-hdr-icon">📋</span>
                                        <span>Planned Flights</span>
                                        <span class="va-hdr-num" id="va-count-planned-exp">—</span>
                                    </div>
                                </div>
                                <div class="va-header-chevron is-open">▼</div>
                            </div>

                            {{-- Tabs --}}
                            <div class="va-tabs">
                                <div class="va-tab active" id="va-tab-btn-active">
                                    ✈ Active <span class="va-tab-count" id="va-tab-count-active">0</span>
                                </div>
                                <div class="va-tab" id="va-tab-btn-planned">
                                    📋 Planned <span class="va-tab-count" id="va-tab-count-planned">0</span>
                                </div>
                            </div>

                            {{-- ── Active Flights Tab ── --}}
                            <div class="va-tab-panel active" id="va-tab-active">
                                <div class="va-thead va-g-act">
                                    <div>Flight</div>
                                    <div>Route</div>
                                    <div>Alt</div>
                                    <div>Spd</div>
                                    <div>Distance</div>
                                    <div class="va-status-center">Status</div>
                                    <div>Pilot</div>
                                </div>
                                <div class="va-scroll-wrap" id="va-scroll-active">
                                    <div class="va-scroll-body" id="va-rows-active">
                                        <div class="va-table-info">Loading…</div>
                                    </div>
                                    <div class="va-scroll-hint">scroll for more</div>
                                </div>
                            </div>

                            {{-- ── Planned Flights Tab ── --}}
                            <div class="va-tab-panel" id="va-tab-planned">
                                <div class="va-thead va-g-plan">
                                    <div>Flight</div>
                                    <div>Pilot</div>
                                </div>
                                <div class="va-scroll-wrap" id="va-scroll-planned">
                                    <div class="va-scroll-body" id="va-rows-planned">
                                        <div class="va-table-info">Loading…</div>
                                    </div>
                                    <div class="va-scroll-hint">scroll for more</div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- FLIGHT INFO (TOP-RIGHT) --}}
                    <div id="map-info-box" class="map-info-card-big" rv-show="pirep.id">
                        <div class="map-info-card-header">
                            <img id="map-airline-logo"
                                 class="map-airline-logo"
                                 rv-src="pirep.airline.logo"
                                 alt=""
                                 onerror="this.style.display='none'"
                                 onload="this.style.display='block'">
                            <div class="map-info-route-big">
                                { pirep.dpt_airport.icao }&nbsp;›&nbsp;{ pirep.arr_airport.icao }
                            </div>
                        </div>
                        <div class="map-info-card-body">
                            <div class="map-info-row-big">
                                <strong class="map-info-callsign">{ pirep.airline.icao }{ pirep.flight_number }</strong>
                            </div>
                            <div class="map-info-row-big">
                                { pirep.aircraft.registration } ({ pirep.aircraft.icao })
                            </div>
                            <hr>
                            <div class="map-info-row-big">{ pirep.position.altitude } ft</div>
                            <div class="map-info-row-big">{ pirep.position.gs } kts</div>
                            <div class="map-info-row-big">Time flown: { pirep.flight_time | time_hm }</div>
                            <hr>
                            <span class="status-badge"
                                  rv-text="pirep.status_text"
                                  rv-data-status="pirep.status_text"></span>
                        </div>
                    </div>

                    {{-- ══ CREW BOARDING PASS — initial vollständig versteckt ══ --}}

                    <div id="va-boarding-pass">
                        <div class="bp-head">
                            <div class="bp-head-left">
                                <div class="bp-logo-wrap" id="bp-logo-wrap">
                                    <img id="bp-logo" alt="" onerror="this.parentElement.classList.add('no-logo');this.style.display='none'">
                                </div>
                                <span class="bp-callsign" id="bp-callsign">—</span>
                            </div>
                            <button class="bp-close" onclick="window.vaInfoCardClose()" title="Close">✕</button>
                        </div>
                        <div class="bp-route">
                            <div class="bp-icao">
                                <div class="bp-icao-code" id="bp-dep">—</div>
                                <div class="bp-icao-label" id="bp-dep-name"></div>
                            </div>
                            <div class="bp-arrow">
                                <span class="bp-arrow-icon">✈</span>
                                <span class="bp-arrow-dist" id="bp-dist"></span>
                            </div>
                            <div class="bp-icao">
                                <div class="bp-icao-code" id="bp-arr">—</div>
                                <div class="bp-icao-label" id="bp-arr-name"></div>
                            </div>
                        </div>
                        <div class="bp-grid">
                            <div class="bp-cell"><div class="bp-cell-label">Pilot</div><div class="bp-cell-value" id="bp-pilot">—</div></div>
                            <div class="bp-cell"><div class="bp-cell-label">Aircraft</div><div class="bp-cell-value" id="bp-aircraft">—</div></div>
                            <div class="bp-cell"><div class="bp-cell-label">Altitude</div><div class="bp-cell-value" id="bp-alt">—</div></div>
                            <div class="bp-cell"><div class="bp-cell-label">Speed</div><div class="bp-cell-value" id="bp-spd">—</div></div>
                            <div class="bp-cell"><div class="bp-cell-label">Heading</div><div class="bp-cell-value" id="bp-hdg">—</div></div>
                            <div class="bp-cell"><div class="bp-cell-label">Progress</div>
                                <div class="bp-cell-value" id="bp-progress">—</div>
                                <div class="bp-progress-wrap">
                                    <div class="bp-progress-bar-bg"><div class="bp-progress-bar-fill" id="bp-progress-bar"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="bp-footer">
                            <span class="bp-status status-badge" id="bp-status" data-status=""></span>
                            <span class="bp-crew-label">Crew Pass</span>
                        </div>
                    </div>

                    {{-- WEATHER BOX (BOTTOM-LEFT) --}}
                    <div class="map-weather-box-left" id="weather-box">
                        <div class="map-weather-title" id="weather-title" onclick="window.mobToggleWeather()">Weather Layers <span id="weather-chevron" class="weather-chevron">▼</span></div>
                        <div id="weather-content">
                        <div class="map-weather-buttons">
                            <button id="btnClouds" type="button" class="weather-btn" title="Clouds">
                                <i class="fas fa-cloud"></i><span>Clouds</span>
                            </button>
                            <button id="btnRadar" type="button" class="weather-btn" title="Radar / Precipitation">
                                <i class="fas fa-cloud-sun-rain"></i><span>Radar</span>
                            </button>
                            <button id="btnStorms" type="button" class="weather-btn" title="Pressure (storm proxy)">
                                <i class="fas fa-bolt"></i><span>Pressure</span>
                            </button>
                            <button id="btnWind" type="button" class="weather-btn" title="Wind">
                                <i class="fas fa-wind"></i><span>Wind</span>
                            </button>
                            <button id="btnTemp" type="button" class="weather-btn" title="Temperature">
                                <i class="fas fa-thermometer-half"></i><span>Temp</span>
                            </button>
                            <button id="btnCombined" type="button" class="weather-btn" title="Combined mode">
                                <i class="fas fa-layer-group"></i><span>Combo</span>
                            </button>
                            <button id="btnDarkMap" type="button" class="weather-btn weather-btn-full" title="Dark map">
                                <i class="fas fa-moon"></i><span>Dark map</span>
                            </button>
                        </div>
                        <div class="weather-slider-wrapper">
                            <span>Opacity</span>
                            <input type="range" id="weatherOpacity" min="0.2" max="1" step="0.05" value="1">
                        </div>
                    </div><!-- end weather-content -->
                    </div><!-- end weather-box -->

                    {{-- NETWORK BOX (BOTTOM-RIGHT) --}}
                    {{-- Mobile Toggle Buttons --}}
                    <button id="mob-toggle-panel" onclick="window.mobTogglePanel()">
                        ✈ Flights ▼
                    </button>
                    <div class="map-vatsim-box" id="vatsim-box">
                        <div class="map-vatsim-title" id="vatsim-title" onclick="window.mobToggleVatsimContent()">
                            <span class="vatsim-dot" id="vatsimDot"></span>
                            Network <span id="vatsim-chevron" class="vatsim-chevron">▼</span>
                        </div>
                        <div id="vatsim-content">
                        <div class="map-network-toggle-row">
                            <button id="btnNetVatsim" type="button"
                                    class="map-network-toggle-btn map-network-toggle-btn-vatsim"
                                    title="VATSIM an/aus">
                                <span id="vatsimNetDot" class="map-network-dot"></span>
                                VATSIM
                            </button>
                            <button id="btnNetIvao" type="button"
                                    class="map-network-toggle-btn map-network-toggle-btn-ivao"
                                    title="IVAO an/aus">
                                <span id="ivaoNetDot" class="map-network-dot"></span>
                                IVAO
                            </button>
                        </div>

                        <div class="map-vatsim-buttons">
                            <button id="btnVatsimPilots" type="button" class="vatsim-btn" title="Piloten anzeigen">
                                <i class="fas fa-plane"></i><span>Pilots</span>
                            </button>
                            <button id="btnVatsimCtrl" type="button" class="vatsim-btn active" title="Controller anzeigen">
                                <i class="fas fa-headset"></i><span>Controllers</span>
                            </button>
                            <button id="btnVatsimSectors" type="button" class="vatsim-btn vatsim-btn-full">
                                <i class="fas fa-draw-polygon"></i><span>FIR Sectors</span>
                            </button>
                            <button id="btnFollowFlight" type="button" class="vatsim-btn active vatsim-btn-full vatsim-btn-follow">
                                <i class="fas fa-crosshairs"></i><span>Follow Flight</span>
                            </button>
                        </div>

                        <div class="map-network-stats-row">
                            <div id="vatsimStats" class="map-network-stat map-network-stat-vatsim">—</div>
                            <div id="ivaoStats" class="map-network-stat map-network-stat-ivao">...</div>
                        </div>

                        <div class="map-network-legend">
                            <div class="map-network-legend-item">
                                <span class="map-network-legend-badge map-network-legend-badge-delivery">D</span>
                                <span class="map-network-legend-label">Delivery</span>
                            </div>
                            <div class="map-network-legend-item">
                                <span class="map-network-legend-badge map-network-legend-badge-ground">G</span>
                                <span class="map-network-legend-label">Ground</span>
                            </div>
                            <div class="map-network-legend-item">
                                <span class="map-network-legend-badge map-network-legend-badge-tower">T</span>
                                <span class="map-network-legend-label">Tower</span>
                            </div>
                            <div class="map-network-legend-item">
                                <span class="map-network-legend-badge map-network-legend-badge-appatis">A<span class="map-network-legend-ai-suffix">i</span></span>
                                <span class="map-network-legend-label">App / ATIS</span>
                            </div>
                            <div class="map-network-legend-item">
                                <span class="map-network-legend-badge map-network-legend-badge-center">C</span>
                                <span class="map-network-legend-label">Center</span>
                            </div>
                            <div class="map-network-legend-item">
                                <span class="map-network-legend-badge map-network-legend-badge-atis">i</span>
                                <span class="map-network-legend-label">ATIS only</span>
                            </div>
                        </div>
                    </div><!-- end vatsim-content -->
                    </div><!-- end vatsim-box -->

                </div>
            </div>
        </div>
    </div>
</div>



@if($lmScriptsView)
    @include($lmScriptsView)
@endif



