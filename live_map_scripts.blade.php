@section('scripts')
    @parent
    <!-- lm3:skripte-anfang -->

    <script>
        // Defensive: force any L.TileLayer URL — including ones created by other
        // scripts on the page (legacy live_map.js, themes, core phpVMS map) — to
        // HTTPS. Without this, mixed-content warnings pile up into the hundreds
        // whenever another module ships an older Leaflet tile layer with http://.
        (function lmUpgradeTileUrlsToHttps() {
            if (!window.L || !L.TileLayer || L.TileLayer.prototype._lmHttpsUpgradePatched) return;
            var origInit = L.TileLayer.prototype.initialize;
            L.TileLayer.prototype.initialize = function (url, options) {
                if (typeof url === 'string' && url.indexOf('http://') === 0) {
                    url = 'https://' + url.substring(7);
                }
                return origInit.call(this, url, options);
            };
            var origSetUrl = L.TileLayer.prototype.setUrl;
            L.TileLayer.prototype.setUrl = function (url, noRedraw) {
                if (typeof url === 'string' && url.indexOf('http://') === 0) {
                    url = 'https://' + url.substring(7);
                }
                return origSetUrl.call(this, url, noRedraw);
            };
            L.TileLayer.prototype._lmHttpsUpgradePatched = true;
        })();

        {{-- Den CARTO-Schluessel an JEDE Kachel haengen, auch an die, die
             dieses Modul gar nicht baut.

             # Der Befund (ProAvia, 27.08.2026)

             „I still see the api key required message on page load. If I
             open the map type box, select another map style and then
             select Carto Light it loads fine."

             Genau das Muster: Die Ebenen, die DIESES Modul erzeugt, tragen
             den Schluessel (siehe `mitCartoKey` weiter unten) — deshalb ist
             die Karte nach dem Umschalten sauber. Beim Laden liegt aber eine
             ANDERE Ebene oben. Sie stammt aus dem phpVMS-Kern: Die Seite
             reicht `providers: {'CartoDB.Positron': {}}` an
             `render_live_map` durch, und der Kern baut daraus selbst eine
             Kachelebene:

                 for (r in i.providers)
                     n.tileLayer.provider(r, i.providers[r]).addTo(o)

             Deren Adresse kommt aus dem Katalog von `leaflet-providers`, und
             der hat fuer CartoDB **keinen Platz fuer einen Schluessel**. An
             dieser Ebene kommt kein Modul-Code vorbei.

             # Warum hier

             Dieselbe Ueberlegung wie beim HTTPS-Aufzug direkt darueber: die
             eine Stelle abfangen, durch die jede Kachel geht. Damit ist
             gleichgueltig, wer die Ebene baut — Kern, Theme, dieses Modul
             oder eine, die spaeter dazukommt.

             `getTileUrl` und nicht `initialize`: Manche Ebenen bekommen ihre
             Adresse erst spaeter (`setUrl`), und der Katalog setzt sie aus
             Bausteinen zusammen. Beim Abruf steht sie fest.

             ⚠ Das laeuft VOR `DOMContentLoaded`. Ein Skript weiter unten
             kaeme zu spaet: Die ersten Kacheln waeren schon angefordert und
             trugen das Wasserzeichen, bis der Nutzer schwenkt. --}}
        @php
            // Bewusst hier selbst gelesen statt aus $liveMapUiConfig: Diese
            // Datei soll nicht davon abhaengen, dass das andere Widget
            // vorher gerendert wurde.
            $lmCartoKeyFuerPatch = trim((string) (setting('acars.carto_api_key', env('CARTO_API_KEY', '')) ?? ''));
        @endphp
        @if($lmCartoKeyFuerPatch !== '')
        (function lmCartoKeyAnJedeKachel() {
            var SCHLUESSEL = @json($lmCartoKeyFuerPatch);

            function patch(L) {
                if (!L || !L.TileLayer || !L.TileLayer.prototype) return false;
                if (L.TileLayer.prototype._lmCartoKeyPatched) return true;
                var alt = L.TileLayer.prototype.getTileUrl;
                if (typeof alt !== 'function') return false;
                L.TileLayer.prototype.getTileUrl = function (coords) {
                    var url = alt.call(this, coords);
                    if (typeof url === 'string' && url.indexOf('cartocdn.com') !== -1 && !/[?&]key=/.test(url)) {
                        url += (url.indexOf('?') === -1 ? '?' : '&') + 'key=' + encodeURIComponent(SCHLUESSEL);
                    }
                    return url;
                };
                L.TileLayer.prototype._lmCartoKeyPatched = true;
                return true;
            }

            // Ist Leaflet schon da, ist es hier vorbei.
            if (patch(window.L)) return;

            // Sonst ein Wachposten auf `window.L`. Themes laden Leaflet
            // unterschiedlich frueh; ein reines `if (!window.L) return;`
            // wuerde bei einem spaeten Theme LAUTLOS nichts tun, und das
            // Wasserzeichen bliebe — ohne dass irgendwo etwas anschlaegt.
            try {
                var wert;
                Object.defineProperty(window, 'L', {
                    configurable: true,
                    get: function () { return wert; },
                    set: function (v) { wert = v; patch(v); }
                });
            } catch (e) { /* Eigenschaft nicht setzbar — die Notnagel unten greifen. */ }
            document.addEventListener('DOMContentLoaded', function () { patch(window.L); });
            window.addEventListener('load', function () { patch(window.L); });
        })();
        @endif
    </script>

    {{-- Reine Hilfsfunktionen der Live-Karte (ohne DOM, ohne Leaflet).
         Zwischen den Markierungen <lm3-rein> steht Code, den die Tests in
         tests/widget.test.mjs herausschneiden und ohne Browser ausfuehren —
         dort also nichts einsetzen, was Blade oder ein Fenster braucht. --}}
    <script>
    /* <lm3-rein> */
    (function (wurzel) {
        'use strict';

        // ── Phasen: Reihenfolge, Farben und Namen wie die Admin-Webapp auf live
        //    (webapp/src/data/phaseColors.ts). Der Test vergleicht beides Zeichen
        //    fuer Zeichen mit einer Abschrift dieser Datei.
        var PHASE_COLOR = {
            PREFLIGHT: '#94a3b8', BOARDING: '#cbd5e1', PUSHBACK: '#14b8a6', TAXI_OUT: '#fbbf24',
            TAKEOFF_ROLL: '#fb923c', TAKEOFF: '#f97316', CLIMB: '#22d3ee', CRUISE: '#10b981',
            LEVEL: '#38bdf8', HOLDING: '#c084fc', DESCENT: '#3b82f6', APPROACH: '#f59e0b',
            FINAL: '#ef4444', LANDING: '#dc2626', TAXI_IN: '#94a3b8', BLOCKS_ON: '#64748b',
            ON_BLOCK: '#64748b', ARRIVED: '#475569', PIREP_SUBMITTED: '#334155'
        };
        var PHASE_LIST = [
            'PREFLIGHT', 'BOARDING', 'PUSHBACK', 'TAXI_OUT', 'TAKEOFF_ROLL', 'TAKEOFF',
            'CLIMB', 'CRUISE', 'HOLDING', 'DESCENT', 'APPROACH', 'FINAL', 'LANDING',
            'TAXI_IN', 'BLOCKS_ON', 'ARRIVED', 'PIREP_SUBMITTED'
        ];
        var PHASE_LABEL = {
            PREFLIGHT: 'Preflight', BOARDING: 'Boarding', PUSHBACK: 'Pushback', TAXI_OUT: 'Taxi Out',
            TAKEOFF_ROLL: 'T/O Roll', TAKEOFF: 'Takeoff', CLIMB: 'Climb', CRUISE: 'Cruise',
            LEVEL: 'Level', HOLDING: 'Holding', DESCENT: 'Descent', APPROACH: 'Approach',
            FINAL: 'Final', LANDING: 'Landing', TAXI_IN: 'Taxi In', BLOCKS_ON: 'Blocks On',
            ON_BLOCK: 'Blocks On', ARRIVED: 'Arrived', PIREP_SUBMITTED: 'Pirep eingereicht'
        };
        var PHASE_FALLBACK_FARBE = '#94a3b8';

        function phaseColor(phase) {
            return Object.prototype.hasOwnProperty.call(PHASE_COLOR, phase || '') ? PHASE_COLOR[phase] : PHASE_FALLBACK_FARBE;
        }
        function phaseLabel(phase) {
            if (!phase) return '—';
            if (Object.prototype.hasOwnProperty.call(PHASE_LABEL, phase)) return PHASE_LABEL[phase];
            return String(phase).replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        }
        // Anzeige-Phase wie `phaseForDisplay`: „Level" nur im Steig-/Sinkflug,
        // wenn die Phasen-Engine ein Level-Segment meldet. Filter und Zoom
        // nehmen weiter die rohe Phase.
        function phaseForDisplay(currentPhase, shadowSegment) {
            var p = String(currentPhase || '').toUpperCase();
            if ((p === 'CLIMB' || p === 'DESCENT') && String(shadowSegment || '').toLowerCase() === 'level') return 'LEVEL';
            return currentPhase == null ? '—' : currentPhase;
        }

        // ── Formate wie in der Webapp ──
        function formatSimLabel(raw) {
            var s = String(raw == null ? '' : raw).trim();
            if (!s) return '';
            var k = s.toUpperCase().replace(/[\s._-]/g, '');
            if (k.indexOf('MSFS2024') !== -1 || k.indexOf('FS2024') !== -1) return 'MSFS 2024';
            if (k.indexOf('MSFS2020') !== -1 || k.indexOf('FS2020') !== -1) return 'MSFS 2020';
            if (k.indexOf('MSFS') !== -1 || k.indexOf('MICROSOFTFLIGHT') !== -1) return 'MSFS';
            if (k.indexOf('XPLANE12') !== -1 || k === 'XP12') return 'X-Plane 12';
            if (k.indexOf('XPLANE11') !== -1 || k === 'XP11') return 'X-Plane 11';
            if (k.indexOf('XPLANE') !== -1 || k.indexOf('XP') === 0) return 'X-Plane';
            if (k.indexOf('PREPAR3D') !== -1 || k === 'P3D') return 'P3D';
            if (k === 'OTHER') return 'Other';
            return s.replace(/_/g, ' ');
        }
        function fmtQnhBoth(hpa) {
            return Math.round(hpa) + ' hPa / ' + (hpa * 0.0295299830714).toFixed(2) + ' inHg';
        }
        function ageString(ts, jetzt) {
            var sec = Math.round(((jetzt == null ? Date.now() : jetzt) - ts) / 1000);
            if (sec < 60) return sec + 's';
            if (sec < 3600) return Math.round(sec / 60) + 'm';
            return Math.round(sec / 3600) + 'h';
        }
        function zahl(n, sprache) {
            if (n == null || !isFinite(n)) return '—';
            return Math.round(n).toLocaleString(sprache === 'de' ? 'de-DE' : 'en-US');
        }
        function vsText(vs) {
            if (vs == null || !isFinite(vs)) return '—';
            var pfeil = vs > 100 ? ' ↑' : vs < -100 ? ' ↓' : ' →';
            return (vs > 0 ? '+' : '') + Math.round(vs) + pfeil;
        }
        function apFlags(pos) {
            if (!pos) return '';
            return [pos.ap_master && 'AP', pos.ap_hdg && 'HDG', pos.ap_alt && 'ALT', pos.ap_nav && 'NAV', pos.ap_app && 'APP']
                .filter(Boolean).join(' ');
        }
        function tonnen(kg, stellen) {
            return kg == null || !isFinite(kg) ? null : (kg / 1000).toFixed(stellen == null ? 1 : stellen);
        }

        // ── Datumsgrenze ──
        // Leaflet verbindet zwei Punkte auf dem kurzen Weg in LAENGENGRADEN:
        // 179° O → 179° W wird eine Linie quer ueber die Welt. Darum die
        // Laengen fortlaufend weiterzaehlen (181° statt −179°). Leaflet zeichnet
        // Laengen ausserhalb ±180 in der Nachbarkopie der Welt weiter.
        function neben(lon, bezug) {
            return lon + 360 * Math.round((bezug - lon) / 360);
        }
        function fortlaufend(punkte) {
            var aus = [];
            for (var i = 0; i < (punkte || []).length; i++) {
                var p = punkte[i];
                if (!p || !isFinite(p[0]) || !isFinite(p[1])) continue;
                var lon = aus.length ? neben(p[1], aus[aus.length - 1][1]) : p[1];
                aus.push([p[0], lon].concat(p.slice(2)));
            }
            return aus;
        }
        // Ganze Punktfolge um volle 360° so verschieben, dass ihr LETZTER Punkt
        // (die aktuelle Position) neben `bezugLon` liegt — Form bleibt gleich.
        function verschiebeNahe(punkte, bezugLon) {
            if (!punkte || !punkte.length || !isFinite(bezugLon)) return punkte || [];
            var letzter = punkte[punkte.length - 1][1];
            var d = neben(letzter, bezugLon) - letzter;
            if (!d) return punkte;
            return punkte.map(function (p) { return [p[0], p[1] + d].concat(p.slice(2)); });
        }

        // ── Listen ──
        function filterNachPhase(fluege, phase) {
            if (!phase || phase === 'ALL') return fluege.slice();
            return fluege.filter(function (f) { return f.phase === phase; });
        }
        // Geplante Fluege (phpVMS-Buchungen) ohne die, die schon live fliegen.
        function geplanteOhneAktive(buchungen, aktive) {
            var fliegt = {};
            aktive.forEach(function (f) { if (f.callsign) fliegt[String(f.callsign).toUpperCase()] = true; });
            return buchungen.filter(function (b) {
                var cs = String(b.callsign || '').toUpperCase();
                return !cs || !fliegt[cs];
            });
        }
        function sortiere(fluege) {
            return fluege.slice().sort(function (a, b) {
                var off = (a.status === 'offline') - (b.status === 'offline');
                if (off) return off;
                return String(a.callsign || a.id).localeCompare(String(b.callsign || b.id));
            });
        }

        wurzel.LM3 = {
            PHASE_COLOR: PHASE_COLOR, PHASE_LIST: PHASE_LIST, PHASE_LABEL: PHASE_LABEL,
            phaseColor: phaseColor, phaseLabel: phaseLabel, phaseForDisplay: phaseForDisplay,
            formatSimLabel: formatSimLabel, fmtQnhBoth: fmtQnhBoth, ageString: ageString, zahl: zahl,
            vsText: vsText, apFlags: apFlags, tonnen: tonnen,
            neben: neben, fortlaufend: fortlaufend, verschiebeNahe: verschiebeNahe,
            filterNachPhase: filterNachPhase, geplanteOhneAktive: geplanteOhneAktive, sortiere: sortiere
        };
    })(typeof window !== 'undefined' ? window : globalThis);
    /* </lm3-rein> */
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var LIVE_MAP_UI = @json($liveMapUiConfig);
            window.LIVE_MAP_UI = LIVE_MAP_UI || {};

            // Flight focus lock (von aelteren Erweiterungen gelesen)
            window._liveMapFlightFocusLock = !!window._liveMapFlightFocusLock;


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
                var weatherAvailable = weatherCfg.weatherAvailable !== false;

                // Server already knows whether any OWM key is configured. If not,
                // don't even wire up the layers — every tile request would just
                // round-trip to the proxy for a blank SVG and stall the page.
                if (!weatherAvailable) {
                    console.info('[LiveMap] Weather disabled (no OWM key configured); skipping overlays');
                    setWeatherUnavailable('Weather layers unavailable (no API key configured)');
                    return;
                }

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
                var stormsLayerCandidates = ["pressure_new", "precipitation_new", "clouds_new"];
                var stormsLayerCandidateIndex = 0;
                var stormsLayerName = stormsLayerCandidates[stormsLayerCandidateIndex];
                var stormsLayer = L.tileLayer(weatherTileUrl(stormsLayerName), { opacity:1, pane:'weatherPane', className:'owm-thunder-layer owm-storms-layer', attribution:"Pressure © OpenWeatherMap" });
                var windLayer   = L.tileLayer(weatherTileUrl("wind_new"), { opacity:1, pane:'weatherPane', className:'owm-wind-layer', attribution:"Wind © OpenWeatherMap" });
                var tempLayer   = L.tileLayer(weatherTileUrl("temp_new"), { opacity:1, pane:'weatherPane', className:'owm-temp-layer', attribution:"Temperature © OpenWeatherMap" });

                if (!btnClouds || !btnRadar) return;

                var allLayers = [cloudsLayer, precipLayer, stormsLayer, windLayer, tempLayer];
                btnClouds._on = btnRadar._on = btnStorms._on = btnWind._on = btnTemp._on = false;
                [
                    [btnClouds, cloudsLayer, 'Clouds', true],
                    [btnRadar, precipLayer, 'Radar', true],
                    [btnStorms, stormsLayer, 'Pressure', true],
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
                                console.warn('[LiveMap] Pressure/Storm proxy fallback: ' + prevName + ' -> ' + stormsLayerName);
                                return;
                            } catch (e) {
                                console.warn('[LiveMap] Pressure fallback switch failed', e);
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
            //  POSITION AUF PLAUSIBILITAET PRUEFEN — "Null Island" (0°/0°)
            //
            //  Ein Simulator meldet 0/0, solange er nicht weiss, wo die
            //  Maschine steht (P3D vor dem Laden des Flugplatzes, MSFS
            //  waehrend des Ladens). phpVMS speichert das als ganz normale
            //  ACARS-Position — mit drei sichtbaren Folgen:
            //    * der Flieger sitzt im Golf von Guinea,
            //    * die "geflogene" Strecke ist der Abstand Abflugort → 0/0
            //      (KSEA → 0/0 sind rund 7.200 nm),
            //    * der Fortschritt steht dadurch auf 100 %.
            //  Gemeldet am 01.09.2026 (FA852 KSEA→KFLL, P3Dv5).
            //
            //  Solche Positionen gelten hier als NICHT vorhanden: kein
            //  Marker, keine Strecke, kein Fortschritt, kein Auto-Zoom
            //  dorthin. Der Flug selbst bleibt in der Liste stehen.
            // ════════════════════════════════════════════════════════════
            var LM_NULL_ISLAND_DEG = 0.05; // ~5,5 km um 0/0 — dort liegt kein Flugplatz

            function lmUsableLatLng(lat, lng) {
                lat = parseFloat(lat);
                lng = parseFloat(lng);
                if (!isFinite(lat) || !isFinite(lng)) return false;
                if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return false;
                // NUR wenn BEIDE nahe 0 sind. 0°N/10°E ist Gabun und voellig gueltig.
                if (Math.abs(lat) < LM_NULL_ISLAND_DEG && Math.abs(lng) < LM_NULL_ISLAND_DEG) return false;
                return true;
            }
            window.lmUsableLatLng = lmUsableLatLng;

            // Entwertet die Position eines Flugs an Ort und Stelle, wenn sie
            // unbrauchbar ist. Alles Weitere (Zeile, Boarding-Pass, Auto-Zoom)
            // liest `position` und faellt damit von selbst auf "—" zurueck.
            function lmSanitizeFlightPosition(f) {
                if (!f || !f.position) return f;
                var p   = f.position;
                var lat = p.lat;
                var lng = (p.lon != null) ? p.lon : p.lng;
                if (lat == null || lng == null || !lmUsableLatLng(lat, lng)) {
                    f._lmNoPosition  = true;
                    f._lmRawPosition = p; // nur zum Nachsehen in der Konsole
                    f.position       = null;
                }
                return f;
            }
            window.lmSanitizeFlightPosition = lmSanitizeFlightPosition;

            // Dieselbe Pruefung fuer die geflogene Spur: GeoJSON-Koordinaten kommen als
            // [lon, lat, alt]. Es wird KOPIERT statt am Original geaendert — die Antwort
            // des Kerns wird beim naechsten Durchlauf noch einmal verwendet.
            function lmStripNullIslandFromGeoJson(gj) {
                if (!gj || typeof gj !== 'object') return gj;
                var keep = function (c) { return Array.isArray(c) && lmUsableLatLng(c[1], c[0]); };
                var walk = function (node) {
                    if (!node || typeof node !== 'object') return node;
                    if (node.type === 'FeatureCollection' && Array.isArray(node.features)) {
                        return Object.assign({}, node, { features: node.features.map(walk) });
                    }
                    if (node.type === 'Feature') {
                        return Object.assign({}, node, { geometry: walk(node.geometry) });
                    }
                    if (node.type === 'LineString' && Array.isArray(node.coordinates)) {
                        var pts = node.coordinates.filter(keep);
                        // Eine Linie aus einem einzigen Punkt zeichnet nichts, kann aber
                        // in Leaflet-Erweiterungen stolpern — dann lieber gar keine.
                        return Object.assign({}, node, { coordinates: pts.length >= 2 ? pts : [] });
                    }
                    if (node.type === 'MultiLineString' && Array.isArray(node.coordinates)) {
                        return Object.assign({}, node, {
                            coordinates: node.coordinates.map(function (line) {
                                if (!Array.isArray(line)) return line;
                                var l = line.filter(keep);
                                return l.length >= 2 ? l : [];
                            }).filter(function (line) { return !Array.isArray(line) || line.length > 0; }),
                        });
                    }
                    return node;
                };
                return walk(gj);
            }
            window.lmStripNullIslandFromGeoJson = lmStripNullIslandFromGeoJson;

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
            var vatsimFetchInFlight = false;
            var ivaoFetchInFlight = false;

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
                var rawIcao = String(toIcao || '').toUpperCase();
                var toPos = staticAirportPos[rawIcao] || staticAirportPos['K'+rawIcao] || staticAirportPos['C'+rawIcao] || staticAirportPos['P'+rawIcao];
                if (!toPos) return;
                var toIcaoSafe = h(safeCallsign(rawIcao) || rawIcao || '—');
                // Datumsgrenze: Leaflet zieht eine Gerade von 179° O nach 179° W
                // einmal quer ueber die Welt. Das Ziel deshalb um volle 360°
                // neben das Flugzeug legen (181° statt −179°) — Leaflet zeichnet
                // Laengen ausserhalb ±180 in der Nachbarkopie der Welt weiter.
                var von = L.latLng(fromLatLng);
                var ziel = L.latLng(toPos[0], toPos[1]);
                ziel = L.latLng(ziel.lat, ziel.lng + 360 * Math.round((von.lng - ziel.lng) / 360));
                L.polyline([von, ziel], { color:'#e74c3c', weight:2, opacity:0.8, dashArray:'8 6' }).addTo(routeLineLayer);
                L.marker(ziel, {
                    icon: L.divIcon({ html:'<div style="background:#e74c3c;color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:3px;white-space:nowrap;box-shadow:0 1px 4px rgba(0,0,0,0.4)">' + toIcaoSafe + '</div>', className:'', iconSize:[null,null], iconAnchor:[20,-4] }),
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
                var logoUrl = safeAssetUrl(AIRLINE_LOGOS[icao]);
                if (!logoUrl) return '';
                return '<div style="text-align:center;padding:6px 0 10px;border-bottom:1px solid #eee;margin-bottom:8px">' +
                    '<img src="' + h(logoUrl) + '" style="max-height:38px;max-width:140px;object-fit:contain;vertical-align:middle" onerror="this.closest(\'div\').remove();" alt="' + h(icao) + '"></div>';
            }

            function buildAircraftIcon(heading) {
                var hdg = parseFloat(heading);
                if (!isFinite(hdg)) hdg = 0;
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22"><g transform="rotate(' + hdg + ',16,16)"><ellipse cx="16" cy="16" rx="2.5" ry="10" fill="#1a6fc4"/><polygon points="16,14 3,20 3,22 16,18 29,22 29,20" fill="#1a6fc4"/><polygon points="16,24 10,29 10,30 16,27 22,30 22,29" fill="#1a6fc4"/><ellipse cx="16" cy="10" rx="1.5" ry="3" fill="rgba(255,255,255,0.35)"/></g></svg>';
                return L.divIcon({ html:'<img src="data:image/svg+xml;base64,' + btoa(svg) + '" width="22" height="22" style="display:block">', className:'', iconSize:[22,22], iconAnchor:[11,11] });
            }

            function buildIvaoAircraftIcon(heading) {
                var hdg = parseFloat(heading);
                if (!isFinite(hdg)) hdg = 0;
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22"><g transform="rotate(' + hdg + ',16,16)"><ellipse cx="16" cy="16" rx="2.5" ry="10" fill="#e67e22"/><polygon points="16,14 3,20 3,22 16,18 29,22 29,20" fill="#e67e22"/><polygon points="16,24 10,29 10,30 16,27 22,30 22,29" fill="#e67e22"/><ellipse cx="16" cy="10" rx="1.5" ry="3" fill="rgba(255,255,255,0.35)"/></g></svg>';
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
                var w=Math.max((Object.keys(counts).length+1)*18,icao.length*7+8,30)+16, hgt=36;
                return L.divIcon({ html:'<div style="width:'+w+'px;height:'+hgt+'px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer"><span style="font-size:9px;font-weight:700;color:#1a1a1a;text-shadow:0 0 3px #fff,0 0 3px #fff;letter-spacing:.3px;line-height:1">'+h(icao)+'</span><div style="display:flex;gap:2px;align-items:center">'+dots+'</div></div>', className:'vatsim-airport-marker', iconSize:[w,hgt], iconAnchor:[w/2,hgt/2] });
            }

            function vRow(label, value) {
                return '<div class="vatsim-popup-row"><span class="label">'+h(label)+'</span><span class="value">'+h(value)+'</span></div>';
            }

            function buildPilotPopup(p) {
                var fp=p.flight_plan||{}, dep=fp.departure||'—', arr=fp.arrival||'—';
                return '<div class="vatsim-popup"><div class="vatsim-popup-header">'+buildLogoHtml(p.callsign)+'<div class="vatsim-popup-callsign">'+h(p.callsign)+'</div><div class="vatsim-popup-route">'+h(dep)+' &rsaquo; '+h(arr)+'</div></div><div class="vatsim-popup-body">'+vRow('Aircraft',fp.aircraft_short||fp.aircraft_faa||'—')+vRow('Altitude',p.altitude?p.altitude.toLocaleString()+' ft':'—')+vRow('Speed',p.groundspeed?p.groundspeed+' kts':'—')+vRow('Heading',p.heading!=null?p.heading+'°':'—')+vRow('Pilot',p.name||'—')+'</div></div>';
            }

            var CTRL_RATINGS = {1:'OBS',2:'S1',3:'S2',4:'S3',5:'C1',6:'C2',7:'C3',8:'I1',9:'I2',10:'I3',11:'SUP',12:'ADM'};
            var IVAO_CTRL_RATINGS = {1:'OBS',2:'AS1',3:'AS2',4:'AS3',5:'ADC',6:'APC',7:'ACC',8:'SEC',9:'SAI',10:'CAI',11:'SUP',12:'ADM'};

            /* ── Security helpers: XSS prevention ── */
            function h(s) {
                if (s == null) return '';
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
            }
            function safeAssetUrl(u) {
                if (!u) return '';
                var s = String(u).trim();
                if (!s || /[<>"'`]/.test(s)) return '';
                if (s.indexOf('//') === 0) s = 'https:' + s;
                if (/^https?:\/\//i.test(s)) return s.replace(/^http:\/\//i, 'https://');
                if (s.charAt(0) === '/') return s;
                return '';
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
                var rColor = rNum>=11?'#8e44ad':rNum>=8?'#c0392b':rNum>=5?'#27ae60':rNum>=2?'#2980b9':'#95a5a6';
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
 var t=BADGE[c.facility]||{label:'ATC',color:'#7f8c8d'};return '<div style="padding:7px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:3px"><span style="background:'+t.color+';color:#fff;padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;flex-shrink:0">'+h(t.label)+'</span><span style="font-size:13px;font-weight:700;color:#1a1a1a">'+h(c.callsign||'—')+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+h(safeFreq(c.frequency||''))+'</span></div>'+ctrlInfoLine(c)+'</div>';}).join('');
                var atisRows='';
                var atisId='atis_'+icao.replace(/\W/g,'')+'_'+Date.now();
                if(atisList&&atisList.length){var atisBlocks=atisList.map(function(a){var lines=Array.isArray(a.text_atis)?a.text_atis:[];var fullText=h(lines.join(' '));var preview=fullText.length>60?fullText.substring(0,60)+'…':fullText;var hasMore=fullText.length>60;return '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:4px"><span style="background:#5dade2;color:#fff;padding:2px 7px;border-radius:3px;font-size:10px;font-weight:700;flex-shrink:0">ATIS</span><span style="font-size:12px;font-weight:700;color:#1a1a1a">'+h(a.callsign)+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+safeFreq(a.frequency||'—')+'</span></div>'+(fullText?('<div style="font-size:10px;color:#555;line-height:1.5;background:#f8faff;padding:5px 8px;border-radius:4px;word-break:break-word"><span class="atis-preview-'+atisId+'">'+preview+'</span><span class="atis-full-'+atisId+'" style="display:none">'+fullText+'</span>'+(hasMore?'<br><span onclick="var p=this.parentElement;var prev=p.querySelector(\'.atis-preview-'+atisId+'\');var full=p.querySelector(\'.atis-full-'+atisId+'\');if(full.style.display===\'none\'){prev.style.display=\'none\';full.style.display=\'\';this.textContent=\'▲ Hide ATIS\';}else{prev.style.display=\'\';full.style.display=\'none\';this.textContent=\'▼ Show full ATIS\';}" style="color:#3498db;cursor:pointer;font-size:10px;font-weight:600">▼ Show full ATIS</span>':'')+'</div>'):'')+'</div>';}).join('');atisRows='<div style="margin-top:4px;padding-top:8px;border-top:2px dashed #d6eaf8">'+atisBlocks+'</div>';}
                var total=ctrlList.length+(atisList?atisList.length:0);
                var airportFullName=airportNameCache[icao]||airportNameCache['K'+icao]||'';
                return '<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+h(icao)+'</div><div style="display:flex;align-items:center;gap:6px;margin-top:2px">'+(airportFullName?'<span class="vatsim-popup-route" style="margin:0">'+h(airportFullName)+'</span>':'')+'<span style="background:#27ae60;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">VATSIM</span></div><div style="font-size:11px;color:#aaa;margin-top:3px">'+total+' station'+(total!==1?'s':'')+' active</div></div><div class="vatsim-popup-body">'+ctrlRows+atisRows+'</div></div>';
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

            // ════════════════════════════════════════════════════════════
            //  SEKTOREN AN DER DATUMSGRENZE
            //
            //  Die VATSpy-Grenzen teilen Pazifik-FIRs (Anchorage, Oakland
            //  Oceanic, Magadan, Nadi, Auckland …) bei ±180° in zwei Stuecke.
            //  Leaflet zeigte davon zwei Fehler:
            //    * der Rand zog an 180° eine senkrechte Naht mitten durch den
            //      Sektor,
            //    * Flaechen gibt es nur in EINER Weltkopie — schiebt man die
            //      Karte ueber die Datumsgrenze, fehlte die andere Haelfte.
            //  Nur Sektoren, die ±180° beruehren, werden deshalb als Flaeche
            //  ohne Rand + eigener Umriss ohne Schnittkante gezeichnet, und
            //  zusaetzlich um ±360° verschoben. Alle anderen bleiben wie bisher.
            // ════════════════════════════════════════════════════════════
            function lmAufDatumsgrenze(lon) { return Math.abs(Math.abs(lon) - 180) < 1e-9; }

            function lmRinge(feature) {
                var g = feature && feature.geometry;
                if (!g) return [];
                if (g.type === 'Polygon') return g.coordinates;
                if (g.type === 'MultiPolygon') { var r = []; g.coordinates.forEach(function (p) { p.forEach(function (ring) { r.push(ring); }); }); return r; }
                return [];
            }

            function lmBeruehrtDatumsgrenze(feature) {
                return lmRinge(feature).some(function (ring) {
                    return ring.some(function (c) { return lmAufDatumsgrenze(c[0]); });
                });
            }

            /** Umriss ohne die Kanten, die genau auf ±180° liegen. */
            function lmUmrissOhneNaht(feature) {
                var linien = [];
                lmRinge(feature).forEach(function (ring) {
                    var zug = [];
                    for (var i = 0; i < ring.length; i++) {
                        var a = ring[i - 1], b = ring[i];
                        // Auch +180 → −180 direkt: diese Kante liefe sonst einmal um die Welt.
                        if (i > 0 && lmAufDatumsgrenze(a[0]) && lmAufDatumsgrenze(b[0])) {
                            if (zug.length >= 2) linien.push(zug);
                            zug = [b];
                        } else {
                            zug.push(b);
                        }
                    }
                    if (zug.length >= 2) linien.push(zug);
                });
                return { type: 'Feature', properties: {}, geometry: { type: 'MultiLineString', coordinates: linien } };
            }

            /** Dasselbe Feature um `d` Grad Laenge verschoben (Weltkopie). */
            function lmVerschoben(feature, d) {
                function schiebe(c) { return typeof c[0] === 'number' ? [c[0] + d].concat(c.slice(1)) : c.map(schiebe); }
                return { type: 'Feature', properties: feature.properties || {}, geometry: { type: feature.geometry.type, coordinates: schiebe(feature.geometry.coordinates) } };
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
                    var shortSafe = h(short);
                    var callsignSafe = h(info.callsign || '—');
                    var freqSafe = safeFreq(info.frequency || '') || '—';
                    var firLabelSafe = h((freqSafe && freqSafe !== '—') ? freqSafe : ((firName || '').split(' ')[0] || '—'));
                    var netBadge=info.network==='IVAO'?'<span style="background:#e67e22;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">IVAO</span>':'<span style="background:#27ae60;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">VATSIM</span>'; var popupContent='<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+callsignSafe+'</div><div style="display:flex;align-items:center;gap:6px;margin-top:2px"><span class="vatsim-popup-route" style="margin:0">'+h(firName)+'</span>'+netBadge+'</div>'+(isUpper?'<div style="font-size:10px;color:#8e44ad;font-weight:700;margin-top:2px">▲ Upper Airspace</div>':'')+'</div><div class="vatsim-popup-body">'+vRow('Frequency',safeFreq(info.frequency || '') || '—')+ctrlInfoLine(info)+'</div></div>';
                    var anDerDatumsgrenze=false;
                    features.forEach(function(feature){
                        try {
                            if (!lmBeruehrtDatumsgrenze(feature)) {
                                var layer=L.geoJSON(feature,{style:{color:color,weight:borderWeight,opacity:0.75,fillColor:color,fillOpacity:fillOpacity,dashArray:dashArray}});
                                layer.on('mouseover',function(e){e.target.setStyle({fillOpacity:hoverFill,weight:borderWeight+0.5,dashArray:''}); });
                                layer.on('mouseout',function(e){e.target.setStyle({fillOpacity:fillOpacity,weight:borderWeight,dashArray:dashArray}); });
                                layer.bindPopup(popupContent,{maxWidth:260});
                                layer.addTo(sectorLayer);
                                return;
                            }
                            // An der Datumsgrenze: Flaeche ohne Rand + Umriss ohne
                            // Schnittkante, in der Hauptwelt und je eine Weltkopie
                            // links und rechts. Hover wirkt auf alle Teile zugleich.
                            anDerDatumsgrenze=true;
                            var umriss=lmUmrissOhneNaht(feature);
                            var flaechen=[], raender=[];
                            [0, 360, -360].forEach(function(d){
                                var f=d?lmVerschoben(feature,d):feature;
                                var u=d?lmVerschoben(umriss,d):umriss;
                                var fl=L.geoJSON(f,{style:{stroke:false,fillColor:color,fillOpacity:fillOpacity}});
                                var rd=L.geoJSON(u,{interactive:false,style:{color:color,weight:borderWeight,opacity:0.75,dashArray:dashArray,fill:false}});
                                fl.bindPopup(popupContent,{maxWidth:260});
                                flaechen.push(fl); raender.push(rd);
                            });
                            var hover=function(an){
                                flaechen.forEach(function(fl){fl.setStyle({fillOpacity:an?hoverFill:fillOpacity});});
                                raender.forEach(function(rd){rd.setStyle({weight:an?borderWeight+0.5:borderWeight,dashArray:an?'':dashArray});});
                            };
                            flaechen.forEach(function(fl){
                                fl.on('mouseover',function(){hover(true);});
                                fl.on('mouseout',function(){hover(false);});
                                fl.addTo(sectorLayer);
                            });
                            raender.forEach(function(rd){rd.addTo(sectorLayer);});
                        } catch(e){}
                    });
                    var biggest=features.reduce(function(best,f){return polyArea(f)>polyArea(best)?f:best;},features[0]);
                    var center=polyCenter(biggest);
                    if(!center) return;
                    var labelW=Math.max(short.length*8+16,64), labelH=36;
                    (anDerDatumsgrenze ? [0, 360, -360] : [0]).forEach(function(d){
                    L.marker([center[0], center[1] + d],{
                        icon:L.divIcon({html:'<div style="background:'+color+';color:#fff;padding:3px 9px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;box-shadow:0 2px 5px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5);white-space:nowrap;text-align:center">'+shortSafe+'<br><span style="font-size:9px;font-weight:400;opacity:0.85">'+firLabelSafe+'</span></div>',className:'',iconSize:[labelW,labelH],iconAnchor:[labelW/2,labelH/2]}),
                        zIndexOffset: -200,
                        interactive: false,
                        keyboard: false,
                        title: info.callsign
                    }).addTo(sectorLayer);
                    });
                });
            }

            function updateCtrlZoom(map) {
                var z=map.getZoom();
                document.querySelectorAll('.vatsim-airport-marker, .ivao-airport-marker').forEach(function(el){
                    var label=el.querySelector('div:first-child');
                    // Ist die Klasse am Marker selbst (divIcon className), waere
                    // `parentElement` die GANZE Marker-Ebene — unter Zoom 3
                    // verschwanden dann alle Flugzeuge mit (gemessen 30.10.2026).
                    var ziel = el.classList.contains('leaflet-marker-icon') ? el : el.parentElement;
                    if (!ziel || ziel.classList.contains('leaflet-pane')) return;
                    if(z<3){ziel.style.display='none';}
                    else{ziel.style.display='';if(label) label.style.display=z>=5?'':'none';}
                });
            }

            function buildAirportCtrlIconIvao(icao, ctrlList, atisList) {
                var TYPES={2:{short:'D',color:'#2980b9'},3:{short:'G',color:'#d35400'},4:{short:'T',color:'#c0392b'},5:{short:'A',color:'#27ae60'}};
                var order=[2,3,4,5],counts={};
                ctrlList.forEach(function(c){if(TYPES[c.facility])counts[c.facility]=(counts[c.facility]||0)+1;});
                var ac=atisList?atisList.length:0,hasApp=!!(counts[5]),appCount=counts[5]||0;
                var dots=order.filter(function(f){return f!==5&&counts[f];}).map(function(f){var t=TYPES[f],n=counts[f];return '<span style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:3px;background:'+t.color+';color:#fff;font-size:8px;font-weight:800;box-shadow:0 1px 2px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5)">'+t.short+(n>1?'<span style="position:absolute;top:-4px;right:-4px;background:#c0392b;color:#fff;border-radius:50%;width:9px;height:9px;font-size:6px;display:flex;align-items:center;justify-content:center;border:1px solid #fff;line-height:1;font-weight:900">'+n+'</span>':'')+'</span>';}).join('');
                if(hasApp||ac>0){var hasAtis=ac>0,badgeText,badgeBg,badgeW2=18,badgeH2=18,badgeRadius='4px';if(hasApp&&hasAtis){badgeText='A<span style="font-style:italic;font-size:9px;opacity:0.9">i</span>';badgeBg='#27ae60';badgeW2=22;}else if(hasApp){badgeText='A';badgeBg='#27ae60';}else{badgeText='<span style="font-style:italic">i</span>';badgeBg='#5dade2';badgeRadius='50%';}dots+='<span style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:'+badgeW2+'px;height:'+badgeH2+'px;border-radius:'+badgeRadius+';background:'+badgeBg+';color:#fff;font-size:9px;font-weight:900;box-shadow:0 1px 4px rgba(0,0,0,0.5);border:1.5px solid rgba(255,255,255,0.6)">'+badgeText+'</span>';}
                var w=Math.max((Object.keys(counts).length+1)*18,icao.length*7+8,30)+16,hgt=36;
                return L.divIcon({html:'<div style="width:'+w+'px;height:'+hgt+'px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer;outline:2px solid #e67e22;border-radius:3px;outline-offset:1px"><span style="font-size:9px;font-weight:700;color:#1a1a1a;text-shadow:0 0 3px #fff,0 0 3px #fff;letter-spacing:.3px;line-height:1">'+h(icao)+'</span><div style="display:flex;gap:2px;align-items:center">'+dots+'</div></div>',className:'ivao-airport-marker',iconSize:[w,hgt],iconAnchor:[w/2,hgt/2]});
            }

            function buildAirportCtrlPopupIvao(icao, ctrlList, atisList) {
                var order={2:1,3:2,4:3,5:4};
                ctrlList=ctrlList.slice().sort(function(a,b){return(order[a.facility]||9)-(order[b.facility]||9);});
                var BADGE={2:{label:'DEL',color:'#2980b9'},3:{label:'GND',color:'#d35400'},4:{label:'TWR',color:'#c0392b'},5:{label:'APP',color:'#27ae60'}};
                var ctrlRows=ctrlList.map(function(c){
                    var t=BADGE[c.facility]||{label:'ATC',color:'#7f8c8d'};
                    return '<div style="padding:7px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:3px"><span style="background:'+t.color+';color:#fff;padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;flex-shrink:0">'+h(t.label)+'</span><span style="font-size:13px;font-weight:700;color:#1a1a1a">'+h(c.callsign||'—')+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+h(safeFreq(c.frequency||''))+'</span></div>'+ctrlInfoLine(c)+'</div>';
                }).join('');
                var atisRows='';
                var atisId='atis_ivao_'+icao.replace(/\W/g,'')+'_'+Date.now();
                if(atisList&&atisList.length){
                    var atisBlocks=atisList.map(function(a){
                        var lines=Array.isArray(a.text_atis)?a.text_atis:[];
                        var fullText=h(lines.join(' '));
                        var preview=fullText.length>60?fullText.substring(0,60)+'…':fullText;
                        var hasMore=fullText.length>60;
                        return '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0"><div style="display:flex;align-items:center;gap:8px;margin-bottom:4px"><span style="background:#5dade2;color:#fff;padding:2px 7px;border-radius:3px;font-size:10px;font-weight:700;flex-shrink:0">ATIS</span><span style="font-size:12px;font-weight:700;color:#1a1a1a">'+h(a.callsign)+'</span><span style="font-size:12px;color:#888;margin-left:auto">'+safeFreq(a.frequency||'—')+'</span></div>'+(fullText?('<div style="font-size:10px;color:#555;line-height:1.5;background:#f8faff;padding:5px 8px;border-radius:4px;word-break:break-word"><span class="atis-preview-'+atisId+'">'+preview+'</span><span class="atis-full-'+atisId+'" style="display:none">'+fullText+'</span>'+(hasMore?'<br><span onclick="var p=this.parentElement;var prev=p.querySelector(\'.atis-preview-'+atisId+'\');var full=p.querySelector(\'.atis-full-'+atisId+'\');if(full.style.display===\'none\'){prev.style.display=\'none\';full.style.display=\'\';this.textContent=\'▲ Hide ATIS\';}else{prev.style.display=\'\';full.style.display=\'none\';this.textContent=\'▼ Show full ATIS\';}" style="color:#3498db;cursor:pointer;font-size:10px;font-weight:600">▼ Show full ATIS</span>':'')+'</div>'):'')+'</div>';
                    }).join('');
                    atisRows='<div style="margin-top:4px;padding-top:8px;border-top:2px dashed #fde8cc">'+atisBlocks+'</div>';
                }
                var total=ctrlList.length+(atisList?atisList.length:0);
                var airportFullName=airportNameCache[icao]||airportNameCache['K'+icao]||'';
                return '<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+h(icao)+'</div><div style="display:flex;align-items:center;gap:6px;margin-top:2px">'+(airportFullName?'<span class="vatsim-popup-route" style="margin:0">'+h(airportFullName)+'</span>':'')+'<span style="background:#e67e22;color:#fff;font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;letter-spacing:.5px;flex-shrink:0">IVAO</span></div><div style="font-size:11px;color:#aaa;margin-top:3px">'+total+' station'+(total!==1?'s':'')+' active</div></div><div class="vatsim-popup-body">'+ctrlRows+atisRows+'</div></div>';
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
                if (vatsimFetchInFlight) return Promise.resolve(false);
                vatsimFetchInFlight = true;
                return Promise.all([
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
                            var traconIcon=L.divIcon({html:'<div style="display:flex;flex-direction:column;align-items:center;white-space:nowrap;pointer-events:auto"><div style="background:'+color+';color:#fff;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:.5px;box-shadow:0 1px 4px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5);line-height:1.4">'+h(root)+'</div><div style="width:4px;height:4px;border-radius:50%;background:'+color+';margin-top:2px"></div></div>',className:'',iconSize:[root.length*8+16,26],iconAnchor:[(root.length*8+16)/2,13]});
                            L.marker(entry.pos,{icon:traconIcon,title:c.callsign,zIndexOffset:400}).bindPopup('<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+h(c.callsign||'—')+'</div><div class="vatsim-popup-route">TRACON / Approach Control</div></div><div class="vatsim-popup-body">'+vRow('Frequency',safeFreq(c.frequency||'') || '—')+ctrlInfoLine(c)+'</div></div>',{maxWidth:260}).addTo(vatsimCtrlLayer);
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
                    return true;
                }).catch(function(err){
                    console.error('[VATSIM] Fehler:',err);
                    return false;
                }).finally(function() {
                    vatsimFetchInFlight = false;
                });
            }

            function loadIvao(map) {
                if (ivaoFetchInFlight) return Promise.resolve(false);
                ivaoFetchInFlight = true;
                return fetch(IVAO_DATA_API).then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();}).then(function(data){
                    var clients=data.clients||{}, pilots=clients.pilots||[], atcs=clients.atcs||[];
                    var statsEl=document.getElementById('ivaoStats'), dotEl=document.getElementById('ivaoNetDot');
                    if(statsEl) statsEl.textContent='\u2708'+pilots.length+'  \uD83C\uDFA7'+atcs.length;
                    if(dotEl)   dotEl.style.background='#fff';
                    if(!showIvao) return true;

                    ivaoPilotsLayer.clearLayers(); ivaoCtrlLayer.clearLayers(); ivaoSectorLayer.clearLayers();
                    var IVAO_FAC={'DEL':2,'GND':3,'TWR':4,'APP':5,'DEP':5,'CTR':6,'FSS':1};

                    pilots.forEach(function(p){
                        var trk=p.lastTrack||{},lat=parseFloat(trk.latitude),lon=parseFloat(trk.longitude);
                        if(isNaN(lat)||isNaN(lon)) return;
                        var fp=p.flightPlan||{},dep=fp.departureId||'—',arr=fp.arrivalId||'—',ac=(fp.aircraft&&fp.aircraft.icaoCode)||'—',hdg=trk.heading||0;
                        var popupHtml='<div class="vatsim-popup"><div class="vatsim-popup-header">'+buildLogoHtml(p.callsign)+'<div class="vatsim-popup-callsign">'+h(p.callsign)+'</div><div class="vatsim-popup-route">'+h(dep)+' &rsaquo; '+h(arr)+'</div><div style="font-size:9px;font-weight:700;color:#e67e22;margin-top:2px">IVAO</div></div><div class="vatsim-popup-body">'+vRow('Aircraft',ac)+vRow('Altitude',trk.altitude?trk.altitude.toLocaleString()+' ft':'—')+vRow('Speed',trk.groundSpeed?trk.groundSpeed+' kts':'—')+vRow('Heading',hdg+'°')+vRow('Pilot',String(p.userId||'—'))+'</div></div>';
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
                    return true;
                }).catch(function(err){
                    console.error('[IVAO] Error:',err);
                    return false;
                }).finally(function() {
                    ivaoFetchInFlight = false;
                });
            }

            // ════════════════════════════════════════════════════════════
            //  LIVE-FLUEGE (v5) — Quelle laut Einstellung, immer ueber das
            //  Modul (/livemap/api/fluege). Felder und Phasen wie die
            //  Admin-Webapp auf live: Liste = Flugtafel, Karte = Flugdetail.
            // ════════════════════════════════════════════════════════════
            var LM_SPRACHE = (window.LIVE_MAP_UI && window.LIVE_MAP_UI.sprache === 'de') ? 'de' : 'en';
            var LM_T = ({
                de: {
                    keine: 'Gerade ist niemand unterwegs.', keineFilter: 'Kein Flug in dieser Phase.',
                    keineGeplant: 'Keine geplanten Flüge.', laden: 'Flüge werden geladen …',
                    folgen: 'Folgen', folgt: 'Folgt', frei: 'Frei', alle: 'Alle Phasen', zuletzt: 'vor',
                    schliessen: 'Schließen', stand: 'Stand', offline: 'offline', abflug: 'Abflug',
                    pilot: 'Pilot', fehler: {
                        kein_schluessel: 'Für AeroACARS Live ist kein Lese-Schlüssel hinterlegt.',
                        abgelehnt: 'AeroACARS Live hat den Lese-Schlüssel abgelehnt.',
                        nicht_erreichbar: 'AeroACARS Live ist gerade nicht erreichbar.',
                        adresse: 'Die Adresse von AeroACARS Live ist ungültig.',
                        ungueltig: 'Unerwartete Antwort vom Server.',
                        netz: 'Flugdaten konnten nicht geladen werden.'
                    }
                },
                en: {
                    keine: 'No one is flying right now.', keineFilter: 'No flight in this phase.',
                    keineGeplant: 'No planned flights.', laden: 'Loading flights …',
                    folgen: 'Follow', folgt: 'Following', frei: 'Free', alle: 'All phases', zuletzt: 'ago',
                    schliessen: 'Close', stand: 'Stand', offline: 'offline', abflug: 'Departure',
                    pilot: 'Pilot', fehler: {
                        kein_schluessel: 'No read key is stored for AeroACARS Live.',
                        abgelehnt: 'AeroACARS Live rejected the read key.',
                        nicht_erreichbar: 'AeroACARS Live cannot be reached right now.',
                        adresse: 'The AeroACARS Live address is invalid.',
                        ungueltig: 'Unexpected answer from the server.',
                        netz: 'Flight data could not be loaded.'
                    }
                }
            })[LM_SPRACHE];

            function lmEsc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }
            function lmKurzname(name) {
                var teile = String(name == null ? '' : name).trim().split(/\s+/).filter(Boolean);
                if (!teile.length) return '—';
                if (teile.length === 1) return teile[0];
                return teile[0] + ' ' + teile[teile.length - 1].charAt(0).toUpperCase() + '.';
            }

            var LM_FLIEGER_SVG = '<svg viewBox="0 0 32 32" width="30" height="30" aria-hidden="true">' +
                '<path d="M16 2.5c1.1 0 1.9 1.3 1.9 3.2v7.1l10.6 6.2v2.6l-10.6-3.1v6.4l3.1 2.4v2.1L16 27.9l-5 1.5v-2.1l3.1-2.4v-6.4L3.5 21.6V19l10.6-6.2V5.7c0-1.9.8-3.2 1.9-3.2z"/></svg>';

            function starteVaFluege(map) {
                var cfg = window.LIVE_MAP_UI || {};
                var zustand = {
                    fluege: [], geplant: [], auswahl: null, phase: 'ALL', tab: 'aktiv',
                    folgen: !!cfg.defaultFollowFlight, ersteAnsicht: true, fehler: null, geladen: false
                };
                var marker = {};            // id → L.marker
                var flugEbene = L.layerGroup().addTo(map);
                var spurEbene = L.layerGroup().addTo(map);
                var spurDaten = { id: null, spur: [], route: [] };
                var abrufLaeuft = false, abrufTimer = null, spurTimer = null;

                var el = {
                    wrap: document.getElementById('lm3'),
                    panel: document.getElementById('lm3-panel'),
                    liste: document.getElementById('lm3-liste'),
                    meldung: document.getElementById('lm3-meldung'),
                    zahl: document.getElementById('lm3-zahl'),
                    nAktiv: document.getElementById('lm3-n-aktiv'),
                    nGeplant: document.getElementById('lm3-n-geplant'),
                    tabAktiv: document.getElementById('lm3-tab-aktiv'),
                    tabGeplant: document.getElementById('lm3-tab-geplant'),
                    phase: document.getElementById('lm3-phase'),
                    filterZeile: document.getElementById('lm3-filter'),
                    detail: document.getElementById('lm3-detail'),
                    punkt: document.getElementById('lm3-status-punkt'),
                    folgen: document.getElementById('btnFollowFlight')
                };

                // ── Phasenfilter: Reihenfolge wie die Webapp ──
                if (el.phase) {
                    var opt = document.createElement('option');
                    opt.value = 'ALL'; opt.textContent = LM_T.alle; el.phase.appendChild(opt);
                    LM3.PHASE_LIST.forEach(function (p) {
                        var o = document.createElement('option');
                        o.value = p; o.textContent = LM3.phaseLabel(p); el.phase.appendChild(o);
                    });
                    el.phase.addEventListener('change', function () { zustand.phase = el.phase.value; zeichneListe(); zeichneMarker(); });
                }
                function setzeTab(tab) {
                    zustand.tab = tab;
                    if (el.tabAktiv) el.tabAktiv.setAttribute('aria-selected', tab === 'aktiv' ? 'true' : 'false');
                    if (el.tabGeplant) el.tabGeplant.setAttribute('aria-selected', tab === 'geplant' ? 'true' : 'false');
                    if (el.filterZeile) el.filterZeile.hidden = tab !== 'aktiv';
                    zeichneListe();
                }
                if (el.tabAktiv) el.tabAktiv.addEventListener('click', function () { setzeTab('aktiv'); });
                if (el.tabGeplant) el.tabGeplant.addEventListener('click', function () { setzeTab('geplant'); });

                // ── Geplante Fluege: phpVMS-Buchungen (wie bisher) ──
                function geplanteAusBuchungen() {
                    var roh = (typeof VA_USER_BIDS !== 'undefined' && Array.isArray(VA_USER_BIDS)) ? VA_USER_BIDS : [];
                    return roh.map(function (b) {
                        return {
                            callsign: ((b.airline && b.airline.icao) || '') + (b.flight_number || ''),
                            dep: (b.dpt_airport && (b.dpt_airport.icao || b.dpt_airport.id)) || b.dpt_airport_id || null,
                            arr: (b.arr_airport && (b.arr_airport.icao || b.arr_airport.id)) || b.arr_airport_id || null,
                            pilot: lmKurzname(b.user && b.user.name),
                            zeit: b.dep_time || ''
                        };
                    });
                }

                // ── Abruf ──
                function holeFluege() {
                    if (abrufLaeuft) return Promise.resolve();
                    abrufLaeuft = true;
                    return fetch(cfg.flightsApi, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(function (r) { return r.json().catch(function () { return { ok: false, fehler: 'ungueltig' }; }); })
                        .then(function (d) {
                            zustand.geladen = true;
                            if (!d || d.ok !== true || !Array.isArray(d.fluege)) {
                                zustand.fehler = (d && d.fehler) || 'ungueltig';
                                zustand.fluege = [];
                            } else {
                                zustand.fehler = null;
                                zustand.fluege = LM3.sortiere(d.fluege);
                            }
                        })
                        .catch(function () { zustand.geladen = true; zustand.fehler = 'netz'; })
                        .then(function () {
                            abrufLaeuft = false;
                            zustand.geplant = LM3.geplanteOhneAktive(geplanteAusBuchungen(), zustand.fluege);
                            if (zustand.auswahl && !findeFlug(zustand.auswahl)) schliesseDetail();
                            zeichneAlles();
                        });
                }
                function planeAbruf(ms) {
                    if (abrufTimer) clearTimeout(abrufTimer);
                    abrufTimer = setTimeout(function () {
                        holeFluege().then(function () { planeAbruf(); });
                    }, ms != null ? ms : (document.hidden ? 60000 : (cfg.refreshMs || 10000)));
                }
                document.addEventListener('visibilitychange', function () { if (!document.hidden) planeAbruf(300); });

                function findeFlug(id) {
                    for (var i = 0; i < zustand.fluege.length; i++) if (zustand.fluege[i].id === id) return zustand.fluege[i];
                    return null;
                }
                function sichtbareFluege() { return LM3.filterNachPhase(zustand.fluege, zustand.phase); }

                // ── Zeichnen ──
                function zeichneAlles() { zeichneKopf(); zeichneListe(); zeichneMarker(); zeichneDetail(); folge(); }

                function zeichneKopf() {
                    var n = zustand.fluege.length;
                    if (el.zahl) el.zahl.textContent = zustand.fehler ? '!' : String(n);
                    if (el.nAktiv) el.nAktiv.textContent = String(n);
                    if (el.nGeplant) el.nGeplant.textContent = String(zustand.geplant.length);
                    if (el.punkt) el.punkt.className = 'lm3-punkt ' + (zustand.fehler ? 'ist-fehler' : (n ? 'ist-live' : ''));
                    if (el.meldung) {
                        if (zustand.fehler) {
                            el.meldung.hidden = false;
                            el.meldung.textContent = LM_T.fehler[zustand.fehler] || LM_T.fehler.netz;
                        } else {
                            el.meldung.hidden = true;
                            el.meldung.textContent = '';
                        }
                    }
                }

                function leer(text) {
                    var d = document.createElement('p');
                    d.className = 'lm3-leer';
                    d.textContent = text;
                    return d;
                }

                function zeichneListe() {
                    if (!el.liste) return;
                    el.liste.textContent = '';
                    if (!zustand.geladen) { el.liste.appendChild(leer(LM_T.laden)); return; }
                    if (zustand.tab === 'geplant') {
                        if (!zustand.geplant.length) { el.liste.appendChild(leer(LM_T.keineGeplant)); return; }
                        zustand.geplant.forEach(function (g) { el.liste.appendChild(zeileGeplant(g)); });
                        return;
                    }
                    if (zustand.fehler) return;
                    var liste = sichtbareFluege();
                    if (!liste.length) { el.liste.appendChild(leer(zustand.fluege.length ? LM_T.keineFilter : LM_T.keine)); return; }
                    liste.forEach(function (f) { el.liste.appendChild(zeileAktiv(f)); });
                }

                function zeileAktiv(f) {
                    var pos = f.pos || {};
                    var phase = LM3.phaseForDisplay(f.phase, f.shadow_segment);
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'lm3-zeile' + (zustand.auswahl === f.id ? ' ist-gewaehlt' : '') + (f.status === 'offline' ? ' ist-offline' : '');
                    b.setAttribute('role', 'listitem');
                    b.style.setProperty('--ph', LM3.phaseColor(phase));
                    var flugzeug = [f.aircraft_icao, f.registration].filter(Boolean).join(' · ') || '—';
                    var fuel = LM3.tonnen(pos.fuel_total_kg);
                    var ap = LM3.apFlags(pos);
                    b.innerHTML =
                        '<span class="lm3-z1"><strong class="lm3-cs">' + lmEsc(f.callsign || (LM_T.pilot + ' ' + f.pilot)) + '</strong>' +
                        '<span class="lm3-pille">' + lmEsc(LM3.phaseLabel(phase)) + '</span>' +
                        '<span class="lm3-alter" title="Last">' + (f.last_seen ? lmEsc(LM3.ageString(f.last_seen)) : '—') + '</span></span>' +
                        '<span class="lm3-z2">' + lmEsc(f.dep || '?') + ' <span class="lm3-pfeil">→</span> ' + lmEsc(f.arr || '?') +
                        '<span class="lm3-trenner">·</span>' + lmEsc(flugzeug) + '</span>' +
                        '<span class="lm3-z3">' + lmEsc(f.pilot_name || (f.va + '/' + f.pilot)) +
                        (f.status === 'offline' ? ' <em class="lm3-off">' + lmEsc(LM_T.offline) + '</em>' : '') + '</span>' +
                        '<span class="lm3-werte">' +
                        wert('ALT', pos.alt_ft != null ? LM3.zahl(pos.alt_ft, LM_SPRACHE) : '—') +
                        wert('IAS', pos.ias_kt != null ? Math.round(pos.ias_kt) : '—') +
                        wert('GS', pos.gs_kt != null ? Math.round(pos.gs_kt) : '—') +
                        wert('V/S', LM3.vsText(pos.vs_fpm), pos.vs_fpm != null && Math.abs(pos.vs_fpm) > 2000) +
                        wert('HDG', pos.hdg_true != null ? Math.round(pos.hdg_true) + '°' : '—') +
                        wert('Fuel', fuel != null ? fuel + 't' : '—') +
                        wert('AP', ap || '—', false, 'lm3-wert-ap') +
                        '</span>';
                    b.addEventListener('click', function () { waehle(f.id, true); });
                    return b;
                }
                function wert(name, w, warn, extra) {
                    return '<span class="lm3-wert' + (warn ? ' ist-warn' : '') + (extra ? ' ' + extra : '') + '"><span>' + lmEsc(name) + '</span><b>' + lmEsc(w) + '</b></span>';
                }
                function zeileGeplant(g) {
                    var d = document.createElement('div');
                    d.className = 'lm3-zeile lm3-zeile-geplant';
                    d.setAttribute('role', 'listitem');
                    d.innerHTML =
                        '<span class="lm3-z1"><strong class="lm3-cs">' + lmEsc(g.callsign || '—') + '</strong>' +
                        (g.zeit ? '<span class="lm3-alter">' + lmEsc(g.zeit) + '</span>' : '') + '</span>' +
                        '<span class="lm3-z2">' + lmEsc(g.dep || '?') + ' <span class="lm3-pfeil">→</span> ' + lmEsc(g.arr || '?') + '</span>' +
                        '<span class="lm3-z3">' + lmEsc(g.pilot) + '</span>';
                    return d;
                }

                // ── Marker ──
                function lageFuer(f) {
                    if (!f.pos) return null;
                    var lon = f.pos.lon;
                    // Gewaehlter Flug: Marker in dieselbe Weltkopie wie seine Spur.
                    // Alle anderen: in die Weltkopie, in der die Karte gerade steht.
                    if (spurDaten.id === f.id && spurDaten.spur.length) lon = LM3.neben(lon, spurDaten.spur[spurDaten.spur.length - 1][1]);
                    else lon = LM3.neben(lon, map.getCenter().lng);
                    return [f.pos.lat, lon];
                }
                function icon(f, gewaehlt) {
                    var phase = LM3.phaseForDisplay(f.phase, f.shadow_segment);
                    var hdg = f.pos && f.pos.hdg_true != null ? Math.round(f.pos.hdg_true) : 0;
                    var label = gewaehlt ? '<span class="lm3-mlabel">' + lmEsc(f.callsign || f.pilot) + '</span>' : '';
                    return L.divIcon({
                        className: 'lm3-marker' + (gewaehlt ? ' ist-gewaehlt' : '') + (f.status === 'offline' ? ' ist-offline' : ''),
                        html: '<span class="lm3-flieger" style="--ph:' + LM3.phaseColor(phase) + ';transform:rotate(' + hdg + 'deg)">' + LM_FLIEGER_SVG + '</span>' + label,
                        iconSize: [34, 34], iconAnchor: [17, 17]
                    });
                }
                function zeichneMarker() {
                    var sichtbar = {};
                    sichtbareFluege().forEach(function (f) {
                        var lage = lageFuer(f);
                        if (!lage) return;
                        sichtbar[f.id] = true;
                        var gew = zustand.auswahl === f.id;
                        var m = marker[f.id];
                        if (!m) {
                            m = L.marker(lage, { icon: icon(f, gew), keyboard: true, title: f.callsign || f.id, riseOnHover: true });
                            m.on('click', function (ev) { L.DomEvent.stopPropagation(ev); waehle(f.id, false); });
                            m.addTo(flugEbene);
                            marker[f.id] = m;
                        } else {
                            m.setLatLng(lage);
                            m.setIcon(icon(f, gew));
                        }
                        m.setZIndexOffset(gew ? 1000 : 0);
                    });
                    Object.keys(marker).forEach(function (id) {
                        if (!sichtbar[id]) { flugEbene.removeLayer(marker[id]); delete marker[id]; }
                    });
                }

                // ── Auswahl, Spur, Route ──
                function waehle(id, ausListe) {
                    zustand.auswahl = id;
                    var f = findeFlug(id);
                    if (el.wrap) el.wrap.classList.add('hat-detail');
                    spurDaten = { id: id, spur: [], route: [] };
                    spurEbene.clearLayers();
                    if (typeof routeLineLayer !== 'undefined') routeLineLayer.clearLayers();
                    zeichneListe(); zeichneMarker(); zeichneDetail();
                    if (f && f.pos) {
                        // Aus der Weltansicht heranholen, damit Spur und Lage lesbar sind.
                        if (map.getZoom() < 5) map.setView(lageFuer(f), 5, { animate: false });
                        zentriere(lageFuer(f), true);
                    }
                    holeSpur(id);
                    if (spurTimer) clearInterval(spurTimer);
                    spurTimer = setInterval(function () { if (!document.hidden) holeSpur(id); }, 30000);
                    if (ausListe && el.wrap && el.wrap.classList.contains('lm3--schmal')) setzePanel(false);
                }
                function schliesseDetail() {
                    zustand.auswahl = null;
                    spurDaten = { id: null, spur: [], route: [] };
                    spurEbene.clearLayers();
                    if (typeof routeLineLayer !== 'undefined') routeLineLayer.clearLayers();
                    if (spurTimer) { clearInterval(spurTimer); spurTimer = null; }
                    if (el.wrap) el.wrap.classList.remove('hat-detail');
                    zeichneListe(); zeichneMarker(); zeichneDetail();
                }
                window.vaInfoCardClose = schliesseDetail;

                function holeSpur(id) {
                    var f = findeFlug(id);
                    if (!f) return;
                    var basis = cfg.flightsApi + '/' + encodeURIComponent(f.va) + '/' + encodeURIComponent(f.pilot);
                    Promise.all([
                        fetch(basis + '/spur', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return null; }),
                        fetch(basis + '/route', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return null; })
                    ]).then(function (a) {
                        if (zustand.auswahl !== id) return;
                        var spur = a[0] && Array.isArray(a[0].punkte) ? a[0].punkte : [];
                        var route = a[1] && Array.isArray(a[1].punkte) ? a[1].punkte : [];
                        zeichneSpur(f, spur, route);
                    });
                }
                function zeichneSpur(f, spurRoh, routeRoh) {
                    var spur = LM3.fortlaufend(spurRoh);
                    var akt = findeFlug(f.id) || f;
                    if (akt.pos) {
                        var bezug = spur.length ? spur[spur.length - 1][1] : akt.pos.lon;
                        spur.push([akt.pos.lat, LM3.neben(akt.pos.lon, bezug)]);
                    }
                    // Die ganze Spur in die Weltkopie legen, in der die Karte gerade
                    // steht — sonst liegt sie nach 180° in der Nachbarkopie und
                    // Leaflet schneidet sie weg (JAL7 PANC→RJAA, gemessen 30.10.).
                    spur = LM3.verschiebeNahe(spur, map.getCenter().lng);
                    var route = LM3.fortlaufend(routeRoh);
                    if (route.length && spur.length) {
                        var schub = LM3.neben(route[0][1], spur[0][1]) - route[0][1];
                        route = route.map(function (p) { return [p[0], p[1] + schub, p[2]]; });
                    }
                    spurDaten = { id: f.id, spur: spur, route: route };
                    spurEbene.clearLayers();
                    if (typeof routeLineLayer !== 'undefined') routeLineLayer.clearLayers();
                    if (route.length > 1) {
                        L.polyline(route.map(function (p) { return [p[0], p[1]]; }), { className: 'lm3-route', weight: 2, dashArray: '6 6', interactive: false }).addTo(spurEbene);
                    } else if (akt.pos && akt.arr && typeof showRouteLine === 'function') {
                        // Ohne OFP: rote Ziel-Linie wie bisher (staticAirportPos aus VATSpy).
                        showRouteLine(map, lageFuer(akt), akt.arr);
                    }
                    if (spur.length > 1) {
                        L.polyline(spur.map(function (p) { return [p[0], p[1]]; }), { className: 'lm3-spur-schatten', weight: 6, interactive: false }).addTo(spurEbene);
                        L.polyline(spur.map(function (p) { return [p[0], p[1]]; }), { className: 'lm3-spur', weight: 3, interactive: false }).addTo(spurEbene);
                    }
                    zeichneMarker();
                }

                // ── Flugdetail (Felder wie PilotDetail der Webapp) ──
                function zeichneDetail() {
                    if (!el.detail) return;
                    var f = zustand.auswahl ? findeFlug(zustand.auswahl) : null;
                    if (!f) { el.detail.hidden = true; el.detail.innerHTML = ''; return; }
                    var pos = f.pos || {};
                    var phase = LM3.phaseForDisplay(f.phase, f.shadow_segment);
                    var stand = '';
                    if (f.dep_gate && f.arr_gate) stand = LM_T.stand + ' ' + f.dep_gate + ' → ' + f.arr_gate;
                    else if (f.dep_gate) stand = LM_T.stand + ' ' + f.dep_gate;
                    else if (f.arr_gate) stand = LM_T.stand + ' → ' + f.arr_gate;
                    var meta = [f.aircraft_icao || f.registration || '—'];
                    if (f.registration && f.aircraft_icao) meta.push(f.registration);
                    if (f.sim) meta.push(LM3.formatSimLabel(f.sim));
                    meta.push('ACARS: ' + (f.acars || 'AeroACARS'));
                    var warn = '';
                    if (pos.stall_warning) warn += '<span class="lm3-flag">⚠ STALL</span>';
                    if (pos.overspeed_warning) warn += '<span class="lm3-flag">⚠ OVERSPEED</span>';
                    var kv = [];
                    kv.push(['HDG', pos.hdg_true != null ? Math.round(pos.hdg_true) + '° T' : '—']);
                    if (pos.agl_ft != null && pos.agl_ft < 5000) kv.push(['AGL', LM3.zahl(pos.agl_ft, LM_SPRACHE) + ' ft']);
                    if (pos.mach != null && pos.mach > 0.4) kv.push(['Mach', Number(pos.mach).toFixed(3)]);
                    kv.push(['Wind', pos.wind_dir_deg != null ? Math.round(pos.wind_dir_deg) + '° / ' + Math.round(pos.wind_speed_kt || 0) + ' kt' : '—']);
                    kv.push(['QNH', pos.qnh_hpa != null ? LM3.fmtQnhBoth(pos.qnh_hpa) : '—', pos.qnh_hpa != null && pos.altimeter_hpa != null ? 'Set ' + LM3.fmtQnhBoth(pos.altimeter_hpa) : null]);
                    kv.push(['OAT', pos.oat_c != null ? Math.round(pos.oat_c) + '°C' : '—']);
                    kv.push(['Fuel', pos.fuel_total_kg != null ? LM3.tonnen(pos.fuel_total_kg) + ' t' + (pos.fuel_flow_kg_h ? ' · ' + LM3.tonnen(pos.fuel_flow_kg_h, 2) + ' t/h' : '') : '—']);
                    kv.push(['TOW', pos.total_weight_kg != null ? LM3.tonnen(pos.total_weight_kg) + ' t' : '—']);
                    var badge = function (text, an, unbekannt) {
                        return '<span class="lm3-badge' + (an ? ' ist-an' : '') + (unbekannt ? ' ist-unbekannt' : '') + '">' + lmEsc(text) + '</span>';
                    };
                    var flaps = Math.round((pos.flaps_position || 0) * 100);
                    var config = badge('Gear DN', (pos.gear_position || 0) > 0.5) +
                        badge('Flaps ' + flaps + '%', (pos.flaps_position || 0) > 0) +
                        badge(pos.spoilers_armed == null ? 'SPL armed ?' : 'SPL armed', pos.spoilers_armed === true, f.pos != null && pos.spoilers_armed == null) +
                        badge('Park brk', pos.parking_brake === true) +
                        badge('ENG ' + (pos.engines_running || 0), (pos.engines_running || 0) > 0);
                    var apZeile = (pos.ap_master || pos.ap_hdg || pos.ap_alt || pos.ap_nav || pos.ap_app)
                        ? '<div class="lm3-badges">' + badge('AP', !!pos.ap_master) + badge('HDG', !!pos.ap_hdg) + badge('ALT', !!pos.ap_alt) + badge('NAV', !!pos.ap_nav) + badge('APP', !!pos.ap_app) + '</div>'
                        : '';
                    var kachel = function (name, w, einheit, warnTon) {
                        return '<div class="lm3-kachel' + (warnTon ? ' ist-warn' : '') + '"><span>' + lmEsc(name) + '</span><b>' + lmEsc(w) + '</b><small>' + lmEsc(einheit) + '</small></div>';
                    };

                    el.detail.style.setProperty('--ph', LM3.phaseColor(phase));
                    el.detail.innerHTML =
                        '<header class="lm3-d-kopf">' +
                          '<div><div class="lm3-d-cs">' + lmEsc(f.callsign || (LM_T.pilot + ' ' + f.pilot)) + '</div>' +
                          '<div class="lm3-d-route">' + lmEsc(f.dep || '?') + ' <span class="lm3-pfeil">→</span> ' + lmEsc(f.arr || '?') + '</div>' +
                          (stand ? '<div class="lm3-d-stand">' + lmEsc(stand) + '</div>' : '') + '</div>' +
                          '<div class="lm3-d-rechts"><span class="lm3-d-phase">' + lmEsc(LM3.phaseLabel(phase)) + '</span>' +
                          '<button type="button" class="lm3-zu" data-lm3-zu aria-label="' + lmEsc(LM_T.schliessen) + '">×</button></div>' +
                        '</header>' +
                        '<div class="lm3-d-meta">' + lmEsc(meta.join(' · ')) + '<span class="lm3-d-pilot">' + lmEsc(f.pilot_name || (f.va + '/' + f.pilot)) + '</span>' +
                          (f.status === 'offline' ? ' <em class="lm3-off">' + lmEsc(LM_T.offline) + '</em>' : '') + '</div>' +
                        (warn ? '<div class="lm3-flags">' + warn + '</div>' : '') +
                        '<div class="lm3-kacheln">' +
                          kachel('ALT', pos.alt_ft != null ? LM3.zahl(pos.alt_ft, LM_SPRACHE) : '—', 'ft') +
                          kachel('IAS', pos.ias_kt != null ? String(Math.round(pos.ias_kt)) : '—', 'kt') +
                          kachel('GS', pos.gs_kt != null ? String(Math.round(pos.gs_kt)) : '—', 'kt') +
                          kachel('V/S', pos.vs_fpm != null ? (pos.vs_fpm > 0 ? '+' : '') + Math.round(pos.vs_fpm) : '—', 'fpm', pos.vs_fpm != null && Math.abs(pos.vs_fpm) > 2000) +
                        '</div>' +
                        '<dl class="lm3-kv">' + kv.map(function (p) { return '<dt>' + lmEsc(p[0]) + '</dt><dd>' + lmEsc(p[1]) + (p[2] ? '<small>' + lmEsc(p[2]) + '</small>' : '') + '</dd>'; }).join('') + '</dl>' +
                        '<div class="lm3-badges">' + config + '</div>' + apZeile +
                        '<div class="lm3-d-fuss"><span>' + (f.last_seen ? lmEsc(LM3.ageString(f.last_seen)) + ' ' + lmEsc(LM_T.zuletzt) : '') + '</span>' +
                        '<button type="button" class="lm3-knopf" data-lm3-folgen>' + lmEsc(zustand.folgen ? LM_T.folgt : LM_T.folgen) + '</button></div>';
                    el.detail.hidden = false;
                    var zu = el.detail.querySelector('[data-lm3-zu]');
                    if (zu) zu.addEventListener('click', schliesseDetail);
                    var fo = el.detail.querySelector('[data-lm3-folgen]');
                    if (fo) fo.addEventListener('click', function () { setzeFolgen(!zustand.folgen); });
                }

                // ── Folgen: gewaehlter Flug in der Mitte, sonst alle im Blick ──
                function setzeFolgen(an) {
                    zustand.folgen = !!an;
                    syncFolgenKnopf();
                    zeichneDetail();
                    if (zustand.folgen) folge(true);
                }
                function syncFolgenKnopf() {
                    if (!el.folgen) return;
                    el.folgen.classList.toggle('active', zustand.folgen);
                    el.folgen.setAttribute('aria-pressed', zustand.folgen ? 'true' : 'false');
                    var s = el.folgen.querySelector('span');
                    if (s) s.textContent = zustand.folgen ? LM_T.folgen : LM_T.frei;
                }
                if (el.folgen) el.folgen.addEventListener('click', function () { setzeFolgen(!zustand.folgen); });
                syncFolgenKnopf();
                // Wer die Karte selbst bewegt, uebernimmt — Folgen geht aus.
                map.on('dragstart', function () { if (zustand.folgen) setzeFolgen(false); });
                // Nach jedem Verschieben die Marker in die sichtbare Weltkopie holen.
                map.on('moveend', function () { zeichneMarker(); });

                function folge(sofort) {
                    if (!zustand.folgen && !zustand.ersteAnsicht) return;
                    var gew = zustand.auswahl ? findeFlug(zustand.auswahl) : null;
                    if (gew && gew.pos) { zentriere(lageFuer(gew), !sofort); return; }
                    var punkte = [];
                    sichtbareFluege().forEach(function (f) { if (f.pos && f.status !== 'offline') punkte.push(lageFuer(f)); });
                    if (!punkte.length) return;
                    // Ueber die Datumsgrenze: alle Laengen neben die erste legen.
                    var bezug = punkte[0][1];
                    punkte = punkte.map(function (p) { return [p[0], LM3.neben(p[1], bezug)]; });
                    zustand.ersteAnsicht = false;
                    if (punkte.length === 1) { map.setView(punkte[0], Math.max(map.getZoom(), 6), { animate: !sofort }); return; }
                    var opt = freiraum();
                    opt.maxZoom = 7;
                    opt.animate = !sofort;
                    map.fitBounds(L.latLngBounds(punkte), opt);
                }
                // Platz, den Liste und Detail ueber der Karte belegen.
                function freiraum() {
                    var links = 40, rechts = 40, oben = 60, unten = 40;
                    if (el.wrap && !el.wrap.classList.contains('lm3--schmal')) {
                        if (el.panel && !el.panel.classList.contains('ist-zu') && !el.panel.hidden) links = el.panel.offsetWidth + 40;
                        if (el.detail && !el.detail.hidden) rechts = el.detail.offsetWidth + 40;
                    } else if (el.detail && !el.detail.hidden) {
                        unten = el.detail.offsetHeight + 30;
                    } else if (el.panel && el.panel.classList.contains('ist-offen')) {
                        unten = el.panel.offsetHeight + 30;
                    }
                    return { paddingTopLeft: [links, oben], paddingBottomRight: [rechts, unten] };
                }
                // Punkt in die Mitte der FREIEN Kartenflaeche legen (nicht unter
                // Liste, Detail oder das Blatt von unten).
                function zentriere(lage, animiert) {
                    var fr = freiraum(), groesse = map.getSize();
                    var ziel = L.point(
                        (fr.paddingTopLeft[0] + groesse.x - fr.paddingBottomRight[0]) / 2,
                        (fr.paddingTopLeft[1] + groesse.y - fr.paddingBottomRight[1]) / 2
                    );
                    var mitte = map.latLngToContainerPoint(map.getCenter());
                    var punkt = map.latLngToContainerPoint(lage);
                    map.panTo(map.containerPointToLatLng(mitte.add(punkt.subtract(ziel))), { animate: !!animiert });
                }
                map.on('click', function () { if (zustand.auswahl) schliesseDetail(); });

                // ── Panel auf/zu (schmal: unten als Blatt) ──
                function setzePanel(offen) {
                    if (!el.panel) return;
                    el.panel.classList.toggle('ist-offen', !!offen);
                    el.panel.classList.toggle('ist-zu', !offen);
                    var knopf = document.getElementById('mob-toggle-panel');
                    if (knopf) knopf.setAttribute('aria-pressed', offen ? 'true' : 'false');
                    var zu = document.getElementById('lm3-panel-zu');
                    if (zu) zu.setAttribute('aria-expanded', offen ? 'true' : 'false');
                    // Das Blatt von unten verdeckt die Haelfte der Karte — neu einpassen.
                    if (zustand.folgen && el.wrap && el.wrap.classList.contains('lm3--schmal')) setTimeout(function () { folge(true); }, 0);
                }
                var panelZu = document.getElementById('lm3-panel-zu');
                if (panelZu) panelZu.addEventListener('click', function () { setzePanel(el.panel.classList.contains('ist-zu')); });
                var mobKnopf = document.getElementById('mob-toggle-panel');
                if (mobKnopf) mobKnopf.addEventListener('click', function () {
                    var offen = !el.panel.classList.contains('ist-offen');
                    if (offen && zustand.auswahl) schliesseDetail();
                    setzePanel(offen);
                });

                setzeTab('aktiv');
                zeichneKopf();
                holeFluege().then(function () { planeAbruf(); });

                return { setzePanel: setzePanel, zustand: zustand };
            }

            // ════════════════════════════════════════════════════════════
            //  KARTE — eigener Leaflet-Aufbau statt `render_live_map`.
            //  Der Kern wuerde sonst selbst /api/acars abfragen und eigene
            //  VA-Flieger zeichnen (doppelt zu den Live-Fluegen) und eine
            //  zweite Grundebene ohne CARTO-Schluessel bauen (v4.7.4).
            // ════════════════════════════════════════════════════════════
            var lm3Karte = null;

            function applyLayerVisibility() {
                var map = lm3Karte;
                if (!map) return;
                if(vatsimShowPilots&&showVatsim){if(!map.hasLayer(vatsimPilotsLayer))vatsimPilotsLayer.addTo(map);}else map.removeLayer(vatsimPilotsLayer);
                if(vatsimShowPilots&&showIvao){if(!map.hasLayer(ivaoPilotsLayer))ivaoPilotsLayer.addTo(map);}else map.removeLayer(ivaoPilotsLayer);
                if(vatsimShowCtrl&&showVatsim){if(!map.hasLayer(vatsimCtrlLayer))vatsimCtrlLayer.addTo(map);}else map.removeLayer(vatsimCtrlLayer);
                if(vatsimShowCtrl&&showIvao){if(!map.hasLayer(ivaoCtrlLayer))ivaoCtrlLayer.addTo(map);}else map.removeLayer(ivaoCtrlLayer);
                if(vatsimShowSectors&&showVatsim){if(!map.hasLayer(vatsimSectorLayer))vatsimSectorLayer.addTo(map);}else map.removeLayer(vatsimSectorLayer);
                if(vatsimShowSectors&&showIvao){if(!map.hasLayer(ivaoSectorLayer))ivaoSectorLayer.addTo(map);}else map.removeLayer(ivaoSectorLayer);
            }

            (function lm3Start() {
                var cfg = window.LIVE_MAP_UI || {};
                var mapEl = document.getElementById('map');
                var wrap = document.getElementById('lm3');
                if (!window.L || !L.map || !mapEl) {
                    console.error('[LiveMap] Leaflet nicht geladen — Karte kann nicht starten');
                    return;
                }

                // Breite des Widgets (nicht des Fensters!) bestimmt den Aufbau:
                // im GSG-Rahmen nimmt die Seitenleiste ~250 px weg.
                function messeBreite() {
                    if (!wrap) return;
                    var b = wrap.getBoundingClientRect().width;
                    wrap.classList.toggle('lm3--schmal', b < 760);
                    wrap.classList.toggle('lm3--mittel', b >= 760 && b < 1100);
                }
                messeBreite();

                var map = L.map(mapEl, {
                    center: cfg.center || [50, 10],
                    zoom: cfg.zoom || 5,
                    minZoom: 2,
                    maxZoom: 18,
                    worldCopyJump: true,
                    zoomControl: false,
                    scrollWheelZoom: true
                });
                lm3Karte = map;
                window._liveMapInstance = map;
                // Bringt die Seite keine Leaflet-Stile mit, liegen Kacheln als
                // Bilder untereinander statt als Karte. Dann den Rueckfall-Block
                // aus live_map_styles einschalten.
                (function leafletStileSicherstellen() {
                    var pane = mapEl.querySelector('.leaflet-pane');
                    var rueckfall = document.getElementById('lm3-leaflet-css');
                    if (pane && rueckfall && getComputedStyle(pane).position !== 'absolute') {
                        rueckfall.media = 'all';
                        map.invalidateSize();
                    }
                })();
                L.control.zoom({ position: 'bottomright' }).addTo(map);

                // ── Grundkarte (wie bisher, ohne die Grundebene des Kerns) ──
                (function grundkarte() {
                    var allowSatellite = !!cfg.enableSatelliteBasemap;
                    var showSwitcher = !!cfg.showBasemapSwitcher;
                    var defs = {
                        positron: { label: 'Carto Light', url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', options: { subdomains: 'abcd', maxZoom: 20, attribution: '&copy; OpenStreetMap contributors &copy; CARTO' } },
                        osm: { label: 'OpenStreetMap', url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', options: { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' } },
                        dark: { label: 'Carto Dark', url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', options: { subdomains: 'abcd', maxZoom: 20, attribution: '&copy; OpenStreetMap contributors &copy; CARTO' } },
                        satellite: { label: 'Satellite', url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', options: { maxZoom: 18, attribution: 'Tiles &copy; Esri' } }
                    };
                    var keys = ['positron', 'osm', 'dark'];
                    if (allowSatellite) keys.push('satellite');
                    // CARTO verlangt seit dem 26.08.2026 einen Schluessel (sonst Wasserzeichen).
                    var cartoKey = String(cfg.cartoApiKey || '');
                    var mitKey = function (url) {
                        if (!cartoKey || url.indexOf('cartocdn.com') === -1 || /[?&]key=/.test(url)) return url;
                        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'key=' + encodeURIComponent(cartoKey);
                    };
                    var ebenen = {};
                    keys.forEach(function (k) {
                        var l = L.tileLayer(mitKey(defs[k].url), defs[k].options);
                        l._lmBasemapKey = k;
                        ebenen[k] = l;
                    });
                    var gespeichert = '';
                    try { gespeichert = String(localStorage.getItem('livemap_basemap') || '').toLowerCase(); } catch (e) {}
                    var wahl = gespeichert && ebenen[gespeichert] ? gespeichert : String(cfg.defaultBasemap || 'positron').toLowerCase();
                    if (!ebenen[wahl]) wahl = 'positron';
                    ebenen[wahl].addTo(map);
                    if (wrap) wrap.setAttribute('data-grundkarte', wahl);
                    // Umschalter als Aufklapper in der Werkzeugleiste (statt der
                    // Leaflet-Ebenensteuerung unten rechts — die nahm dem Flugdetail
                    // bei 720 px Fensterhoehe den Platz).
                    var kartenKnopf = document.getElementById('lm3-btn-karte');
                    var kartenListe = document.getElementById('lm3-karten-liste');
                    if (showSwitcher && keys.length > 1 && kartenListe) {
                        var waehleGrundkarte = function (k) {
                            if (!ebenen[k] || k === wahl) return;
                            map.removeLayer(ebenen[wahl]);
                            ebenen[k].addTo(map);
                            wahl = k;
                            if (wrap) wrap.setAttribute('data-grundkarte', k);
                            try { localStorage.setItem('livemap_basemap', k); } catch (e) {}
                            [].forEach.call(kartenListe.children, function (b) { b.classList.toggle('active', b.getAttribute('data-grundkarte') === k); });
                        };
                        keys.forEach(function (k) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'lm3-schalter' + (k === wahl ? ' active' : '');
                            b.setAttribute('data-grundkarte', k);
                            b.textContent = defs[k].label;
                            b.addEventListener('click', function () { waehleGrundkarte(k); });
                            kartenListe.appendChild(b);
                        });
                    } else if (kartenKnopf) {
                        kartenKnopf.hidden = true;
                    }
                })();

                attachWeatherToMap(map);
                routeLineLayer.addTo(map);

                // ── VATSIM / IVAO (unveraendert) ──
                var vatsimPollTimer = null, ivaoPollTimer = null;
                function vatsimPause() { return document.hidden ? Math.max(VATSIM_REFRESH_MS * 3, 120000) : VATSIM_REFRESH_MS; }
                function ivaoPause() { return document.hidden ? Math.max(IVAO_REFRESH_MS * 4, 90000) : IVAO_REFRESH_MS; }
                function planeVatsim(ms) {
                    if (vatsimPollTimer) clearTimeout(vatsimPollTimer);
                    vatsimPollTimer = setTimeout(function () { loadVatsim(map).finally(function () { planeVatsim(vatsimPause()); }); }, ms != null ? ms : vatsimPause());
                }
                function planeIvao(ms) {
                    if (ivaoPollTimer) clearTimeout(ivaoPollTimer);
                    ivaoPollTimer = setTimeout(function () { loadIvao(map).finally(function () { planeIvao(ivaoPause()); }); }, ms != null ? ms : ivaoPause());
                }
                Promise.race([logosReady, new Promise(function (r) { setTimeout(r, 3000); })]).then(function () {
                    loadVatsim(map).finally(function () { planeVatsim(vatsimPause()); });
                    loadIvao(map).finally(function () { planeIvao(ivaoPause()); });
                    document.addEventListener('visibilitychange', function () {
                        if (!document.hidden) { planeVatsim(500); planeIvao(500); }
                    });
                });
                map.on('zoomend', function () { updateCtrlZoom(map); });

                var va = starteVaFluege(map);

                // ── Netz-Schalter (wie bisher) ──
                var btnNetVatsim = document.getElementById('btnNetVatsim'), btnNetIvao = document.getElementById('btnNetIvao');
                if (btnNetVatsim) { btnNetVatsim.classList.toggle('active', showVatsim); btnNetVatsim.addEventListener('click', function () { showVatsim = !showVatsim; btnNetVatsim.classList.toggle('active', showVatsim); if (!showVatsim) { map.removeLayer(vatsimPilotsLayer); map.removeLayer(vatsimCtrlLayer); map.removeLayer(vatsimSectorLayer); } else { loadVatsim(map); applyLayerVisibility(); } }); }
                if (btnNetIvao) { btnNetIvao.classList.toggle('active', showIvao); btnNetIvao.addEventListener('click', function () { showIvao = !showIvao; btnNetIvao.classList.toggle('active', showIvao); if (!showIvao) { map.removeLayer(ivaoPilotsLayer); map.removeLayer(ivaoCtrlLayer); map.removeLayer(ivaoSectorLayer); } else { loadIvao(map); applyLayerVisibility(); } }); }
                var btnPilots = document.getElementById('btnVatsimPilots'), btnCtrl = document.getElementById('btnVatsimCtrl'), btnSectors = document.getElementById('btnVatsimSectors');
                if (btnPilots) { btnPilots.classList.toggle('active', vatsimShowPilots); btnPilots.addEventListener('click', function () { vatsimShowPilots = !vatsimShowPilots; btnPilots.classList.toggle('active', vatsimShowPilots); applyLayerVisibility(); }); }
                if (btnCtrl) { btnCtrl.classList.toggle('active', vatsimShowCtrl); btnCtrl.addEventListener('click', function () { vatsimShowCtrl = !vatsimShowCtrl; btnCtrl.classList.toggle('active', vatsimShowCtrl); applyLayerVisibility(); }); }
                if (btnSectors) { btnSectors.classList.toggle('active', vatsimShowSectors); btnSectors.addEventListener('click', function () { vatsimShowSectors = !vatsimShowSectors; btnSectors.classList.toggle('active', vatsimShowSectors); applyLayerVisibility(); }); }
                applyLayerVisibility();

                // ── Werkzeugleiste: Netz / Wetter als Aufklapper, nie gleichzeitig ──
                var aufklapper = [
                    { knopf: document.getElementById('lm3-btn-netz'), box: document.getElementById('vatsim-box'), an: cfg.showNetworkBox !== false },
                    { knopf: document.getElementById('lm3-btn-wetter'), box: document.getElementById('weather-box'), an: cfg.showWeatherBox !== false },
                    { knopf: document.getElementById('lm3-btn-karte'), box: document.getElementById('lm3-karte-box'), an: !document.getElementById('lm3-btn-karte') || !document.getElementById('lm3-btn-karte').hidden }
                ];
                aufklapper.forEach(function (a) {
                    if (!a.knopf || !a.box) return;
                    if (!a.an) { a.knopf.hidden = true; a.box.hidden = true; return; }
                    a.knopf.addEventListener('click', function () {
                        var offen = a.box.hidden;
                        aufklapper.forEach(function (b) { if (b.box) b.box.hidden = true; if (b.knopf) b.knopf.setAttribute('aria-expanded', 'false'); });
                        a.box.hidden = !offen;
                        a.knopf.setAttribute('aria-expanded', offen ? 'true' : 'false');
                        if (wrap) wrap.classList.toggle('hat-aufklapper', offen);
                    });
                });
                if (cfg.showTopFlightsPanel === false) {
                    var p = document.getElementById('lm3-panel');
                    var m = document.getElementById('mob-toggle-panel');
                    if (p) p.hidden = true;
                    if (m) m.hidden = true;
                }

                // Startzustand der Liste: breit offen, schmal nach Einstellung.
                function startPanel() {
                    if (!va) return;
                    var schmal = wrap && wrap.classList.contains('lm3--schmal');
                    va.setzePanel(schmal ? !!cfg.mobileFlightsOpen : true);
                }
                startPanel();

                var letzteSchmal = wrap && wrap.classList.contains('lm3--schmal');
                var neuMessen = function () {
                    messeBreite();
                    var schmal = wrap && wrap.classList.contains('lm3--schmal');
                    if (schmal !== letzteSchmal) { letzteSchmal = schmal; startPanel(); }
                    map.invalidateSize();
                };
                if (window.ResizeObserver && wrap) new ResizeObserver(neuMessen).observe(wrap);
                else window.addEventListener('resize', neuMessen);
            })();
        });
    </script>
    <!-- lm3:skripte-ende -->
@endsection
