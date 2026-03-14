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

                    $oldStyle = $lmBool(setting('acars.livemap_old_style', false), false);
                    $showTopFlights = $oldStyle
                        ? false
                        : $lmBool(setting('acars.livemap_show_top_flights_panel', true), true);
                    $defaultBasemap = strtolower($lmString(setting('acars.livemap_default_basemap', 'positron'), 'positron'));
                    if (!in_array($defaultBasemap, ['positron', 'osm', 'dark', 'satellite'], true)) {
                        $defaultBasemap = 'positron';
                    }

                    $weatherProxyEnabled = $lmBool(setting('acars.livemap_weather_proxy_enabled', true), true);
                    $weatherDefault = strtolower($lmString(setting('acars.livemap_weather_default_layer', 'combo'), 'combo'));
                    if (!in_array($weatherDefault, ['none', 'clouds', 'radar', 'storms', 'wind', 'temp', 'combo'], true)) {
                        $weatherDefault = 'combo';
                    }
                    $weatherOpacity = (float) setting('acars.livemap_weather_default_opacity', 1);
                    if (!is_finite($weatherOpacity) || $weatherOpacity < 0.2 || $weatherOpacity > 1) $weatherOpacity = 1;
                    $owmApiKeyForClient = $weatherProxyEnabled
                        ? ''
                        : $lmString(setting('acars.livemap_owm_api_key', env('LIVEMAP_OWM_API_KEY', '')), '');
                    $flightsHeaderStart = $lmHexColor(setting('acars.livemap_color_flights_header_start', '#1A2A4A'), '#1A2A4A');
                    $flightsHeaderEnd = $lmHexColor(setting('acars.livemap_color_flights_header_end', '#243B6A'), '#243B6A');
                    $weatherHeaderColor = $lmHexColor(setting('acars.livemap_color_weather_header', '#1A2E4A'), '#1A2E4A');
                    $networkHeaderColor = $lmHexColor(setting('acars.livemap_color_network_header', '#1A2E4A'), '#1A2E4A');
                    $boxBackgroundColor = $lmHexColor(setting('acars.livemap_color_box_background', '#FFFFFF'), '#FFFFFF');
                    $mobileButtonColor = $lmHexColor(setting('acars.livemap_color_mobile_button', '#1A2A4A'), '#1A2A4A');
                    $mobileButtonActiveColor = $lmHexColor(setting('acars.livemap_color_mobile_button_active', '#243B6A'), '#243B6A');
                    $boxBackgroundRgba = $lmHexToRgba($boxBackgroundColor, 0.97);
                    $mobileButtonRgba = $lmHexToRgba($mobileButtonColor, 0.92);
                    $mobileButtonActiveRgba = $lmHexToRgba($mobileButtonActiveColor, 0.92);

                    $liveMapUiConfig = [
                        // Top flights panel ("old style" = hidden)
                        'oldStyle'              => $oldStyle,
                        'showTopFlightsPanel'   => $showTopFlights,
                        'defaultBasemap'        => $defaultBasemap,
                        'showBasemapSwitcher'   => $lmBool(setting('acars.livemap_show_basemap_switcher', true), true),
                        'enableSatelliteBasemap'=> $lmBool(setting('acars.livemap_enable_satellite', true), true),
                        // Weather box + defaults
                        'showWeatherBox'        => $lmBool(setting('acars.livemap_show_weather_box', true), true),
                        'weatherProxyEnabled'   => $weatherProxyEnabled,
                        'weatherProxyBaseUrl'   => rtrim(url('/livemap/weather-tile'), '/'),
                        'owmApiKey'             => $owmApiKeyForClient,
                        'weatherDefaultLayer'   => $weatherDefault,
                        'weatherDefaultOpacity' => round($weatherOpacity, 2),
                        // Network box + defaults
                        'showNetworkBox'        => $lmBool(setting('acars.livemap_show_network_box', true), true),
                        'defaultVatsimEnabled'  => $lmBool(setting('acars.livemap_default_network_vatsim', true), true),
                        'defaultIvaoEnabled'    => $lmBool(setting('acars.livemap_default_network_ivao', true), true),
                        'defaultShowPilots'     => $lmBool(setting('acars.livemap_default_show_pilots', false), false),
                        'defaultShowControllers'=> $lmBool(setting('acars.livemap_default_show_controllers', true), true),
                        'defaultShowSectors'    => $lmBool(setting('acars.livemap_default_show_sectors', false), false),
                        'defaultFollowFlight'   => $lmBool(setting('acars.livemap_default_follow_flight', true), true),
                        // Mobile behavior
                        'mobileShowFlightsButton' => $lmBool(setting('acars.livemap_mobile_show_flights_button', true), true),
                        'mobileFlightsOpen'       => $lmBool(setting('acars.livemap_mobile_flights_open', false), false),
                        'mobileWeatherOpen'       => $lmBool(setting('acars.livemap_mobile_weather_open', false), false),
                        'mobileNetworkOpen'       => $lmBool(setting('acars.livemap_mobile_network_open', false), false),
                        'mobileButtonInactive'    => $mobileButtonRgba,
                        'mobileButtonActive'      => $mobileButtonActiveRgba,
                    ];
                @endphp

                                @php
                    $lmCurrentViewName = \Illuminate\Support\Facades\View::getName();
                    $lmViewPrefix = \Illuminate\Support\Str::beforeLast($lmCurrentViewName, '.live_map');
                    $lmStylesView = $lmViewPrefix . '.live_map_styles';
                    $lmScriptsView = $lmViewPrefix . '.live_map_scripts';
                @endphp

                @include($lmStylesView)

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
                                <div class="va-header-chevron" style="transform:rotate(180deg)">▼</div>
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
                                    <div style="text-align:center">Status</div>
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
                                 rv-src="pirep.airline.logo"
                                 alt=""
                                 style="max-width:130px;max-height:40px;height:auto;object-fit:contain;margin-bottom:4px;display:none"
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
                                    <div class="bp-progress-bar-bg"><div class="bp-progress-bar-fill" id="bp-progress-bar" style="width:0%"></div></div>
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
                        <div class="map-weather-title" id="weather-title" onclick="window.mobToggleWeather()" style="cursor:pointer;user-select:none">Weather Layers <span id="weather-chevron" style="font-size:10px;margin-left:4px">▼</span></div>
                        <div id="weather-content">
                        <div class="map-weather-buttons">
                            <button id="btnClouds" type="button" class="weather-btn" title="Clouds">
                                <i class="fas fa-cloud"></i><span>Clouds</span>
                            </button>
                            <button id="btnRadar" type="button" class="weather-btn" title="Radar / Precipitation">
                                <i class="fas fa-cloud-sun-rain"></i><span>Radar</span>
                            </button>
                            <button id="btnStorms" type="button" class="weather-btn" title="Thunder / Storms">
                                <i class="fas fa-bolt"></i><span>Storms</span>
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
                            <button id="btnDarkMap" type="button" class="weather-btn" title="Dark map"
                                    style="flex: 0 0 100%; max-width: 100%;">
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
                        <div class="map-vatsim-title" id="vatsim-title" onclick="window.mobToggleVatsimContent()" style="cursor:pointer;user-select:none;margin-bottom:8px">
                            <span class="vatsim-dot" id="vatsimDot"></span>
                            Network <span id="vatsim-chevron" style="font-size:10px;margin-left:4px">▼</span>
                        </div>
                        <div id="vatsim-content">
                        <div style="display:flex;gap:5px;margin-bottom:8px">
                            <button id="btnNetVatsim" type="button"
                                    style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;
                                           padding:4px 0;border-radius:5px;border:none;cursor:pointer;
                                           font-size:10px;font-weight:700;letter-spacing:.4px;
                                           background:#1abc9c;color:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);
                                           transition:opacity .2s"
                                    title="VATSIM an/aus">
                                <span id="vatsimNetDot" style="width:6px;height:6px;border-radius:50%;
                                      background:#fff;display:inline-block;flex-shrink:0"></span>
                                VATSIM
                            </button>
                            <button id="btnNetIvao" type="button"
                                    style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;
                                           padding:4px 0;border-radius:5px;border:none;cursor:pointer;
                                           font-size:10px;font-weight:700;letter-spacing:.4px;
                                           background:#e67e22;color:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);
                                           opacity:.45;transition:opacity .2s"
                                    title="IVAO an/aus">
                                <span id="ivaoNetDot" style="width:6px;height:6px;border-radius:50%;
                                      background:#fff;display:inline-block;flex-shrink:0"></span>
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
                            <button id="btnVatsimSectors" type="button" class="vatsim-btn"
                                    style="flex:0 0 100%;max-width:100%">
                                <i class="fas fa-draw-polygon"></i><span>FIR Sectors</span>
                            </button>
                            <button id="btnFollowFlight" type="button" class="vatsim-btn active"
                                    style="flex:0 0 100%;max-width:100%;margin-top:4px">
                                <i class="fas fa-crosshairs"></i><span>Follow Flight</span>
                            </button>
                        </div>

                        <div style="display:flex;gap:6px;margin-top:6px;font-size:10px;color:#555">
                            <div id="vatsimStats" style="flex:1;min-width:0;text-align:center;padding:3px 4px;
                                 background:rgba(26,188,156,0.15);border-radius:3px;border:1px solid rgba(26,188,156,0.3);color:#555;
                                 white-space:nowrap;overflow:hidden;text-overflow:ellipsis">—</div>
                            <div id="ivaoStats"   style="flex:1;min-width:0;text-align:center;padding:3px 4px;
                                 background:rgba(230,126,34,0.15);border-radius:3px;border:1px solid rgba(230,126,34,0.3);color:#555;
                                 white-space:nowrap;overflow:hidden;text-overflow:ellipsis">...</div>
                        </div>

                        <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e0e0e0;
                                    display:grid;grid-template-columns:1fr 1fr;gap:3px 8px">
                            <div style="display:flex;align-items:center;gap:5px">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                    width:14px;height:14px;border-radius:3px;background:#3498db;
                                    color:#fff;font-size:8px;font-weight:800;flex-shrink:0">D</span>
                                <span style="font-size:10px;color:#666">Delivery</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                    width:14px;height:14px;border-radius:3px;background:#e67e22;
                                    color:#fff;font-size:8px;font-weight:800;flex-shrink:0">G</span>
                                <span style="font-size:10px;color:#666">Ground</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                    width:14px;height:14px;border-radius:3px;background:#e74c3c;
                                    color:#fff;font-size:8px;font-weight:800;flex-shrink:0">T</span>
                                <span style="font-size:10px;color:#666">Tower</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                    width:20px;height:14px;border-radius:3px;background:#27ae60;
                                    color:#fff;font-size:8px;font-weight:900;flex-shrink:0">A<span style="font-style:italic;font-size:9px">i</span></span>
                                <span style="font-size:10px;color:#666">App / ATIS</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                    width:14px;height:14px;border-radius:3px;background:#1abc9c;
                                    color:#fff;font-size:8px;font-weight:800;flex-shrink:0">C</span>
                                <span style="font-size:10px;color:#666">Center</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                    width:14px;height:14px;border-radius:50%;background:#5dade2;
                                    color:#fff;font-size:8px;font-weight:900;font-style:italic;flex-shrink:0">i</span>
                                <span style="font-size:10px;color:#666">ATIS only</span>
                            </div>
                        </div>
                    </div><!-- end vatsim-content -->
                    </div><!-- end vatsim-box -->

                </div>
            </div>
        </div>
    </div>
</div>



@include($lmScriptsView)


