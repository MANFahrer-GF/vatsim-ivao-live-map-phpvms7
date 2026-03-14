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

                <style>
                    .lm-hidden {
                        display: none !important;
                    }

                    .live-map-wrapper {
                        position: relative;
                        overflow: visible;
                        width: 100%;
                        height: {{ $config['height'] }};
                        --lm-flights-header-start: {{ $flightsHeaderStart }};
                        --lm-flights-header-end: {{ $flightsHeaderEnd }};
                        --lm-weather-header-bg: {{ $weatherHeaderColor }};
                        --lm-network-header-bg: {{ $networkHeaderColor }};
                        --lm-box-bg: {{ $boxBackgroundRgba }};
                        --lm-mobile-btn-inactive: {{ $mobileButtonRgba }};
                        --lm-mobile-btn-active: {{ $mobileButtonActiveRgba }};
                    }

                    #map {
                        width: 100%;
                        height: 100%;
                        transition: filter 0.3s ease;
                    }

                    /* Dark map (CSS "night mode") */
                    .dark-map {
                        filter: brightness(0.5) contrast(1.2) saturate(1.1);
                    }

                    /* FLIGHT INFO CARD (TOP-RIGHT) */
                    .map-info-card-big {
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        width: 240px;
                        background: #ffffff;
                        border-radius: 12px;
                        padding: 0;
                        z-index: 1000;
                        box-shadow: 0 8px 32px rgba(0,0,0,0.22), 0 2px 8px rgba(0,0,0,0.12);
                        font-size: 14px;
                        text-align: center;
                        overflow: hidden;
                    }

                    .map-info-card-header {
                        background: #f8f9fa;
                        border-bottom: 1px solid #eee;
                        padding: 12px 16px 10px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
                    }

                    .map-info-logo-big img {
                        max-width: 120px;
                        max-height: 36px;
                        height: auto;
                        object-fit: contain;
                    }

                    .map-info-logo-big.no-logo {
                        font-size: 13px;
                        font-weight: 700;
                        color: #555;
                        letter-spacing: 1px;
                    }

                    .map-info-card-body {
                        padding: 12px 16px 14px;
                    }

                    .map-info-card-big hr {
                        margin: 8px 0;
                        border: none;
                        border-top: 1px solid #eee;
                    }

                    .map-info-route-big {
                        font-size: 20px;
                        font-weight: 800;
                        letter-spacing: 2px;
                        color: #1a1a1a;
                        margin-bottom: 2px;
                    }

                    .map-info-row-big {
                        font-size: 13px;
                        padding: 2px 0;
                        color: #444;
                    }

                    .map-info-row-big strong {
                        color: #1a1a1a;
                        font-size: 14px;
                    }

                    /* STATUS BADGE */
                    .status-badge {
                        display: inline-block;
                        padding: 3px 12px;
                        border-radius: 999px;
                        font-size: 13px;
                        font-weight: 600;
                        letter-spacing: 0.03em;
                        background: #bdc3c7;
                        color: #ffffff;
                        text-transform: uppercase;
                    }

                    .status-badge[data-status*="board" i],
                    .status-badge[data-status*="sched" i],
                    .status-badge[data-status*="pre-flight" i],
                    .status-badge[data-status*="preflight" i] {
                        background: #3498db;
                    }

                    .status-badge[data-status*="push" i],
                    .status-badge[data-status*="taxi" i] {
                        background: #f39c12;
                    }

                    .status-badge[data-status*="takeoff" i],
                    .status-badge[data-status*="climb" i],
                    .status-badge[data-status*="cruise" i],
                    .status-badge[data-status*="descent" i],
                    .status-badge[data-status*="approach" i],
                    .status-badge[data-status*="enroute" i],
                    .status-badge[data-status*="in flight" i],
                    .status-badge[data-status*="airborne" i] {
                        background: #2ecc71;
                    }

                    .status-badge[data-status*="arrived" i],
                    .status-badge[data-status*="landed" i],
                    .status-badge[data-status*="parked" i],
                    .status-badge[data-status*="completed" i] {
                        background: #16a085;
                    }

                    .status-badge[data-status*="divert" i],
                    .status-badge[data-status*="cancel" i],
                    .status-badge[data-status*="abort" i],
                    .status-badge[data-status*="emerg" i] {
                        background: #e74c3c;
                    }

                    .status-badge[data-status*="pause" i],
                    .status-badge[data-status*="hold" i] {
                        background: #9b59b6;
                    }

                    /* WEATHER BOX (BOTTOM-LEFT) */
                    .map-weather-box-left {
                        position: absolute;
                        bottom: 20px;
                        left: 20px;
                        width: 280px;
                        background: var(--lm-box-bg);
                        border-radius: 10px;
                        padding: 0;
                        z-index: 1100;
                        box-shadow: 0 3px 10px rgba(0,0,0,0.25);
                        border: 1px solid #ddd;
                        overflow: visible;
                    }

                    .map-weather-title {
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.08em;
                        color: rgba(255,255,255,0.85) !important;
                        text-align: center;
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
                        background: var(--lm-weather-header-bg) !important;
                        padding: 8px 12px !important;
                        margin: 0 !important;
                        cursor: pointer;
                        border-radius: 10px 10px 0 0;
                    }
                    #weather-content {
                        overflow: hidden;
                        transition: max-height .3s ease, opacity .2s ease;
                        max-height: 300px;
                        opacity: 1;
                    }
                    #weather-content.collapsed {
                        max-height: 0;
                        opacity: 0;
                    }

                    .map-weather-buttons {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 6px;
                        margin-bottom: 4px;
                    }

                    .weather-btn {
                        flex: 1 0 30%;
                        min-width: 75px;
                        border-radius: 6px;
                        border: 1px solid #d0d0d0;
                        background: #ffffff;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        padding: 4px 4px 2px;
                        font-size: 11px;
                        line-height: 1.2;
                        text-align: center;
                        transition: background .15s;
                    }

                    .weather-btn i {
                        font-size: 17px;
                        color: #555;
                        margin-bottom: 2px;
                    }

                    .weather-btn span {
                        color: #666;
                    }

                    .weather-btn:hover:not(.active) {
                        background: #f0f0f0;
                    }

                    .weather-btn.active {
                        border-color: #2ecc71;
                        background: rgba(46,204,113,0.2);
                    }

                    .weather-btn.active i,
                    .weather-btn.active span {
                        color: #2ecc71;
                    }

                    .weather-slider-wrapper {
                        margin-top: 4px;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        font-size: 11px;
                        color: #555;
                    }

                    .weather-slider-wrapper input[type="range"] {
                        flex: 1;
                    }

                    .owm-clouds-layer,
                    .owm-precip-layer,
                    .owm-storms-layer,
                    .owm-wind-layer,
                    .owm-temp-layer,
                    .owm-thunder-layer {
                        mix-blend-mode: multiply;
                        filter: contrast(3) saturate(4) brightness(0.8);
                    }

                    @media (max-width: 768px) {
                        .map-info-card-big {
                            right: 10px;
                            top: 10px;
                            width: 230px;
                        }
                        /* ── Mobil: Seitliche Tabs ── */
                        .map-weather-box-left {
                            position: absolute !important;
                            left: 0 !important;
                            bottom: 0 !important;
                            overflow: visible !important;
                            top: auto !important;
                            transform: none !important;
                            width: auto !important;
                            min-width: 0 !important;
                            border-radius: 0 8px 0 0 !important;
                            padding: 0 !important;
                            border: none !important;
                            background: transparent !important;
                            box-shadow: none !important;
                        }
                        .map-vatsim-box {
                            position: absolute !important;
                            right: 0 !important;
                            bottom: 0 !important;
                            overflow: visible !important;
                            top: auto !important;
                            transform: none !important;
                            width: auto !important;
                            min-width: 0 !important;
                            border-radius: 8px 0 0 0 !important;
                            padding: 0 !important;
                            border: none !important;
                            background: transparent !important;
                            box-shadow: none !important;
                        }
                        /* Titel als horizontaler Bottom-Tab */
                        .map-weather-title {
                            writing-mode: horizontal-tb !important;
                            transform: none !important;
                            padding: 7px 12px !important;
                            margin: 0 !important;
                            font-size: 10px !important;
                            letter-spacing: .8px !important;
                            white-space: nowrap !important;
                            background: var(--lm-weather-header-bg) !important;
                            color: #fff !important;
                            border-radius: 0 8px 0 0 !important;
                            cursor: pointer !important;
                            display: flex !important;
                            align-items: center !important;
                            gap: 5px !important;
                        }
                        .map-vatsim-title {
                            writing-mode: horizontal-tb !important;
                            transform: none !important;
                            padding: 7px 12px !important;
                            margin: 0 !important;
                            font-size: 10px !important;
                            letter-spacing: .8px !important;
                            white-space: nowrap !important;
                            background: var(--lm-network-header-bg) !important;
                            color: #fff !important;
                            border-radius: 8px 0 0 0 !important;
                            cursor: pointer !important;
                            display: flex !important;
                            align-items: center !important;
                            gap: 5px !important;
                        }
                        #weather-chevron, #vatsim-chevron {
                            font-size: 8px !important;
                        }
                        /* Content klappt zur Mitte auf */
                        #weather-content {
                            max-height: 0 !important;
                            opacity: 0 !important;
                            position: absolute !important;
                            bottom: 100% !important;
                            left: 0 !important;
                            top: auto !important;
                            transform: none !important;
                            background: var(--lm-box-bg) !important;
                            border: 1px solid #ddd !important;
                            border-radius: 10px 10px 0 0 !important;
                            padding: 0 !important;
                            width: 210px !important;
                            box-shadow: 0 -4px 12px rgba(0,0,0,0.18) !important;
                            overflow: hidden !important;
                            transition: max-height .3s, opacity .2s !important;
                        }
                        #weather-content.mob-expanded {
                            max-height: 400px !important;
                            opacity: 1 !important;
                            padding: 10px !important;
                        }
                        #vatsim-content {
                            max-height: 0 !important;
                            opacity: 0 !important;
                            position: absolute !important;
                            bottom: 100% !important;
                            right: 0 !important;
                            top: auto !important;
                            transform: none !important;
                            background: var(--lm-box-bg) !important;
                            border: 1px solid #ddd !important;
                            border-radius: 10px 10px 0 0 !important;
                            padding: 0 !important;
                            width: 210px !important;
                            box-shadow: 0 -4px 12px rgba(0,0,0,0.18) !important;
                            overflow: hidden !important;
                            transition: max-height .3s, opacity .2s !important;
                        }
                        #vatsim-content.mob-expanded {
                            max-height: 500px !important;
                            opacity: 1 !important;
                            padding: 10px !important;
                        }
                        .map-vatsim-title .vatsim-dot {
                            background: rgba(255,255,255,0.5) !important;
                        }
                        .map-vatsim-title .vatsim-dot.live {
                            background: #2ecc71 !important;
                        }

                        /* Panel mobil: versteckt, schwebt oben links */
                        #va-flights-panel {
                            display: none;
                            top: 56px !important;
                            left: 0 !important;
                            right: 0 !important;
                            transform: none !important;
                            width: 100% !important;
                            max-width: 100vw !important;
                            box-sizing: border-box !important;
                        }
                        #va-flights-body.open {
                            max-height: 50vh !important;
                            overflow-y: auto !important;
                            -webkit-overflow-scrolling: touch;
                        }
                        #va-flights-panel.mobile-visible {
                            display: block !important;
                        }
                        /* Collapsed Header mobil verstecken */
                        #va-flights-panel.mobile-visible #va-header-collapsed {
                            display: none !important;
                        }
                        #va-flights-body {
                            width: 100% !important;
                            max-width: 100vw !important;
                            box-sizing: border-box !important;
                        }
                        #va-header-expanded {
                            box-sizing: border-box !important;
                            width: 100% !important;
                        }
                        .va-tabs-bar {
                            box-sizing: border-box !important;
                            width: 100% !important;
                        }
                        .va-table-wrap {
                            width: 100% !important;
                            overflow-x: hidden !important;
                        }
                        /* Planned-Tabelle mobil: kein Pilot, Airport-Namen weg */
                        .va-g-plan {
                            grid-template-columns: 1fr !important;
                        }
                        /* Pilot-Spalte (2. Kind) verstecken */
                        .va-g-plan > *:nth-child(2) { display: none !important; }
                        .va-thead.va-g-plan > *:nth-child(2) { display: none !important; }
                        /* Airport-Kurzname (graues span) in Route verstecken */
                        .va-g-plan .va-route-airport-name { display: none !important; }

                        /* Aktiv-Tabelle mobil: Flight | Route */
                        .va-g-act {
                            grid-template-columns: 1fr 1fr !important;
                            gap: 0 4px !important;
                        }
                        /* ALT(3), SPD(4), DISTANCE(5), STATUS(6), PILOT(7) verstecken */
                        .va-g-act > *:nth-child(3),
                        .va-g-act > *:nth-child(4),
                        .va-g-act > *:nth-child(5),
                        .va-g-act > *:nth-child(6),
                        .va-g-act > *:nth-child(7) { display: none !important; }
                        .va-thead.va-g-act > *:nth-child(3),
                        .va-thead.va-g-act > *:nth-child(4),
                        .va-thead.va-g-act > *:nth-child(5),
                        .va-thead.va-g-act > *:nth-child(6),
                        .va-thead.va-g-act > *:nth-child(7) { display: none !important; }
                        .va-row { font-size: 10px !important; padding: 4px 8px !important; }
                        .va-thead { font-size: 8px !important; padding: 3px 8px !important; }
                        .va-g-act .va-c-flight { gap: 3px !important; }
                        .va-g-act .va-c-flight img { max-height: 12px !important; max-width: 28px !important; }
                        .va-g-act .va-c-flight span { font-size: 10px !important; font-weight: 700 !important; }
                        #va-flights-body { overflow-x: hidden !important; }
                        /* Panel-Header kompakter */
                        #va-header-expanded { padding: 6px 10px !important; }
                        .va-tab-btn { font-size: 10px !important; padding: 4px 8px !important; }
                        /* VATSIM Box mobil: immer sichtbar, aber Content eingeklappt */
                        .map-vatsim-box { display: block !important; }
                        #vatsim-content { max-height: 0 !important; opacity: 0 !important; }
                        #vatsim-content.mob-expanded { max-height: 500px !important; opacity: 1 !important; }
                        /* Mobile Buttons sichtbar (nur Flights-Button) */
                        #mob-toggle-panel { display: flex !important; }
                        /* Boarding Pass schmaler */
                        #va-boarding-pass {
                            width: auto !important;
                            max-width: calc(100vw - 32px) !important;
                            right: 16px !important;
                            left: 16px !important;
                            top: 56px !important;
                            box-sizing: border-box !important;
                            position: absolute !important;
                            overflow: hidden !important;
                        }
                        /* Innere Elemente nicht breiter als Container */
                        #va-boarding-pass > *,
                        #va-boarding-pass .bp-head,
                        #va-boarding-pass .bp-route,
                        #va-boarding-pass .bp-grid,
                        #va-boarding-pass .bp-footer {
                            max-width: 100% !important;
                            box-sizing: border-box !important;
                        }
                        #va-boarding-pass .bp-icao-code { font-size: 16px !important; }
                        #va-boarding-pass .bp-icao-label {
                            font-size: 8px !important;
                            max-width: none !important;
                            white-space: normal !important;
                            word-break: break-word !important;
                            text-align: center !important;
                            line-height: 1.2 !important;
                        }
                        #va-boarding-pass .bp-head { padding: 5px 8px 4px !important; }
                        #va-boarding-pass .bp-route { padding: 6px 8px !important; }
                        #va-boarding-pass .bp-grid {
                            padding: 4px 8px 6px !important;
                            gap: 3px 8px !important;
                            grid-template-columns: 1fr 1fr !important;
                        }
                        #va-boarding-pass .bp-cell-value { font-size: 10px !important; }
                        #va-boarding-pass .bp-cell-label { font-size: 7px !important; }
                        #va-boarding-pass .bp-arrow-icon { font-size: 16px !important; }
                        #va-boarding-pass .bp-arrow { padding: 0 5px !important; }
                        #va-boarding-pass .bp-arrow-dist { font-size: 8px !important; padding: 1px 4px !important; }
                        #va-boarding-pass .bp-footer { padding: 4px 8px !important; }
                        #va-boarding-pass .bp-logo-wrap { min-width: 40px !important; height: 26px !important; }
                        #va-boarding-pass .bp-logo-wrap img { max-height: 20px !important; max-width: 60px !important; }
                        #va-boarding-pass .bp-callsign { font-size: 11px !important; }
                        #va-boarding-pass .bp-progress-bar-bg { height: 4px !important; margin-top: 2px !important; }
                    }
                    /* Mobile Buttons — desktop versteckt */
                    #mob-toggle-panel { display: none; }
                    #mob-toggle-panel {
                        position: absolute; top: 10px; left: 50%; transform: translateX(-50%); z-index: 1200;
                        background: var(--lm-mobile-btn-inactive); color: #fff;
                        border: 1px solid rgba(255,255,255,0.25); border-radius: 8px; padding: 8px 16px;
                        font-size: 13px; font-weight: 700; cursor: pointer;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.35);
                        align-items: center; gap: 6px; white-space: nowrap;
                    }
                    #mob-toggle-panel.lm-mobile-active {
                        background: var(--lm-mobile-btn-active) !important;
                        border-color: rgba(255,255,255,0.85) !important;
                        box-shadow: 0 0 0 2px rgba(255,255,255,0.45), 0 3px 10px rgba(0,0,0,0.38);
                    }

                    /* ── VATSIM CONTROL BOX (BOTTOM-RIGHT) ── */
                    .map-vatsim-box {
                        position: absolute;
                        bottom: 20px;
                        right: 20px;
                        width: 200px;
                        background: var(--lm-box-bg);
                        border-radius: 10px;
                        padding: 0;
                        z-index: 1100;
                        box-shadow: 0 3px 10px rgba(0,0,0,0.25);
                        border: 1px solid #ddd;
                        overflow: visible;
                    }

                    .map-vatsim-title {
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.08em;
                        color: rgba(255,255,255,0.85) !important;
                        text-align: center;
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
                        background: var(--lm-network-header-bg) !important;
                        padding: 8px 12px !important;
                        margin: 0 !important;
                        cursor: pointer;
                        border-radius: 10px 10px 0 0;
                    }
                    #vatsim-content {
                        overflow: hidden;
                        transition: max-height .3s ease, opacity .2s ease;
                        max-height: 500px;
                        opacity: 1;
                        padding: 8px 10px 8px;
                    }
                    #vatsim-content.collapsed {
                        max-height: 0;
                        opacity: 0;
                        padding: 0;
                    }
                    #weather-content {
                        padding: 8px 10px 6px;
                        border-radius: 0 0 10px 10px;
                        overflow: hidden;
                    }


                    .map-vatsim-title .vatsim-dot {
                        width: 8px; height: 8px;
                        border-radius: 50%;
                        background: #bbb;
                        display: inline-block;
                        transition: background 0.3s;
                    }

                    .map-vatsim-title .vatsim-dot.live {
                        background: #2ecc71;
                        box-shadow: 0 0 0 2px rgba(46,204,113,0.3);
                        animation: vatsim-pulse 1.8s infinite;
                    }

                    @keyframes vatsim-pulse {
                        0%, 100% { box-shadow: 0 0 0 2px rgba(46,204,113,0.3); }
                        50%       { box-shadow: 0 0 0 5px rgba(46,204,113,0.0); }
                    }

                    .map-vatsim-buttons {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 5px;
                    }

                    .vatsim-btn {
                        flex: 1 0 45%;
                        border-radius: 6px;
                        border: 1px solid #d0d0d0;
                        background: #fff;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        padding: 5px 4px 4px;
                        font-size: 11px;
                        line-height: 1.3;
                        text-align: center;
                        transition: background 0.15s, border-color 0.15s;
                        outline: none !important;
                        box-shadow: none !important;
                    }
                    .vatsim-btn:focus { outline: none !important; box-shadow: none !important; }
                    .vatsim-btn:hover:not(.active) { background: #f0f5fc; }

                    .vatsim-btn i { font-size: 15px; color: #555; margin-bottom: 2px; }
                    .vatsim-btn span { color: #666; }

                    .vatsim-btn.active { border-color: #3498db; background: #eaf4fd; }
                    .vatsim-btn.active i, .vatsim-btn.active span { color: #2980b9; }

                    #btnFollowFlight { border-color: #d0d0d0; background: #f7f7f7; }
                    #btnFollowFlight i, #btnFollowFlight span { color: #aaa; }
                    #btnFollowFlight.active { border-color: #27ae60; background: #eafaf1; }
                    #btnFollowFlight.active i, #btnFollowFlight.active span { color: #27ae60; }

                    .vatsim-stats {
                        margin-top: 6px;
                        font-size: 11px;
                        color: #888;
                        text-align: center;
                        line-height: 1.6;
                    }

                    .vatsim-ac-icon {
                        width: 26px; height: 26px;
                        display: flex; align-items: center; justify-content: center;
                        filter: drop-shadow(0 1px 3px rgba(0,0,0,0.5));
                    }

                    .vatsim-ctrl-icon {
                        width: 22px; height: 22px;
                        border-radius: 50%;
                        border: 2px solid rgba(255,255,255,0.85);
                        display: flex; align-items: center; justify-content: center;
                        font-size: 9px; font-weight: 700; color: #fff;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.4);
                    }

                    .vatsim-popup {
                        min-width: 220px;
                        font-size: 13px;
                        line-height: 1.5;
                        padding: 0;
                    }

                    .leaflet-popup-content {
                        margin: 0 !important;
                        overflow: hidden;
                        border-radius: 8px;
                    }

                    .vatsim-airport-marker {
                        overflow: visible !important;
                        background: transparent !important;
                        border: none !important;
                    }

                    .vatsim-popup-header {
                        background: #f8f9fa;
                        border-bottom: 1px solid #eee;
                        padding: 10px 14px 8px;
                        text-align: center;
                    }

                    .vatsim-popup-callsign {
                        font-size: 17px;
                        font-weight: 800;
                        letter-spacing: 1.5px;
                        color: #1a1a1a;
                    }

                    .vatsim-popup-route {
                        font-size: 13px;
                        color: #555;
                        margin-top: 2px;
                        letter-spacing: 0.5px;
                    }

                    .vatsim-popup-body {
                        padding: 10px 14px 12px;
                        max-height: 60vh;
                        overflow-y: auto;
                    }

                    .vatsim-popup-row {
                        display: flex;
                        justify-content: space-between;
                        padding: 2px 0;
                        border-bottom: 1px solid #f5f5f5;
                    }

                    .vatsim-popup-row:last-child { border-bottom: none; }
                    .vatsim-popup-row .label { color: #999; font-size: 12px; }
                    .vatsim-popup-row .value { font-weight: 600; color: #1a1a1a; font-size: 12px; }

                    .vatsim-ctrl-badge {
                        display: inline-block;
                        padding: 1px 8px;
                        border-radius: 999px;
                        font-size: 11px;
                        font-weight: 600;
                        color: #fff;
                        margin-top: 4px;
                    }
                </style>

                {{-- ══════════════════════════════════════════════════════════
                     VA ACTIVE FLIGHTS PANEL (TOP-CENTER) — neues Design
                     Header: dunkelblau, zugeklappt/aufgeklappt
                     Tabs: Active Flights | Planned Flights
                     Scroll ab 5 Einträgen mit Fade-Effekt
                ══════════════════════════════════════════════════════════ --}}
                <style>
                    /* ── VA Flights Panel Wrapper (TOP-CENTER) ── */
                    #va-flights-panel {
                        position: absolute;
                        top: 10px;
                        left: 50%;
                        transform: translateX(-50%);
                        z-index: 1000;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                        width: max-content;
                        min-width: 200px;
                        max-width: 860px;
                    }

                    /* ── Collapsed Header ── */
                    #va-header-collapsed {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 10px 16px;
                        background: linear-gradient(135deg, var(--lm-flights-header-start) 0%, var(--lm-flights-header-end) 100%);
                        color: #fff;
                        cursor: pointer;
                        user-select: none;
                        border-radius: 10px;
                        box-shadow: 0 4px 16px rgba(0,0,0,0.28);
                        transition: background .2s;
                        white-space: nowrap;
                    }
                    #va-header-collapsed:hover {
                        background: linear-gradient(135deg, var(--lm-flights-header-start) 0%, var(--lm-flights-header-end) 100%);
                    }

                    /* ── Expanded Panel ── */
                    #va-flights-body {
                        margin-top: 0;
                        background: var(--lm-box-bg);
                        border: 1px solid rgba(0,0,0,0.10);
                        border-radius: 10px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.18);
                        overflow: hidden;
                        max-height: 0;
                        opacity: 0;
                        pointer-events: none;
                        transition: max-height .35s cubic-bezier(.4,0,.2,1),
                                    opacity .22s ease;
                        width: 820px;
                    }
                    #va-flights-body.open {
                        max-height: 70vh; /* Fallback */
                        opacity: 1;
                        pointer-events: auto;
                    }

                    /* ── Expanded Header (innerhalb Body) ── */
                    .va-header-expanded {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 10px 16px;
                        background: linear-gradient(135deg, var(--lm-flights-header-start) 0%, var(--lm-flights-header-end) 100%);
                        color: #fff;
                        cursor: pointer;
                        user-select: none;
                        border-radius: 10px 10px 0 0;
                    }

                    /* ── Gemeinsame Header-Elemente ── */
                    .va-header-left {
                        display: flex;
                        align-items: center;
                        gap: 16px;
                    }
                    .va-header-stat {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        font-size: 13px;
                        font-weight: 600;
                        letter-spacing: .3px;
                    }
                    .va-header-stat .va-hdr-icon { font-size: 15px; line-height: 1; }
                    .va-header-stat .va-hdr-num {
                        font-weight: 800;
                        font-size: 15px;
                        font-variant-numeric: tabular-nums;
                    }
                    .va-header-divider {
                        width: 1px; height: 18px;
                        background: rgba(255,255,255,0.25);
                        margin: 0 4px;
                    }
                    .va-header-stat.va-stat-active .va-hdr-num  { color: #6fcf7c; }
                    .va-header-stat.va-stat-planned .va-hdr-num { color: #7cb8f0; }

                    .va-header-chevron {
                        font-size: 18px;
                        color: rgba(255,255,255,0.6);
                        transition: transform .22s;
                        flex-shrink: 0;
                    }
                    .va-panel-open .va-header-chevron { transform: rotate(180deg); }

                    /* ── Tabs ── */
                    .va-tabs {
                        display: flex;
                        border-bottom: 1px solid #e0e6ec;
                        background: #f8f9fa;
                    }
                    .va-tab {
                        flex: 1;
                        padding: 9px 12px;
                        font-size: 11px;
                        font-weight: 700;
                        letter-spacing: .5px;
                        text-transform: uppercase;
                        color: #999;
                        text-align: center;
                        border-bottom: 2px solid transparent;
                        cursor: pointer;
                        transition: color .15s, border-color .15s;
                        user-select: none;
                    }
                    .va-tab:hover { color: #555; }
                    .va-tab.active { color: #1a3a6b; border-bottom-color: #3498db; }
                    .va-tab-count {
                        display: inline-block;
                        font-size: 9px;
                        font-weight: 800;
                        border-radius: 999px;
                        padding: 2px 7px;
                        margin-left: 5px;
                        min-width: 20px;
                        text-align: center;
                        line-height: 1.5;
                    }
                    .va-tab.active   .va-tab-count { background: #3498db; color: #fff; }
                    .va-tab:not(.active) .va-tab-count { background: #e0e6ec; color: #666; }

                    /* ── Tab-Panels ── */
                    .va-tab-panel { display: none; }
                    .va-tab-panel.active { display: block; }

                    /* ── Thead ── */
                    .va-thead {
                        padding: 7px 16px;
                        background: #f8f9fb;
                        border-bottom: 1px solid #e8ecf0;
                        font-size: 10px;
                        font-weight: 800;
                        color: #8894a5;
                        letter-spacing: .7px;
                        text-transform: uppercase;
                    }

                    /* ── Grid: Active ── */
                    .va-g-act {
                        display: grid;
                        grid-template-columns: 148px 110px 58px 56px 85px 95px 1fr;
                        align-items: center;
                        gap: 0 6px;
                    }

                    /* ── Grid: Planned ── */
                    .va-g-plan {
                        display: grid;
                        grid-template-columns: 1fr auto;
                        align-items: center;
                        gap: 0 16px;
                    }

                    /* ── Row ── */
                    .va-row {
                        padding: 9px 16px;
                        border-bottom: 1px solid #f0f0f0;
                        font-size: 13px;
                        color: #222;
                        align-items: center;
                        transition: background .12s;
                        cursor: pointer;
                        min-height: 48px;
                    }
                    .va-row:last-child { border-bottom: none; }
                    .va-row:hover { background: #f0f6ff; }
                    .va-row.va-row-live { background: #f0f9f0; }
                    .va-row.va-row-live:hover { background: #e4f3e4; }
                    .va-row.active-flight { background: #e8f0fd !important; }

                    /* ── Logo Box ── */
                    .va-logo-box {
                        width: 40px; height: 18px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        overflow: hidden;
                        flex-shrink: 0;
                        background: #fff;
                        border-radius: 4px;
                        border: 1px solid rgba(0,0,0,0.07);
                    }
                    .va-logo-box img {
                        max-width: 100%; max-height: 100%;
                        object-fit: contain; display: block;
                    }

                    /* ── Zellen ── */
                    .va-c-flight {
                        display: flex; align-items: center; gap: 7px;
                        min-width: 0;
                        font-weight: 800;
                        color: #1a3a6b;
                        letter-spacing: .4px;
                        font-size: 13px;
                    }
                    .va-c-flight span:last-child {
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                    }
                    .va-c-route { font-size: 12px; color: #444; white-space: nowrap; }
                    .va-c-route .va-icao { font-weight: 700; color: #333; }
                    .va-c-route .va-arr  { color: #bbb; margin: 0 4px; font-size: 11px; }
                    .va-c-route .va-dist-hint { color: #aaa; font-size: 10px; margin-left: 3px; }
                    .va-c-pilot-name {
                        font-size: 13px; font-weight: 600; color: #333;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; line-height: 1.35;
                    }
                    .va-c-pilot-rank {
                        font-size: 9px; font-weight: 600; color: #8894a5;
                        letter-spacing: .3px; text-transform: uppercase; line-height: 1.35;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                    }
                    .va-c-alt  { font-size: 12px; font-weight: 600; color: #333; white-space: nowrap; }
                    .va-c-spd  { font-size: 12px; color: #555; white-space: nowrap; }
                    .va-c-dist { font-size: 12px; color: #333; white-space: nowrap; font-variant-numeric: tabular-nums; }
                    .va-c-etd  { font-size: 13px; color: #333; text-align: center; font-weight: 600; font-variant-numeric: tabular-nums; }

                    /* ── Status-Badge (Panel-intern) ── */
                    .va-st {
                        font-size: 10px; font-weight: 700;
                        padding: 3px 8px; border-radius: 5px;
                        text-align: center; white-space: nowrap; display: inline-block;
                    }
                    .va-st-fly   { background: #e8f5e9; color: #2e7d32; }
                    .va-st-taxi  { background: #fff3e0; color: #e65100; }
                    .va-st-board { background: #e3f2fd; color: #1565c0; }
                    .va-st-desc  { background: #e0f2f1; color: #00695c; }
                    .va-st-other { background: #f3f4f6; color: #666; }

                    /* ── Scroll ab 5 Zeilen ── */
                    .va-scroll-wrap { position: relative; }
                    .va-scroll-body {
                        max-height: 260px;   /* ca. 5 Zeilen à ~52px */
                        overflow-y: auto;
                        overflow-x: hidden;
                    }
                    .va-scroll-body::-webkit-scrollbar { width: 5px; }
                    .va-scroll-body::-webkit-scrollbar-track { background: #f5f5f5; }
                    .va-scroll-body::-webkit-scrollbar-thumb { background: #c0c8d4; border-radius: 4px; }
                    .va-scroll-wrap::after {
                        content: '';
                        position: absolute; bottom: 0; left: 0; right: 0;
                        height: 36px; pointer-events: none;
                        background: linear-gradient(transparent, rgba(255,255,255,0.95));
                        border-radius: 0 0 10px 10px;
                    }
                    /* Fade nur wenn wirklich scrollbar (JS setzt Klasse) */
                    .va-scroll-wrap.no-scroll::after { display: none; }
                    .va-scroll-hint {
                        position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
                        font-size: 9px; font-weight: 700; color: #aaa; letter-spacing: .5px;
                        text-transform: uppercase; z-index: 2; white-space: nowrap;
                        pointer-events: none;
                    }
                    .va-scroll-hint::before { content: '▼ '; font-size: 8px; }
                    .va-scroll-wrap.no-scroll .va-scroll-hint { display: none; }

                    /* ── Loading / Empty-State ── */
                    .va-table-info {
                        padding: 16px 12px;
                        text-align: center;
                        font-size: 11px;
                        color: #999;
                    }

                    /* ── Dark-Map-Modus ── */
                    .dark-map-panel #va-header-collapsed,
                    .dark-map-panel .va-header-expanded {
                        background: linear-gradient(135deg, #0e1825 0%, #152235 100%);
                    }
                    .dark-map-panel #va-flights-body {
                        background: rgba(22,30,44,0.97);
                        border-color: rgba(255,255,255,0.08);
                    }
                    .dark-map-panel .va-tabs {
                        background: #1a2333;
                        border-color: #2d3748;
                    }
                    .dark-map-panel .va-tab         { color: #6a7a90; }
                    .dark-map-panel .va-tab.active   { color: #7cb8f0; border-bottom-color: #3498db; }
                    .dark-map-panel .va-thead        { background: #1e2530; color: #5a6a80; border-color: #2a3240; }
                    .dark-map-panel .va-row          { border-color: #232d3d; color: #ccc; }
                    .dark-map-panel .va-row:hover    { background: #1e2a3a; }
                    .dark-map-panel .va-row.va-row-live { background: #152a1e; }
                    .dark-map-panel .va-c-flight     { color: #7eb8f7; }
                    .dark-map-panel .va-scroll-wrap::after {
                        background: linear-gradient(transparent, rgba(22,30,44,0.95));
                    }
                </style>

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
                    <style>
                        #va-boarding-pass {
                            /* Beide Methoden: verhindert Flicker beim Laden */
                            display: none !important;
                            visibility: hidden;
                            position: absolute;
                            top: 10px; right: 10px;
                            z-index: 1001;
                            width: 320px; /* desktop */
                            max-width: 100%;
                            background: #fff;
                            border-radius: 10px;
                            box-shadow: 0 6px 24px rgba(0,0,0,0.22), 0 2px 6px rgba(0,0,0,0.10);
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                            overflow: hidden;
                        }
                        #va-boarding-pass.bp-visible {
                            display: block !important;
                            visibility: visible;
                        }
                        .bp-head { background:linear-gradient(135deg,#1a2a4a 0%,#243b6a 100%); padding:10px 12px 8px; display:flex; align-items:center; justify-content:space-between; gap:8px; }
                        .bp-head-left { display:flex; align-items:center; gap:8px; min-width:0; }
                        .bp-logo-wrap { background:rgba(255,255,255,0.95); border-radius:6px; padding:4px 8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; height:34px; min-width:60px; box-shadow:0 1px 4px rgba(0,0,0,0.25); }
                        .bp-logo-wrap img { max-height:28px; max-width:80px; object-fit:contain; display:block; }
                        .bp-logo-wrap.no-logo { font-size:11px; font-weight:800; color:rgba(255,255,255,0.85); letter-spacing:1px; }
                        .bp-callsign { font-size:14px; font-weight:800; color:#fff; letter-spacing:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
                        .bp-close { background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.5); font-size:15px; line-height:1; padding:0; flex-shrink:0; transition:color .15s; }
                        .bp-close:hover { color:#fff; }
                        .bp-route { background:#f8f9fb; padding:12px 16px 12px; display:flex; align-items:center; justify-content:center; gap:0; border-bottom:2px solid #eef0f4; }
                        .bp-icao { text-align:center; flex:1; }
                        .bp-icao-code { font-size:22px; font-weight:900; color:#1a2a4a; letter-spacing:1px; line-height:1; }
                        .bp-icao-label { font-size:10px; color:#667; font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-top:3px; white-space:normal; line-height:1.3; max-width:110px; word-break:break-word; }
                        .bp-arrow { flex-shrink:0; padding:0 12px; display:flex; flex-direction:column; align-items:center; gap:4px; }
                        .bp-arrow-icon { font-size:26px; color:#3498db; line-height:1; }
                        .bp-arrow-dist { font-size:10px; color:#3498db; font-weight:700; white-space:nowrap; background:#e8f4fd; padding:2px 7px; border-radius:10px; }

                        .bp-grid { padding:8px 14px 10px; display:grid; grid-template-columns:1fr 1fr; gap:6px 10px; }
                        .bp-cell-label { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#aab; line-height:1; margin-bottom:2px; }
                        .bp-cell-value { font-size:12px; font-weight:700; color:#1a2a4a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
                        .bp-footer { background:#f4f6fa; border-top:1px solid #e8ecf0; padding:7px 14px; display:flex; align-items:center; justify-content:space-between; }
                        .bp-status { font-size:10px; font-weight:800; padding:3px 10px; border-radius:4px; letter-spacing:.4px; text-transform:uppercase; }
                        .bp-crew-label { font-size:8px; font-weight:700; color:#bbb; letter-spacing:1px; text-transform:uppercase; }
                        .bp-progress-wrap { margin-top:4px; }
                        .bp-progress-bar-bg { height:5px; background:#e8ecf3; border-radius:3px; overflow:hidden; margin-top:3px; }
                        .bp-progress-bar-fill { height:100%; background:linear-gradient(90deg,#3498db,#2ecc71); border-radius:3px; transition:width .4s ease; }
                    </style>

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


@section('scripts')
    @parent

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var LIVE_MAP_UI = @json($liveMapUiConfig);
            window.LIVE_MAP_UI = LIVE_MAP_UI || {};
            var MOBILE_BTN_INACTIVE = String(window.LIVE_MAP_UI.mobileButtonInactive || 'rgba(26,42,74,0.92)');
            var MOBILE_BTN_ACTIVE = String(window.LIVE_MAP_UI.mobileButtonActive || 'rgba(26,188,156,0.92)');

            function setMobileBtnState(el, isActive) {
                if (!el) return;
                el.classList.toggle('lm-mobile-active', !!isActive);
                el.style.background = isActive ? MOBILE_BTN_ACTIVE : MOBILE_BTN_INACTIVE;
                el.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                el.textContent = isActive ? '✈ Flights ▲' : '✈ Flights ▼';
            }

            // UI-Setup from phpVMS settings (Admin side)
            var _flightsPanel = document.getElementById('va-flights-panel');
            var _mobFlightsBtn = document.getElementById('mob-toggle-panel');
            var _weatherBox = document.getElementById('weather-box');
            var _networkBox = document.getElementById('vatsim-box');

            if (_flightsPanel && !window.LIVE_MAP_UI.showTopFlightsPanel) {
                _flightsPanel.classList.add('lm-hidden');
                if (_mobFlightsBtn) _mobFlightsBtn.classList.add('lm-hidden');
            }
            if (_weatherBox && !window.LIVE_MAP_UI.showWeatherBox) {
                _weatherBox.classList.add('lm-hidden');
            }
            if (_networkBox && !window.LIVE_MAP_UI.showNetworkBox) {
                _networkBox.classList.add('lm-hidden');
            }

            var _isMobile = window.innerWidth <= 768;
            if (_mobFlightsBtn && !window.LIVE_MAP_UI.mobileShowFlightsButton) {
                _mobFlightsBtn.classList.add('lm-hidden');
            }
            setMobileBtnState(_mobFlightsBtn, false);

            // ────────────────────────────────────────────────────────
            // Rivets formatters
            // ────────────────────────────────────────────────────────
            if (typeof rivets !== 'undefined') {
                window.liveMapProgress = { initialRemaining: null };

                function nm(val) {
                    if (val == null) return NaN;
                    if (typeof val === 'object' && 'nmi' in val) {
                        var n = parseFloat(val.nmi);
                        return isNaN(n) ? NaN : n;
                    }
                    var n2 = parseFloat(val);
                    return isNaN(n2) ? NaN : n2;
                }

                function progressFraction(remaining, total) {
                    var rem = nm(remaining);
                    var tot = nm(total);
                    if (!rem || rem < 0 || isNaN(rem)) return 0;
                    if (tot && tot > 0 && !isNaN(tot)) {
                        var done = (tot - rem) / tot;
                        if (!isFinite(done)) done = 0;
                        return Math.max(0, Math.min(1, done));
                    }
                    var store = window.liveMapProgress;
                    if (!store.initialRemaining || rem > store.initialRemaining) store.initialRemaining = rem;
                    var initial = store.initialRemaining;
                    var done2 = (initial - rem) / initial;
                    if (!isFinite(done2)) done2 = 0;
                    return Math.max(0, Math.min(1, done2));
                }

                rivets.formatters.to_go = function (remaining) {
                    var rem = nm(remaining);
                    if (!rem || rem < 0 || isNaN(rem)) return '—';
                    return Math.round(rem) + ' nmi';
                };
                rivets.formatters.progress_from_remaining = function (remaining, total) {
                    return Math.round(progressFraction(remaining, total) * 100) + '%';
                };
                rivets.formatters.progress_bar_style = function (remaining, total) {
                    var pct = Math.round(progressFraction(remaining, total) * 100);
                    var color = pct < 30 ? '#e74c3c' : pct < 60 ? '#f39c12' : pct < 85 ? '#f1c40f' : '#2ecc71';
                    return 'width:' + pct + '%; background:' + color + ';';
                };
                rivets.formatters.progress_circle_style = function (remaining, total) {
                    var pct = Math.round(progressFraction(remaining, total) * 100);
                    var color = pct < 30 ? '#e74c3c' : pct < 60 ? '#f39c12' : pct < 85 ? '#f1c40f' : '#2ecc71';
                    return 'background: conic-gradient(' + color + ' 0 ' + pct + '%, #e5e5e5 ' + pct + '% 100%);';
                };
                rivets.formatters.rem_time_from_remaining = function (remaining, gs) {
                    var rem = nm(remaining); var speed = parseFloat(gs);
                    if (!rem || rem <= 0 || !speed || speed <= 0) return '—';
                    var mins = Math.round((rem / speed) * 60);
                    var h = Math.floor(mins / 60), m = mins % 60;
                    return h <= 0 ? m + 'm' : h + 'h ' + (m < 10 ? '0' + m : m) + 'm';
                };
                rivets.formatters.eta_from_remaining = function (remaining, gs) {
                    var rem = nm(remaining); var speed = parseFloat(gs);
                    if (!rem || rem <= 0 || !speed || speed <= 0) return '—';
                    var eta = new Date(Date.now() + (rem / speed) * 3600000);
                    return eta.getHours().toString().padStart(2,'0') + ':' + eta.getMinutes().toString().padStart(2,'0');
                };
            }

            // ────────────────────────────────────────────────────────
            // Weather
            // ────────────────────────────────────────────────────────
            function attachWeatherToMap(map) {
                var weatherCfg = window.LIVE_MAP_UI || {};
                var mapDiv = document.getElementById("map");
                var btnDarkMap = document.getElementById("btnDarkMap");
                var btnClouds = document.getElementById("btnClouds");
                var btnRadar  = document.getElementById("btnRadar");
                var btnStorms = document.getElementById("btnStorms");
                var btnWind   = document.getElementById("btnWind");
                var btnTemp   = document.getElementById("btnTemp");
                var btnCombined  = document.getElementById("btnCombined");
                var opacitySlider = document.getElementById("weatherOpacity");
                var weatherDisabled = false;

                function upsertWeatherNote(message, color) {
                    var weatherContent = document.getElementById('weather-content');
                    if (!weatherContent) return;
                    var note = document.getElementById('weather-unavailable-note');
                    if (!note) {
                        note = document.createElement('div');
                        note.id = 'weather-unavailable-note';
                        note.style.cssText = 'margin-top:8px;font-size:11px;text-align:center';
                        weatherContent.appendChild(note);
                    }
                    note.style.color = color || '#777';
                    note.textContent = message;
                }

                function setWeatherWarning(message) {
                    if (weatherDisabled) return;
                    upsertWeatherNote(message, '#8a6d3b');
                }

                function setWeatherUnavailable(message) {
                    weatherDisabled = true;
                    var weatherBtns = [btnClouds, btnRadar, btnStorms, btnWind, btnTemp, btnCombined];
                    weatherBtns.forEach(function(btn) {
                        if (!btn) return;
                        btn.disabled = true;
                        btn.classList.remove("active");
                        btn.style.opacity = "0.45";
                        btn.style.cursor = "not-allowed";
                        btn.title = message;
                    });
                    if (opacitySlider) {
                        opacitySlider.disabled = true;
                        opacitySlider.value = "1";
                    }
                    var sliderWrap = document.querySelector('.weather-slider-wrapper');
                    if (sliderWrap) sliderWrap.style.display = 'none';

                    upsertWeatherNote(message, '#777');
                }

                if (btnDarkMap && mapDiv) {
                    btnDarkMap.addEventListener("click", function () {
                        var dark = mapDiv.classList.toggle("dark-map");
                        btnDarkMap.classList.toggle("active", dark);
                        localStorage.setItem('livemap_darkmode', dark ? '1' : '0');
                    });
                    if (localStorage.getItem('livemap_darkmode') === '1') {
                        mapDiv.classList.add("dark-map");
                        btnDarkMap.classList.add("active");
                    }
                }

                var weatherProxyEnabled = !!weatherCfg.weatherProxyEnabled;
                var weatherProxyBaseUrl = String(weatherCfg.weatherProxyBaseUrl || '').replace(/\/+$/, '');
                var OWM_API_KEY = String(weatherCfg.owmApiKey || '').trim();

                if (weatherProxyEnabled) {
                    if (!weatherProxyBaseUrl) {
                        console.warn('[LiveMap] Weather proxy URL missing; skipping overlays');
                        setWeatherUnavailable('Weather layers unavailable (proxy not configured)');
                        return;
                    }
                } else {
                    if (!OWM_API_KEY || OWM_API_KEY === "YOUR_OPENWEATHERMAP_API_KEY_HERE" || OWM_API_KEY === "Enter your key here") {
                        console.warn('[LiveMap] OWM API key not set; skipping overlays');
                        setWeatherUnavailable('Weather layers unavailable (missing API key)');
                        return;
                    }
                }

                var weatherPane = map.getPane('weatherPane');
                if (!weatherPane) { map.createPane('weatherPane'); weatherPane = map.getPane('weatherPane'); }
                weatherPane.style.zIndex = 650;
                weatherPane.style.pointerEvents = 'none';

                function weatherTileUrl(layerName) {
                    if (weatherProxyEnabled) {
                        return weatherProxyBaseUrl + '/' + layerName + '/{z}/{x}/{y}.png';
                    }
                    return "https://tile.openweathermap.org/map/" + layerName + "/{z}/{x}/{y}.png?appid=" + encodeURIComponent(OWM_API_KEY);
                }

                var cloudsLayer = L.tileLayer(weatherTileUrl("clouds_new"), { opacity:1, pane:'weatherPane', className:'owm-clouds-layer', attribution:"Clouds © OpenWeatherMap" });
                var precipLayer = L.tileLayer(weatherTileUrl("precipitation_new"), { opacity:1, pane:'weatherPane', className:'owm-precip-layer', attribution:"Precipitation © OpenWeatherMap" });
                var stormsLayerCandidates = ["thunder_new", "weather_new", "precipitation_new"];
                var stormsLayerCandidateIndex = 0;
                var stormsLayerName = stormsLayerCandidates[stormsLayerCandidateIndex];
                var stormsLayer = L.tileLayer(weatherTileUrl(stormsLayerName), { opacity:1, pane:'weatherPane', className:'owm-thunder-layer owm-storms-layer', attribution:"Thunderstorms © OpenWeatherMap" });
                var windLayer   = L.tileLayer(weatherTileUrl("wind_new"), { opacity:1, pane:'weatherPane', className:'owm-wind-layer', attribution:"Wind © OpenWeatherMap" });
                var tempLayer   = L.tileLayer(weatherTileUrl("temp_new"), { opacity:1, pane:'weatherPane', className:'owm-temp-layer', attribution:"Temperature © OpenWeatherMap" });

                if (!btnClouds || !btnRadar) return;

                var allLayers = [cloudsLayer, precipLayer, stormsLayer, windLayer, tempLayer];
                btnClouds._on = btnRadar._on = btnStorms._on = btnWind._on = btnTemp._on = false;
                [
                    [btnClouds, cloudsLayer, 'Clouds', true],
                    [btnRadar, precipLayer, 'Radar', true],
                    [btnStorms, stormsLayer, 'Storms', true],
                    [btnWind, windLayer, 'Wind', false],
                    [btnTemp, tempLayer, 'Temperature', false]
                ].forEach(function(meta){
                    var btn = meta[0], layer = meta[1], label = meta[2], combo = meta[3];
                    layer._lmBtn = btn;
                    layer._lmLabel = label;
                    layer._lmCombo = combo;
                    layer._lmDisabled = false;
                    layer._lmErrCount = 0;
                });

                function setAllWeatherOpacity(op) { allLayers.forEach(function(l){ if(l.setOpacity) l.setOpacity(op); }); }
                function syncCombinedButtonState() {
                    if (!btnCombined) return;
                    var comboLayersAvailable = !cloudsLayer._lmDisabled && !precipLayer._lmDisabled && !stormsLayer._lmDisabled;
                    if (weatherDisabled || !comboLayersAvailable) {
                        btnCombined.disabled = true;
                        btnCombined.classList.remove("active");
                        btnCombined.style.opacity = "0.45";
                        btnCombined.style.cursor = "not-allowed";
                        btnCombined.title = "Combo unavailable (one or more combo layers failed)";
                        return;
                    }
                    btnCombined.disabled = false;
                    btnCombined.style.opacity = "";
                    btnCombined.style.cursor = "";
                    btnCombined.title = "Combined mode";
                    btnCombined.classList.toggle("active", !!btnClouds._on && !!btnRadar._on && !!btnStorms._on);
                }

                function disableWeatherLayerFromErrors(layer, tileErr) {
                    if (weatherDisabled || !layer || layer._lmDisabled) return;
                    layer._lmDisabled = true;

                    try { map.removeLayer(layer); } catch (e) {}
                    if (layer._lmOnError) layer.off('tileerror', layer._lmOnError);

                    if (layer._lmBtn) {
                        layer._lmBtn._on = false;
                        layer._lmBtn.disabled = true;
                        layer._lmBtn.classList.remove("active");
                        layer._lmBtn.style.opacity = "0.45";
                        layer._lmBtn.style.cursor = "not-allowed";
                        layer._lmBtn.title = (layer._lmLabel || 'Weather layer') + " unavailable (tile errors)";
                    }

                    var status = tileErr && tileErr.error && tileErr.error.status
                        ? (' (HTTP ' + tileErr.error.status + ')')
                        : '';
                    setWeatherWarning((layer._lmLabel || 'A weather') + ' layer unavailable (tile errors)' + status + '. Other layers remain available.');
                    syncCombinedButtonState();

                    if (allLayers.every(function(l){ return !!l._lmDisabled; })) {
                        setWeatherUnavailable('Weather layers unavailable (all tile layers failed)' + status);
                    }
                }

                allLayers.forEach(function(layer){
                    layer._lmOnError = function(ev) {
                        if (weatherDisabled || layer._lmDisabled) return;
                        if (layer === stormsLayer && stormsLayerCandidateIndex < (stormsLayerCandidates.length - 1)) {
                            var prevName = stormsLayerName;
                            stormsLayerCandidateIndex++;
                            stormsLayerName = stormsLayerCandidates[stormsLayerCandidateIndex];
                            try {
                                stormsLayer.setUrl(weatherTileUrl(stormsLayerName), false);
                                stormsLayer._lmErrCount = 0;
                                console.warn('[LiveMap] Storms layer fallback: ' + prevName + ' -> ' + stormsLayerName);
                                return;
                            } catch (e) {
                                console.warn('[LiveMap] Storms fallback switch failed', e);
                            }
                        }
                        layer._lmErrCount = (layer._lmErrCount || 0) + 1;
                        if (layer._lmErrCount === 1) {
                            console.warn('[LiveMap] Weather tile request failed for ' + (layer._lmLabel || 'layer'), ev && ev.error ? ev.error : ev);
                        }
                        if (layer._lmErrCount >= 3) {
                            disableWeatherLayerFromErrors(layer, ev);
                        }
                    };
                    layer.on('tileerror', layer._lmOnError);
                });

                function activateLayer(btn, layer) {
                    if (weatherDisabled || !btn || !layer || btn._on || btn.disabled || layer._lmDisabled) return;
                    layer.addTo(map);
                    btn._on = true;
                    btn.classList.add("active");
                    syncCombinedButtonState();
                }
                function toggleLayer(btn, layer) {
                    if (weatherDisabled || !btn || !layer || btn.disabled || layer._lmDisabled) return;
                    if (btn._on) { map.removeLayer(layer); btn.classList.remove("active"); }
                    else { layer.addTo(map); btn.classList.add("active"); }
                    btn._on = !btn._on;
                    syncCombinedButtonState();
                }

                btnClouds.addEventListener("click",   function(){ toggleLayer(btnClouds, cloudsLayer); });
                btnRadar.addEventListener("click",    function(){ toggleLayer(btnRadar, precipLayer); });
                btnStorms.addEventListener("click",   function(){ toggleLayer(btnStorms, stormsLayer); });
                btnWind.addEventListener("click",     function(){ toggleLayer(btnWind, windLayer); });
                btnTemp.addEventListener("click",     function(){ toggleLayer(btnTemp, tempLayer); });
                btnCombined.addEventListener("click", function(){
                    if (weatherDisabled || btnCombined.disabled) return;
                    activateLayer(btnClouds, cloudsLayer);
                    activateLayer(btnRadar, precipLayer);
                    activateLayer(btnStorms, stormsLayer);
                });
                if (opacitySlider) {
                    opacitySlider.addEventListener("input", function(){ setAllWeatherOpacity(parseFloat(this.value)); });
                }

                var defaultOpacity = parseFloat(weatherCfg.weatherDefaultOpacity);
                if (isNaN(defaultOpacity) || defaultOpacity < 0.2 || defaultOpacity > 1) defaultOpacity = 1;
                if (opacitySlider) opacitySlider.value = String(defaultOpacity);
                setAllWeatherOpacity(defaultOpacity);

                var defaultLayer = String(weatherCfg.weatherDefaultLayer || 'none').toLowerCase();
                if (defaultLayer === 'combo') {
                    activateLayer(btnClouds, cloudsLayer);
                    activateLayer(btnRadar, precipLayer);
                    activateLayer(btnStorms, stormsLayer);
                } else if (defaultLayer === 'clouds') {
                    activateLayer(btnClouds, cloudsLayer);
                } else if (defaultLayer === 'radar') {
                    activateLayer(btnRadar, precipLayer);
                } else if (defaultLayer === 'storms') {
                    activateLayer(btnStorms, stormsLayer);
                } else if (defaultLayer === 'wind') {
                    activateLayer(btnWind, windLayer);
                } else if (defaultLayer === 'temp') {
                    activateLayer(btnTemp, tempLayer);
                }
                syncCombinedButtonState();
            }

            // ════════════════════════════════════════════════════════════
            //  VA FLIGHTS PANEL — neues Design mit Tabs + Scroll
            //  Active = ACARS-Flüge in der Luft/rollend
            //  Planned = ACARS-Flüge in Boarding/Pre-flight-Zustand
            // ════════════════════════════════════════════════════════════
            (function () {
                var VA_API        = window.location.origin + '/api/acars';
                var VA_REFRESH_MS = ({{ setting('acars.update_interval', 60) }} * 1000) || 60000;
                var panelOpen     = false;
                var activeCallsign = null;
                var currentTab    = 'active'; // 'active' | 'planned'

                // DOM-Refs
                var headerCollapsed  = document.getElementById('va-header-collapsed');
                var headerExpanded   = document.getElementById('va-header-expanded');
                var panelBody        = document.getElementById('va-flights-body');
                var tabBtnActive     = document.getElementById('va-tab-btn-active');
                var tabBtnPlanned    = document.getElementById('va-tab-btn-planned');
                var tabPanelActive   = document.getElementById('va-tab-active');
                var tabPanelPlanned  = document.getElementById('va-tab-planned');
                var rowsActive       = document.getElementById('va-rows-active');
                var rowsPlanned      = document.getElementById('va-rows-planned');
                var scrollWrapActive = document.getElementById('va-scroll-active');
                var scrollWrapPlan   = document.getElementById('va-scroll-planned');

                // Zähler in beiden Headern + Tabs synchronisieren
                function setCount(activeN, plannedN) {
                    ['va-count-active-hdr','va-count-active-exp','va-tab-count-active'].forEach(function(id){
                        var el = document.getElementById(id); if(el) el.textContent = activeN;
                    });
                    ['va-count-planned-hdr','va-count-planned-exp','va-tab-count-planned'].forEach(function(id){
                        var el = document.getElementById(id); if(el) el.textContent = plannedN;
                    });
                }

                // Toggle öffnen/schließen
                function togglePanel() {
                    // Pass schließen wenn Panel getoggelt
                    var card = document.getElementById('va-boarding-pass');
                    if(card) card.classList.remove('bp-visible');
                    setTimeout(adjustPanelHeight, 360); // nach Transition
                    panelOpen = !panelOpen;
                    if (panelOpen) {
                        headerCollapsed.style.display = 'none';
                        panelBody.classList.add('open');
                    } else {
                        panelBody.classList.remove('open');
                        // collapsed nach Ende der Transition wieder einblenden
                        setTimeout(function(){ headerCollapsed.style.display = ''; }, 350);
                    }
                }
                if (headerCollapsed) headerCollapsed.addEventListener('click', togglePanel);
                if (headerExpanded)  headerExpanded.addEventListener('click', togglePanel);

                // Tab-Wechsel
                function switchTab(tab) {
                    currentTab = tab;
                    setTimeout(adjustPanelHeight, 30);
                    tabBtnActive.classList.toggle('active',  tab === 'active');
                    tabBtnPlanned.classList.toggle('active', tab === 'planned');
                    tabPanelActive.classList.toggle('active',  tab === 'active');
                    tabPanelPlanned.classList.toggle('active', tab === 'planned');
                }
                if (tabBtnActive)  tabBtnActive.addEventListener('click',  function(){
                    switchTab('active');
                    var card = document.getElementById('va-boarding-pass');
                    if(card) card.classList.remove('bp-visible');
                });
                if (tabBtnPlanned) tabBtnPlanned.addEventListener('click', function(){
                    switchTab('planned');
                    var card = document.getElementById('va-boarding-pass');
                    if(card) card.classList.remove('bp-visible');
                });

                // Scroll-Fade: nur anzeigen wenn Inhalt wirklich überläuft
                function updateScrollFade(wrap, scrollEl) {
                    if (!wrap || !scrollEl) return;
                    var scrollable = scrollEl.scrollHeight > scrollEl.clientHeight + 4;
                    wrap.classList.toggle('no-scroll', !scrollable);
                    // Bei Scrollen bis unten Fade ausblenden
                    scrollEl.onscroll = function() {
                        var atBottom = scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 8;
                        wrap.classList.toggle('no-scroll', atBottom || !scrollable);
                    };
                }

                // Deutsche Status-Texte → Englisch
                var STATUS_DE_EN = {
                    'unterwegs':'En Route','im flug':'En Route','in der luft':'En Route',
                    'geplant':'Planned','boarding':'Boarding','rollt':'Taxiing','rollen':'Taxiing',
                    'starten':'Taking Off','steigen':'Climbing','reiseflug':'Cruise',
                    'sinken':'Descending','anflug':'Approach','landung':'Landing',
                    'gelandet':'Landed','abgeschlossen':'Completed','abgebrochen':'Cancelled','pausiert':'Paused',
                };
                function translateStatus(s) {
                    if (!s) return s;
                    return STATUS_DE_EN[s.toLowerCase().trim()] || s;
                }
                window.translateStatus = translateStatus;

                // Status → CSS-Klasse (Panel-intern)
                function panelStatusClass(s) {
                    if (!s) return 'va-st-other';
                    var sl = s.toLowerCase();
                    if (sl.includes('en route') || sl.includes('unterwegs') || sl.includes('cruise') ||
                        sl.includes('climb') || sl.includes('airborne') || sl.includes('enroute')) return 'va-st-fly';
                    if (sl.includes('taxi') || sl.includes('rollt') || sl.includes('push') ||
                        sl.includes('taking') || sl.includes('starten')) return 'va-st-taxi';
                    if (sl.includes('board') || sl.includes('planned') || sl.includes('geplant') ||
                        sl.includes('sched') || sl.includes('preflight')) return 'va-st-board';
                    if (sl.includes('descent') || sl.includes('approach') || sl.includes('sinken') ||
                        sl.includes('anflug') || sl.includes('landing')) return 'va-st-desc';
                    return 'va-st-other';
                }

                // Ist ein Flug "aktiv" (in der Luft/rollt) oder "geplant/boarding"?
                function isActiveFlight(f) {
                    if (f._isBid) return false;
                    var stat = translateStatus(f.status_text || f.status || '').toLowerCase();
                    // Hat Position mit Bewegung → aktiv
                    if (f.position && f.position.gs && parseFloat(f.position.gs) > 5) return true;
                    if (f.position && f.position.altitude && parseFloat(f.position.altitude) > 200) return true;
                    // Status-basiert
                    var activeWords = ['enroute','en route','unterwegs','cruise','climb','descent',
                                       'approach','airborne','takeoff','landing','taxi','rollt','push'];
                    for (var i = 0; i < activeWords.length; i++) {
                        if (stat.includes(activeWords[i])) return true;
                    }
                    return false;
                }

                // Airline-Logo URL aus AIRLINE_LOGOS (wird weiter unten definiert)
                function getLogoUrl(airline) {
                    if (!airline) return '';
                    var icao = (airline.icao || '').toUpperCase();
                    if (!icao) return '';
                    if (typeof AIRLINE_LOGOS !== 'undefined' && AIRLINE_LOGOS[icao]) return AIRLINE_LOGOS[icao];
                    return airline.logo || '';
                }

                // Logo-HTML für Zeilen
                function rowLogoHtml(airline) {
                    var url = getLogoUrl(airline);
                    if (!url) return '<span class="va-logo-box"></span>';
                    return '<span class="va-logo-box"><img src="' +
                        url.replace(/^http:\/\//i, 'https://') +
                        '" onerror="this.parentElement.innerHTML=\'\'"></span>';
                }

                // Pilot-Name aus Flug-Objekt
                function pilotName(f) {
                    if (f.user) {
                        return f.user.name ||
                            (f.user.first_name ? f.user.first_name + (f.user.last_name ? ' ' + f.user.last_name.charAt(0) + '.' : '') : '') || '—';
                    }
                    if (f.pilot) return f.pilot.name || f.pilot.first_name || '—';
                    return '—';
                }

                // Pilot-Rank (aus phpVMS-Daten wenn vorhanden)
                function pilotRank(f) {
                    if (f.user && f.user.rank && f.user.rank.name) return f.user.rank.name;
                    if (f.pilot && f.pilot.rank) return f.pilot.rank;
                    return '';
                }

                // Zeile für Active-Tab bauen
                function buildActiveRow(f) {
                    var callsign = (f.airline && f.airline.icao ? f.airline.icao : '') + (f.flight_number || f.callsign || '');
                    var dep  = (f.dpt_airport && (f.dpt_airport.icao || f.dpt_airport.id)) || '—';
                    var arr  = (f.arr_airport  && (f.arr_airport.icao  || f.arr_airport.id))  || '—';
                    var reg  = (f.aircraft && f.aircraft.registration) || '';
                    var ac   = (f.aircraft && f.aircraft.icao) || '';
                    var acStr = reg ? reg + (ac ? ' (' + ac + ')' : '') : (ac || '—');
                    var alt  = (f.position && f.position.altitude)
                                ? (parseFloat(f.position.altitude) > 1000
                                    ? 'FL' + Math.round(parseFloat(f.position.altitude) / 100)
                                    : Math.round(parseFloat(f.position.altitude)) + ' ft')
                                : '—';
                    var spd  = (f.position && f.position.gs) ? f.position.gs + ' kt' : '—';
                    var stat = translateStatus(f.status_text || f.status || '—');
                    var sCls = panelStatusClass(stat);

                    var distFlown   = (f.position && f.position.distance && f.position.distance.nmi != null)
                                        ? Math.round(parseFloat(f.position.distance.nmi)) : null;
                    var distPlanned = (f.planned_distance && f.planned_distance.nmi != null)
                                        ? Math.round(parseFloat(f.planned_distance.nmi)) : null;
                    var dist = distFlown !== null && distPlanned !== null
                                ? distFlown + ' / ' + distPlanned
                                : distFlown !== null ? distFlown + ' nmi'
                                : distPlanned !== null ? '— / ' + distPlanned
                                : '—';

                    var pName = pilotName(f);
                    var pRank = pilotRank(f);
                    var lat   = f.position && f.position.lat ? parseFloat(f.position.lat) : null;
                    var lng   = f.position && f.position.lon ? parseFloat(f.position.lon)
                              : f.position && f.position.lng ? parseFloat(f.position.lng) : null;

                    var row = document.createElement('div');
                    row.className = 'va-row va-g-act va-row-live' + (callsign === activeCallsign ? ' active-flight' : '');
                    row.setAttribute('data-callsign', callsign);
                    row.innerHTML =
                        '<div class="va-c-flight">' + rowLogoHtml(f.airline) + '<span>' + (callsign || '—') + '</span></div>' +
                        '<div class="va-c-route"><span class="va-icao">' + dep + '</span><span class="va-arr">›</span><span class="va-icao">' + arr + '</span></div>' +
                        '<div class="va-c-alt">' + alt + '</div>' +
                        '<div class="va-c-spd">' + spd + '</div>' +
                        '<div class="va-c-dist">' + dist + '</div>' +
                        '<div style="text-align:center"><span class="va-st ' + sCls + '">' + stat + '</span></div>' +
                        '<div><div class="va-c-pilot-name" title="' + h(pName) + '">' + h(pName) + '</div>' +
                        (pRank ? '<div class="va-c-pilot-rank">' + pRank + '</div>' : '') + '</div>';

                    row.addEventListener('click', function () {
                        document.querySelectorAll('#va-rows-active .active-flight, #va-rows-planned .active-flight')
                            .forEach(function(r){ r.classList.remove('active-flight'); });
                        row.classList.add('active-flight');
                        activeCallsign = callsign;
                        if (typeof window.vaInfoCardOpen === 'function') window.vaInfoCardOpen(f, lat, lng);
                    });
                    return row;
                }

                // Zeile für Planned-Tab bauen
                function buildPlannedRow(f) {
                    var callsign = (f.airline && f.airline.icao ? f.airline.icao : '') + (f.flight_number || f.callsign || '');
                    var dep      = (f.dpt_airport && (f.dpt_airport.icao || f.dpt_airport.id)) || f.dpt_airport_id || '—';
                    var arr      = (f.arr_airport  && (f.arr_airport.icao  || f.arr_airport.id))  || f.arr_airport_id || '—';
                    var depName  = (f.dpt_airport && f.dpt_airport.name) || '';
                    var arrName  = (f.arr_airport  && f.arr_airport.name)  || '';
                    var depShort = depName ? depName.split(/[\/,(\-]/)[0].trim() : '';
                    var arrShort = arrName ? arrName.split(/[\/,(\-]/)[0].trim() : '';

                    var bidBadge = f._isBid
                        ? '<span style="display:inline-block;font-size:9px;font-weight:700;background:#e3f2fd;color:#1565c0;padding:1px 5px;border-radius:3px;margin-left:5px;vertical-align:middle">BOOKED</span>'
                        : '';

                    var pName = pilotName(f);
                    var pRank = pilotRank(f);

                    var row = document.createElement('div');
                    row.className = 'va-row va-g-plan' + (callsign === activeCallsign ? ' active-flight' : '');
                    row.setAttribute('data-callsign', callsign);
                    row.innerHTML =
                        // Spalte 1: Logo + Callsign + Badge + Route darunter
                        '<div>' +
                            '<div class="va-c-flight">' + rowLogoHtml(f.airline) +
                                '<span>' + (callsign||'—') + '</span>' + bidBadge +
                            '</div>' +
                            '<div style="margin-top:3px;font-size:11px;font-weight:500;color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' +
                                '<span style="font-weight:800;color:#1a3a6b;letter-spacing:.3px">' + dep + '</span>' +
                                (depShort ? '<span class="va-route-airport-name" style="color:#999"> ' + depShort + '</span>' : '') +
                                '<span style="color:#3498db;font-weight:700;margin:0 6px">›</span>' +
                                '<span style="font-weight:800;color:#1a3a6b;letter-spacing:.3px">' + arr + '</span>' +
                                (arrShort ? '<span class="va-route-airport-name" style="color:#999"> ' + arrShort + '</span>' : '') +
                            '</div>' +
                        '</div>' +
                        // Spalte 2: Pilot
                        '<div style="text-align:right"><div class="va-c-pilot-name" title="' + h(pName) + '">' + h(pName) + '</div>' +
                        (pRank ? '<div class="va-c-pilot-rank">' + pRank + '</div>' : '') + '</div>';

                    row.addEventListener('click', function () {
                        document.querySelectorAll('#va-rows-active .active-flight, #va-rows-planned .active-flight')
                            .forEach(function(r){ r.classList.remove('active-flight'); });
                        row.classList.add('active-flight');
                        activeCallsign = callsign;
                        // Keine Info-Kachel bei Planned — nur Karte zentrieren
                        var depIcao = dep !== '—' ? dep : null;
                        if (depIcao && typeof staticAirportPos !== 'undefined' && staticAirportPos[depIcao] && typeof map !== 'undefined' && map._loaded) {
                            map.setView(staticAirportPos[depIcao], Math.max(map.getZoom(), 6), {animate: true});
                        }
                        var card = document.getElementById('va-boarding-pass');
                        if (card) card.style.display = 'none';
                    });
                    return row;
                }

                function adjustPanelHeight() {
                    var body = document.getElementById('va-flights-body');
                    if (!body || !body.classList.contains('open')) return;
                    // Kurz auf auto setzen um echte Höhe zu messen
                    body.style.maxHeight = 'none';
                    var h = body.scrollHeight;
                    body.style.maxHeight = Math.min(h, window.innerHeight * 0.7) + 'px';
                }

                function renderFlights(flights) {
                    if (!rowsActive || !rowsPlanned) return;

                    var activeList  = [];
                    var plannedList = [];

                    flights.forEach(function(f) {
                        if (f._isBid) plannedList.push(f);
                        else          activeList.push(f);
                    });

                    // Cache für Marker-Klick
                    window._vaActiveFlights = activeList;

                    setCount(activeList.length, plannedList.length);
                    setTimeout(adjustPanelHeight, 50);

                    // Active
                    rowsActive.innerHTML = '';
                    if (activeList.length === 0) {
                        rowsActive.innerHTML = '<div class="va-table-info">No active flights</div>';
                    } else {
                        activeList.forEach(function(f){ rowsActive.appendChild(buildActiveRow(f)); });
                    }
                    setTimeout(function(){ updateScrollFade(scrollWrapActive, rowsActive); }, 50);

                    // Planned
                    rowsPlanned.innerHTML = '';
                    if (plannedList.length === 0) {
                        rowsPlanned.innerHTML = '<div class="va-table-info">No planned flights</div>';
                    } else {
                        plannedList.forEach(function(f){ rowsPlanned.appendChild(buildPlannedRow(f)); });
                    }
                    setTimeout(function(){ updateScrollFade(scrollWrapPlan, rowsPlanned); }, 50);

                    // Keep all active aircraft in view when follow mode is enabled
                    if (typeof window._liveMapFitToActiveFlights === 'function') {
                        setTimeout(function(){ window._liveMapFitToActiveFlights({ animate: false }); }, 80);
                    }
                }

                function loadVaFlights() {
                    fetch(VA_API)
                        .then(function(r){ return r.json(); })
                        .then(function(resp){
                            var acarsFlights = Array.isArray(resp) ? resp
                                            : (resp.data && Array.isArray(resp.data)) ? resp.data : [];
                            var activeFlightKeys = {};
                            function normId(v) { return (v == null) ? '' : String(v).trim(); }
                            function activeUserId(f) {
                                if (!f) return '';
                                if (f.user_id != null) return normId(f.user_id);
                                if (f.user && f.user.id != null) return normId(f.user.id);
                                if (f.pilot_id != null) return normId(f.pilot_id);
                                if (f.pilot && f.pilot.id != null) return normId(f.pilot.id);
                                return '';
                            }
                            acarsFlights.forEach(function(f){
                                var flightId = normId(f && f.flight_id);
                                if (!flightId) return;
                                var userId = activeUserId(f);
                                if (userId) activeFlightKeys[userId + ':' + flightId] = true;
                                activeFlightKeys['*:' + flightId] = true;
                            });
                            var bids = (typeof VA_USER_BIDS !== 'undefined' ? VA_USER_BIDS : [])
                                .filter(function(b){
                                    var bidFlightId = normId(b && b.flight_id);
                                    if (!bidFlightId) return true;
                                    var bidUserId = normId(b && b._bidUserId);
                                    if (bidUserId) return !activeFlightKeys[bidUserId + ':' + bidFlightId];
                                    return !activeFlightKeys['*:' + bidFlightId];
                                });
                            renderFlights(acarsFlights.concat(bids));
                        })
                        .catch(function(){
                            setCount('!', '!');
                            if (rowsActive)  rowsActive.innerHTML  = '<div class="va-table-info">⚠ Unavailable</div>';
                            if (rowsPlanned) rowsPlanned.innerHTML = '<div class="va-table-info">⚠ Unavailable</div>';
                        });
                }

                loadVaFlights();
                setInterval(loadVaFlights, VA_REFRESH_MS);

                // Dark-Map → Panel abdunkeln
                var wrapper = document.querySelector('.live-map-wrapper');
                var mapEl   = document.getElementById('map');
                if (mapEl && wrapper) {
                    new MutationObserver(function(){
                        wrapper.classList.toggle('dark-map-panel', mapEl.classList.contains('dark-map'));
                    }).observe(mapEl, { attributes: true, attributeFilter: ['class'] });
                }
            })();

            // ════════════════════════════════════════════════════════════
            //  VATSIM LIVE INTEGRATION
            // ════════════════════════════════════════════════════════════

            // ════════════════════════════════════════════════════════════
            //  PORTABLE CONFIG — works on any domain / phpVMS installation
            //  phpVMS API: window.location.origin is used automatically.
            //  External APIs below are public — no changes needed.
            // ════════════════════════════════════════════════════════════
            var PHPVMS_BASE       = window.location.origin;   // auto-detect
            var VATSIM_DATA_API   = 'https://data.vatsim.net/v3/vatsim-data.json';
            var VATSIM_TRX_API    = 'https://data.vatsim.net/v3/transceivers-data.json';
            var VATSPY_BOUNDS_API = 'https://raw.githubusercontent.com/vatsimnetwork/vatspy-data-project/master/Boundaries.geojson';
            var VATSPY_DAT_API    = 'https://raw.githubusercontent.com/vatsimnetwork/vatspy-data-project/master/VATSpy.dat';
            var IVAO_DATA_API     = 'https://api.ivao.aero/v2/tracker/whazzup';
            var VATSIM_REFRESH_MS = 30000;
            var IVAO_REFRESH_MS   = 15000;

            var UPPER_FIR = { 'EDUU':1,'EDYY':1,'ESAA':1,'EISN':1,'BIRD':1,'GMMM':1 };

            var staticAirportPos    = {};
            var airportNameCache    = {};
            var staticAirportLoaded = false;
            var firNameCache        = {};
            var firNameLoaded       = false;

            var showVatsim = !!(window.LIVE_MAP_UI && window.LIVE_MAP_UI.defaultVatsimEnabled);
            var showIvao   = !!(window.LIVE_MAP_UI && window.LIVE_MAP_UI.defaultIvaoEnabled);

            var vatsimShowPilots  = !!(window.LIVE_MAP_UI && window.LIVE_MAP_UI.defaultShowPilots);
            var vatsimShowCtrl    = !!(window.LIVE_MAP_UI && window.LIVE_MAP_UI.defaultShowControllers);
            var vatsimShowSectors = !!(window.LIVE_MAP_UI && window.LIVE_MAP_UI.defaultShowSectors);

            var vatsimPilotsLayer = L.layerGroup();
            var vatsimCtrlLayer   = L.layerGroup();
            var vatsimSectorLayer = L.layerGroup();
            var ivaoPilotsLayer   = L.layerGroup();
            var ivaoCtrlLayer     = L.layerGroup();
            var ivaoSectorLayer   = L.layerGroup();
            var routeLineLayer    = L.layerGroup();
            var lastDrawnArr      = null;

            function showRouteLine(map, fromLatLng, toIcao) {
                routeLineLayer.clearLayers();
                var toPos = staticAirportPos[toIcao] || staticAirportPos['K'+toIcao] || staticAirportPos['C'+toIcao] || staticAirportPos['P'+toIcao];
                if (!toPos) return;
                L.polyline([fromLatLng, toPos], { color:'#e74c3c', weight:2, opacity:0.8, dashArray:'8 6' }).addTo(routeLineLayer);
                L.marker(toPos, {
                    icon: L.divIcon({ html:'<div style="background:#e74c3c;color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:3px;white-space:nowrap;box-shadow:0 1px 4px rgba(0,0,0,0.4)">' + toIcao + '</div>', className:'', iconSize:[null,null], iconAnchor:[20,-4] }),
                    interactive: false,
                }).addTo(routeLineLayer);
            }

            var firBoundsGeoJson = null;
            var ctrlPosCache     = {};
            var firPrefixMap     = {};
            var uirToFirsMap     = {};

            var CTRL_TYPES = {
                0:{label:'OBS',color:'#95a5a6'}, 1:{label:'FSS',color:'#8e44ad'},
                2:{label:'DEL',color:'#2980b9'}, 3:{label:'GND',color:'#d35400'},
                4:{label:'TWR',color:'#e74c3c'}, 5:{label:'APP',color:'#27ae60'},
                6:{label:'CTR',color:'#1abc9c'},
            };

            @php
                try {
                    $airlineLogos = \App\Models\Airline::whereNotNull('logo')
                        ->where('logo', '!=', '')
                        ->get(['icao', 'logo'])
                        ->mapWithKeys(function ($a) {
                            $logo = $a->logo;
                            if ($logo && !str_starts_with($logo, 'http')) $logo = url($logo);
                            if ($logo && str_starts_with($logo, 'http://')) $logo = 'https://' . substr($logo, 7);
                            return [strtoupper($a->icao) => $logo];
                        })->toArray();
                } catch (\Exception $e) {
                    $airlineLogos = [];
                }
            @endphp
            var AIRLINE_LOGOS = @json($airlineLogos);
            var logosReady = Promise.resolve();

            var VA_USER_BIDS = (function() {
                try {
                    @php
                    $userBidsJson = \App\Models\Bid::query()
                        ->with(['user', 'flight', 'flight.airline', 'flight.dpt_airport', 'flight.arr_airport'])
                        ->get()
                        ->map(function($bid) {
                            $f = $bid->flight;
                            if (!$f) return null;
                            $bidUserName = optional($bid->user)->name;
                            if (!$bidUserName) {
                                $bidUserName = trim((optional($bid->user)->first_name ?? '') . ' ' . (optional($bid->user)->last_name ?? ''));
                            }
                            if (!$bidUserName) $bidUserName = '—';
                            $logo = optional($f->airline)->logo;
                            if ($logo && !str_starts_with($logo, 'http')) $logo = url($logo);
                            if ($logo && str_starts_with($logo, 'http://')) $logo = 'https://' . substr($logo, 7);
                            return [
                                '_isBid'           => true,
                                '_bidId'           => $bid->id,
                                '_bidUserId'       => $bid->user_id ?? null,
                                'status'           => 'planned',
                                'status_text'      => 'Planned',
                                'position'         => null,
                                'flight_id'        => $bid->flight_id,
                                'flight_number'    => $f->flight_number,
                                'callsign'         => $f->callsign ?? null,
                                'dpt_airport_id'   => $f->dpt_airport_id,
                                'arr_airport_id'   => $f->arr_airport_id,
                                'dpt_airport'      => $f->dpt_airport ? ['icao' => $f->dpt_airport->icao, 'id' => $f->dpt_airport->id, 'name' => $f->dpt_airport->name] : null,
                                'arr_airport'      => $f->arr_airport ? ['icao' => $f->arr_airport->icao, 'id' => $f->arr_airport->id, 'name' => $f->arr_airport->name] : null,
                                'airline'          => $f->airline ? ['icao' => $f->airline->icao, 'name' => $f->airline->name, 'logo' => $logo] : null,
                                'planned_distance' => $f->distance ? ['nmi' => $f->distance] : null,
                                'dep_time'         => optional($f)->dpt_time,
                                'aircraft'         => null,
                                'user'             => [
                                    'id'   => optional($bid->user)->id,
                                    'name' => $bidUserName,
                                ],
                            ];
                        })
                        ->filter()
                        ->values();
                    @endphp
                    var raw = @json($userBidsJson);
                    return Array.isArray(raw) ? raw : [];
                } catch(e) { return []; }
            })();

            function buildLogoHtml(callsign) {
                if (!callsign || callsign.length < 3) return '';
                var icao = callsign.substring(0,3).toUpperCase();
                if (!/^[A-Z]{3}$/.test(icao)) return '';
                var logoUrl = AIRLINE_LOGOS[icao];
                if (!logoUrl) return '';
                return '<div style="text-align:center;padding:6px 0 10px;border-bottom:1px solid #eee;margin-bottom:8px">' +
                    '<img src="' + logoUrl + '" style="max-height:38px;max-width:140px;object-fit:contain;vertical-align:middle" onerror="this.closest(\'div\').remove();" alt="' + icao + '"></div>';
            }

            function buildAircraftIcon(heading) {
                var h = heading != null ? heading : 0;
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22"><g transform="rotate(' + h + ',16,16)"><ellipse cx="16" cy="16" rx="2.5" ry="10" fill="#1a6fc4"/><polygon points="16,14 3,20 3,22 16,18 29,22 29,20" fill="#1a6fc4"/><polygon points="16,24 10,29 10,30 16,27 22,30 22,29" fill="#1a6fc4"/><ellipse cx="16" cy="10" rx="1.5" ry="3" fill="rgba(255,255,255,0.35)"/></g></svg>';
                return L.divIcon({ html:'<img src="data:image/svg+xml;base64,' + btoa(svg) + '" width="22" height="22" style="display:block">', className:'', iconSize:[22,22], iconAnchor:[11,11] });
            }

            function buildIvaoAircraftIcon(heading) {
                var h = heading != null ? heading : 0;
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22"><g transform="rotate(' + h + ',16,16)"><ellipse cx="16" cy="16" rx="2.5" ry="10" fill="#e67e22"/><polygon points="16,14 3,20 3,22 16,18 29,22 29,20" fill="#e67e22"/><polygon points="16,24 10,29 10,30 16,27 22,30 22,29" fill="#e67e22"/><ellipse cx="16" cy="10" rx="1.5" ry="3" fill="rgba(255,255,255,0.35)"/></g></svg>';
                return L.divIcon({ html:'<img src="data:image/svg+xml;base64,' + btoa(svg) + '" width="22" height="22" style="display:block">', className:'', iconSize:[22,22], iconAnchor:[11,11] });
            }

            function buildAirportCtrlIcon(icao, ctrlList, atisList) {
                var TYPES = { 2:{short:'D',color:'#3498db'}, 3:{short:'G',color:'#e67e22'}, 4:{short:'T',color:'#e74c3c'}, 5:{short:'A',color:'#27ae60'} };
                var order = [2,3,4,5];
                var counts = {};
                ctrlList.forEach(function(c){ if(TYPES[c.facility]) counts[c.facility]=(counts[c.facility]||0)+1; });
                var ac = atisList ? atisList.length : 0;
                var hasApp = !!(counts[5]); var appCount = counts[5]||0;
                var dots = order.filter(function(f){ return f!==5&&counts[f]; }).map(function(f){
                    var t=TYPES[f],n=counts[f];
                    return '<span style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:3px;background:'+t.color+';color:#fff;font-size:8px;font-weight:800;box-shadow:0 1px 2px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5)">'+t.short+(n>1?'<span style="position:absolute;top:-4px;right:-4px;background:#c0392b;color:#fff;border-radius:50%;width:9px;height:9px;font-size:6px;display:flex;align-items:center;justify-content:center;border:1px solid #fff;line-height:1;font-weight:900">'+n+'</span>':'')+'</span>';
                }).join('');
                if (hasApp||ac>0) {
                    var hasAtis=ac>0,badgeText,badgeBg,badgeW2=18,badgeH2=18,badgeRadius='4px';
                    if(hasApp&&hasAtis){badgeText='A<span style="font-style:italic;font-size:9px;opacity:0.9">i</span>';badgeBg='#27ae60';badgeW2=22;}
                    else if(hasApp){badgeText='A';badgeBg='#27ae60';}
                    else{badgeText='<span style="font-style:italic">i</span>';badgeBg='#5dade2';badgeRadius='50%';}
                    var acb=(appCount>1)?'<span style="position:absolute;top:-4px;right:-4px;background:#c0392b;color:#fff;border-radius:50%;width:9px;height:9px;font-size:6px;display:flex;align-items:center;justify-content:center;border:1px solid #fff;line-height:1;font-weight:900">'+appCount+'</span>':'';
                    dots+='<span style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:'+badgeW2+'px;height:'+badgeH2+'px;border-radius:'+badgeRadius+';background:'+badgeBg+';color:#fff;font-size:9px;font-weight:900;box-shadow:0 1px 4px rgba(0,0,0,0.5);border:1.5px solid rgba(255,255,255,0.6)">'+badgeText+acb+'</span>';
                }
                var w=Math.max((Object.keys(counts).length+1)*18,icao.length*7+8,30)+16, h=36;
                return L.divIcon({ html:'<div style="width:'+w+'px;height:'+h+'px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer"><span style="font-size:9px;font-weight:700;color:#1a1a1a;text-shadow:0 0 3px #fff,0 0 3px #fff;letter-spacing:.3px;line-height:1">'+icao+'</span><div style="display:flex;gap:2px;align-items:center">'+dots+'</div></div>', className:'vatsim-airport-marker', iconSize:[w,h], iconAnchor:[w/2,h/2] });
            }

            function vRow(label, value) {
                return '<div class="vatsim-popup-row"><span class="label">'+label+'</span><span class="value">'+value+'</span></div>';
            }

            function buildPilotPopup(p) {
                var fp=p.flight_plan||{}, dep=fp.departure||'—', arr=fp.arrival||'—';
                return '<div class="vatsim-popup"><div class="vatsim-popup-header">'+buildLogoHtml(p.callsign)+'<div class="vatsim-popup-callsign">'+h(p.callsign)+'</div><div class="vatsim-popup-route">'+h(dep)+' &rsaquo; '+h(arr)+'</div></div><div class="vatsim-popup-body">'+vRow('Aircraft',h(fp.aircraft_short||fp.aircraft_faa||'—'))+vRow('Altitude',p.altitude?p.altitude.toLocaleString()+' ft':'—')+vRow('Speed',p.groundspeed?p.groundspeed+' kts':'—')+vRow('Heading',p.heading!=null?p.heading+'°':'—')+vRow('Pilot',h(p.name||'—'))+'</div></div>';
            }

            var CTRL_RATINGS = {1:'OBS',2:'S1',3:'S2',4:'S3',5:'C1',6:'C2',7:'C3',8:'I1',9:'I2',10:'I3',11:'SUP',12:'ADM'};
            var IVAO_CTRL_RATINGS = {1:'OBS',2:'AS1',3:'AS2',4:'AS3',5:'ADC',6:'APC',7:'ACC',8:'SEC',9:'SAI',10:'CAI',11:'SUP',12:'ADM'};

            /* ── Security helpers: XSS prevention ── */
            function h(s) {
                if (s == null) return '';
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
            }
            function safeUrl(u) { return (u && /^https:\/\/[a-zA-Z0-9._\-\/]+$/.test(u)) ? u : ''; }
            function safeCallsign(s) { return s ? String(s).replace(/[^A-Z0-9_\-]/gi,'').substring(0,20) : ''; }
            function safeFreq(s) { return s ? String(s).replace(/[^0-9.]/g,'').substring(0,8) : ''; }
            function ctrlRatingBadge(rating) {
                if (!rating) return '';
                var r=CTRL_RATINGS[rating]||('R'+rating);
                var rColor=rating>=11?'#8e44ad':rating>=8?'#c0392b':rating>=5?'#27ae60':rating>=2?'#2980b9':'#95a5a6';
                return '<span style="background:'+rColor+';color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;font-weight:700;letter-spacing:.3px">'+r+'</span>';
            }
            function ctrlOnlineTime(logonTime) {
                if (!logonTime) return '';
                try { var diff=Math.floor((Date.now()-new Date(logonTime).getTime())/60000),h=Math.floor(diff/60),m=diff%60; return h>0?h+'h '+m+'min':m+'min'; } catch(e){return '';}
            }
            function ctrlInfoLine(c) {
                var name   = h(c.name || '');
                var cidRaw = c.cid ? String(c.cid).replace(/[^0-9A-Z]/gi,'') : '';
                var cid    = cidRaw ? '#'+cidRaw : '';
                var rMap   = (c.network==='IVAO') ? IVAO_CTRL_RATINGS : CTRL_RATINGS;
                var rNum   = parseInt(c.rating)||0;
                var rating = rNum ? (rMap[rNum]||('R'+rNum)) : '';
                var online = ctrlOnlineTime(c.logon_time);
                var rColor = rNum>=11?'#8e44ad':c.rating>=8?'#c0392b':c.rating>=5?'#27ae60':c.rating>=2?'#2980b9':'#95a5a6';
                var parts = [];
                if(name) parts.push('<span style="font-weight:600;color:#333">'+name+'</span>');
                if(cid)  { var cidHtml = safeUrl(c.cid_link||'') ? '<a href="'+safeUrl(c.cid_link||'')+'" target="_blank" rel="noopener noreferrer" style="color:#3498db;font-size:10px;text-decoration:none" title="IVAO Tracker">'+cid+' ↗</a>' : '<span style="color:#aaa;font-size:10px">'+cid+'</span>'; parts.push(cidHtml); }
                if(rating) parts.push('<span style="background:'+rColor+';color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;font-weight:700;vertical-align:middle">'+rating+'</span>');
                if(online) parts.push('<span style="font-size:10px;color:#777;white-space:nowrap">⏱ '+online+'</span>');
                if(!parts.length) return '';
                return '<div style="font-size:11px;margin-top:4px;display:flex;flex-wrap:wrap;align-items:center;gap:4px">'+parts.join('')+'</div>';
            }

            function buildAirportCtrlPopup(icao, ctrlList, atisList) {
                var order={2:1,3:2,4:3,5:4};
                ctrlList=ctrlList.slice().sort(function(a,b){return(order[a.facility]||9)-(order[b.facility]||9);});
                var BADGE={2:{label:'DEL',color:'#2980b9'},3:{label:'GND',color:'#d35400'},4:{label:'TWR',color:'#c0392b'},5:{label:'APP',color:'#27ae60'}};
                var ctrlRows=ctrlList.map(function(c){
var t=BADGE[c.facility]||{label:'ATC',color:'#7f8c8d'};return '<div style="padding:7px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:3px"><span style="background:'+t.color+';color:#fff;padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;flex-shrink:0">'+t.label+'</span><span style="font-size:13px;font-weight:700;color:#1a1a1a">'+c.callsign+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+(c.frequency||'')+'</span></div>'+ctrlInfoLine(c)+'</div>';}).join('');
                var atisRows='';
                var atisId='atis_'+icao.replace(/\W/g,'')+'_'+Date.now();
                if(atisList&&atisList.length){var atisBlocks=atisList.map(function(a){var lines=Array.isArray(a.text_atis)?a.text_atis:[];var fullText=h(lines.join(' '));var preview=fullText.length>60?fullText.substring(0,60)+'…':fullText;var hasMore=fullText.length>60;return '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:4px"><span style="background:#5dade2;color:#fff;padding:2px 7px;border-radius:3px;font-size:10px;font-weight:700;flex-shrink:0">ATIS</span><span style="font-size:12px;font-weight:700;color:#1a1a1a">'+h(a.callsign)+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+safeFreq(a.frequency||'—')+'</span></div>'+(fullText?('<div style="font-size:10px;color:#555;line-height:1.5;background:#f8faff;padding:5px 8px;border-radius:4px;word-break:break-word"><span class="atis-preview-'+atisId+'">'+preview+'</span><span class="atis-full-'+atisId+'" style="display:none">'+fullText+'</span>'+(hasMore?'<br><span onclick="var p=this.parentElement;var prev=p.querySelector(\'.atis-preview-'+atisId+'\');var full=p.querySelector(\'.atis-full-'+atisId+'\');if(full.style.display===\'none\'){prev.style.display=\'none\';full.style.display=\'\';this.textContent=\'▲ Hide ATIS\';}else{prev.style.display=\'\';full.style.display=\'none\';this.textContent=\'▼ Show full ATIS\';}" style="color:#3498db;cursor:pointer;font-size:10px;font-weight:600">▼ Show full ATIS</span>':'')+'</div>'):'')+'</div>';}).join('');atisRows='<div style="margin-top:4px;padding-top:8px;border-top:2px dashed #d6eaf8">'+atisBlocks+'</div>';}
                var total=ctrlList.length+(atisList?atisList.length:0);
                var airportFullName=airportNameCache[icao]||airportNameCache['K'+icao]||'';
                return '<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+icao+'</div><div style="display:flex;align-items:center;gap:6px;margin-top:2px">'+(airportFullName?'<span class="vatsim-popup-route" style="margin:0">'+h(airportFullName)+'</span>':'')+'<span style="background:#27ae60;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">VATSIM</span></div><div style="font-size:11px;color:#aaa;margin-top:3px">'+total+' station'+(total!==1?'s':'')+' active</div></div><div class="vatsim-popup-body">'+ctrlRows+atisRows+'</div></div>';
            }

            function polyCenter(feature) {
                try {
                    var bestRing=null,bestArea=0,geom=feature.geometry;
                    var polys=geom.type==='Polygon'?[geom.coordinates]:geom.coordinates;
                    polys.forEach(function(poly){var ring=poly[0],a=0;for(var i=0,j=ring.length-1;i<ring.length;j=i++){a+=(ring[j][0]+ring[i][0])*(ring[j][1]-ring[i][1]);}a=Math.abs(a)/2;if(a>bestArea){bestArea=a;bestRing=ring;}});
                    if(!bestRing) return null;
                    var minLat=90,maxLat=-90,minLon=180,maxLon=-180;
                    bestRing.forEach(function(c){if(c[1]<minLat)minLat=c[1];if(c[1]>maxLat)maxLat=c[1];if(c[0]<minLon)minLon=c[0];if(c[0]>maxLon)maxLon=c[0];});
                    return[(minLat+maxLat)/2,(minLon+maxLon)/2];
                } catch(e){return null;}
            }

            function polyArea(feature) {
                try {
                    var geom=feature.geometry,rings=[];
                    if(geom.type==='Polygon') rings=[geom.coordinates[0]];
                    else if(geom.type==='MultiPolygon') geom.coordinates.forEach(function(p){rings.push(p[0]);});
                    var maxArea=0;
                    rings.forEach(function(ring){var area=0;for(var i=0,j=ring.length-1;i<ring.length;j=i++){area+=(ring[j][0]+ring[i][0])*(ring[j][1]-ring[i][1]);}area=Math.abs(area)/2;if(area>maxArea)maxArea=area;});
                    return maxArea;
                } catch(e){return 0;}
            }

            function renderActiveSectors(activeFirMap, sectorTarget) {
                var sectorLayer=sectorTarget||vatsimSectorLayer;
                sectorLayer.clearLayers();
                if(!firBoundsGeoJson||!firBoundsGeoJson.features) return;
                var featureById={};
                firBoundsGeoJson.features.forEach(function(feature){
                    var props=feature.properties||{};
                    var rawId=(feature.id||props.id||props.oceanic_prefix||'').toString().toUpperCase();
                    if(!rawId) return;
                    var normId=rawId.replace(/-/g,'_');
                    featureById[normId]=feature; featureById[rawId]=feature;
                });
                var claimedFeatures={};
                var ctrlFeatureMap={};
                function _norm(s){return s.replace(/-/g,'_');}
                Object.keys(activeFirMap).forEach(function(mapKey){
                    var info=activeFirMap[mapKey];
                    var root=info.root||mapKey.split('_')[0];
                    var isSubKey=mapKey.indexOf('_')!==-1;
                    var resolvedRoot=firPrefixMap[root]||root;
                    var features=[];
                    /* Phase 1: exact sub-key match (sectored callsigns like UNKL_N_CTR) */
                    if(isSubKey){var f=featureById[mapKey]||featureById[mapKey.replace(/_/g,'-')];if(f){features=[f];claimedFeatures[mapKey]=true;claimedFeatures[mapKey.replace(/_/g,'-')]=true;}}
                    /* Phase 2: broad normalised search — runs for ALL keys when Phase 1 missed */
                    if(features.length===0){var nR=_norm(root),nRe=_norm(resolvedRoot);Object.keys(featureById).forEach(function(fK){if(claimedFeatures[fK])return;var nk=_norm(fK),ft=featureById[fK];if(nk===nRe||nk===nR||nk==='K'+nR||nR==='K'+nk){if(features.indexOf(ft)===-1)features.push(ft);}});}
                    /* Phase 3: startsWith fallback for sub-sector GeoJSON IDs (e.g. UNKL-1) */
                    if(features.length===0){var nR2=_norm(root),nRe2=_norm(resolvedRoot);Object.keys(featureById).forEach(function(fK){if(claimedFeatures[fK])return;var nk=_norm(fK),ft=featureById[fK];if(nk.indexOf(nRe2+'_')===0||nk.indexOf(nR2+'_')===0){if(features.indexOf(ft)===-1)features.push(ft);}});}
                    /* Phase 4: UIR expansion — resolve UIR (e.g. RU-SC) to constituent FIR polygons */
                    if(features.length===0){var uirFirs=uirToFirsMap[root]||uirToFirsMap[resolvedRoot]||uirToFirsMap[_norm(root)]||null;if(uirFirs){uirFirs.forEach(function(fid){var nfid=_norm(fid);var ft=featureById[nfid]||featureById[fid];if(ft&&features.indexOf(ft)===-1)features.push(ft);});if(features.length)info.isUpper=true;}}
                    if(features.length>0) ctrlFeatureMap[mapKey]=features;
                });

                Object.keys(ctrlFeatureMap).forEach(function(matchKey){
                    var features=ctrlFeatureMap[matchKey];
                    var info=activeFirMap[matchKey];
                    var color=info.color||'#1abc9c';
                    var short=info.root||info.callsign.split('_')[0];
                    var firName=firNameCache[short]||info.callsign;
                    var isUpper=!!info.isUpper;
                    var fillOpacity=isUpper?0:0.08, hoverFill=isUpper?0.06:0.22, borderWeight=isUpper?2:1.5, dashArray=isUpper?'10 6':'5 4';
                    var netBadge=info.network==='IVAO'?'<span style="background:#e67e22;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">IVAO</span>':'<span style="background:#27ae60;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">VATSIM</span>'; var popupContent='<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+info.callsign+'</div><div style="display:flex;align-items:center;gap:6px;margin-top:2px"><span class="vatsim-popup-route" style="margin:0">'+h(firName)+'</span>'+netBadge+'</div>'+(isUpper?'<div style="font-size:10px;color:#8e44ad;font-weight:700;margin-top:2px">▲ Upper Airspace</div>':'')+'</div><div class="vatsim-popup-body">'+vRow('Frequency',info.frequency||'—')+ctrlInfoLine(info)+'</div></div>';
                    features.forEach(function(feature){
                        try {
                            var layer=L.geoJSON(feature,{style:{color:color,weight:borderWeight,opacity:0.75,fillColor:color,fillOpacity:fillOpacity,dashArray:dashArray}});
                            layer.on('mouseover',function(e){e.target.setStyle({fillOpacity:hoverFill,weight:borderWeight+0.5,dashArray:''}); });
                            layer.on('mouseout',function(e){e.target.setStyle({fillOpacity:fillOpacity,weight:borderWeight,dashArray:dashArray}); });
                            layer.bindPopup(popupContent,{maxWidth:260});
                            layer.addTo(sectorLayer);
                        } catch(e){}
                    });
                    var biggest=features.reduce(function(best,f){return polyArea(f)>polyArea(best)?f:best;},features[0]);
                    var center=polyCenter(biggest);
                    if(!center) return;
                    var freqStr=info.frequency||'', labelW=Math.max(short.length*8+16,64), labelH=36;
                    L.marker(center,{icon:L.divIcon({html:'<div style="background:'+color+';color:#fff;padding:3px 9px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;box-shadow:0 2px 5px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5);white-space:nowrap;text-align:center">'+short+'<br><span style="font-size:9px;font-weight:400;opacity:0.85">'+(freqStr||firName.split(' ')[0])+'</span></div>',className:'',iconSize:[labelW,labelH],iconAnchor:[labelW/2,labelH/2]}),zIndexOffset:200,title:info.callsign}).bindPopup(popupContent,{maxWidth:260}).addTo(sectorLayer);
                });
            }

            function updateCtrlZoom(map) {
                var z=map.getZoom();
                document.querySelectorAll('.vatsim-airport-marker, .ivao-airport-marker').forEach(function(el){
                    var label=el.querySelector('div:first-child');
                    if(z<3){el.parentElement.style.display='none';}
                    else{el.parentElement.style.display='';if(label) label.style.display=z>=5?'':'none';}
                });
            }

            function buildAirportCtrlIconIvao(icao, ctrlList, atisList) {
                var TYPES={2:{short:'D',color:'#2980b9'},3:{short:'G',color:'#d35400'},4:{short:'T',color:'#c0392b'},5:{short:'A',color:'#27ae60'}};
                var order=[2,3,4,5],counts={};
                ctrlList.forEach(function(c){if(TYPES[c.facility])counts[c.facility]=(counts[c.facility]||0)+1;});
                var ac=atisList?atisList.length:0,hasApp=!!(counts[5]),appCount=counts[5]||0;
                var dots=order.filter(function(f){return f!==5&&counts[f];}).map(function(f){var t=TYPES[f],n=counts[f];return '<span style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:3px;background:'+t.color+';color:#fff;font-size:8px;font-weight:800;box-shadow:0 1px 2px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5)">'+t.short+(n>1?'<span style="position:absolute;top:-4px;right:-4px;background:#c0392b;color:#fff;border-radius:50%;width:9px;height:9px;font-size:6px;display:flex;align-items:center;justify-content:center;border:1px solid #fff;line-height:1;font-weight:900">'+n+'</span>':'')+'</span>';}).join('');
                if(hasApp||ac>0){var hasAtis=ac>0,badgeText,badgeBg,badgeW2=18,badgeH2=18,badgeRadius='4px';if(hasApp&&hasAtis){badgeText='A<span style="font-style:italic;font-size:9px;opacity:0.9">i</span>';badgeBg='#27ae60';badgeW2=22;}else if(hasApp){badgeText='A';badgeBg='#27ae60';}else{badgeText='<span style="font-style:italic">i</span>';badgeBg='#5dade2';badgeRadius='50%';}dots+='<span style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:'+badgeW2+'px;height:'+badgeH2+'px;border-radius:'+badgeRadius+';background:'+badgeBg+';color:#fff;font-size:9px;font-weight:900;box-shadow:0 1px 4px rgba(0,0,0,0.5);border:1.5px solid rgba(255,255,255,0.6)">'+badgeText+'</span>';}
                var w=Math.max((Object.keys(counts).length+1)*18,icao.length*7+8,30)+16,h=36;
                return L.divIcon({html:'<div style="width:'+w+'px;height:'+h+'px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer;outline:2px solid #e67e22;border-radius:3px;outline-offset:1px"><span style="font-size:9px;font-weight:700;color:#1a1a1a;text-shadow:0 0 3px #fff,0 0 3px #fff;letter-spacing:.3px;line-height:1">'+icao+'</span><div style="display:flex;gap:2px;align-items:center">'+dots+'</div></div>',className:'ivao-airport-marker',iconSize:[w,h],iconAnchor:[w/2,h/2]});
            }

            function buildAirportCtrlPopupIvao(icao, ctrlList, atisList) {
                var order={2:1,3:2,4:3,5:4};
                ctrlList=ctrlList.slice().sort(function(a,b){return(order[a.facility]||9)-(order[b.facility]||9);});
                var BADGE={2:{label:'DEL',color:'#2980b9'},3:{label:'GND',color:'#d35400'},4:{label:'TWR',color:'#c0392b'},5:{label:'APP',color:'#27ae60'}};
                var ctrlRows=ctrlList.map(function(c){
                    var t=BADGE[c.facility]||{label:'ATC',color:'#7f8c8d'};
                    return '<div style="padding:7px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:3px"><span style="background:'+t.color+';color:#fff;padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;flex-shrink:0">'+t.label+'</span><span style="font-size:13px;font-weight:700;color:#1a1a1a">'+c.callsign+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+(c.frequency||'')+'</span></div>'+ctrlInfoLine(c)+'</div>';
                }).join('');
                var atisRows='';
                var atisId='atis_ivao_'+icao.replace(/\W/g,'')+'_'+Date.now();
                if(atisList&&atisList.length){
                    var atisBlocks=atisList.map(function(a){
                        var lines=Array.isArray(a.text_atis)?a.text_atis:[];
                        var fullText=lines.join(' ');
                        var preview=fullText.length>60?fullText.substring(0,60)+'…':fullText;
                        var hasMore=fullText.length>60;
                        return '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:4px"><span style="background:#5dade2;color:#fff;padding:2px 7px;border-radius:3px;font-size:10px;font-weight:700;flex-shrink:0">ATIS</span><span style="font-size:12px;font-weight:700;color:#1a1a1a">'+h(a.callsign)+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+safeFreq(a.frequency||'—')+'</span></div>'+(fullText?('<div style="font-size:10px;color:#555;line-height:1.5;background:#f8faff;padding:5px 8px;border-radius:4px;word-break:break-word"><span class="atis-preview-'+atisId+'">'+preview+'</span><span class="atis-full-'+atisId+'" style="display:none">'+fullText+'</span>'+(hasMore?'<br><span onclick="var p=this.parentElement;var prev=p.querySelector(\'.atis-preview-'+atisId+'\');var full=p.querySelector(\'.atis-full-'+atisId+'\');if(full.style.display===\'none\'){prev.style.display=\'none\';full.style.display=\'\';this.textContent=\'▲ Hide ATIS\';}else{prev.style.display=\'\';full.style.display=\'none\';this.textContent=\'▼ Show full ATIS\';}" style="color:#3498db;cursor:pointer;font-size:10px;font-weight:600">▼ Show full ATIS</span>':'')+'</div>'):'')+'</div>';
                    }).join('');
                    atisRows='<div style="margin-top:4px;padding-top:8px;border-top:2px dashed #fde8cc">'+atisBlocks+'</div>';
                }
                var total=ctrlList.length+(atisList?atisList.length:0);
                var airportFullName=airportNameCache[icao]||airportNameCache['K'+icao]||'';
                return '<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+icao+'</div><div style="display:flex;align-items:center;gap:6px;margin-top:2px">'+(airportFullName?'<span class="vatsim-popup-route" style="margin:0">'+h(airportFullName)+'</span>':'')+'<span style="background:#e67e22;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">IVAO</span></div><div style="font-size:11px;color:#aaa;margin-top:3px">'+total+' station'+(total!==1?'s':'')+' active</div></div><div class="vatsim-popup-body">'+ctrlRows+atisRows+'</div></div>';
            }

            function loadFirNames() {
                if (firNameLoaded) return Promise.resolve();
                return fetch(VATSPY_DAT_API).then(function(r){return r.text();}).then(function(text){
                    firNameLoaded=true; staticAirportLoaded=true;
                    var section='';
                    text.split('\n').forEach(function(line){
                        line=line.trim();
                        if(!line||line.startsWith(';')) return;
                        if(line.startsWith('[')){section=line.replace(/[\[\]]/g,'').toLowerCase();return;}
                        var parts=line.split('|');
                        if(section==='airports'&&parts.length>=4){
                            var icao=parts[0].trim().toUpperCase(),aname=parts[1].trim(),lat=parseFloat(parts[2]),lon=parseFloat(parts[3]);
                            if(icao&&!isNaN(lat)&&!isNaN(lon)){staticAirportPos[icao]=[lat,lon];if(aname)airportNameCache[icao]=aname;}
                        } else if(section==='firs'&&parts.length>=2){
                            var ficao=parts[0].trim().toUpperCase(),name=parts[1].trim(),prefix=(parts[2]||'').trim().toUpperCase();
                            if(ficao&&name){firNameCache[ficao]=name;if(prefix){firNameCache[prefix]=name;firPrefixMap[prefix]=ficao;firPrefixMap[ficao]=ficao;}}
                        } else if(section==='uirs'&&parts.length>=2){
                            /* UIR format varies: ID|FIR1,FIR2 or ID|Name|FIR1,FIR2 */
                            var uid=parts[0].trim().toUpperCase();
                            /* Collect ALL fields after the ID, split each by comma/space */
                            var allFirs=[];
                            for(var pi=1;pi<parts.length;pi++){
                                var chunk=parts[pi].trim().toUpperCase();
                                chunk.split(/[,\s]+/).forEach(function(tok){
                                    tok=tok.trim();
                                    /* Only keep tokens that look like ICAO codes (2-5 uppercase alphanum, optionally with hyphen) */
                                    if(tok&&/^[A-Z0-9\-]{2,8}$/.test(tok)&&tok.length<=6) allFirs.push(tok);
                                });
                            }
                            if(uid&&allFirs.length){uirToFirsMap[uid]=allFirs;firPrefixMap[uid]=uid;}
                        }
                    });
                }).catch(function(e){firNameLoaded=true;staticAirportLoaded=true;console.warn('[VATSIM] VATSpy.dat nicht geladen:',e);});
            }

            function loadTransceivers() {
                return fetch(VATSIM_TRX_API).then(function(r){return r.json();}).then(function(trxList){
                    ctrlPosCache={};
                    (trxList||[]).forEach(function(entry){
                        if(!entry.callsign||!entry.transceivers||!entry.transceivers.length) return;
                        var trx=entry.transceivers[0],lat=parseFloat(trx.latDeg),lon=parseFloat(trx.lonDeg);
                        if(!isNaN(lat)&&!isNaN(lon)&&(Math.abs(lat)>0.001||Math.abs(lon)>0.001)) ctrlPosCache[entry.callsign.toUpperCase()]=[lat,lon];
                    });
                }).catch(function(err){console.warn('[VATSIM] Transceivers nicht geladen:',err);});
            }

            function normalizeKey(prefix) {
                if(staticAirportPos[prefix]) return prefix;
                if(staticAirportPos['K'+prefix]) return 'K'+prefix;
                if(staticAirportPos['C'+prefix]) return 'C'+prefix;
                if(staticAirportPos['P'+prefix]) return 'P'+prefix;
                if(prefix.length===4&&prefix[0]==='K'&&staticAirportPos[prefix.slice(1)]) return prefix.slice(1);
                return prefix;
            }

            function distKm(a,b) {
                var R=6371,dLat=(b[0]-a[0])*Math.PI/180,dLon=(b[1]-a[1])*Math.PI/180;
                var s=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(a[0]*Math.PI/180)*Math.cos(b[0]*Math.PI/180)*Math.sin(dLon/2)*Math.sin(dLon/2);
                return R*2*Math.atan2(Math.sqrt(s),Math.sqrt(1-s));
            }

            function loadVatsim(map) {
                Promise.all([
                    fetch(VATSIM_DATA_API).then(function(r){return r.json();}),
                    loadTransceivers(),
                    loadFirNames()
                ]).then(function(results){
                    var data=results[0];
                    vatsimPilotsLayer.clearLayers();
                    vatsimCtrlLayer.clearLayers();
                    var pilots=data.pilots||[];

                    pilots.forEach(function(p){
                        if(p.latitude==null||p.longitude==null) return;
                        var fp=p.flight_plan||{};
                        var marker=L.marker([p.latitude,p.longitude],{icon:buildAircraftIcon(p.heading),title:p.callsign}).bindPopup(buildPilotPopup(p),{maxWidth:280});
                        if(fp.arrival){marker.on('click',function(){showRouteLine(map,[p.latitude,p.longitude],fp.arrival.toUpperCase());});}
                        marker.addTo(vatsimPilotsLayer);
                    });

                    var controllers=data.controllers||[], atisRaw=data.atis||[], ctrlDone=0;
                    var atisGroups={}, airportGroups={}, centerList=[];

                    atisRaw.forEach(function(a){
                        var raw=a.callsign.split('_')[0].toUpperCase(), key=normalizeKey(raw);
                        var pos=staticAirportPos[key]||staticAirportPos[raw]||null;
                        if(!pos)(data.airports||[]).forEach(function(ap){if(!pos&&(ap.icao===raw||ap.icao===key)){var lt=parseFloat(ap.latitude||ap.lat),ln=parseFloat(ap.longitude||ap.lon);if(!isNaN(lt)&&!isNaN(ln))pos=[lt,ln];}});
                        if(!pos) pos=ctrlPosCache[a.callsign.toUpperCase()];
                        if(!pos) return;
                        if(!atisGroups[key]) atisGroups[key]={pos:pos,list:[]};
                        atisGroups[key].list.push(a);
                    });

                    controllers.forEach(function(c){
                        if(c.facility===0) return;
                        var prefix=c.callsign.split('_')[0].toUpperCase();
                        var pos=staticAirportPos[prefix]||staticAirportPos['K'+prefix]||staticAirportPos['P'+prefix]||staticAirportPos['C'+prefix]||null;
                        if(!pos)(data.airports||[]).forEach(function(a){if(!pos&&(a.icao===prefix||a.icao==='K'+prefix)){var alat=parseFloat(a.latitude||a.lat),alon=parseFloat(a.longitude||a.lon);if(!isNaN(alat)&&!isNaN(alon))pos=[alat,alon];}});
                        if(!pos) pos=ctrlPosCache[c.callsign.toUpperCase()];
                        /* CTR/FSS without position: keep for FIR sector matching (polygon doesn't need pos) */
                        if(!pos&&(c.facility===6||c.facility===1)){centerList.push({ctrl:c,pos:null});return;}
                        if(!pos) return;
                        if(c.facility===6||c.facility===1){centerList.push({ctrl:c,pos:pos});}
                        else{
                            var raw=c.callsign.split('_')[0].toUpperCase();
                            var isRealAirport=!!(staticAirportPos[raw]||staticAirportPos['K'+raw]||staticAirportPos['C'+raw]||staticAirportPos['P'+raw]);
                            if(!isRealAirport){centerList.push({ctrl:c,pos:pos,isTracon:true});}
                            else{var key=normalizeKey(raw);if(!airportGroups[key])airportGroups[key]={pos:pos,ctrls:[]};airportGroups[key].ctrls.push(c);}
                        }
                    });

                    var allAirportKeys={};
                    Object.keys(airportGroups).forEach(function(k){allAirportKeys[k]=true;});
                    Object.keys(atisGroups).forEach(function(k){allAirportKeys[k]=true;});

                    var traconMerged={};
                    centerList.forEach(function(entry,idx){
                        if(!entry.isTracon) return;
                        var bestKey=null,bestDist=80;
                        Object.keys(allAirportKeys).forEach(function(k){var grp=airportGroups[k]||atisGroups[k];if(!grp)return;var d=distKm(entry.pos,grp.pos);if(d<bestDist){bestDist=d;bestKey=k;}});
                        if(bestKey){if(!airportGroups[bestKey]){airportGroups[bestKey]={pos:(atisGroups[bestKey]||{}).pos||entry.pos,ctrls:[]};allAirportKeys[bestKey]=true;}airportGroups[bestKey].ctrls.push(entry.ctrl);traconMerged[idx]=true;}
                    });

                    Object.keys(allAirportKeys).forEach(function(icao){
                        var group=airportGroups[icao]||{pos:atisGroups[icao].pos,ctrls:[]};
                        var atisList=atisGroups[icao]?atisGroups[icao].list:[];
                        ctrlDone+=group.ctrls.length+atisList.length;
                        L.marker(group.pos,{icon:buildAirportCtrlIcon(icao,group.ctrls,atisList),title:icao,zIndexOffset:500}).bindPopup(buildAirportCtrlPopup(icao,group.ctrls,atisList),{maxWidth:300}).addTo(vatsimCtrlLayer);
                    });

                    var activeFirMap={};
                    centerList.forEach(function(entry,idx){
                        if(traconMerged[idx]) return;
                        var c=entry.ctrl, parts=c.callsign.split('_'), root=parts[0].toUpperCase();
                        var isUpper=!!UPPER_FIR[root];
                        var color=entry.isTracon?'#27ae60':isUpper?'#8e44ad':c.facility===6?'#1abc9c':'#8e44ad';
                        ctrlDone++;
                        if(entry.isTracon){
                            var traconIcon=L.divIcon({html:'<div style="display:flex;flex-direction:column;align-items:center;white-space:nowrap;pointer-events:auto"><div style="background:'+color+';color:#fff;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:.5px;box-shadow:0 1px 4px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5);line-height:1.4">'+root+'</div><div style="width:4px;height:4px;border-radius:50%;background:'+color+';margin-top:2px"></div></div>',className:'',iconSize:[root.length*8+16,26],iconAnchor:[(root.length*8+16)/2,13]});
                            L.marker(entry.pos,{icon:traconIcon,title:c.callsign,zIndexOffset:400}).bindPopup('<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+c.callsign+'</div><div class="vatsim-popup-route">TRACON / Approach Control</div></div><div class="vatsim-popup-body">'+vRow('Frequency',c.frequency||'—')+ctrlInfoLine(c)+'</div></div>',{maxWidth:260}).addTo(vatsimCtrlLayer);
                        } else {
                            var mapKey=parts.length>=3?root+'_'+parts[1].toUpperCase():root;
                            if(!activeFirMap[mapKey])activeFirMap[mapKey]={callsign:c.callsign,frequency:c.frequency,name:c.name,cid:c.cid,rating:c.rating,logon_time:c.logon_time,visual_range:c.visual_range,color:color,isUpper:isUpper,root:root,network:'VATSIM'};
                        }
                    });

                    vatsimSectorLayer.clearLayers();
                    var boundsPromise=firBoundsGeoJson?Promise.resolve():fetch(VATSPY_BOUNDS_API).then(function(r){return r.json();}).then(function(gj){firBoundsGeoJson=gj;});
                    Promise.all([boundsPromise,loadFirNames()]).then(function(){if(firBoundsGeoJson)renderActiveSectors(activeFirMap);}).catch(function(e){console.warn('[VATSIM] Sektoren-Fehler:',e);});

                    if(typeof applyLayerVisibility==='function') applyLayerVisibility();
                    var statsEl=document.getElementById('vatsimStats'), dotEl=document.getElementById('vatsimNetDot');
                    if(statsEl) statsEl.textContent='\u2708'+pilots.length+'  \uD83C\uDFA7'+ctrlDone;
                    if(dotEl)   dotEl.classList.add('live');
                }).catch(function(err){console.error('[VATSIM] Fehler:',err);});
            }

            function loadIvao(map) {
                fetch(IVAO_DATA_API).then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();}).then(function(data){
                    var clients=data.clients||{}, pilots=clients.pilots||[], atcs=clients.atcs||[];
                    var statsEl=document.getElementById('ivaoStats'), dotEl=document.getElementById('ivaoNetDot');
                    if(statsEl) statsEl.textContent='\u2708'+pilots.length+'  \uD83C\uDFA7'+atcs.length;
                    if(dotEl)   dotEl.style.background='#fff';
                    if(!showIvao) return;

                    ivaoPilotsLayer.clearLayers(); ivaoCtrlLayer.clearLayers(); ivaoSectorLayer.clearLayers();
                    var IVAO_FAC={'DEL':2,'GND':3,'TWR':4,'APP':5,'DEP':5,'CTR':6,'FSS':1};

                    pilots.forEach(function(p){
                        var trk=p.lastTrack||{},lat=parseFloat(trk.latitude),lon=parseFloat(trk.longitude);
                        if(isNaN(lat)||isNaN(lon)) return;
                        var fp=p.flightPlan||{},dep=fp.departureId||'—',arr=fp.arrivalId||'—',ac=(fp.aircraft&&fp.aircraft.icaoCode)||'—',hdg=trk.heading||0;
                        var popupHtml='<div class="vatsim-popup"><div class="vatsim-popup-header">'+buildLogoHtml(p.callsign)+'<div class="vatsim-popup-callsign">'+h(p.callsign)+'</div><div class="vatsim-popup-route">'+h(dep)+' &rsaquo; '+h(arr)+'</div><div style="font-size:9px;font-weight:700;color:#e67e22;margin-top:2px">IVAO</div></div><div class="vatsim-popup-body">'+vRow('Aircraft',h(ac))+vRow('Altitude',trk.altitude?trk.altitude.toLocaleString()+' ft':'—')+vRow('Speed',trk.groundSpeed?trk.groundSpeed+' kts':'—')+vRow('Heading',hdg+'°')+vRow('Pilot',h(String(p.userId||'—')))+'</div></div>';
                        var marker=L.marker([lat,lon],{icon:buildIvaoAircraftIcon(hdg),title:p.callsign}).bindPopup(popupHtml,{maxWidth:280});
                        if(arr&&arr!=='—'){marker.on('click',function(){routeLineLayer.clearLayers();showRouteLine(map,[lat,lon],arr);});}
                        marker.addTo(ivaoPilotsLayer);
                    });

                    var ivaoAirportGroups={}, ivaoFirMap={};
                    atcs.forEach(function(c){
                        var trk=c.lastTrack||{},sess=c.atcSession||{},posType=(sess.position||'OBS').toUpperCase();
                        var facility=IVAO_FAC[posType]||0,freq=sess.frequency||'',cs=c.callsign||'';
                        var lat=parseFloat(trk.latitude),lon=parseFloat(trk.longitude);
                        if(facility===0) return;
                        var atisLines=(c.atis&&Array.isArray(c.atis.lines))?c.atis.lines:[];
                        if(facility===6||facility===1){
                            var root=cs.split('_')[0].toUpperCase(), isUpper=!!UPPER_FIR[root];
                            if(!ivaoFirMap[root]){var _vid=c.userId||''; ivaoFirMap[root]={callsign:cs,frequency:freq,color:isUpper?'#8e44ad':'#16a085',isUpper:isUpper,root:root,network:'IVAO',cid:_vid,cid_link:_vid?'https://tracker.ivao.aero/atc/'+_vid:'',rating:c.rating||0,logon_time:c.createdAt||''}; }
                        } else if(!isNaN(lat)&&!isNaN(lon)){
                            var icaoRaw=cs.split('_')[0].toUpperCase();
                            if(!ivaoAirportGroups[icaoRaw])ivaoAirportGroups[icaoRaw]={pos:[lat,lon],ctrls:[],atis:[]};
                            var ivaoVid=c.userId||''; ivaoAirportGroups[icaoRaw].ctrls.push({callsign:cs,facility:facility,frequency:freq,name:'',cid:ivaoVid,cid_link:'https://tracker.ivao.aero/atc/'+ivaoVid,rating:c.rating||0,logon_time:c.createdAt||'',network:'IVAO'});
                            if(atisLines.length)ivaoAirportGroups[icaoRaw].atis.push({callsign:cs,frequency:freq,text_atis:atisLines});
                        }
                    });

                    Object.keys(ivaoAirportGroups).forEach(function(icao){
                        var group=ivaoAirportGroups[icao];
                        var pos=staticAirportPos[icao]||group.pos;
                        L.marker(pos,{icon:buildAirportCtrlIconIvao(icao,group.ctrls,group.atis),title:icao,zIndexOffset:490}).bindPopup(buildAirportCtrlPopupIvao(icao,group.ctrls,group.atis),{maxWidth:300}).addTo(ivaoCtrlLayer);
                    });

                    if(vatsimShowSectors) renderActiveSectors(ivaoFirMap, ivaoSectorLayer);
                    if(typeof applyLayerVisibility==='function') applyLayerVisibility();
                }).catch(function(err){console.error('[IVAO] Error:',err);});
            }

            // ── Leaflet-Hooks (registrieren VOR render_live_map) ──
            if (typeof L !== 'undefined' && L.Map && typeof L.Map.addInitHook === 'function') {

                L.Map.addInitHook(function () {
                    attachWeatherToMap(this);
                });

                L.Map.addInitHook(function () {
                    var map = this;
                    window._liveMapInstance = map;

                    (function setupBasemapControl() {
                        var cfg = window.LIVE_MAP_UI || {};
                        var allowSatellite = !!cfg.enableSatelliteBasemap;
                        var showSwitcher = !!cfg.showBasemapSwitcher;

                        var basemapDefs = {
                            positron: {
                                label: 'Carto Light',
                                url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                                options: { subdomains: 'abcd', maxZoom: 20, attribution: '&copy; OpenStreetMap contributors &copy; CARTO' },
                            },
                            osm: {
                                label: 'OpenStreetMap',
                                url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                                options: { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' },
                            },
                            dark: {
                                label: 'Carto Dark',
                                url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                                options: { subdomains: 'abcd', maxZoom: 20, attribution: '&copy; OpenStreetMap contributors &copy; CARTO' },
                            },
                            satellite: {
                                label: 'Satellite',
                                url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                                options: { maxZoom: 18, attribution: 'Tiles &copy; Esri' },
                            },
                        };

                        var enabledKeys = ['positron', 'osm', 'dark'];
                        if (allowSatellite) enabledKeys.push('satellite');

                        var mapLayers = {};
                        enabledKeys.forEach(function(key) {
                            var def = basemapDefs[key];
                            if (!def) return;
                            var layer = L.tileLayer(def.url, def.options || {});
                            layer._lmBasemapKey = key;
                            mapLayers[key] = layer;
                        });

                        function clearCurrentBaseLayers() {
                            map.eachLayer(function(layer) {
                                if (!(layer instanceof L.TileLayer)) return;
                                var pane = (layer.options && layer.options.pane) || 'tilePane';
                                if (pane !== 'weatherPane') map.removeLayer(layer);
                            });
                        }

                        var preferred = String(cfg.defaultBasemap || 'positron').toLowerCase();
                        var saved = '';
                        try { saved = String(localStorage.getItem('livemap_basemap') || '').toLowerCase(); } catch (e) {}

                        var selectedKey = saved && mapLayers[saved] ? saved : preferred;
                        if (!mapLayers[selectedKey]) selectedKey = 'positron';
                        if (!mapLayers[selectedKey]) selectedKey = Object.keys(mapLayers)[0];
                        if (!selectedKey) return;

                        clearCurrentBaseLayers();
                        mapLayers[selectedKey].addTo(map);

                        if (showSwitcher && Object.keys(mapLayers).length > 1) {
                            var controlLayers = {};
                            Object.keys(mapLayers).forEach(function(key) {
                                controlLayers[basemapDefs[key].label] = mapLayers[key];
                            });
                            L.control.layers(controlLayers, null, { position: 'topleft', collapsed: true }).addTo(map);
                            map.on('baselayerchange', function(evt) {
                                var key = evt && evt.layer && evt.layer._lmBasemapKey ? evt.layer._lmBasemapKey : '';
                                if (key) {
                                    try { localStorage.setItem('livemap_basemap', key); } catch (e) {}
                                }
                            });
                        }
                    })();

                    routeLineLayer.addTo(map);

                    var timeout = new Promise(function(res){ setTimeout(res, 3000); });
                    Promise.race([logosReady, timeout]).then(function(){
                        loadVatsim(map);
                        setInterval(function(){ loadVatsim(map); }, VATSIM_REFRESH_MS);
                        loadIvao(map);
                        setInterval(function(){ loadIvao(map); }, IVAO_REFRESH_MS);
                    });

                    map.on('zoomend', function(){ updateCtrlZoom(map); });

                    map.on('click', function(){
                        drawSeq++;
                        routeLineLayer.clearLayers();
                        lastDrawnArr = null;
                        var vc = document.getElementById('va-boarding-pass');
                        if (vc && vc.classList.contains('bp-visible')) {
                            vc.classList.remove('bp-visible');
                            document.querySelectorAll('#va-rows-active .active-flight, #va-rows-planned .active-flight')
                                .forEach(function(r){ r.classList.remove('active-flight'); });
                        }
                    });

                    function makeVaIcon() {
                        var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="38" height="38"><ellipse cx="20" cy="20" rx="3.5" ry="13" fill="#ffffff" stroke="#1a3a6b" stroke-width="1.8"/><rect x="17.5" y="14" width="5" height="12" rx="1.5" fill="#e74c3c" opacity="0.85"/><polygon points="20,17 2,25 2,28 20,23 38,28 38,25" fill="#ffffff" stroke="#1a3a6b" stroke-width="1.5"/><polygon points="20,31 11,38 11,39.5 20,36 29,39.5 29,38" fill="#ffffff" stroke="#1a3a6b" stroke-width="1.3"/><ellipse cx="20" cy="10" rx="2" ry="3.5" fill="rgba(100,160,255,0.7)"/></svg>';
                        return L.divIcon({ html:'<div style="filter:drop-shadow(0 2px 5px rgba(0,0,0,0.8));width:38px;height:38px"><img src="data:image/svg+xml;base64,' + btoa(svg) + '" width="38" height="38" style="display:block"></div>', className:'', iconSize:[38,38], iconAnchor:[19,19] });
                    }

                    var infoBox  = document.getElementById('map-info-box');
                    var drawSeq  = 0;
                    var vaMarkerCache = {};

                    window.vaInfoCardClose = function() {
                        var card = document.getElementById('va-boarding-pass');
                        if (card) card.classList.remove('bp-visible');
                        drawSeq++;
                        routeLineLayer.clearLayers();
                        lastDrawnArr = null;
                        document.querySelectorAll('#va-rows-active .active-flight, #va-rows-planned .active-flight')
                            .forEach(function(r){ r.classList.remove('active-flight'); });
                    };

                    window.vaInfoCardOpen = function(flight, lat, lng) {
                        // Pass kurz zurücksetzen für saubere Anzeige
                        var _card = document.getElementById('va-boarding-pass');
                        if(_card) _card.classList.remove('bp-visible');
                        // Mobil: Tabelle schließen wenn Pass geöffnet wird
                        if(window.innerWidth <= 768) {
                            var panel = document.getElementById('va-flights-panel');
                            if(panel) panel.classList.remove('mobile-visible');
                            var btn = document.getElementById('mob-toggle-panel');
                            setMobileBtnState(btn, false);
                        }
                        var dep     = (flight.dpt_airport&&(flight.dpt_airport.icao||flight.dpt_airport.id))||flight.dpt_airport_id||'—';
                        var arr     = (flight.arr_airport &&(flight.arr_airport.icao ||flight.arr_airport.id))||flight.arr_airport_id||'—';
                        var depName = (flight.dpt_airport&&flight.dpt_airport.name)||'';
                        var arrName = (flight.arr_airport&&flight.arr_airport.name)||'';
                        var cs      = (flight.airline&&flight.airline.icao?flight.airline.icao:'')+(flight.flight_number||flight.callsign||'');
                        var reg     = (flight.aircraft&&flight.aircraft.registration)||'';
                        var ac      = (flight.aircraft&&flight.aircraft.icao)||'';
                        var acStr   = reg?(reg+(ac?' · '+ac:'')):(ac||'—');
                        var alt     = (flight.position&&flight.position.altitude)
                                        ? (parseFloat(flight.position.altitude)>1000
                                            ? 'FL'+Math.round(parseFloat(flight.position.altitude)/100)
                                            : Math.round(parseFloat(flight.position.altitude))+' ft')
                                        : '—';
                        var spd     = (flight.position&&flight.position.gs)?flight.position.gs+' kt':'—';
                        var hdg     = (flight.position&&flight.position.heading!=null)?flight.position.heading+'°':'—';
                        var stat    = (window.translateStatus||function(s){return s;})(flight.status_text||flight.status||'—');
                        var distFlown   = flight.position&&flight.position.distance&&flight.position.distance.nmi!=null?Math.round(parseFloat(flight.position.distance.nmi)):null;
                        var distPlanned = flight.planned_distance&&flight.planned_distance.nmi!=null?Math.round(parseFloat(flight.planned_distance.nmi)):null;
                        var prog = '—';
                        if(distFlown!==null&&distPlanned!==null&&distPlanned>0){
                            prog = Math.min(100,Math.round((distFlown/distPlanned)*100))+'%  ('+distFlown+' / '+distPlanned+' nm)';
                        } else if(distPlanned!==null){ prog = distPlanned+' nm total'; }
                        var pilot='—';
                        if(flight.user){pilot=flight.user.name||(flight.user.first_name?flight.user.first_name+(flight.user.last_name?' '+flight.user.last_name.charAt(0)+'.':''):'')||'—';}
                        else if(flight.pilot){pilot=flight.pilot.name||flight.pilot.first_name||'—';}

                        var set=function(id,val){var el=document.getElementById(id);if(el)el.textContent=val;};
                        set('bp-dep',      dep);
                        set('bp-dep-name', depName);
                        set('bp-arr',      arr);
                        set('bp-arr-name', arrName);
                        set('bp-callsign', cs||'—');
                        set('bp-pilot',    pilot);
                        set('bp-aircraft', acStr);
                        set('bp-alt',      alt);
                        set('bp-spd',      spd);
                        set('bp-hdg',      hdg);
                        set('bp-progress', prog);
                        var barEl = document.getElementById('bp-progress-bar');
                        if(barEl){
                            var pct = (distFlown!==null&&distPlanned!==null&&distPlanned>0)
                                ? Math.min(100,Math.round((distFlown/distPlanned)*100)) : 0;
                            barEl.style.width = pct + '%';
                        }
                        set('bp-dist',     distPlanned?distPlanned+' nm':'');

                        var badge=document.getElementById('bp-status');
                        if(badge){badge.textContent=stat;badge.setAttribute('data-status',stat);}

                        var logoWrap=document.getElementById('bp-logo-wrap');
                        var logoImg=document.getElementById('bp-logo');
                        if(logoImg&&flight.airline&&flight.airline.logo){
                            logoImg.src=flight.airline.logo.replace(/^http:\/\//i,'https://');
                            logoImg.style.display='block';
                            if(logoWrap)logoWrap.classList.remove('no-logo');
                        } else {
                            if(logoImg)logoImg.style.display='none';
                            if(logoWrap){logoWrap.classList.add('no-logo');logoWrap.textContent=(flight.airline&&flight.airline.icao)||cs.substring(0,3)||'';}
                        }

                        if(lat!==null&&lng!==null)map.setView([lat,lng],Math.max(map.getZoom(),7),{animate:true});
                        if(lat!==null&&lng!==null&&arr&&arr!=='—'){
                            drawSeq++;routeLineLayer.clearLayers();lastDrawnArr=null;
                            showRouteLine(map,L.latLng(lat,lng),arr);
                        }

                        var rivCard=document.getElementById('map-info-box');
                        if(rivCard)rivCard.style.display='none';
                        // CSS-Klasse statt style.display — verhindert Flicker
                        var card=document.getElementById('va-boarding-pass');
                        if(card)card.classList.add('bp-visible');
                    };

                    (function(){
                        var rivCard=document.getElementById('map-info-box');
                        if(!rivCard) return;
                        // Immer versteckt halten — wir nutzen nur den Boarding-Pass
                        new MutationObserver(function(){
                            var isVisible = rivCard.style.display!=='none' && getComputedStyle(rivCard).display!=='none';
                            if(isVisible) rivCard.style.display = 'none';
                        }).observe(rivCard,{attributes:true,attributeFilter:['style','class']});
                    })();

                    map.on('layeradd', function(e) {
                        var layer=e.layer;
                        if(!layer||!layer.getIcon) return;
                        try {
                            var icon=layer.getIcon(), url=(icon&&icon.options&&icon.options.iconUrl)||'';
                            if(url.indexOf('aircraft.png')===-1) return;
                            layer.setIcon(makeVaIcon());
                            layer.setZIndexOffset(10000);
                            var cs=(layer.options&&layer.options.title)||'';
                            if(cs) vaMarkerCache[cs]=layer;

                            layer.on('click', function(ev){
                                if(ev&&ev.originalEvent) L.DomEvent.stopPropagation(ev);

                                var pos      = layer.getLatLng();
                                var callsign = (layer.options&&layer.options.title)||'';
                                var lat      = pos.lat;
                                var lng      = pos.lng;

                                function openPass(){
                                    var flights = window._vaActiveFlights || [];
                                    var flight  = null;

                                    // 1) Callsign-Match (wenn vorhanden)
                                    if(callsign) {
                                        for(var i=0;i<flights.length;i++){
                                            var f   = flights[i];
                                            var fcs = (f.airline&&f.airline.icao?f.airline.icao:'')+(f.flight_number||f.callsign||'');
                                            if(fcs===callsign||f.callsign===callsign){ flight=f; break; }
                                        }
                                    }

                                    // 2) Fallback: nächster Flug zur Marker-Position
                                    if(!flight && flights.length>0) {
                                        var bestDist = Infinity;
                                        for(var j=0;j<flights.length;j++){
                                            var fj = flights[j];
                                            if(!fj.position||fj.position.lat==null) continue;
                                            var dlat = parseFloat(fj.position.lat)-lat;
                                            var dlng = parseFloat(fj.position.lon||fj.position.lng||0)-lng;
                                            var dist = dlat*dlat+dlng*dlng;
                                            if(dist<bestDist){ bestDist=dist; flight=fj; }
                                        }
                                        // Nur akzeptieren wenn wirklich nah (< ~0.1 Grad)
                                        if(bestDist > 0.01) flight = null;
                                    }

                                    if(!flight) return false;

                                    window.vaInfoCardOpen(flight, lat, lng);
                                    document.querySelectorAll('#va-rows-active .active-flight')
                                        .forEach(function(r){ r.classList.remove('active-flight'); });
                                    var safeCs = safeCallsign(callsign); var row = safeCs ? document.querySelector('#va-rows-active [data-callsign="'+safeCs+'"]') : null;
                                    if(row) row.classList.add('active-flight');
                                    return true;
                                }

                                // Sofort versuchen (Cache schon befüllt)
                                if(!openPass()){
                                    // Cache noch leer — kurz warten bis renderFlights durchläuft
                                    var tries = 0;
                                    var iv = setInterval(function(){
                                        if(openPass() || ++tries > 10) clearInterval(iv);
                                    }, 100);
                                }
                            });
                        } catch(err){}
                    });

                    var logoImg=document.getElementById('map-airline-logo');
                    if(logoImg){
                        new MutationObserver(function(){
                            var src=logoImg.getAttribute('src')||'';
                            if(src&&src!==logoImg._lastSrc){logoImg._lastSrc=src;logoImg.src=src.replace(/^http:\/\//,'https://');}
                        }).observe(logoImg,{attributes:true,attributeFilter:['src']});
                    }

                    var followEnabled = !!(window.LIVE_MAP_UI && window.LIVE_MAP_UI.defaultFollowFlight);
                    var _origPanTo=map.panTo.bind(map), _origSetView=map.setView.bind(map);
                    var _origFlyTo=map.flyTo?map.flyTo.bind(map):null;
                    var _origFitBounds=map.fitBounds?map.fitBounds.bind(map):null;
                    var isApplyingAutoFit = false;
                    var lastAutoFitSignature = '';
                    var lastAutoFitAt = 0;

                    function withAutoFitGuard(fn) {
                        isApplyingAutoFit = true;
                        try { return fn(); }
                        finally { isApplyingAutoFit = false; }
                    }

                    function collectActiveFlightPoints() {
                        var flights = window._vaActiveFlights || [];
                        var points = [];
                        for (var i = 0; i < flights.length; i++) {
                            var pos = flights[i] && flights[i].position;
                            if (!pos) continue;
                            var lat = parseFloat(pos.lat);
                            var lng = (pos.lon != null) ? parseFloat(pos.lon) : parseFloat(pos.lng);
                            if (!isFinite(lat) || !isFinite(lng)) continue;
                            points.push([lat, lng]);
                        }
                        return points;
                    }

                    function pointsSignature(points) {
                        return points
                            .map(function(p){ return p[0].toFixed(2) + ',' + p[1].toFixed(2); })
                            .sort()
                            .join('|');
                    }

                    function hasSelectedFlightRow() {
                        return !!document.querySelector('#va-rows-active .active-flight, #va-rows-planned .active-flight');
                    }

                    function shouldUseMultiFollow() {
                        if (!followEnabled || isApplyingAutoFit) return false;
                        if (hasSelectedFlightRow()) return false;
                        return collectActiveFlightPoints().length > 1;
                    }

                    function fitToActiveFlights(options) {
                        options = options || {};
                        if (!map || !map._loaded) return false;
                        if (!followEnabled && !options.force) return false;
                        if (!options.force && hasSelectedFlightRow()) return false;

                        var points = collectActiveFlightPoints();
                        if (points.length === 0) return false;

                        if (points.length === 1) {
                            return withAutoFitGuard(function(){
                                _origSetView(points[0], Math.max(map.getZoom(), 7), { animate: options.animate !== false });
                                return true;
                            });
                        }

                        if (!_origFitBounds) return false;

                        var sig = pointsSignature(points);
                        var now = Date.now();
                        if (!options.force && sig === lastAutoFitSignature && (now - lastAutoFitAt) < 5000) return false;
                        lastAutoFitSignature = sig;
                        lastAutoFitAt = now;

                        var bounds = L.latLngBounds(points);
                        if (!bounds.isValid()) return false;

                        var isMobile = window.innerWidth <= 768;
                        return withAutoFitGuard(function(){
                            _origFitBounds(bounds, {
                                animate: options.animate !== false,
                                paddingTopLeft: isMobile ? [20, 20] : [36, 36],
                                paddingBottomRight: isMobile ? [20, 20] : [36, 36],
                                maxZoom: isMobile ? 7 : 8
                            });
                            return true;
                        });
                    }

                    window._liveMapFitToActiveFlights = fitToActiveFlights;
                    window._liveMapFollowEnabled = function(){ return followEnabled; };

                    map.panTo=function(latlng,options){
                        if(isApplyingAutoFit) return _origPanTo(latlng,options);
                        if(!followEnabled) return map;
                        if(shouldUseMultiFollow()){ fitToActiveFlights({ animate:false }); return map; }
                        return _origPanTo(latlng,options);
                    };
                    map.setView=function(center,zoom,options){
                        if(isApplyingAutoFit) return _origSetView(center,zoom,options);
                        if(!followEnabled&&map._loaded){
                            var cz=map.getZoom();
                            if(zoom!==undefined&&zoom!==cz)return _origSetView(map.getCenter(),zoom,options);
                            return map;
                        }
                        if(shouldUseMultiFollow()){ fitToActiveFlights({ animate:false }); return map; }
                        return _origSetView(center,zoom,options);
                    };
                    if(_origFlyTo) map.flyTo=function(latlng,zoom,options){
                        if(isApplyingAutoFit) return _origFlyTo(latlng,zoom,options);
                        if(!followEnabled)return map;
                        if(shouldUseMultiFollow()){ fitToActiveFlights({ animate:false }); return map; }
                        return _origFlyTo(latlng,zoom,options);
                    };

                    function applyLayerVisibility() {
                        if(vatsimShowPilots&&showVatsim){if(!map.hasLayer(vatsimPilotsLayer))vatsimPilotsLayer.addTo(map);}else map.removeLayer(vatsimPilotsLayer);
                        if(vatsimShowPilots&&showIvao){if(!map.hasLayer(ivaoPilotsLayer))ivaoPilotsLayer.addTo(map);}else map.removeLayer(ivaoPilotsLayer);
                        if(vatsimShowCtrl&&showVatsim){if(!map.hasLayer(vatsimCtrlLayer))vatsimCtrlLayer.addTo(map);}else map.removeLayer(vatsimCtrlLayer);
                        if(vatsimShowCtrl&&showIvao){if(!map.hasLayer(ivaoCtrlLayer))ivaoCtrlLayer.addTo(map);}else map.removeLayer(ivaoCtrlLayer);
                        if(vatsimShowSectors&&showVatsim){if(!map.hasLayer(vatsimSectorLayer))vatsimSectorLayer.addTo(map);}else map.removeLayer(vatsimSectorLayer);
                        if(vatsimShowSectors&&showIvao){if(!map.hasLayer(ivaoSectorLayer))ivaoSectorLayer.addTo(map);}else map.removeLayer(ivaoSectorLayer);
                    }

                    var btnNetVatsim=document.getElementById('btnNetVatsim'), btnNetIvao=document.getElementById('btnNetIvao');
                    if(btnNetVatsim){btnNetVatsim.style.opacity=showVatsim?'1':'.45';btnNetVatsim.addEventListener('click',function(){showVatsim=!showVatsim;btnNetVatsim.style.opacity=showVatsim?'1':'.45';if(!showVatsim){map.removeLayer(vatsimPilotsLayer);map.removeLayer(vatsimCtrlLayer);map.removeLayer(vatsimSectorLayer);}else applyLayerVisibility();});}
                    if(btnNetIvao){btnNetIvao.style.opacity=showIvao?'1':'.45';btnNetIvao.addEventListener('click',function(){showIvao=!showIvao;btnNetIvao.style.opacity=showIvao?'1':'.45';if(!showIvao){map.removeLayer(ivaoPilotsLayer);map.removeLayer(ivaoCtrlLayer);map.removeLayer(ivaoSectorLayer);}else{loadIvao(map);applyLayerVisibility();}});}

                    var btnPilots=document.getElementById('btnVatsimPilots'), btnCtrl=document.getElementById('btnVatsimCtrl');
                    var btnSectors=document.getElementById('btnVatsimSectors'), btnFollow=document.getElementById('btnFollowFlight');
                    if(btnPilots){btnPilots.classList.toggle('active',vatsimShowPilots);btnPilots.addEventListener('click',function(){vatsimShowPilots=!vatsimShowPilots;btnPilots.classList.toggle('active',vatsimShowPilots);applyLayerVisibility();});}
                    if(btnCtrl){btnCtrl.classList.toggle('active',vatsimShowCtrl);btnCtrl.addEventListener('click',function(){vatsimShowCtrl=!vatsimShowCtrl;btnCtrl.classList.toggle('active',vatsimShowCtrl);applyLayerVisibility();});}
                    if(btnSectors){btnSectors.classList.toggle('active',vatsimShowSectors);btnSectors.addEventListener('click',function(){vatsimShowSectors=!vatsimShowSectors;btnSectors.classList.toggle('active',vatsimShowSectors);applyLayerVisibility();});}
                    if(btnFollow){
                        var syncFollowBtn = function() {
                            btnFollow.classList.toggle('active',followEnabled);
                            var span=btnFollow.querySelector('span'),icon=btnFollow.querySelector('i');
                            if(span)span.textContent=followEnabled?'Follow Flight':'Free Scroll';
                            if(icon)icon.className=followEnabled?'fas fa-crosshairs':'fas fa-lock-open';
                        };
                        syncFollowBtn();
                        btnFollow.addEventListener('click',function(){
                            followEnabled=!followEnabled;
                            syncFollowBtn();
                            if(followEnabled) setTimeout(function(){ fitToActiveFlights({ force:true, animate:true }); }, 50);
                        });
                    }
                    setTimeout(function(){ fitToActiveFlights({ animate:false }); }, 250);
                    applyLayerVisibility();
                });

            } else {
                console.error('[LiveMap] Leaflet nicht geladen, Hooks konnten nicht registriert werden');
            }

            // ── render_live_map ──
            if (!window.phpvms || !phpvms.map || typeof phpvms.map.render_live_map !== 'function') {
                console.error('[LiveMap] phpvms.map helper not available; cannot init live map');
                return;
            }

            // ── Mobile Toggle Funktionen ──
            window.mobTogglePanel = function() {
                var panel = document.getElementById('va-flights-panel');
                if (!panel) return;
                var isVisible = panel.classList.contains('mobile-visible');
                panel.classList.toggle('mobile-visible', !isVisible);
                if (!isVisible) {
                    // Panel öffnen + Tabelle aufklappen
                    var body = document.getElementById('va-flights-body');
                    if (body && !body.classList.contains('open')) body.classList.add('open');
                    setTimeout(adjustPanelHeight, 60);
                }
                var btn = document.getElementById('mob-toggle-panel');
                setMobileBtnState(btn, !isVisible);
            };

            window.mobToggleWeather = function() {
                var wc = document.getElementById('weather-content');
                var ch = document.getElementById('weather-chevron');
                if (!wc) return;
                var isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    wc.classList.toggle('mob-expanded');
                    if (ch) ch.textContent = wc.classList.contains('mob-expanded') ? '▼' : '▶';
                } else {
                    wc.classList.toggle('collapsed');
                    if (ch) ch.textContent = wc.classList.contains('collapsed') ? '▶' : '▼';
                }
            };

            // Vatsim Content toggle (Titel-Klick)
            window.mobToggleVatsimContent = function() {
                var vc = document.getElementById('vatsim-content');
                var ch = document.getElementById('vatsim-chevron');
                if (!vc) return;
                var isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    vc.classList.toggle('mob-expanded');
                    if (ch) ch.textContent = vc.classList.contains('mob-expanded') ? '▼' : '▶';
                } else {
                    vc.classList.toggle('collapsed');
                    if (ch) ch.textContent = vc.classList.contains('collapsed') ? '▶' : '▼';
                }
            };

            // Mobile default states from admin settings
            if (window.innerWidth <= 768) {
                var mobileCfg = window.LIVE_MAP_UI || {};

                var panel = document.getElementById('va-flights-panel');
                var panelBodyMob = document.getElementById('va-flights-body');
                var panelBtn = document.getElementById('mob-toggle-panel');
                var flightsShouldOpen = !!mobileCfg.mobileFlightsOpen && !!mobileCfg.showTopFlightsPanel;

                if (panelBodyMob) panelBodyMob.classList.toggle('open', flightsShouldOpen);
                if (panel) panel.classList.toggle('mobile-visible', flightsShouldOpen);
                setMobileBtnState(panelBtn, flightsShouldOpen);

                var weatherContent = document.getElementById('weather-content');
                var weatherChevron = document.getElementById('weather-chevron');
                var weatherShouldOpen = !!mobileCfg.mobileWeatherOpen;
                if (weatherContent) weatherContent.classList.toggle('mob-expanded', weatherShouldOpen);
                if (weatherChevron) weatherChevron.textContent = weatherShouldOpen ? '▼' : '▶';

                var networkContent = document.getElementById('vatsim-content');
                var networkChevron = document.getElementById('vatsim-chevron');
                var networkShouldOpen = !!mobileCfg.mobileNetworkOpen;
                if (networkContent) networkContent.classList.toggle('mob-expanded', networkShouldOpen);
                if (networkChevron) networkChevron.textContent = networkShouldOpen ? '▼' : '▶';
            }

            phpvms.map.render_live_map({
                center: [parseFloat('{{ $center[0] }}') || 50.0, parseFloat('{{ $center[1] }}') || 10.0],
                zoom: parseInt('{{ $zoom }}') || 6,
                aircraft_icon: '{!! public_asset('/assets/img/acars/aircraft.png') !!}',
                refresh_interval: {{ setting('acars.update_interval', 60) }},
                units: '{{ setting('units.distance') }}',
                flown_route_color: '#db2433',
                leafletOptions: {
                    scrollWheelZoom: true,
                    providers: {
                        'CartoDB.Positron': {},
                    }
                }
            });

        });
    </script>
@endsection
