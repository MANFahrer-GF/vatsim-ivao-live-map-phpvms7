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
