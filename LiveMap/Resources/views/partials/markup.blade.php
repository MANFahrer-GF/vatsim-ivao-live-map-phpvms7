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
@php($lmTafelUnten = ($liveMapUiConfig['tafelLage'] ?? 'top') === 'bottom')
<div class="live-map-wrapper lm3 {{ $lmTafelUnten ? 'lm3--tafel-unten' : '' }}" id="lm-root" data-quelle="{{ $liveMapUiConfig['quelle'] }}">
    {{-- Die Flaeche traegt die Hoehe; unten haengt die Tafel darunter. --}}
    <div class="lm3-flaeche" id="lm3-flaeche">
    <div id="map" role="region" aria-label="{{ $lmDe ? 'Live-Karte' : 'Live map' }}"></div>

    <div class="lm3-ui">
        <div class="lm3-mitte">
            @if(!$lmTafelUnten)
                @include('livemap::partials.tafel')
            @endif
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
    @if($lmTafelUnten)
        <div class="lm3-unten">
            @include('livemap::partials.tafel')
        </div>
    @endif
    {{-- Dezente Signatur fuer alle Besucher (Thomas 04.11.2026), ohne Spendenlink. --}}
    <p class="lm3-signatur">Crafted with <span aria-hidden="true">&hearts;</span><span class="lm-sr">love</span> in Germany by Thomas Kant</p>
</div>
