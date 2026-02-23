<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 mb-3">
        <div class="card border mb-0">
            <div class="card-body p-0 position-relative">

                <style>
                    .live-map-wrapper {
                        position: relative;
                        width: 100%;
                        height: {{ $config['height'] }};
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

                    /* Boarding / planned */
                    .status-badge[data-status*="board" i],
                    .status-badge[data-status*="sched" i],
                    .status-badge[data-status*="pre-flight" i],
                    .status-badge[data-status*="preflight" i] {
                        background: #3498db;
                    }

                    /* Ground movement */
                    .status-badge[data-status*="push" i],
                    .status-badge[data-status*="taxi" i] {
                        background: #f39c12;
                    }

                    /* Airborne phases */
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

                    /* Completed */
                    .status-badge[data-status*="arrived" i],
                    .status-badge[data-status*="landed" i],
                    .status-badge[data-status*="parked" i],
                    .status-badge[data-status*="completed" i] {
                        background: #16a085;
                    }

                    /* Abnormal */
                    .status-badge[data-status*="divert" i],
                    .status-badge[data-status*="cancel" i],
                    .status-badge[data-status*="abort" i],
                    .status-badge[data-status*="emerg" i] {
                        background: #e74c3c;
                    }

                    /* Paused / holding */
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
                        background: rgba(255,255,255,0.97);
                        border-radius: 10px;
                        padding: 8px 10px 6px;
                        z-index: 1100;
                        box-shadow: 0 3px 10px rgba(0,0,0,0.25);
                        border: 1px solid #ddd;
                    }

                    .map-weather-title {
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.08em;
                        color: #777;
                        margin-bottom: 4px;
                        text-align: center;
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
                    }

                    .weather-btn i {
                        font-size: 17px;
                        color: #555;
                        margin-bottom: 2px;
                    }

                    .weather-btn span {
                        color: #666;
                    }

                    .weather-btn.active {
                        border-color: #2ecc71;
                        background: #e9f9f0;
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

                    /* Make OWM overlays clearly visible */
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
                        .map-weather-box-left {
                            left: 10px;
                            bottom: 10px;
                            width: 240px;
                        }
                    }

                    /* ── VATSIM CONTROL BOX (BOTTOM-RIGHT) ── */
                    .map-vatsim-box {
                        position: absolute;
                        bottom: 20px;
                        right: 20px;
                        width: 200px;
                        background: rgba(255,255,255,0.97);
                        border-radius: 10px;
                        padding: 8px 10px 8px;
                        z-index: 1100;
                        box-shadow: 0 3px 10px rgba(0,0,0,0.25);
                        border: 1px solid #ddd;
                    }

                    .map-vatsim-title {
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.08em;
                        color: #777;
                        margin-bottom: 6px;
                        text-align: center;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
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

                    .vatsim-btn i { font-size: 15px; color: #555; margin-bottom: 2px; }
                    .vatsim-btn span { color: #666; }

                    /* Aktiv: subtiler blauer Hintergrund, kein dicker Rahmen */
                    .vatsim-btn.active { border-color: #3498db; background: #eaf4fd; }
                    .vatsim-btn.active i, .vatsim-btn.active span { color: #2980b9; }

                    /* Follow-Button: inaktiv = ausgegraut, aktiv = grün (nicht blau) */
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

                    /* ── VATSIM AIRCRAFT MARKER ── */
                    .vatsim-ac-icon {
                        width: 26px; height: 26px;
                        display: flex; align-items: center; justify-content: center;
                        filter: drop-shadow(0 1px 3px rgba(0,0,0,0.5));
                    }

                    /* ── VATSIM CONTROLLER MARKER ── */
                    .vatsim-ctrl-icon {
                        width: 22px; height: 22px;
                        border-radius: 50%;
                        border: 2px solid rgba(255,255,255,0.85);
                        display: flex; align-items: center; justify-content: center;
                        font-size: 9px; font-weight: 700; color: #fff;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.4);
                    }

                    /* ── VATSIM POPUP ── */
                    .vatsim-popup {
                        min-width: 220px;
                        font-size: 13px;
                        line-height: 1.5;
                        padding: 0;
                    }

                    /* Leaflet popup-content padding entfernen damit Header bündig ist */
                    .leaflet-popup-content {
                        margin: 0 !important;
                        overflow: hidden;
                        border-radius: 8px;
                    }

                    /* Airport-Marker: Leaflet darf nicht clippen */
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

                <div class="live-map-wrapper">
                    <div id="map"></div>

                    {{-- FLIGHT INFO (TOP-RIGHT) --}}
                    <div id="map-info-box" class="map-info-card-big" rv-show="pirep.id">

                        {{-- Header: Logo + Route --}}
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

                        {{-- Body: Flight details --}}
                        <div class="map-info-card-body">
                            <div class="map-info-row-big">
                                <strong>{ pirep.airline.icao }{ pirep.flight_number }</strong>
                            </div>
                            <div class="map-info-row-big">
                                { pirep.aircraft.registration } ({ pirep.aircraft.icao })
                            </div>

                            <hr>

                            <div class="map-info-row-big">{ pirep.position.altitude } ft</div>
                            <div class="map-info-row-big">{ pirep.position.gs } kts</div>
                            <div class="map-info-row-big">Time flown: { pirep.flight_time | time_hm }</div>

                            <hr>

                            {{-- STATUS BADGE --}}
                            <span class="status-badge"
                                  rv-text="pirep.status_text"
                                  rv-data-status="pirep.status_text"></span>
                        </div>

                    </div>

                    {{-- WEATHER BOX (BOTTOM-LEFT) --}}
                    <div class="map-weather-box-left">
                        <div class="map-weather-title">Weather Layers</div>

                        <div class="map-weather-buttons">
                            {{-- Row 1 --}}
                            <button id="btnClouds" type="button" class="weather-btn" title="Clouds">
                                <i class="fas fa-cloud"></i>
                                <span>Clouds</span>
                            </button>

                            <button id="btnRadar" type="button" class="weather-btn" title="Radar / Precipitation">
                                <i class="fas fa-cloud-sun-rain"></i>
                                <span>Radar</span>
                            </button>

                            <button id="btnStorms" type="button" class="weather-btn" title="Thunder / Storms">
                                <i class="fas fa-bolt"></i>
                                <span>Storms</span>
                            </button>

                            {{-- Row 2 --}}
                            <button id="btnWind" type="button" class="weather-btn" title="Wind">
                                <i class="fas fa-wind"></i>
                                <span>Wind</span>
                            </button>

                            <button id="btnTemp" type="button" class="weather-btn" title="Temperature">
                                <i class="fas fa-thermometer-half"></i>
                                <span>Temp</span>
                            </button>

                            <button id="btnCombined" type="button" class="weather-btn" title="Combined mode">
                                <i class="fas fa-layer-group"></i>
                                <span>Combo</span>
                            </button>

                            {{-- Row 3: Dark map --}}
                            <button id="btnDarkMap" type="button" class="weather-btn" title="Dark map"
                                    style="flex: 0 0 100%; max-width: 100%;">
                                <i class="fas fa-moon"></i>
                                <span>Dark map</span>
                            </button>
                        </div>

                        <div class="weather-slider-wrapper">
                            <span>Opacity</span>
                            <input type="range" id="weatherOpacity" min="0.2" max="1" step="0.05" value="1">
                        </div>
                    </div>

                    {{-- VATSIM BOX (BOTTOM-RIGHT) --}}
                    <div class="map-vatsim-box">
                        <div class="map-vatsim-title">
                            <span class="vatsim-dot" id="vatsimDot"></span>
                            VATSIM Live
                        </div>
                        <div class="map-vatsim-buttons">
                            <button id="btnVatsimPilots" type="button" class="vatsim-btn" title="Show VATSIM pilots">
                                <i class="fas fa-plane"></i>
                                <span>Pilots</span>
                            </button>
                            <button id="btnVatsimCtrl" type="button" class="vatsim-btn active" title="Show VATSIM controllers">
                                <i class="fas fa-headset"></i>
                                <span>Controllers</span>
                            </button>
                            <button id="btnVatsimSectors" type="button" class="vatsim-btn" title="Show FIR sector boundaries"
                                    style="flex:0 0 100%;max-width:100%">
                                <i class="fas fa-draw-polygon"></i>
                                <span>FIR Sectors</span>
                            </button>
                            <button id="btnFollowFlight" type="button" class="vatsim-btn active"
                                    title="Follow own flight" style="flex:0 0 100%;max-width:100%;margin-top:4px">
                                <i class="fas fa-crosshairs"></i>
                                <span>Follow Flight</span>
                            </button>
                        </div>
                        <div class="vatsim-stats" id="vatsimStats">Lade Daten…</div>

                        <!-- Badge-Legende -->
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

                </div>

            </div>
        </div>
    </div>
</div>


@section('scripts')
    @parent

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('[LiveMap] DOMContentLoaded');

            // Rivets formatters
            if (typeof rivets !== 'undefined') {
                // store for fallback baseline
                window.liveMapProgress = {
                    initialRemaining: null
                };

                function nm(val) {
                    if (val == null) return NaN;
                    if (typeof val === 'object' && 'nmi' in val) {
                        var n = parseFloat(val.nmi);
                        return isNaN(n) ? NaN : n;
                    }
                    var n2 = parseFloat(val);
                    return isNaN(n2) ? NaN : n2;
                }

                // core helper: returns fraction 0–1 based on remaining + total
                function progressFraction(remaining, total) {
                    var rem = nm(remaining);
                    var tot = nm(total);
                    if (!rem || rem < 0 || isNaN(rem)) return 0;

                    // If we have a valid total, use it
                    if (tot && tot > 0 && !isNaN(tot)) {
                        var done = (tot - rem) / tot;
                        if (!isFinite(done)) done = 0;
                        return Math.max(0, Math.min(1, done));
                    }

                    // Fallback: dynamic baseline from remaining only
                    var store = window.liveMapProgress;
                    if (!store.initialRemaining || rem > store.initialRemaining) {
                        store.initialRemaining = rem;
                    }
                    var initial = store.initialRemaining;
                    var done2 = (initial - rem) / initial;
                    if (!isFinite(done2)) done2 = 0;
                    return Math.max(0, Math.min(1, done2));
                }

                // Remaining distance display
                rivets.formatters.to_go = function (remaining) {
                    var rem = nm(remaining);
                    if (!rem || rem < 0 || isNaN(rem)) return '—';
                    return Math.round(rem) + ' nmi';
                };

                // Progress 0–100% text
                rivets.formatters.progress_from_remaining = function (remaining, total) {
                    var frac = progressFraction(remaining, total);
                    return Math.round(frac * 100) + '%';
                };

                // Style for bar: width + color based on percentage
                rivets.formatters.progress_bar_style = function (remaining, total) {
                    var frac = progressFraction(remaining, total);
                    var pct = Math.round(frac * 100);

                    var color;
                    if (pct < 30) {
                        color = '#e74c3c'; // red
                    } else if (pct < 60) {
                        color = '#f39c12'; // orange
                    } else if (pct < 85) {
                        color = '#f1c40f'; // yellow
                    } else {
                        color = '#2ecc71'; // green
                    }

                    return 'width:' + pct + '%; background:' + color + ';';
                };

                // Style for circular progress (conic-gradient)
                rivets.formatters.progress_circle_style = function (remaining, total) {
                    var frac = progressFraction(remaining, total);
                    var pct = Math.round(frac * 100);

                    var color;
                    if (pct < 30) {
                        color = '#e74c3c';
                    } else if (pct < 60) {
                        color = '#f39c12';
                    } else if (pct < 85) {
                        color = '#f1c40f';
                    } else {
                        color = '#2ecc71';
                    }

                    return 'background: conic-gradient(' + color + ' 0 ' + pct +
                        '%, #e5e5e5 ' + pct + '% 100%);';
                };

                // Remaining time from remaining + GS
                rivets.formatters.rem_time_from_remaining = function (remaining, gs) {
                    var rem = nm(remaining);
                    var speed = parseFloat(gs);
                    if (!rem || rem <= 0 || !speed || speed <= 0) return '—';

                    var hours = rem / speed;
                    var mins = Math.round(hours * 60);
                    var h = Math.floor(mins / 60);
                    var m = mins % 60;
                    if (h <= 0) return m + 'm';
                    return h + 'h ' + (m < 10 ? '0' + m : m) + 'm';
                };

                // ETA local from remaining + GS
                rivets.formatters.eta_from_remaining = function (remaining, gs) {
                    var rem = nm(remaining);
                    var speed = parseFloat(gs);
                    if (!rem || rem <= 0 || !speed || speed <= 0) return '—';

                    var hours = rem / speed;
                    var ms = hours * 3600000;
                    var now = new Date();
                    var eta = new Date(now.getTime() + ms);
                    var hh = eta.getHours().toString().padStart(2, '0');
                    var mm = eta.getMinutes().toString().padStart(2, '0');
                    return hh + ':' + mm;
                };
            }

            function attachWeatherToMap(map) {
                console.log('[LiveMap] attachWeatherToMap called, map:', map);

                // 👉 PUT YOUR REAL OWM API KEY HERE
                var OWM_API_KEY = "YOUR_OPENWEATHERMAP_API_KEY_HERE";

                if (!OWM_API_KEY || OWM_API_KEY === "YOUR_OPENWEATHERMAP_API_KEY_HERE") {
                    console.warn('[LiveMap] OWM API key not set; skipping overlays');
                    return;
                }

                // Create a dedicated pane for all weather overlays
                var weatherPane = map.getPane('weatherPane');
                if (!weatherPane) {
                    map.createPane('weatherPane');
                    weatherPane = map.getPane('weatherPane');
                }
                weatherPane.style.zIndex = 650;
                weatherPane.style.pointerEvents = 'none';

                // --- OWM layers ---
                var cloudsLayer = L.tileLayer(
                    "https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=" + OWM_API_KEY,
                    {
                        opacity: 1.0,
                        pane: 'weatherPane',
                        className: 'owm-clouds-layer',
                        attribution: "Clouds © OpenWeatherMap"
                    }
                );

                var precipLayer = L.tileLayer(
                    "https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=" + OWM_API_KEY,
                    {
                        opacity: 1.0,
                        pane: 'weatherPane',
                        className: 'owm-precip-layer',
                        attribution: "Precipitation © OpenWeatherMap"
                    }
                );

                var stormsLayer = L.tileLayer(
                    "https://tile.openweathermap.org/map/thunder_new/{z}/{x}/{y}.png?appid=" + OWM_API_KEY,
                    {
                        opacity: 1.0,
                        pane: 'weatherPane',
                        className: 'owm-thunder-layer owm-storms-layer',
                        attribution: "Thunderstorms © OpenWeatherMap"
                    }
                );

                var windLayer = L.tileLayer(
                    "https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=" + OWM_API_KEY,
                    {
                        opacity: 1.0,
                        pane: 'weatherPane',
                        className: 'owm-wind-layer',
                        attribution: "Wind © OpenWeatherMap"
                    }
                );

                var tempLayer = L.tileLayer(
                    "https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=" + OWM_API_KEY,
                    {
                        opacity: 1.0,
                        pane: 'weatherPane',
                        className: 'owm-temp-layer',
                        attribution: "Temperature © OpenWeatherMap"
                    }
                );

                // Weather starts OFF — user activates manually
                // precipLayer.addTo(map);  ← disabled

                // Buttons
                var btnClouds   = document.getElementById("btnClouds");
                var btnRadar    = document.getElementById("btnRadar");
                var btnStorms   = document.getElementById("btnStorms");
                var btnWind     = document.getElementById("btnWind");
                var btnTemp     = document.getElementById("btnTemp");
                var btnCombined = document.getElementById("btnCombined");
                var btnDarkMap  = document.getElementById("btnDarkMap");
                var opacitySlider = document.getElementById("weatherOpacity");
                var mapDiv = document.getElementById("map");

                if (!btnClouds || !btnRadar || !btnStorms || !btnWind || !btnTemp || !btnCombined || !btnDarkMap) {
                    console.error('[LiveMap] Weather buttons not found in DOM');
                    return;
                }

                // Track button states
                btnClouds._on   = false;
                btnRadar._on    = false;
                btnStorms._on   = false;
                btnWind._on     = false;
                btnTemp._on     = false;

                // No weather active on start

                var allLayers = [cloudsLayer, precipLayer, stormsLayer, windLayer, tempLayer];

                function setAllWeatherOpacity(op) {
                    allLayers.forEach(function (layer) {
                        if (layer.setOpacity) {
                            layer.setOpacity(op);
                        }
                    });
                }

                function toggleLayer(btn, layer) {
                    if (!layer) return;

                    if (btn._on) {
                        map.removeLayer(layer);
                        btn.classList.remove("active");
                    } else {
                        layer.addTo(map);
                        btn.classList.add("active");
                    }
                    btn._on = !btn._on;
                }

                // Button handlers
                btnClouds.addEventListener("click", function () {
                    toggleLayer(btnClouds, cloudsLayer);
                });

                btnRadar.addEventListener("click", function () {
                    toggleLayer(btnRadar, precipLayer);
                });

                btnStorms.addEventListener("click", function () {
                    toggleLayer(btnStorms, stormsLayer);
                });

                btnWind.addEventListener("click", function () {
                    toggleLayer(btnWind, windLayer);
                });

                btnTemp.addEventListener("click", function () {
                    toggleLayer(btnTemp, tempLayer);
                });

                // Combined mode: Clouds + Radar + Thunder ON
                btnCombined.addEventListener("click", function () {
                    if (!btnClouds._on) {
                        cloudsLayer.addTo(map);
                        btnClouds._on = true;
                        btnClouds.classList.add("active");
                    }
                    if (!btnRadar._on) {
                        precipLayer.addTo(map);
                        btnRadar._on = true;
                        btnRadar.classList.add("active");
                    }
                    if (!btnStorms._on) {
                        stormsLayer.addTo(map);
                        btnStorms._on = true;
                        btnStorms.classList.add("active");
                    }
                });

                // Dark map toggle (CSS filter)
                btnDarkMap.addEventListener("click", function () {
                    var dark = mapDiv.classList.toggle("dark-map");
                    if (dark) {
                        btnDarkMap.classList.add("active");
                    } else {
                        btnDarkMap.classList.remove("active");
                    }
                });

                // Opacity slider
                opacitySlider.addEventListener("input", function () {
                    var op = parseFloat(this.value);
                    setAllWeatherOpacity(op);
                });
            }

            // ════════════════════════════════════════════════════════════
            //  VATSIM LIVE INTEGRATION  (VOR render_live_map!)
            //  Standard AUS – manuell einschalten
            //  Controller-Positionen via Transceivers-API (wie vatSpy)
            //  Airline-Logos via avs.io
            // ════════════════════════════════════════════════════════════

            var VATSIM_DATA_API   = 'https://data.vatsim.net/v3/vatsim-data.json';
            var VATSIM_TRX_API    = 'https://data.vatsim.net/v3/transceivers-data.json';
            var VATSPY_BOUNDS_API = 'https://raw.githubusercontent.com/vatsimnetwork/vatspy-data-project/master/Boundaries.geojson';
            var VATSPY_DAT_API    = 'https://raw.githubusercontent.com/vatsimnetwork/vatspy-data-project/master/VATSpy.dat';
            var VATSIM_REFRESH_MS = 30000;

            // Statischer Airport-Positions-Cache aus VATSpy.dat (ICAO → [lat, lon])
            // Deckt ALLE Airports ab — nie wieder falsche Positionen durch Transceivers
            var staticAirportPos  = {};
            var airportNameCache  = {}; // ICAO → full airport name
            var staticAirportLoaded = false;

            // Cache: ICAO → FIR-Name
            var firNameCache = {};
            var firNameLoaded = false;

            // Default: Controllers + FIR Sectors ON, Pilots OFF
            var vatsimShowPilots = false;
            var vatsimShowCtrl   = true;

            var vatsimPilotsLayer = L.layerGroup();
            var vatsimCtrlLayer   = L.layerGroup();
            var vatsimSectorLayer = L.layerGroup(); // FIR/CTR-Sektorgrenzen
            var routeLineLayer    = L.layerGroup(); // Gestrichelte Linie zum Ziel

            // Zeichnet gestrichelte Linie von Flugzeug zu Ziel-Airport
            // Wird bei Klick auf Flugzeug gezeigt, bei Klick woanders entfernt
            function showRouteLine(map, fromLatLng, toIcao) {
                routeLineLayer.clearLayers();
                var toPos = staticAirportPos[toIcao]
                         || staticAirportPos['K' + toIcao]
                         || staticAirportPos['C' + toIcao]
                         || staticAirportPos['P' + toIcao];
                if (!toPos) return; // Ziel unbekannt

                // Gestrichelte Linie
                var line = L.polyline([fromLatLng, toPos], {
                    color: '#e74c3c',
                    weight: 2,
                    opacity: 0.8,
                    dashArray: '8 6',
                    dashOffset: '0',
                }).addTo(routeLineLayer);

                // Ziel-Marker: kleiner roter Punkt mit ICAO
                L.marker(toPos, {
                    icon: L.divIcon({
                        html: '<div style="background:#e74c3c;color:#fff;font-size:9px;font-weight:700;' +
                              'padding:2px 6px;border-radius:3px;white-space:nowrap;' +
                              'box-shadow:0 1px 4px rgba(0,0,0,0.4)">' + toIcao + '</div>',
                        className: '', iconSize: [null, null], iconAnchor: [20, -4],
                    }),
                    interactive: false,
                }).addTo(routeLineLayer);

                // Klick auf Karte → Linie entfernen
                map.once('click', function() { routeLineLayer.clearLayers(); });
            }
            var vatsimShowSectors = false;

            // FIR-Boundaries-Cache: wird einmal geladen und gecacht
            var firBoundsGeoJson  = null;

            // Controller-Positions-Cache: callsign → [lat, lon]
            // Wird aus der Transceivers-API befüllt (exakt wie vatSpy das macht)
            var ctrlPosCache = {};

            var CTRL_TYPES = {
                0: { label: 'OBS', color: '#95a5a6' },
                1: { label: 'FSS', color: '#8e44ad' },
                2: { label: 'DEL', color: '#2980b9' },
                3: { label: 'GND', color: '#d35400' },
                4: { label: 'TWR', color: '#e74c3c' },
                5: { label: 'APP', color: '#27ae60' },
                6: { label: 'CTR', color: '#1abc9c' },
            };

            // Airline-Logo: jsDelivr CDN → github.com/sexym0nk3y/airline-logos (~900 Airlines)
            // Kein API-Key nötig, kostenlos, sehr zuverlässig
            @php
                try {
                    $airlineLogos = \App\Models\Airline::whereNotNull('logo')
                        ->where('logo', '!=', '')
                        ->get(['icao','logo'])
                        ->mapWithKeys(function($a) {
                            $logo = $a->logo;
                            if ($logo && !str_starts_with($logo, 'http')) {
                                $logo = url($logo);
                            }
                            if ($logo && str_starts_with($logo, 'http://')) {
                                $logo = 'https://' . substr($logo, 7);
                            }
                            return [strtoupper($a->icao) => $logo];
                        })->toArray();
                } catch(\Exception $e) {
                    $airlineLogos = [];
                }
            @endphp
            var AIRLINE_LOGOS = {!! json_encode($airlineLogos) !!};
            console.log('[LiveMap] Airline-Logos geladen: ' + Object.keys(AIRLINE_LOGOS).length);

            function buildLogoHtml(callsign) {
                if (!callsign || callsign.length < 3) return '';
                var icao = callsign.substring(0, 3).toUpperCase();
                if (!/^[A-Z]{3}$/.test(icao)) return '';
                var logoUrl = AIRLINE_LOGOS[icao];
                if (!logoUrl) return '';
                return '<div style="text-align:center;padding:6px 0 10px;border-bottom:1px solid #eee;margin-bottom:8px">' +
                    '<img src="' + logoUrl + '" ' +
                    'style="max-height:38px;max-width:140px;object-fit:contain;vertical-align:middle" ' +
                    'onerror="this.closest(\'div\').remove();" ' +
                    'alt="' + icao + '">' +
                    '</div>';
            }

            // Flugzeug-Icon: realistisches Flugzeug-SVG, nach Heading rotiert
            function buildAircraftIcon(heading) {
                var h = (heading != null ? heading : 0);
                var svg =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22">' +
                    '<g transform="rotate(' + h + ',16,16)">' +
                    '<ellipse cx="16" cy="16" rx="2.5" ry="10" fill="#1a6fc4"/>' +
                    '<polygon points="16,14 3,20 3,22 16,18 29,22 29,20" fill="#1a6fc4"/>' +
                    '<polygon points="16,24 10,29 10,30 16,27 22,30 22,29" fill="#1a6fc4"/>' +
                    '<ellipse cx="16" cy="10" rx="1.5" ry="3" fill="rgba(255,255,255,0.35)"/>' +
                    '</g></svg>';
                return L.divIcon({
                    html: '<img src="data:image/svg+xml;base64,' + btoa(svg) + '" width="22" height="22" style="display:block">',
                    className: '',
                    iconSize:   [22, 22],
                    iconAnchor: [11, 11],
                });
            }

            // ── Airport-Icon: VATSIM-Radar-Stil, kompakt ──
            // ICAO oben, darunter eine Reihe gefärbter Punkte (ein Punkt pro Facility-Typ)
            // Anzahl-Superskript wenn mehrere gleiche Stationen online
            function buildAirportCtrlIcon(icao, ctrlList, atisList) {
                var TYPES = {
                    2: { short:'D', color:'#3498db' }, // DEL
                    3: { short:'G', color:'#e67e22' }, // GND
                    4: { short:'T', color:'#e74c3c' }, // TWR
                    5: { short:'A', color:'#27ae60' }, // APP
                };
                var order = [2, 3, 4, 5];

                // Zähle pro Typ
                var counts = {};
                ctrlList.forEach(function(c) {
                    if (TYPES[c.facility]) counts[c.facility] = (counts[c.facility]||0) + 1;
                });

                var ac = atisList ? atisList.length : 0;
                // APP + ATIS zusammenfassen: wenn APP vorhanden, ATIS als "+" im APP-Badge zeigen
                var hasApp  = !!(counts[5]);
                var appCount = counts[5] || 0;

                var dots = order.filter(function(f) {
                    return f !== 5 && counts[f]; // APP wird separat behandelt
                }).map(function(f) {
                    var t = TYPES[f], n = counts[f];
                    return '<span style="position:relative;display:inline-flex;align-items:center;' +
                        'justify-content:center;width:14px;height:14px;border-radius:3px;' +
                        'background:' + t.color + ';color:#fff;font-size:8px;font-weight:800;' +
                        'box-shadow:0 1px 2px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5)">' +
                        t.short +
                        (n > 1 ? '<span style="position:absolute;top:-4px;right:-4px;background:#c0392b;' +
                            'color:#fff;border-radius:50%;width:9px;height:9px;font-size:6px;' +
                            'display:flex;align-items:center;justify-content:center;' +
                            'border:1px solid #fff;line-height:1;font-weight:900">' + n + '</span>' : '') +
                        '</span>';
                }).join('');

                // APP+ATIS: großer oranger Badge, "i" wenn ATIS vorhanden
                if (hasApp || ac > 0) {
                    var hasAtis = ac > 0;
                    // Badge-Text: "A" wenn nur APP, "Ai" wenn APP+ATIS, "i" wenn nur ATIS
                    var badgeText, badgeBg, badgeW2, badgeH2, badgeRadius;
                    if (hasApp && hasAtis) {
                        badgeText   = 'A<span style="font-style:italic;font-size:9px;opacity:0.9">i</span>';
                        badgeBg     = '#27ae60';
                        badgeW2     = 22; badgeH2 = 18; badgeRadius = '4px';
                    } else if (hasApp) {
                        badgeText   = 'A';
                        badgeBg     = '#27ae60';
                        badgeW2     = 18; badgeH2 = 18; badgeRadius = '4px';
                    } else {
                        badgeText   = '<span style="font-style:italic">i</span>';
                        badgeBg     = '#5dade2';
                        badgeW2     = 18; badgeH2 = 18; badgeRadius = '50%';
                    }
                    var appCountBadge = (appCount > 1)
                        ? '<span style="position:absolute;top:-4px;right:-4px;background:#c0392b;' +
                          'color:#fff;border-radius:50%;width:9px;height:9px;font-size:6px;' +
                          'display:flex;align-items:center;justify-content:center;' +
                          'border:1px solid #fff;line-height:1;font-weight:900">' + appCount + '</span>'
                        : '';
                    dots += '<span style="position:relative;display:inline-flex;align-items:center;' +
                        'justify-content:center;width:' + badgeW2 + 'px;height:' + badgeH2 + 'px;' +
                        'border-radius:' + badgeRadius + ';background:' + badgeBg + ';color:#fff;' +
                        'font-size:9px;font-weight:900;letter-spacing:0;' +
                        'box-shadow:0 1px 4px rgba(0,0,0,0.5);border:1.5px solid rgba(255,255,255,0.6)">' +
                        badgeText + appCountBadge + '</span>';
                }

                var badgeW = (Object.keys(counts).length + 1) * 18;
                var labelW = icao.length * 7 + 8;
                var w      = Math.max(badgeW, labelW, 30) + 16;
                // Klickbereich nach unten vergrößern damit Badges gut treffbar sind
                var h = 36;

                return L.divIcon({
                    html: '<div style="width:' + w + 'px;height:' + h + 'px;display:flex;flex-direction:column;' +
                        'align-items:center;justify-content:center;gap:2px;cursor:pointer">' +
                        '<span style="font-size:9px;font-weight:700;color:#1a1a1a;' +
                        'text-shadow:0 0 3px #fff,0 0 3px #fff;letter-spacing:.3px;line-height:1">' +
                        icao + '</span>' +
                        '<div style="display:flex;gap:2px;align-items:center">' +
                        dots + '</div>' +
                        '</div>',
                    className: 'vatsim-airport-marker',
                    iconSize:   [w, h],
                    iconAnchor: [w / 2, h / 2],
                });
            }

            // Popup-Zeile
            function vRow(label, value) {
                return '<div class="vatsim-popup-row">' +
                    '<span class="label">' + label + '</span>' +
                    '<span class="value">' + value + '</span></div>';
            }

            // Pilot-Popup: Card-Stil wie die Fluginfo-Card, mit aktuellem Logo
            function buildPilotPopup(p) {
                var fp  = p.flight_plan || {};
                var dep = fp.departure || '—';
                var arr = fp.arrival   || '—';
                return '<div class="vatsim-popup">' +
                    '<div class="vatsim-popup-header">' +
                        buildLogoHtml(p.callsign) +
                        '<div class="vatsim-popup-callsign">' + p.callsign + '</div>' +
                        '<div class="vatsim-popup-route">' + dep + ' &rsaquo; ' + arr + '</div>' +
                    '</div>' +
                    '<div class="vatsim-popup-body">' +
                        vRow('Aircraft', fp.aircraft_short || fp.aircraft_faa || '—') +
                        vRow('Altitude', p.altitude    ? p.altitude.toLocaleString() + ' ft' : '—') +
                        vRow('Speed',    p.groundspeed ? p.groundspeed + ' kts' : '—') +
                        vRow('Heading',  p.heading != null ? p.heading + '°' : '—') +
                        vRow('Pilot',    p.name || '—') +
                    '</div>' +
                '</div>';
            }

            // Airport-Popup: Controller + ATIS im Card-Stil
            function buildAirportCtrlPopup(icao, ctrlList, atisList) {
                var order = {2:1, 3:2, 4:3, 5:4};
                ctrlList = ctrlList.slice().sort(function(a,b){
                    return (order[a.facility]||9) - (order[b.facility]||9);
                });

                var BADGE = {
                    2: { label:'DEL', color:'#2980b9' },
                    3: { label:'GND', color:'#d35400' },
                    4: { label:'TWR', color:'#c0392b' },
                    5: { label:'APP', color:'#27ae60' },
                };

                var ctrlRows = ctrlList.map(function(c) {
                    var t = BADGE[c.facility] || { label:'ATC', color:'#7f8c8d' };
                    return '<div style="padding:7px 0;border-bottom:1px solid #f0f0f0">' +
                        '<div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">' +
                            '<span style="background:' + t.color + ';color:#fff;padding:3px 8px;border-radius:4px;' +
                                'font-size:10px;font-weight:700;letter-spacing:.5px;flex-shrink:0">' + t.label + '</span>' +
                            '<span style="font-size:13px;font-weight:700;color:#1a1a1a">' + c.callsign + '</span>' +
                            '<span style="font-size:12px;color:#888;margin-left:auto">' + (c.frequency || '') + '</span>' +
                        '</div>' +
                        ctrlInfoLine(c) +
                        '</div>';
                }).join('');

                // ATIS: kompakt mit Toggle
                var atisRows = '';
                var atisId = 'atis_' + icao.replace(/\W/g,'') + '_' + Date.now();
                if (atisList && atisList.length) {
                    var atisBlocks = atisList.map(function(a) {
                        var lines = Array.isArray(a.text_atis) ? a.text_atis : [];
                        var fullText = lines.join(' ');
                        var preview  = fullText.length > 60 ? fullText.substring(0, 60) + '…' : fullText;
                        var hasMore  = fullText.length > 60;
                        return '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0">' +
                            '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">' +
                                '<span style="background:#5dade2;color:#fff;padding:2px 7px;border-radius:3px;' +
                                    'font-size:10px;font-weight:700;flex-shrink:0">ATIS</span>' +
                                '<span style="font-size:12px;font-weight:700;color:#1a1a1a">' + a.callsign + '</span>' +
                                '<span style="font-size:12px;color:#888;margin-left:auto">' + (a.frequency||'—') + '</span>' +
                            '</div>' +
                            (fullText ? (
                                '<div style="font-size:10px;color:#555;line-height:1.5;background:#f8faff;' +
                                'padding:5px 8px;border-radius:4px;word-break:break-word">' +
                                    '<span class="atis-preview-' + atisId + '">' + preview + '</span>' +
                                    '<span class="atis-full-' + atisId + '" style="display:none">' + fullText + '</span>' +
                                    (hasMore ?
                                        '<br><span onclick="' +
                                        'var p=this.parentElement;' +
                                        'var prev=p.querySelector(\'.atis-preview-' + atisId + '\');' +
                                        'var full=p.querySelector(\'.atis-full-' + atisId + '\');' +
                                        'if(full.style.display===\'none\'){prev.style.display=\'none\';full.style.display=\'\';this.textContent=\'▲ Hide ATIS\';}' +
                                        'else{prev.style.display=\'\';full.style.display=\'none\';this.textContent=\'▼ Show full ATIS\';}" ' +
                                        'style="color:#3498db;cursor:pointer;font-size:10px;font-weight:600">▼ Show full ATIS</span>'
                                    : '') +
                                '</div>'
                            ) : '') +
                        '</div>';
                    }).join('');

                    atisRows = '<div style="margin-top:4px;padding-top:8px;border-top:2px dashed #d6eaf8">' +
                        atisBlocks + '</div>';
                }

                var total = ctrlList.length + (atisList ? atisList.length : 0);
                var airportFullName = airportNameCache[icao]
                    || airportNameCache['K' + icao]
                    || airportNameCache[icao.replace(/^K/, '')]
                    || '';
                return '<div class="vatsim-popup">' +
                    '<div class="vatsim-popup-header">' +
                        '<div class="vatsim-popup-callsign">' + icao + '</div>' +
                        (airportFullName ? '<div class="vatsim-popup-route">' + airportFullName + '</div>' : '') +
                        '<div style="font-size:11px;color:#aaa;margin-top:2px">' + total + ' station' + (total !== 1 ? 's' : '') + ' active</div>' +
                    '</div>' +
                    '<div class="vatsim-popup-body">' + ctrlRows + atisRows + '</div>' +
                    '</div>';
            }

            // ── VATSpy.dat laden: FIR-Namen + Airport-Positionen ──
            // [Airports] Format: ICAO|Name|Lat|Lon|IATA|IsPseudo
            // [FIRs]     Format: ICAO|Name|CallsignPrefix|IsOceanic
            //   Beispiel US: KZNY|New York ARTCC|ZNY|0
            //   Beispiel EU: EDWW|Bremen Radar|EDWW|0
            // firPrefixMap: callsignPrefix → ICAO  (z.B. "ZNY" → "KZNY", "EDWW" → "EDWW")
            var firPrefixMap = {}; // callsign prefix → FIR ICAO

            function loadFirNames() {
                if (firNameLoaded) return Promise.resolve();
                return fetch(VATSPY_DAT_API)
                    .then(function(r) { return r.text(); })
                    .then(function(text) {
                        firNameLoaded = true;
                        staticAirportLoaded = true;
                        var section = '';
                        text.split('\n').forEach(function(line) {
                            line = line.trim();
                            if (!line || line.startsWith(';')) return;
                            if (line.startsWith('[')) {
                                section = line.replace(/[\[\]]/g,'').toLowerCase();
                                return;
                            }
                            var parts = line.split('|');
                            if (section === 'airports' && parts.length >= 4) {
                                var icao = parts[0].trim().toUpperCase();
                                var aname = parts[1].trim();
                                var lat  = parseFloat(parts[2]);
                                var lon  = parseFloat(parts[3]);
                                if (icao && !isNaN(lat) && !isNaN(lon)) {
                                    staticAirportPos[icao] = [lat, lon];
                                    if (aname) airportNameCache[icao] = aname;
                                }
                            } else if (section === 'firs' && parts.length >= 2) {
                                var ficao  = parts[0].trim().toUpperCase(); // z.B. KZNY
                                var name   = parts[1].trim();               // z.B. New York ARTCC
                                var prefix = (parts[2] || '').trim().toUpperCase(); // z.B. ZNY
                                if (ficao && name) {
                                    firNameCache[ficao]  = name; // KZNY → name
                                    if (prefix) {
                                        firNameCache[prefix] = name; // ZNY → name (direkter Zugriff)
                                        firPrefixMap[prefix] = ficao; // ZNY → KZNY
                                        firPrefixMap[ficao]  = ficao; // KZNY → KZNY (Selbst-Map)
                                    }
                                }
                            }
                        });
                        console.log('[VATSIM] VATSpy: ' + Object.keys(staticAirportPos).length +
                            ' Airports, ' + Object.keys(firPrefixMap).length + ' FIR-Prefixes');
                    })
                    .catch(function(e) {
                        firNameLoaded = true;
                        staticAirportLoaded = true;
                        console.warn('[VATSIM] VATSpy.dat nicht geladen:', e);
                    });
            }

            // ── Controller-Hilfsfunktionen ──
            var CTRL_RATINGS = {
                1:'OBS', 2:'S1', 3:'S2', 4:'S3', 5:'C1', 6:'C2', 7:'C3',
                8:'I1', 9:'I2', 10:'I3', 11:'SUP', 12:'ADM'
            };
            function ctrlRatingBadge(rating) {
                if (!rating) return '';
                var r = CTRL_RATINGS[rating] || ('R'+rating);
                var rColor = rating >= 11 ? '#8e44ad' : rating >= 8 ? '#c0392b'
                           : rating >= 5  ? '#27ae60' : rating >= 2 ? '#2980b9' : '#95a5a6';
                return '<span style="background:' + rColor + ';color:#fff;padding:1px 5px;' +
                    'border-radius:3px;font-size:9px;font-weight:700;letter-spacing:.3px">' + r + '</span>';
            }
            function ctrlOnlineTime(logonTime) {
                if (!logonTime) return '';
                try {
                    var diff = Math.floor((Date.now() - new Date(logonTime).getTime()) / 60000);
                    var h = Math.floor(diff / 60), m = diff % 60;
                    return h > 0 ? h + 'h ' + m + 'min' : m + 'min';
                } catch(e) { return ''; }
            }
            // Kompakte Controller-Info-Zeile: Name (#CID) · Rating · Zeit
            function ctrlInfoLine(c) {
                var parts = [];
                if (c.name) parts.push('<span style="font-weight:600">' + c.name + '</span>');
                if (c.cid && !c.name) parts.push('#' + c.cid);
                else if (c.cid) parts.push('<span style="color:#bbb">#' + c.cid + '</span>');
                var rating = ctrlRatingBadge(c.rating);
                var time   = ctrlOnlineTime(c.logon_time);
                var meta = [rating, time ? '⏱ ' + time : ''].filter(Boolean).join('  ');
                return '<div style="font-size:11px;color:#666;margin-top:1px;display:flex;' +
                    'align-items:center;gap:5px;flex-wrap:wrap">' +
                    (parts.length ? parts.join(' ') : '') +
                    (meta ? '<span style="margin-left:auto;display:flex;gap:4px;align-items:center">' +
                        meta + '</span>' : '') + '</div>';
            }
            function polyCenter(feature) {
                try {
                    var bestRing = null, bestArea = 0;
                    var geom = feature.geometry;
                    var polys = geom.type === 'Polygon'
                        ? [geom.coordinates]
                        : geom.coordinates; // MultiPolygon
                    polys.forEach(function(poly) {
                        var ring = poly[0];
                        var a = 0;
                        for (var i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                            a += (ring[j][0] + ring[i][0]) * (ring[j][1] - ring[i][1]);
                        }
                        a = Math.abs(a) / 2;
                        if (a > bestArea) { bestArea = a; bestRing = ring; }
                    });
                    if (!bestRing) return null;
                    var minLat=90, maxLat=-90, minLon=180, maxLon=-180;
                    bestRing.forEach(function(c) {
                        if (c[1] < minLat) minLat = c[1];
                        if (c[1] > maxLat) maxLat = c[1];
                        if (c[0] < minLon) minLon = c[0];
                        if (c[0] > maxLon) maxLon = c[0];
                    });
                    return [(minLat + maxLat) / 2, (minLon + maxLon) / 2];
                } catch(e) { return null; }
            }

            // ── Polygon-Fläche berechnen ──
            function polyArea(feature) {
                try {
                    var geom = feature.geometry;
                    var rings = [];
                    if (geom.type === 'Polygon') rings = [geom.coordinates[0]];
                    else if (geom.type === 'MultiPolygon') {
                        geom.coordinates.forEach(function(p) { rings.push(p[0]); });
                    }
                    var maxArea = 0;
                    rings.forEach(function(ring) {
                        var area = 0;
                        for (var i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                            area += (ring[j][0] + ring[i][0]) * (ring[j][1] - ring[i][1]);
                        }
                        area = Math.abs(area) / 2;
                        if (area > maxArea) maxArea = area;
                    });
                    return maxArea;
                } catch(e) { return 0; }
            }

            // ── FIR-Sektorgrenzen rendern ──
            function renderActiveSectors(activeFirMap) {
                vatsimSectorLayer.clearLayers();
                if (!firBoundsGeoJson || !firBoundsGeoJson.features) return;

                // Features pro CTR sammeln
                // Matching-Strategie mit firPrefixMap:
                //   ctrId="ZNY" → firPrefixMap["ZNY"]="KZNY" → GeoJSON feature.properties.id="KZNY" ✓
                //   ctrId="EDWW" → firPrefixMap["EDWW"]="EDWW" → GeoJSON id="EDWW" ✓
                var ctrlFeatureMap = {};
                firBoundsGeoJson.features.forEach(function(feature) {
                    var props = feature.properties || {};
                    // GeoJSON Feature id kann top-level ODER in properties sein
                    var firId = (feature.id || props.id || props.oceanic_prefix || '').toString().toUpperCase();
                    if (!firId) return;

                    var matchKey = null;
                    Object.keys(activeFirMap).forEach(function(ctrId) {
                        if (matchKey) return;
                        // Via firPrefixMap: ZNY → KZNY → match mit "KZNY"
                        var resolvedId = firPrefixMap[ctrId] || ctrId;
                        if (resolvedId === firId) { matchKey = ctrId; return; }
                        // Direkte Varianten
                        if (ctrId === firId) { matchKey = ctrId; return; }
                        if ('K' + ctrId === firId) { matchKey = ctrId; return; }
                        if (ctrId === 'K' + firId) { matchKey = ctrId; return; }
                        // Sub-Sektor: firId beginnt mit resolvedId (z.B. EDGG_S beginnt mit EDGG)
                        if (firId.indexOf(resolvedId) === 0 && resolvedId.length >= 3) {
                            matchKey = ctrId;
                        }
                    });
                    if (!matchKey) return;
                    if (!ctrlFeatureMap[matchKey]) ctrlFeatureMap[matchKey] = [];
                    ctrlFeatureMap[matchKey].push(feature);
                });

                Object.keys(ctrlFeatureMap).forEach(function(matchKey) {
                    var features = ctrlFeatureMap[matchKey];
                    var info     = activeFirMap[matchKey];
                    var color    = info.color || '#1abc9c';
                    var short    = info.callsign.split('_')[0];

                    // Echter FIR-Name aus VATSpy.dat, sonst Callsign
                    var firName  = firNameCache[short] || info.callsign;

                    // Sub-Sektor-Liste für Popup bauen
                    var subList = features.map(function(f) {
                        var id = ((f.properties || {}).id || '').toUpperCase();
                        return id || short;
                    });
                    // Duplikate entfernen, sortieren
                    subList = subList.filter(function(v,i,a){ return a.indexOf(v)===i; }).sort();

                    var subRows = '';
                    if (subList.length > 1) {
                        subRows = '<div style="margin-top:8px;padding-top:6px;border-top:1px solid #eee">' +
                            '<div style="font-size:10px;font-weight:700;color:#888;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px">' +
                            'Teilsektoren</div>' +
                            '<div style="display:flex;flex-wrap:wrap;gap:3px">' +
                            subList.map(function(sid) {
                                return '<span style="background:#f0f0f0;color:#333;padding:1px 6px;' +
                                    'border-radius:3px;font-size:10px;font-weight:600">' + sid + '</span>';
                            }).join('') +
                            '</div></div>';
                    }

                    var popupContent =
                        '<div class="vatsim-popup">' +
                        '<div class="vatsim-popup-header">' +
                            '<div class="vatsim-popup-callsign">' + info.callsign + '</div>' +
                            '<div class="vatsim-popup-route">' + firName + '</div>' +
                        '</div>' +
                        '<div class="vatsim-popup-body">' +
                            vRow('Frequency', info.frequency || '—') +
                            ctrlInfoLine(info) +
                            subRows +
                        '</div></div>';

                    // Alle Flächen zeichnen — jeder Teilsektor klickbar
                    features.forEach(function(feature) {
                        var subId = ((feature.properties || {}).id || '').toUpperCase();
                        var subCenter = polyCenter(feature);
                        try {
                            var layer = L.geoJSON(feature, {
                                style: {
                                    color: color, weight: 1.5, opacity: 0.65,
                                    fillColor: color, fillOpacity: 0.08, dashArray: '5 4',
                                },
                            });
                            // Hover: Sub-Sektor hervorheben
                            layer.on('mouseover', function(e) {
                                e.target.setStyle({ fillOpacity: 0.22, weight: 2, dashArray: '' });
                            });
                            layer.on('mouseout', function(e) {
                                e.target.setStyle({ fillOpacity: 0.08, weight: 1.5, dashArray: '5 4' });
                            });
                            layer.bindPopup(popupContent, { maxWidth: 260 });
                            layer.addTo(vatsimSectorLayer);
                        } catch(e) {}

                        // Kleines Sub-Sektor-Label im Zentroid (nur wenn mehrere Teilsektoren)
                        if (features.length > 1 && subId && subId !== short && subCenter) {
                            var subShort = subId.replace(short, '').replace(/^_/, '') || subId;
                            L.marker(subCenter, {
                                icon: L.divIcon({
                                    html: '<div style="color:' + color + ';font-size:9px;font-weight:700;' +
                                          'text-shadow:0 0 3px #fff,0 0 3px #fff;opacity:0.8;' +
                                          'white-space:nowrap;pointer-events:none">' + subShort + '</div>',
                                    className: '', iconSize: [40, 14], iconAnchor: [20, 7],
                                }),
                                interactive: false, zIndexOffset: 100,
                            }).addTo(vatsimSectorLayer);
                        }
                    });

                    // Haupt-Label am größten Teilsektor
                    var biggest = features.reduce(function(best, f) {
                        return polyArea(f) > polyArea(best) ? f : best;
                    }, features[0]);
                    var center = polyCenter(biggest);
                    if (!center) return;

                    var freqStr  = info.frequency || '';
                    var labelW   = Math.max(short.length * 8 + 16, 64);
                    var labelH   = 36;
                    L.marker(center, {
                        icon: L.divIcon({
                            html: '<div style="background:' + color + ';color:#fff;' +
                                  'padding:3px 9px;border-radius:4px;font-size:10px;font-weight:700;' +
                                  'letter-spacing:.5px;box-shadow:0 2px 5px rgba(0,0,0,0.4);' +
                                  'border:1px solid rgba(255,255,255,0.5);white-space:nowrap;text-align:center">' +
                                  short +
                                  '<br><span style="font-size:9px;font-weight:400;opacity:0.85">' +
                                  (freqStr || firName.split(' ')[0]) + '</span>' +
                                  '</div>',
                            className: '', iconSize: [labelW, labelH], iconAnchor: [labelW/2, labelH/2],
                        }),
                        zIndexOffset: 200, title: info.callsign,
                    })
                    .bindPopup(popupContent, { maxWidth: 260 })
                    .addTo(vatsimSectorLayer);
                });
            }

            // ── Zoom-basierte Sichtbarkeit für Controller-Marker ──
            // Bei Zoom < 6: nur FIR-Sektoren sichtbar, Airport-Marker ausblenden
            // Bei Zoom 6-8: Airport-Marker ohne ICAO-Label (nur Badges)
            // Bei Zoom >= 9: volle Anzeige
            function updateCtrlZoom(map) {
                var z = map.getZoom();
                var markers = document.querySelectorAll('.vatsim-airport-marker');
                markers.forEach(function(el) {
                    var label = el.querySelector('div:first-child'); // ICAO-Label
                    if (z < 3) {
                        el.parentElement.style.display = 'none';
                    } else {
                        el.parentElement.style.display = '';
                        if (label) label.style.display = z >= 5 ? '' : 'none';
                    }
                });
            }
            function loadTransceivers() {
                return fetch(VATSIM_TRX_API)
                    .then(function(r) { return r.json(); })
                    .then(function(trxList) {
                        ctrlPosCache = {};
                        (trxList || []).forEach(function(entry) {
                            // entry = { callsign: "EDDF_TWR", transceivers: [{latDeg, lonDeg, ...}] }
                            if (!entry.callsign || !entry.transceivers || !entry.transceivers.length) return;
                            var trx = entry.transceivers[0];
                            var lat = parseFloat(trx.latDeg);
                            var lon = parseFloat(trx.lonDeg);
                            if (!isNaN(lat) && !isNaN(lon) && (Math.abs(lat) > 0.001 || Math.abs(lon) > 0.001)) {
                                ctrlPosCache[entry.callsign.toUpperCase()] = [lat, lon];
                            }
                        });
                        console.log('[VATSIM] Transceivers geladen: ' + Object.keys(ctrlPosCache).length + ' Einträge');
                    })
                    .catch(function(err) {
                        console.warn('[VATSIM] Transceivers nicht geladen:', err);
                    });
            }

            // ── Haupt-Ladefunktion: alle APIs parallel ──
            function loadVatsim(map) {
                Promise.all([
                    fetch(VATSIM_DATA_API).then(function(r) { return r.json(); }),
                    loadTransceivers(),
                    loadFirNames()   // lädt staticAirportPos + firNameCache (gecacht nach 1. Aufruf)
                ])
                .then(function(results) {
                    var data = results[0];

                    vatsimPilotsLayer.clearLayers();
                    vatsimCtrlLayer.clearLayers();

                    var pilots   = data.pilots || [];

                    // ── Piloten ──
                    pilots.forEach(function(p) {
                        if (p.latitude == null || p.longitude == null) return;
                        var fp = p.flight_plan || {};
                        var marker = L.marker([p.latitude, p.longitude], {
                            icon:  buildAircraftIcon(p.heading),
                            title: p.callsign,
                        })
                        .bindPopup(buildPilotPopup(p), { maxWidth: 280 });

                        // Klick → gestrichelte Linie zum Ziel
                        if (fp.arrival) {
                            marker.on('click', function() {
                                showRouteLine(map, [p.latitude, p.longitude], fp.arrival.toUpperCase());
                            });
                        }
                        marker.addTo(vatsimPilotsLayer);
                    });

                    // ── Controller aufteilen: ATIS / Airport / Center ──
                    var controllers = data.controllers || [];
                    var atisRaw     = data.atis || [];
                    var ctrlDone    = 0;

                    // ── Key-Normalisierung: EWR und KEWR → immer KEWR (canonical ICAO) ──
                    // Verhindert Duplikat-Marker wenn ATIS "KEWR_ATIS" aber Controller "EWR_TWR"
                    function normalizeKey(prefix) {
                        if (staticAirportPos[prefix]) return prefix;          // direkt bekannt
                        if (staticAirportPos['K' + prefix]) return 'K'+prefix; // US 3→4
                        if (staticAirportPos['C' + prefix]) return 'C'+prefix; // Kanada
                        if (staticAirportPos['P' + prefix]) return 'P'+prefix; // Pazifik
                        // Umgekehrt: KEWR → EWR wenn staticAirportPos keinen K-Prefix hat
                        if (prefix.length === 4 && prefix[0] === 'K' && staticAirportPos[prefix.slice(1)]) {
                            return prefix.slice(1);
                        }
                        return prefix; // unbekannt: unveränderter Fallback
                    }

                    // ATIS: separater Cache ICAO → [atisEntry, ...]
                    var atisGroups = {};
                    atisRaw.forEach(function(a) {
                        var raw    = a.callsign.split('_')[0].toUpperCase();
                        var key    = normalizeKey(raw);
                        var pos    = staticAirportPos[key]
                                  || staticAirportPos[raw]
                                  || null;
                        if (!pos) (data.airports || []).forEach(function(ap) {
                            if (!pos && (ap.icao === raw || ap.icao === key)) {
                                var lt = parseFloat(ap.latitude || ap.lat);
                                var ln = parseFloat(ap.longitude || ap.lon);
                                if (!isNaN(lt) && !isNaN(ln)) pos = [lt, ln];
                            }
                        });
                        if (!pos) pos = ctrlPosCache[a.callsign.toUpperCase()];
                        if (!pos) return;
                        if (!atisGroups[key]) atisGroups[key] = { pos: pos, list: [] };
                        atisGroups[key].list.push(a);
                    });

                    // Controller: CTR/FSS vs. Airport-Facilities
                    var airportGroups = {}; // ICAO → { pos, ctrls[], atis[] }
                    var centerList    = []; // CTR/FSS einzeln

                    controllers.forEach(function(c) {
                        if (c.facility === 0) return;

                        // Position-Hierarchie:
                        // 1. VATSpy.dat statisch (alle ~7000 Airports, 100% korrekt)
                        //    → auch K-Prefix-Variante für US-Airports (DTW → KDTW)
                        // 2. data.airports (VATSIM live)
                        // 3. Transceivers (Fallback)
                        var prefix = c.callsign.split('_')[0].toUpperCase();
                        var pos    = staticAirportPos[prefix]
                                  || staticAirportPos['K' + prefix]   // US: DTW → KDTW
                                  || staticAirportPos['P' + prefix]   // Pazifik: GUM → PGUM
                                  || staticAirportPos['C' + prefix]   // Kanada: YYZ → CYYZ
                                  || null;
                        if (!pos) (data.airports || []).forEach(function(a) {
                            if (!pos && (a.icao === prefix || a.icao === 'K'+prefix)) {
                                var alat = parseFloat(a.latitude || a.lat);
                                var alon = parseFloat(a.longitude || a.lon);
                                if (!isNaN(alat) && !isNaN(alon)) pos = [alat, alon];
                            }
                        });
                        if (!pos) pos = ctrlPosCache[c.callsign.toUpperCase()];
                        if (!pos) return;

                        if (c.facility === 6 || c.facility === 1) {
                            // CTR / FSS → immer als Center
                            centerList.push({ ctrl: c, pos: pos });
                        } else {
                            // Prüfen ob Prefix ein echter Airport ist (in staticAirportPos bekannt)
                            // Wenn nicht → TRACON (z.B. SCT, N90, PCT) → als Center behandeln
                            var raw     = c.callsign.split('_')[0].toUpperCase();
                            var isRealAirport = !!(
                                staticAirportPos[raw] ||
                                staticAirportPos['K'+raw] ||
                                staticAirportPos['C'+raw] ||
                                staticAirportPos['P'+raw]
                            );
                            if (!isRealAirport) {
                                // TRACON: eigener Label wie CTR, aber in orange (APP-Farbe)
                                centerList.push({ ctrl: c, pos: pos, isTracon: true });
                            } else {
                                var key = normalizeKey(raw);
                                if (!airportGroups[key]) airportGroups[key] = { pos: pos, ctrls: [] };
                                airportGroups[key].ctrls.push(c);
                            }
                        }
                    });

                    // Airport-Marker zusammenführen mit ATIS
                    // Auch reine ATIS-only-Airports berücksichtigen
                    var allAirportKeys = {};
                    Object.keys(airportGroups).forEach(function(k){ allAirportKeys[k] = true; });
                    Object.keys(atisGroups).forEach(function(k){ allAirportKeys[k] = true; });

                    // TRACON-Entries: Nähe zu vorhandenen Airports prüfen
                    // Wenn ein TRACON-Marker < 80km von einem Airport-Marker liegt → mergen
                    function distKm(a, b) {
                        var R = 6371;
                        var dLat = (b[0]-a[0]) * Math.PI/180;
                        var dLon = (b[1]-a[1]) * Math.PI/180;
                        var s = Math.sin(dLat/2)*Math.sin(dLat/2) +
                                Math.cos(a[0]*Math.PI/180)*Math.cos(b[0]*Math.PI/180)*
                                Math.sin(dLon/2)*Math.sin(dLon/2);
                        return R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1-s));
                    }

                    var traconMerged = {}; // traconIndex → true wenn eingemergt
                    centerList.forEach(function(entry, idx) {
                        if (!entry.isTracon) return;
                        var bestKey  = null;
                        var bestDist = 80; // max 80km
                        Object.keys(allAirportKeys).forEach(function(k) {
                            var grp = airportGroups[k] || atisGroups[k];
                            if (!grp) return;
                            var d = distKm(entry.pos, grp.pos);
                            if (d < bestDist) { bestDist = d; bestKey = k; }
                        });
                        if (bestKey) {
                            if (!airportGroups[bestKey]) {
                                airportGroups[bestKey] = {
                                    pos: (atisGroups[bestKey] || {}).pos || entry.pos,
                                    ctrls: []
                                };
                                allAirportKeys[bestKey] = true;
                            }
                            // TRACON-Controller als APP (facility 5) in die Airport-Gruppe eintragen
                            airportGroups[bestKey].ctrls.push(entry.ctrl);
                            traconMerged[idx] = true;
                        }
                    });

                    Object.keys(allAirportKeys).forEach(function(icao) {
                        var group    = airportGroups[icao] || { pos: atisGroups[icao].pos, ctrls: [] };
                        var atisList = atisGroups[icao] ? atisGroups[icao].list : [];
                        ctrlDone += group.ctrls.length + atisList.length;

                        L.marker(group.pos, {
                            icon: buildAirportCtrlIcon(icao, group.ctrls, atisList),
                            title: icao,
                            zIndexOffset: 500,
                        })
                        .bindPopup(buildAirportCtrlPopup(icao, group.ctrls, atisList), { maxWidth: 300 })
                        .addTo(vatsimCtrlLayer);
                    });

                    // ── CTR/FSS/TRACON: FIR-Karte mit vollständigen Infos aufbauen ──
                    var activeFirMap = {};
                    centerList.forEach(function(entry, idx) {
                        if (traconMerged[idx]) return; // bereits in Airport-Marker eingemergt
                        var c        = entry.ctrl;
                        var short    = c.callsign.split('_')[0].toUpperCase();
                        // Farb-Schema: CTR=türkis, FSS=lila, TRACON=orange
                        var color    = entry.isTracon ? '#27ae60'
                                     : c.facility === 6 ? '#1abc9c'
                                     : '#8e44ad';
                        ctrlDone++;
                        // Beim TRACON-Matching: kein FIR-Sektor erwartet → kein activeFirMap-Eintrag
                        // Stattdessen: direkt als Marker auf den vatsimCtrlLayer
                        if (entry.isTracon) {
                            var traconIcon = L.divIcon({
                                html: '<div style="display:flex;flex-direction:column;align-items:center;' +
                                      'white-space:nowrap;pointer-events:auto">' +
                                      '<div style="background:' + color + ';color:#fff;' +
                                      'padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;' +
                                      'letter-spacing:.5px;box-shadow:0 1px 4px rgba(0,0,0,0.4);' +
                                      'border:1px solid rgba(255,255,255,0.5);line-height:1.4">' +
                                      short + '</div>' +
                                      '<div style="width:4px;height:4px;border-radius:50%;' +
                                      'background:' + color + ';margin-top:2px"></div>' +
                                      '</div>',
                                className: '',
                                iconSize:   [short.length * 8 + 16, 26],
                                iconAnchor: [(short.length * 8 + 16) / 2, 13],
                            });
                            var traconPopup =
                                '<div class="vatsim-popup">' +
                                '<div class="vatsim-popup-header">' +
                                    '<div class="vatsim-popup-callsign">' + c.callsign + '</div>' +
                                    '<div class="vatsim-popup-route">TRACON / Approach Control</div>' +
                                '</div>' +
                                '<div class="vatsim-popup-body">' +
                                    vRow('Frequency', c.frequency || '—') +
                                    ctrlInfoLine(c) +
                                '</div></div>';
                            L.marker(entry.pos, {
                                icon: traconIcon, title: c.callsign, zIndexOffset: 400,
                            })
                            .bindPopup(traconPopup, { maxWidth: 260 })
                            .addTo(vatsimCtrlLayer);
                        } else {
                            activeFirMap[short] = {
                                callsign:     c.callsign,
                                frequency:    c.frequency,
                                name:         c.name,
                                cid:          c.cid,
                                rating:       c.rating,
                                logon_time:   c.logon_time,
                                visual_range: c.visual_range,
                                color:        color,
                            };
                        }
                    });

                    // ── FIR-Sektorgrenzen anzeigen ──
                    vatsimSectorLayer.clearLayers();
                    var renderSectors = function() {
                        if (firBoundsGeoJson) renderActiveSectors(activeFirMap);
                    };

                    var boundsPromise = firBoundsGeoJson
                        ? Promise.resolve()
                        : fetch(VATSPY_BOUNDS_API).then(function(r){ return r.json(); }).then(function(gj){ firBoundsGeoJson = gj; });

                    Promise.all([boundsPromise, loadFirNames()])
                        .then(renderSectors)
                        .catch(function(e){ console.warn('[VATSIM] Sektoren-Fehler:', e); });

                    // Layer nur anzeigen wenn Toggle aktiv
                    if (vatsimShowPilots && !map.hasLayer(vatsimPilotsLayer)) vatsimPilotsLayer.addTo(map);
                    if (vatsimShowCtrl   && !map.hasLayer(vatsimCtrlLayer))   vatsimCtrlLayer.addTo(map);
                    if (vatsimShowSectors && !map.hasLayer(vatsimSectorLayer)) vatsimSectorLayer.addTo(map);

                    // Stats (kein "klick zum aktivieren" mehr)
                    var statsEl = document.getElementById('vatsimStats');
                    var dotEl   = document.getElementById('vatsimDot');
                    if (statsEl) statsEl.textContent = '✈ ' + pilots.length + '  •  🎧 ' + ctrlDone;
                    if (dotEl)   dotEl.classList.add('live');

                    console.log('[VATSIM] ' + pilots.length + ' Piloten, ' + ctrlDone + ' Controller positioniert');
                })
                .catch(function(err) {
                    console.error('[VATSIM] Fehler:', err);
                    var statsEl = document.getElementById('vatsimStats');
                    if (statsEl) statsEl.textContent = '⚠ Nicht erreichbar';
                });
            }

            // ── Beide Init-Hooks registrieren VOR render_live_map! ──
            if (typeof L !== 'undefined' && L.Map && typeof L.Map.addInitHook === 'function') {

                // Hook 1: OWM Weather
                console.log('[LiveMap] Registering Leaflet init hook for OWM');
                L.Map.addInitHook(function () {
                    attachWeatherToMap(this);
                });

                // Hook 2: VATSIM + Follow-Flight + VA-Icon
                console.log('[LiveMap] Registering Leaflet init hook for VATSIM');
                L.Map.addInitHook(function () {
                    var map = this;

                    // RouteLineLayer immer auf der Karte (über allem)
                    routeLineLayer.addTo(map);

                    // Daten im Hintergrund laden
                    loadVatsim(map);
                    setInterval(function() { loadVatsim(map); }, VATSIM_REFRESH_MS);

                    // Zoom-basierte Sichtbarkeit
                    map.on('zoomend', function() { updateCtrlZoom(map); });

                    // ── VA-Flugzeug: Klick → Linie zum Ziel ──
                    // phpvms setzt Marker via layeradd; wir hängen click-Handler dran
                    map.on('layeradd', function(eAdd) {
                        var layer = eAdd.layer;
                        if (!layer || !layer.getIcon) return;
                        try {
                            var icon = layer.getIcon();
                            var url = (icon && icon.options && icon.options.iconUrl) || '';
                            if (url.indexOf('aircraft.png') === -1) return;
                            // Ziel aus phpvms Rivets-Daten lesen
                            layer.on('click', function() {
                                var arrIcao = null;
                                // Versuche Ziel aus dem sichtbaren pirep-Binding zu lesen
                                var routeEl = document.querySelector('[rv-text="pirep.arr_airport.icao"]');
                                if (routeEl && routeEl.textContent) arrIcao = routeEl.textContent.trim();
                                if (arrIcao && layer.getLatLng) {
                                    showRouteLine(map, layer.getLatLng(), arrIcao);
                                }
                            });
                        } catch(err) {}
                    });

                    // ── VA-Flugzeug: groß, weiß mit dunklem Rand — unübersehbar ──
                    function makeVaIcon(heading) {
                        var h = heading || 0;
                        var svg =
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="38" height="38">' +
                            '<g transform="rotate(' + h + ',20,20)">' +
                            // Rumpf weiß
                            '<ellipse cx="20" cy="20" rx="3.5" ry="13" fill="#ffffff" stroke="#1a3a6b" stroke-width="1.8"/>' +
                            // Roter Streifen auf dem Rumpf
                            '<rect x="17.5" y="14" width="5" height="12" rx="1.5" fill="#e74c3c" opacity="0.85"/>' +
                            // Tragflächen
                            '<polygon points="20,17 2,25 2,28 20,23 38,28 38,25" fill="#ffffff" stroke="#1a3a6b" stroke-width="1.5"/>' +
                            // Höhenleitwerk
                            '<polygon points="20,31 11,38 11,39.5 20,36 29,39.5 29,38" fill="#ffffff" stroke="#1a3a6b" stroke-width="1.3"/>' +
                            // Cockpit blau
                            '<ellipse cx="20" cy="10" rx="2" ry="3.5" fill="rgba(100,160,255,0.7)"/>' +
                            '</g></svg>';
                        return L.divIcon({
                            html: '<div style="filter:drop-shadow(0 2px 5px rgba(0,0,0,0.8))">' +
                                  '<img src="data:image/svg+xml;base64,' + btoa(svg) + '" width="38" height="38" style="display:block"></div>',
                            className: '', iconSize: [38, 38], iconAnchor: [19, 19],
                        });
                    }
                    map.on('layeradd', function(e) {
                        var layer = e.layer;
                        if (!layer || !layer.getIcon) return;
                        try {
                            var icon = layer.getIcon();
                            var url  = (icon && icon.options && icon.options.iconUrl) || '';
                            if (url.indexOf('aircraft.png') !== -1) {
                                layer.setIcon(makeVaIcon(layer.options.rotationAngle || 0));
                                // Immer über allen anderen Markern
                                layer.setZIndexOffset(10000);
                            }
                        } catch(err) {}
                    });

                    // ── Logo: https erzwingen via MutationObserver auf rv-src ──
                    var logoImg = document.getElementById('map-airline-logo');
                    if (logoImg) {
                        var logoObserver = new MutationObserver(function() {
                            var src = logoImg.getAttribute('src') || '';
                            if (src && src !== logoImg._lastSrc) {
                                logoImg._lastSrc = src;
                                // http → https erzwingen
                                logoImg.src = src.replace(/^http:\/\//, 'https://');
                            }
                        });
                        logoObserver.observe(logoImg, { attributes: true, attributeFilter: ['src'] });
                    }

                    // ── Follow-Flight Toggle ──
                    var followEnabled = true;

                    // phpvms ruft intern panTo / setView auf → intercepten
                    var _origPanTo   = map.panTo.bind(map);
                    var _origSetView = map.setView.bind(map);
                    var _origFlyTo   = map.flyTo ? map.flyTo.bind(map) : null;

                    map.panTo = function(latlng, options) {
                        if (!followEnabled) return map;
                        return _origPanTo(latlng, options);
                    };
                    map.setView = function(center, zoom, options) {
                        if (!followEnabled && map._loaded) {
                            // Zoom-Änderung immer erlauben, nur Center-Follow blockieren
                            var currentZoom = map.getZoom();
                            if (zoom !== undefined && zoom !== currentZoom) {
                                // Nur zoomen, nicht panen
                                return _origSetView(map.getCenter(), zoom, options);
                            }
                            return map;
                        }
                        return _origSetView(center, zoom, options);
                    };
                    if (_origFlyTo) {
                        map.flyTo = function(latlng, zoom, options) {
                            if (!followEnabled) return map;
                            return _origFlyTo(latlng, zoom, options);
                        };
                    }

                    // Toggle-Buttons
                    var btnPilots  = document.getElementById('btnVatsimPilots');
                    var btnCtrl    = document.getElementById('btnVatsimCtrl');
                    var btnSectors = document.getElementById('btnVatsimSectors');
                    var btnFollow  = document.getElementById('btnFollowFlight');

                    if (btnPilots) {
                        btnPilots.addEventListener('click', function() {
                            vatsimShowPilots = !vatsimShowPilots;
                            if (vatsimShowPilots) { vatsimPilotsLayer.addTo(map); btnPilots.classList.add('active'); }
                            else                  { map.removeLayer(vatsimPilotsLayer); btnPilots.classList.remove('active'); }
                        });
                    }
                    if (btnCtrl) {
                        btnCtrl.addEventListener('click', function() {
                            vatsimShowCtrl = !vatsimShowCtrl;
                            if (vatsimShowCtrl) { vatsimCtrlLayer.addTo(map); btnCtrl.classList.add('active'); }
                            else                { map.removeLayer(vatsimCtrlLayer); btnCtrl.classList.remove('active'); }
                        });
                    }
                    if (btnSectors) {
                        btnSectors.addEventListener('click', function() {
                            vatsimShowSectors = !vatsimShowSectors;
                            if (vatsimShowSectors) { vatsimSectorLayer.addTo(map); btnSectors.classList.add('active'); }
                            else                   { map.removeLayer(vatsimSectorLayer); btnSectors.classList.remove('active'); }
                        });
                    }
                    if (btnFollow) {
                        btnFollow.addEventListener('click', function() {
                            followEnabled = !followEnabled;
                            btnFollow.classList.toggle('active', followEnabled);
                            var span = btnFollow.querySelector('span');
                            var icon = btnFollow.querySelector('i');
                            if (span) span.textContent = followEnabled ? 'Follow Flight' : 'Free Scroll';
                            if (icon) icon.className   = followEnabled ? 'fas fa-crosshairs' : 'fas fa-lock-open';
                        });
                    }
                });

            } else {
                console.error('[LiveMap] Leaflet nicht geladen, Hooks konnten nicht registriert werden');
            }

            // ── render_live_map erstellt die Leaflet-Map → feuert alle Hooks ──
            if (!window.phpvms || !phpvms.map || typeof phpvms.map.render_live_map !== 'function') {
                console.error('[LiveMap] phpvms.map helper not available; cannot init live map');
                return;
            }

            console.log('[LiveMap] Calling phpvms.map.render_live_map');

            phpvms.map.render_live_map({
                center: ['{{ $center[0] }}', '{{ $center[1] }}'],
                zoom: '{{ $zoom }}',
                aircraft_icon: '{!! public_asset('/assets/img/acars/aircraft.png') !!}',
                refresh_interval: {{ setting('acars.update_interval', 60) }},
                units: '{{ setting('units.distance ') }}',
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
