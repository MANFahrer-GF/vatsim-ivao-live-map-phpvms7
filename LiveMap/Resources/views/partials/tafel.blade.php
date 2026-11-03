{{-- Flugtafel 5.1. Lage laut Verwaltung (acars.livemap_flights_panel):
     oben = aufklappbar ueber der Karte (wie 4.7.9), unten = feste Tabelle
     unter der Karte, ausserhalb der Kartenflaeche (Thomas 03.11.2026:
     „Tabelle unter der map“). Gleiche IDs in beiden Lagen, es gibt immer
     nur eine Tafel. --}}
<section class="lm3-tafel" id="va-flights-panel" aria-label="{{ $lmDe ? 'Flüge' : 'Flights' }}">
    @if($lmTafelUnten)
    <div class="lm3-tafel-kopf" id="lm3-tafel-kopf">
    @else
    <button type="button" class="lm3-tafel-kopf" id="lm3-tafel-kopf" aria-expanded="false" aria-controls="lm3-tafel-inhalt">
    @endif
        <span class="lm3-kopf-stat"><span class="lm3-punkt" id="lm3-status-punkt" aria-hidden="true"></span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z"/></svg>
            {{ $lmDe ? 'Aktive Flüge' : 'Active Flights' }} <b class="lm3-n-aktiv" id="lm3-zahl">—</b></span>
        <span class="lm3-kopf-trenner" aria-hidden="true"></span>
        <span class="lm3-kopf-stat"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 3h-1.2a3 3 0 0 0-5.6 0H8a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-4-.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2zM15 18H9v-1.5h6zm0-4H9v-1.5h6zm0-4H9V8.5h6z"/></svg>
        {{ $lmDe ? 'Geplant' : 'Planned' }} <b class="lm3-n-geplant" id="lm3-zahl-geplant">—</b></span>
    @if($lmTafelUnten)
    </div>
    @else
        <span class="lm3-chevron" aria-hidden="true">▾</span>
    </button>
    @endif
    <div class="lm3-tafel-inhalt" id="lm3-tafel-inhalt" @unless($lmTafelUnten) hidden @endunless>
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
