{{-- ══════════════════════════════════════════════════════════════════
     LIVE-KARTE 5.1 — Aufbau
     Optik wie 4.7.9 (kompakte Tafel oben, kleiner Detailkasten rechts),
     Technik wie 5.0 (eigene Daten, eigenes Leaflet).

     Gegen Ueberlappung (Thomas 03.11.2026: „wichtig ist mir, dass nix
     ueberlappt!“) liegt ueber der Karte EIN Raster statt frei schwebender
     Kaesten:

         breit:   [Zoom] [Tafel, mittig] [rechte Spalte]
                  rechte Spalte = Werkzeuge → offener Kasten → Detail,
                  untereinander in einer Flex-Spalte
         schmal:  eine Spalte: Werkzeuge → Kasten → (Karte) → Blatt unten

     Was in verschiedenen Zellen steht, kann sich nicht schneiden. Wird es
     eng, gibt die Tafel Spalten ab und die rechte Spalte scrollt — nichts
     legt sich ueber etwas anderes.
══════════════════════════════════════════════════════════════════ --}}
<div class="live-map-wrapper lm3" id="lm-root" data-quelle="{{ $liveMapUiConfig['quelle'] }}">
    <div id="map" role="region" aria-label="{{ $lmDe ? 'Live-Karte' : 'Live map' }}"></div>

    <div class="lm3-ui">
        <div class="lm3-mitte">
            <section class="lm3-tafel" id="va-flights-panel" aria-label="{{ $lmDe ? 'Flüge' : 'Flights' }}">
                <button type="button" class="lm3-tafel-kopf" id="lm3-tafel-kopf" aria-expanded="false" aria-controls="lm3-tafel-inhalt">
                    <span class="lm3-kopf-stat"><span class="lm3-punkt" id="lm3-status-punkt" aria-hidden="true"></span>
                        {{ $lmDe ? 'Aktive Flüge' : 'Active Flights' }} <b class="lm3-n-aktiv" id="lm3-zahl">—</b></span>
                    <span class="lm3-kopf-trenner" aria-hidden="true"></span>
                    <span class="lm3-kopf-stat">{{ $lmDe ? 'Geplant' : 'Planned' }} <b class="lm3-n-geplant" id="lm3-zahl-geplant">—</b></span>
                    <span class="lm3-chevron" aria-hidden="true">▾</span>
                </button>
                <div class="lm3-tafel-inhalt" id="lm3-tafel-inhalt" hidden>
                    <div class="lm3-reiter" role="tablist">
                        <button type="button" role="tab" id="lm3-tab-aktiv" aria-selected="true">
                            {{ $lmDe ? 'Aktiv' : 'Active' }} <span class="lm3-tabzahl" id="lm3-n-aktiv">0</span>
                        </button>
                        <button type="button" role="tab" id="lm3-tab-geplant" aria-selected="false">
                            {{ $lmDe ? 'Geplant' : 'Planned' }} <span class="lm3-tabzahl" id="lm3-n-geplant">0</span>
                        </button>
                        <label class="lm3-filter" id="lm3-filter">
                            <span class="lm-sr">{{ $lmDe ? 'Phase' : 'Phase' }}</span>
                            <select id="lm3-phase"></select>
                        </label>
                    </div>
                    <p class="lm3-meldung" id="lm3-meldung" role="status" hidden></p>
                    <div class="lm3-thead lm3-g-akt" id="lm3-kopf-aktiv" aria-hidden="true">
                        <span>{{ $lmDe ? 'Flug' : 'Flight' }}</span>
                        <span>Route</span>
                        <span class="lm3-sp-alt">{{ $lmDe ? 'Höhe' : 'Alt' }}</span>
                        <span class="lm3-sp-gs">GS</span>
                        <span class="lm3-sp-dist">{{ $lmDe ? 'Distanz' : 'Distance' }}</span>
                        <span class="lm3-sp-phase">Phase</span>
                        <span class="lm3-sp-pilot">Pilot</span>
                    </div>
                    <div class="lm3-thead lm3-g-plan" id="lm3-kopf-geplant" aria-hidden="true" hidden>
                        <span>{{ $lmDe ? 'Flug' : 'Flight' }}</span>
                        <span>Route</span>
                        <span class="lm3-sp-zeit">{{ $lmDe ? 'Abflug' : 'Departure' }}</span>
                        <span class="lm3-sp-pilot">Pilot</span>
                    </div>
                    <div class="lm3-zeilen" id="lm3-liste" role="list"></div>
                </div>
            </section>
        </div>

        <div class="lm3-rechts">
            <div class="lm3-werkzeuge" role="toolbar" aria-label="{{ $lmDe ? 'Werkzeuge' : 'Tools' }}">
                <button type="button" class="lm3-wz" id="lm3-btn-netz" aria-expanded="false" aria-controls="vatsim-box" title="{{ $lmDe ? 'Netzwerk' : 'Network' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    <span class="lm3-wz-text">{{ $lmDe ? 'Netzwerk' : 'Network' }}</span>
                    <span class="vatsim-dot" id="vatsimDot" aria-hidden="true"></span>
                </button>
                <button type="button" class="lm3-wz" id="lm3-btn-wetter" aria-expanded="false" aria-controls="weather-box" title="{{ $lmDe ? 'Wetter' : 'Weather' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18h10a4 4 0 0 0 .6-7.95A6 6 0 0 0 6.1 9.5 4.3 4.3 0 0 0 7 18z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    <span class="lm3-wz-text">{{ $lmDe ? 'Wetter' : 'Weather' }}</span>
                </button>
                <button type="button" class="lm3-wz" id="lm3-btn-karte" aria-expanded="false" aria-controls="lm3-karte-box" title="{{ $lmDe ? 'Kartenstil' : 'Map style' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 8l10 5 10-5-10-5zM2 13l10 5 10-5M2 17.5l10 5 10-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    <span class="lm3-wz-text">{{ $lmDe ? 'Karte' : 'Map' }}</span>
                </button>
                <button type="button" class="lm3-wz vatsim-btn-follow" id="btnFollowFlight" aria-pressed="false" title="{{ $lmDe ? 'Flug folgen' : 'Follow flight' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.5" fill="currentColor"/><path d="M12 1v4M12 19v4M1 12h4M19 12h4" stroke="currentColor" stroke-width="2"/></svg>
                    <span class="lm3-wz-text">{{ $lmDe ? 'Folgen' : 'Follow' }}</span>
                </button>
            </div>

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
                        <div class="map-network-legend-item"><span class="map-network-legend-badge map-network-legend-badge-atis">i</span><span class="map-network-legend-label">{{ $lmDe ? 'nur ATIS' : 'ATIS only' }}</span></div>
                    </div>
                </div>
            </div>

            <div class="lm3-box" id="lm3-karte-box" hidden>
                <div class="lm3-box-titel">{{ $lmDe ? 'Kartenstil' : 'Map style' }}</div>
                <div class="lm3-raster" id="lm3-karten-liste"></div>
                @if($cartoApiKey === '')
                    <p class="lm3-hinweis">{{ $lmDe ? 'CARTO-Stile erscheinen, sobald in der Verwaltung ein CARTO-Schlüssel hinterlegt ist.' : 'CARTO styles appear once a CARTO key is set in the admin.' }}</p>
                @endif
            </div>

            <div class="lm3-box map-weather-box-left" id="weather-box" hidden>
                <div class="lm3-box-titel">{{ $lmDe ? 'Wetter' : 'Weather' }}</div>
                <div id="weather-content">
                    <div class="lm3-raster">
                        <button id="btnClouds" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Wolken' : 'Clouds' }}</button>
                        <button id="btnRadar" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Niederschlag' : 'Radar' }}</button>
                        <button id="btnStorms" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Luftdruck' : 'Pressure' }}</button>
                        <button id="btnWind" type="button" class="lm3-schalter weather-btn">Wind</button>
                        <button id="btnTemp" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Temperatur' : 'Temp' }}</button>
                        <button id="btnCombined" type="button" class="lm3-schalter weather-btn">{{ $lmDe ? 'Kombiniert' : 'Combo' }}</button>
                        <button id="btnDarkMap" type="button" class="lm3-schalter weather-btn lm3-breit">{{ $lmDe ? 'Karte abdunkeln' : 'Dim map' }}</button>
                    </div>
                    <div class="lm3-regler">
                        <label for="weatherOpacity">{{ $lmDe ? 'Deckkraft' : 'Opacity' }}</label>
                        <input type="range" id="weatherOpacity" min="0.2" max="1" step="0.05" value="1">
                    </div>
                </div>
            </div>

            <aside class="lm3-detail" id="lm3-detail" aria-live="polite" hidden></aside>
        </div>
    </div>
</div>
