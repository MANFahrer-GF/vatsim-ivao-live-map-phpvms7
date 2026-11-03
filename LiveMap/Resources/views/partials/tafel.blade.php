{{-- Flugtafel 5.1. Lage laut Verwaltung (acars.livemap_flights_panel):
     oben = aufklappbar ueber der Karte (wie 4.7.9), unten = feste Tabelle
     unter der Karte, ausserhalb der Kartenflaeche (Thomas 03.11.2026:
     „Tabelle unter der map“). Unten gibt es keinen Aufklapp-Kopf: die
     Reiterzeile ist der Kopf und traegt dessen ID (Thomas 03.11.2026:
     „Hier braucht man die Ausklappsache nicht“). Spaltenkoepfe sortieren.
     Gleiche IDs in beiden Lagen, es gibt immer nur eine Tafel. --}}
<section class="lm3-tafel" id="va-flights-panel" aria-label="{{ $lmDe ? 'Flüge' : 'Flights' }}">
    @unless($lmTafelUnten)
    <button type="button" class="lm3-tafel-kopf" id="lm3-tafel-kopf" aria-expanded="false" aria-controls="lm3-tafel-inhalt">
        <span class="lm3-kopf-stat"><span class="lm3-punkt" id="lm3-status-punkt" aria-hidden="true"></span>
            {{ $lmDe ? 'Aktive Flüge' : 'Active Flights' }} <b class="lm3-n-aktiv" id="lm3-zahl">—</b></span>
        <span class="lm3-kopf-trenner" aria-hidden="true"></span>
        <span class="lm3-kopf-stat">{{ $lmDe ? 'Geplant' : 'Planned' }} <b class="lm3-n-geplant" id="lm3-zahl-geplant">—</b></span>
        <span class="lm3-chevron" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
    </button>
    @endunless
    <div class="lm3-tafel-inhalt" id="lm3-tafel-inhalt" @unless($lmTafelUnten) hidden @endunless>
        <div class="lm3-reiter" role="tablist" @if($lmTafelUnten) id="lm3-tafel-kopf" @endif>
            @if($lmTafelUnten)
                <span class="lm3-punkt" id="lm3-status-punkt" aria-hidden="true"></span>
            @endif
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
        <div class="lm3-thead lm3-g-akt" id="lm3-kopf-aktiv">
            <button type="button" class="lm3-th" data-sort="flug">{{ $lmDe ? 'Flug' : 'Flight' }}</button>
            <button type="button" class="lm3-th" data-sort="route">Route</button>
            <button type="button" class="lm3-th lm3-sp-alt" data-sort="hoehe">{{ $lmDe ? 'Höhe' : 'Alt' }}</button>
            <button type="button" class="lm3-th lm3-sp-gs" data-sort="gs">GS</button>
            <button type="button" class="lm3-th lm3-sp-dist" data-sort="rest">{{ $lmDe ? 'Rest' : 'To go' }}</button>
            <button type="button" class="lm3-th lm3-sp-phase" data-sort="phase">Phase</button>
            <button type="button" class="lm3-th lm3-sp-pilot" data-sort="pilot">Pilot</button>
        </div>
        <div class="lm3-thead lm3-g-plan" id="lm3-kopf-geplant" hidden>
            <button type="button" class="lm3-th" data-sort="flug">{{ $lmDe ? 'Flug' : 'Flight' }}</button>
            <button type="button" class="lm3-th" data-sort="route">Route</button>
            <button type="button" class="lm3-th lm3-sp-zeit" data-sort="zeit">{{ $lmDe ? 'Abflug' : 'Departure' }}</button>
            <button type="button" class="lm3-th lm3-sp-pilot" data-sort="pilot">Pilot</button>
        </div>
        <div class="lm3-zeilen" id="lm3-liste" role="list"></div>
    </div>
</section>
