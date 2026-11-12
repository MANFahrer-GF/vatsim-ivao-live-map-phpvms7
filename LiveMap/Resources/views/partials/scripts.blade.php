<!-- lm3:skripte-anfang -->
{{-- 5.1: Die Karte leiht sich nichts mehr vom Theme. Eigenes Leaflet 1.9.4
     (gepinnt, mit Pruefsumme), sofort mit noConflict() beiseitegelegt: Das
     Theme darf danach sein eigenes `window.L` laden (SPTheme/Seven bringen
     1.8 in vendor.js mit) — die Karte benutzt nur `window.LM_L`.
     Die Skripte stehen bewusst direkt im Widget und nicht in
     dem Skript-Abschnitt des Layouts: ob ein Theme diesen Abschnitt ausgibt, haengt vom
     Theme ab. --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha384-cxOPjt7s7Iz04uaHJceBmS+qpjv2JkIHNVcuOrM+YHwZOmJGBXI00mdUXEq65HTH" crossorigin="anonymous"></script>
<script>
    window.LM_L = (window.L && typeof window.L.noConflict === 'function' && window.L.version === '1.9.4') ? window.L.noConflict() : null;
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

        // Fehler, bei denen der letzte gute Stand stehen bleiben darf (kurze
        // Stoerung). Schluessel/Adresse falsch: nicht — dort waere er irrefuehrend.
        function fehlerIstVoruebergehend(code) {
            return code === 'nicht_erreichbar' || code === 'netz' || code === 'ungueltig' || code === 'phpvms';
        }

        // ── Listen ──
        function filterNachPhase(fluege, phase) {
            if (!phase || phase === 'ALL') return fluege.slice();
            return fluege.filter(function (f) { return f.phase === phase; });
        }
        // Geplante Fluege (phpVMS-Buchungen) ohne die, die schon live fliegen.
        function geplanteOhneAktive(buchungen, aktive) {
            var fliegt = {};
            aktive.forEach(function (f) { if (f.callsign) fliegt[rufzeichenSchluessel(f.callsign)] = true; });
            return buchungen.filter(function (b) {
                var cs = rufzeichenSchluessel(b.callsign);
                return !cs || !fliegt[cs];
            });
        }
        // AeroACARS meldet „ITY 4532“, phpVMS kennt „ITY4532“.
        function rufzeichenSchluessel(cs) {
            return String(cs == null ? '' : cs).toUpperCase().replace(/[^A-Z0-9]/g, '');
        }

        // ── Hoehe wie in der 4.7.9-Tafel: ab 18 000 ft als Flugflaeche ──
        function hoeheText(ft, sprache) {
            if (ft == null || !isFinite(ft)) return '';
            if (ft >= 18000) return 'FL' + ('00' + Math.round(ft / 100)).slice(-3);
            return zahl(ft, sprache) + ' ft';
        }

        // ── Strecke: Grosskreis in nm; geflogen = gesamt − Rest ──
        function distanzNm(lat1, lon1, lat2, lon2) {
            var r = Math.PI / 180;
            var a = Math.sin((lat2 - lat1) * r / 2), b = Math.sin((lon2 - lon1) * r / 2);
            var h = a * a + Math.cos(lat1 * r) * Math.cos(lat2 * r) * b * b;
            return 2 * 3440.065 * Math.asin(Math.min(1, Math.sqrt(h)));
        }
        function gueltigerOrt(o) {
            return !!o && isFinite(o.lat) && isFinite(o.lon) && o.lat !== null && o.lon !== null && !(Math.abs(o.lat) < 0.01 && Math.abs(o.lon) < 0.01);
        }
        function fortschritt(dep, arr, pos) {
            if (!gueltigerOrt(dep) || !gueltigerOrt(arr) || !gueltigerOrt(pos)) return null;
            var gesamt = distanzNm(dep.lat, dep.lon, arr.lat, arr.lon);
            if (!(gesamt > 1)) return null;
            var rest = Math.min(gesamt, distanzNm(pos.lat, pos.lon, arr.lat, arr.lon));
            var geflogen = Math.max(0, gesamt - rest);
            return { gesamt: gesamt, rest: rest, geflogen: geflogen, anteil: geflogen / gesamt };
        }
        // ── ETA: jetzt + Rest / GS (Idee: Elisa, 12.11.2026) ──
        // Grosskreis-Rest, also eine Schaetzung; Anzeige in UTC wie die
        // Flugplanzeiten in phpVMS. Nur ab Reiseflug: im Startlauf und
        // Steigflug ist die GS noch klein und die ETA Stunden zu spaet
        // (Abnahme 5.2.0: 60 kt im Startlauf → 04:56z statt 19:09z).
        var ETA_PHASEN = { CRUISE: 1, HOLDING: 1, DESCENT: 1, APPROACH: 1, FINAL: 1, LANDING: 1 };
        var ETA_MIN_GS = 30;              // darunter keine sinnvolle Rechnung
        var ETA_VERALTET_MS = 10 * 60000; // keine Meldung seit 10 min → keine ETA
        function etaMinuten(rest, gs) {
            if (rest == null || !isFinite(rest) || gs == null || !isFinite(gs) || gs < ETA_MIN_GS) return null;
            return rest / gs * 60;
        }
        // ETA eines Flugs fuer die Tafel: '' wenn offline, veraltet, nicht im
        // Reiseflug oder spaeter. `weg` = fortschritt(...) oder null.
        // Rueckfall nur, wenn die Phase unbekannt ist (fremder ACARS-Client mit
        // eigenem Status): Geradeausflug mit Reisegeschwindigkeit ueber 10 000 ft
        // zaehlt als Reiseflug. Bekannte Start-/Steigphasen nie — auch nicht beim
        // Abfangen im Steigflug (dort waere die GS noch klein, Abnahme 5.2.1).
        function imGeradeausflug(f) {
            var p = String(f.phase || '').toUpperCase(), pos = f.pos || {};
            if (PHASE_LIST.indexOf(p) >= 0 || p === 'ON_BLOCK') return false;
            return pos.gs_kt != null && pos.gs_kt >= 150 && pos.vs_fpm != null && isFinite(pos.vs_fpm) && Math.abs(pos.vs_fpm) <= 500
                && pos.alt_ft != null && pos.alt_ft >= 10000;
        }
        // „Level" im Steigflug unter 5000 ft AGL: Die AeroACARS-Phasen-Engine schaltet
        // dort nie auf Cruise (phase_v2.rs CRUISE_MIN_AGL_FT), ein VFR-Reiseflug (C172
        // auf 2300 ft, GSG 1524 am 12.11.) bliebe sonst ohne ETA. Darueber wird aus
        // „Level" nach 4–10 min von selbst Cruise; ein abfangender Jet bekommt seine
        // ETA erst dann (sonst Stunden zu spaet bei langer Reststrecke).
        function tieferReiseflug(f) {
            var pos = f.pos || {}, agl = pos.agl_ft != null ? pos.agl_ft : pos.alt_ft;
            return phaseForDisplay(f.phase, f.shadow_segment) === 'LEVEL' && String(f.phase).toUpperCase() === 'CLIMB'
                && agl != null && agl < 5000 && pos.gs_kt != null && pos.gs_kt <= 180;
        }
        function etaMinutenFuerFlug(f, weg, jetztMs) {
            if (!f || !weg || f.status === 'offline') return null;
            if (f.last_seen && jetztMs - f.last_seen > ETA_VERALTET_MS) return null;
            var reise = ETA_PHASEN[String(f.phase || '').toUpperCase()] || tieferReiseflug(f);
            if (!reise && !imGeradeausflug(f)) return null;
            return etaMinuten(weg.rest, f.pos ? f.pos.gs_kt : null);
        }
        function etaFuerFlug(f, weg, jetztMs) {
            return etaMinutenFuerFlug(f, weg, jetztMs) == null ? '' : etaText(weg.rest, f.pos.gs_kt, jetztMs);
        }
        function etaText(rest, gs, jetztMs) {
            var min = etaMinuten(rest, gs);
            if (min == null) return '';
            var d = new Date(jetztMs + min * 60000);
            return ('0' + d.getUTCHours()).slice(-2) + ':' + ('0' + d.getUTCMinutes()).slice(-2) + 'z';
        }

        // ── Flugzeugklassen (Form + Groesse des Symbols) ──
        // ICAO-Typ → Klasse nach der Doc-8643-Beschreibung (L1P/L2P, L2T,
        // L2J-Geschaeftsreise, L2J Schmal-/Grossraum, L4J, H…). Unbekannt →
        // Schmalrumpf, der haeufigste Fall auf GSG.
        var KLASSEN = {
            kolben: { symbol: 'k1', px: 20 }, turboprop: { symbol: 'k2', px: 26 }, bizjet: { symbol: 'k3', px: 22 },
            schmal: { symbol: 'k4', px: 28 }, gross: { symbol: 'k5', px: 34 }, vier: { symbol: 'k6', px: 38 },
            heli: { symbol: 'k7', px: 24 }
        };
        var TYP_KLASSE = (function () {
            var t = {}, gruppen = {
                kolben: 'C150 C152 C162 C170 C172 C175 C177 C180 C182 C185 C195 C206 C207 C210 C310 C337 C340 C402 C414 C421 P28A P28B P28R P28T P28U PA18 PA22 PA24 PA28 PA30 PA31 PA32 PA34 PA38 PA44 PA46 P32R SR20 SR22 S22T DA40 DA42 DA50 DA62 DV20 BE19 BE23 BE24 BE33 BE35 BE36 BE55 BE58 BE60 BE76 BE95 M20P M20T J3 CUB PC6P DR40 DR10 RV4 RV6 RV7 RV8 RV10 RV12 RV14 C42 CRUZ G115 EA30 EXTR TB10 TB20 TB21 GA8 DHC2 DHC3 AN2 Y18 LA4 ICON A5 SAVG',
                turboprop: 'AT43 AT44 AT45 AT46 AT72 AT73 AT75 AT76 DH8A DH8B DH8C DH8D DHC4 DHC5 DHC6 DHC7 B190 B350 BE20 BE30 BE9L BE9T BE99 BE10 C208 C212 C295 CN35 C27J C130 C30J L382 PC6T PC7 PC9 PC12 PC21 TBM7 TBM8 TBM9 KODI JS31 JS32 JS41 SF34 SB20 D228 D328 E110 E120 F27 F50 SW3 SW4 AN24 AN26 AN28 AN30 AN32 L410 Y12 P180 P46T M600 AC90 AC95 MU2 G21 G73T AT8T AT5T BN2T',
                bizjet: 'C25A C25B C25C C25M C500 C501 C510 C525 C526 C550 C551 C560 C56X C650 C680 C68A C700 C750 CL30 CL35 CL60 GL5T GL6T GL7T GL8T GLEX GLF2 GLF3 GLF4 GLF5 GLF6 G150 G280 GALX E50P E55P E545 E550 LJ23 LJ24 LJ25 LJ31 LJ35 LJ40 LJ45 LJ55 LJ60 LJ70 LJ75 FA10 FA20 FA50 FA7X FA8X FA6X F2TH F900 H25A H25B H25C HDJT PC24 SF50 PRM1 BE40 ASTR WW24 SBR1 SBR2 J328 EA50 ELIT VSJT',
                gross: 'A306 A30B A310 A332 A333 A337 A338 A339 A359 A35K B762 B763 B764 B772 B773 B77L B77W B778 B779 B788 B789 B78X DC10 MD11 L101 A3ST BLCF IL86 KC10 K35E',
                vier: 'A342 A343 A345 A346 A388 A400 B741 B742 B743 B744 B748 B74D B74R B74S B701 B703 K35R E3TF E3CF E6 DC8 DC86 DC87 VC10 IL62 IL76 IL96 A124 A225 C17 C5 C5M B52 CONC B461 B462 B463 RJ70 RJ85 RJ1H P3 L188 C135 KC135 AN22 TU95 TU44',
                heli: 'H125 H130 H135 H145 H155 H160 H175 H225 EC20 EC25 EC30 EC35 EC45 EC55 EC75 AS32 AS50 AS55 AS65 AS3B B06 B06T B105 B212 B222 B230 B407 B412 B427 B429 B430 B47G B505 R22 R44 R66 S61 S64 S76 S92 A109 A119 A129 A139 A149 A169 A189 AW09 EH10 UH1 UH60 H60 H47 H53 MI8 MI17 MI24 MI26 KA32 G2CA CABR NH90 TIGR LYNX PUMA'
            };
            Object.keys(gruppen).forEach(function (k) { gruppen[k].split(' ').forEach(function (typ) { if (typ) t[typ] = k; }); });
            return t;
        })();
        function flugzeugKlasse(icao) {
            var k = String(icao == null ? '' : icao).toUpperCase().trim();
            return Object.prototype.hasOwnProperty.call(TYP_KLASSE, k) ? TYP_KLASSE[k] : 'schmal';
        }
        function klassenInfo(klasse) { return KLASSEN[klasse] || KLASSEN.schmal; }
        // Am Boden etwas kleiner, damit sich ein voller Flughafen nicht stapelt.
        var BODEN_PHASEN = { PREFLIGHT: 1, BOARDING: 1, PUSHBACK: 1, TAXI_OUT: 1, TAXI_IN: 1, BLOCKS_ON: 1, ON_BLOCK: 1, ARRIVED: 1, PIREP_SUBMITTED: 1 };
        function amBoden(phase) { return !!BODEN_PHASEN[String(phase || '').toUpperCase()]; }
        // ── Sortierung der Tafel (Spaltenkoepfe) ──
        // Standard „phase“: Flugreihenfolge (Preflight … Arrived), in der Phase
        // nach Rest-Strecke. Offline immer unten, fehlende Werte immer hinten,
        // Rufzeichen mit Zahlen natuerlich (GSG7 vor GSG101). `weg(f)` liefert
        // die Strecke ({rest}) oder null — die Flughaefen kennt nur das Widget.
        var SORT_WERT = {
            flug: function (f) { return f.callsign || f.id; },
            route: function (f) { return (f.dep || '') + ' ' + (f.arr || ''); },
            typ: function (f) { return f.aircraft_icao; },
            hoehe: function (f) { return f.pos ? f.pos.alt_ft : null; },
            gs: function (f) { return f.pos ? f.pos.gs_kt : null; },
            pilot: function (f) { return f.pilot_name || f.pilot; },
            zeit: function (f) { return f.zeit; }
        };
        function phasenRang(p) {
            p = String(p || '').toUpperCase();
            if (p === 'ON_BLOCK') p = 'BLOCKS_ON';
            var i = PHASE_LIST.indexOf(p);
            return i < 0 ? PHASE_LIST.length : i;
        }
        function vergleicheWert(a, b) {
            var leerA = a == null || a === '' || (typeof a === 'number' && !isFinite(a));
            var leerB = b == null || b === '' || (typeof b === 'number' && !isFinite(b));
            if (leerA || leerB) return leerA === leerB ? 0 : (leerA ? 2 : -2);   // ±2: hinten, egal welche Richtung
            var c = (typeof a === 'number' && typeof b === 'number') ? a - b
                : String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' });
            return c < 0 ? -1 : (c > 0 ? 1 : 0);
        }
        function sortiereNach(fluege, schluessel, richtung, weg) {
            var rest = function (f) { var w = weg ? weg(f) : null; return w ? w.rest : null; };
            var dreh = richtung === 'ab' ? -1 : 1;
            var stufe = function (c) { return Math.abs(c) === 2 ? c : dreh * c; };
            var nachRufzeichen = function (a, b) { return vergleicheWert(SORT_WERT.flug(a), SORT_WERT.flug(b)); };
            return fluege.slice().sort(function (a, b) {
                var off = (a.status === 'offline') - (b.status === 'offline');
                if (off) return off;
                var c;
                if (schluessel === 'phase') {
                    c = dreh * (phasenRang(a.phase) - phasenRang(b.phase));
                    if (!c) c = vergleicheWert(rest(a), rest(b));
                    if (Math.abs(c) === 2) return c;
                } else if (schluessel === 'rest') {
                    c = stufe(vergleicheWert(rest(a), rest(b)));
                } else if (schluessel === 'eta') {
                    var jetzt = Date.now();
                    var eta = function (f) { return etaMinutenFuerFlug(f, weg ? weg(f) : null, jetzt); };
                    c = stufe(vergleicheWert(eta(a), eta(b)));
                } else {
                    c = stufe(vergleicheWert((SORT_WERT[schluessel] || SORT_WERT.flug)(a), (SORT_WERT[schluessel] || SORT_WERT.flug)(b)));
                }
                return c || nachRufzeichen(a, b);
            });
        }

        // ── VATSIM/IVAO-Lotsen: Regeln nach dem Verfahren von VATSIM Radar ──
        // (Vorlage aeroacars-live recorder/src/atcKarte.ts; Regeln CC BY-NC 4.0).
        // Beobachter melden sich auf 199.998; nur 117–137 MHz ist Sprechfunk.
        function aufSprechfrequenz(f) {
            var khz = Math.round(parseFloat(f) * 1000);
            return isFinite(khz) && khz >= 117000 && khz <= 137000;
        }
        function erstesWort(t) { return String(t).split(/[_-]/)[0]; }
        // VATSpy.dat auswerten. Platz-Schluessel: Funk-Praefix vor ICAO, echter
        // Platz vor Behelf (so loest CLT_GND nach KCLT auf, wie KCLT_ATIS).
        function vatspyLesen(text) {
            var plaetze = {}, namen = {}, firNachWort = {}, uirNachWort = {}, firNamen = {}, roh = [], abschnitt = '';
            String(text || '').split(/\r?\n/).forEach(function (zeile) {
                zeile = zeile.trim();
                if (!zeile || zeile.charAt(0) === ';') return;
                if (zeile.charAt(0) === '[') { abschnitt = zeile.toUpperCase(); return; }
                var t = zeile.split('|');
                if (abschnitt === '[AIRPORTS]') {
                    if (t.length < 4) return;
                    var icao = t[0].trim().toUpperCase(), lat = parseFloat(t[2]), lon = parseFloat(t[3]);
                    if (!icao || !isFinite(lat) || !isFinite(lon)) return;
                    if (t[1] && !namen[icao]) namen[icao] = t[1].trim();
                    roh.push({ icao: icao, lat: lat, lon: lon, praefix: (t[4] || '').trim().toUpperCase(), behelf: (t[6] || '').trim() === '1' });
                } else if (abschnitt === '[FIRS]') {
                    if (t.length < 4) return;
                    var e = { icao: t[0].trim().toUpperCase(), name: (t[1] || '').trim(), praefix: (t[2] || '').trim().toUpperCase(), grenze: (t[3] || '').trim().toUpperCase() };
                    if (!e.grenze) return;
                    if (e.name) { if (!firNamen[e.icao]) firNamen[e.icao] = e.name; if (e.praefix && !firNamen[e.praefix]) firNamen[e.praefix] = e.name; }
                    var schon = {};
                    [erstesWort(e.icao), e.praefix ? erstesWort(e.praefix) : ''].forEach(function (k) {
                        if (!k || schon[k]) return;
                        schon[k] = 1;
                        (firNachWort[k] = firNachWort[k] || []).push(e);
                    });
                } else if (abschnitt === '[UIRS]') {
                    if (t.length < 3) return;
                    var uid = t[0].trim().toUpperCase();
                    var firs = t[2].toUpperCase().split(',').map(function (x) { return x.trim(); }).filter(Boolean);
                    if (!uid || !firs.length) return;
                    (uirNachWort[erstesWort(uid)] = uirNachWort[erstesWort(uid)] || []).push({ icao: uid, name: (t[1] || '').trim(), firs: firs });
                }
            });
            function belege(schluessel, nimm) {
                roh.forEach(function (p) {
                    if (!nimm(p)) return;
                    var k = schluessel(p);
                    if (k && !plaetze[k]) plaetze[k] = [p.lat, p.lon];
                });
            }
            belege(function (p) { return p.praefix; }, function (p) { return p.praefix && !p.behelf; });
            belege(function (p) { return p.praefix; }, function (p) { return p.praefix && p.behelf; });
            belege(function (p) { return p.icao; }, function (p) { return !p.behelf; });
            belege(function (p) { return p.icao; }, function () { return true; });
            return { plaetze: plaetze, namen: namen, firNachWort: firNachWort, uirNachWort: uirNachWort, firNamen: firNamen };
        }
        // Laengster Eintrag, dessen Kennung am Anfang des Rufzeichens steht; ein
        // exakter Treffer auf das Rufzeichen ohne letztes Wort schlaegt alles.
        // Gleich gute Treffer alle (LPPO_CTR: Ozean-FIR und Azoren-Zone).
        function firTreffer(rufzeichen, firNachWort) {
            var ruf = String(rufzeichen || '').toUpperCase(), teile = ruf.split(/[_-]/);
            var mitte = teile.slice(0, -1).join('_'), beste = -1, treffer = [];
            (firNachWort[teile[0]] || []).forEach(function (e) {
                var kennung = e.praefix || e.icao;
                if (ruf.indexOf(kennung) !== 0 && ruf.indexOf(e.icao) !== 0) return;
                var laenge = (e.praefix === mitte || e.icao === mitte) ? 100 : kennung.length;
                if (laenge > beste) { beste = laenge; treffer = [e]; }
                else if (laenge === beste) treffer.push(e);
            });
            return treffer;
        }
        // Grenz-Kennungen eines Center-/FSS-Lotsen. Sammelposition (GULF, CARI)
        // vor Einzel-FIR. Kein Frequenzriegel: VATSIM Radar zeigt Sektoren nach
        // der Rufzeichen-Endung (LPPC_X1_CTR).
        function grenzenFuerLotse(rufzeichen, firNachWort, uirNachWort) {
            var ruf = String(rufzeichen || '').toUpperCase(), wort = erstesWort(ruf);
            // Laengste passende Sammelposition (AFRE_E vor AFRE).
            var uir = null;
            (uirNachWort[wort] || []).forEach(function (u) {
                if (ruf.indexOf(u.icao) === 0 && (!uir || u.icao.length > uir.icao.length)) uir = u;
            });
            if (uir) return { kennungen: uir.firs.slice(), name: uir.name, uir: true };
            var t = firTreffer(ruf, firNachWort);
            var kennungen = [];
            t.forEach(function (e) { if (kennungen.indexOf(e.grenze) === -1) kennungen.push(e.grenze); });
            return { kennungen: kennungen, name: t.length ? t[0].name : '', uir: false };
        }
        // Sechs FIRs fuehren zwei Flaechen (kontinental/ozeanisch); _FSS betreut den Ozean.
        function firFlaeche(liste, rufzeichen) {
            if (!liste || !liste.length) return null;
            if (liste.length === 1) return liste[0];
            var willOzean = /_FSS$/i.test(String(rufzeichen || ''));
            for (var i = 0; i < liste.length; i++) {
                var p = liste[i].properties || {};
                if ((String(p.oceanic == null ? '0' : p.oceanic) === '1') === willOzean) return liste[i];
            }
            return liste[0];
        }

        // Hoehe der Kartenflaeche: Fenster minus was ueber der Karte steht, was unten
        // fest am Fenster klebt (Cookie-Leiste) und 16 px Luft. Tafel unten teilt sich
        // den Platz: wenige Fluege, grosse Karte; viele Fluege, Karte mindestens 60 %
        // und die Liste geht auf der Seite weiter. Danach Mindest-/Hoechsthoehe.
        function kartenHoehe(p) {
            var h = p.fenster - Math.max(0, p.oben || 0) - (p.untenFest || 0) - 16;
            if (p.tafel != null) h = Math.max(h - p.tafel, h * 0.6);
            return Math.round(Math.max(p.min || 420, Math.min(p.max || 1000, h)));
        }
        // Polster des Themes unter dem Widget auf 16 px schliessen (SPTheme liess
        // 93 px leer bis zur Fusszeile, Thomas 12.11.2026): neuer Aussenabstand
        // unten fuer die Huelle des Widgets.
        function lueckenAusgleich(luecke, abstand) {
            return luecke > 16 ? abstand - (luecke - 16) : abstand;
        }
        // Nach dem Setzen nachgemessen: nur so viel Ausgleich behalten, wie der Kasten
        // darunter wirklich nachgerueckt ist (rueckt er nicht, bleibt der alte Abstand).
        function wirksamerAusgleich(abstand, soll, lueckeVorher, lueckeNachher) {
            var gerueckt = lueckeVorher - lueckeNachher;
            if (gerueckt < 1) return abstand;
            return Math.max(soll, abstand - gerueckt);
        }

        wurzel.LM3 = {
            kartenHoehe: kartenHoehe, lueckenAusgleich: lueckenAusgleich, wirksamerAusgleich: wirksamerAusgleich,
            PHASE_COLOR: PHASE_COLOR, PHASE_LIST: PHASE_LIST, PHASE_LABEL: PHASE_LABEL,
            phaseColor: phaseColor, phaseLabel: phaseLabel, phaseForDisplay: phaseForDisplay,
            formatSimLabel: formatSimLabel, fmtQnhBoth: fmtQnhBoth, ageString: ageString, zahl: zahl,
            vsText: vsText, apFlags: apFlags, tonnen: tonnen,
            neben: neben, fortlaufend: fortlaufend, verschiebeNahe: verschiebeNahe,
            fehlerIstVoruebergehend: fehlerIstVoruebergehend, filterNachPhase: filterNachPhase, geplanteOhneAktive: geplanteOhneAktive, sortiereNach: sortiereNach, phasenRang: phasenRang,
            rufzeichenSchluessel: rufzeichenSchluessel, hoeheText: hoeheText, distanzNm: distanzNm, fortschritt: fortschritt,
            etaMinuten: etaMinuten, etaText: etaText, etaFuerFlug: etaFuerFlug,
            KLASSEN: KLASSEN, flugzeugKlasse: flugzeugKlasse, klassenInfo: klassenInfo, amBoden: amBoden,
            aufSprechfrequenz: aufSprechfrequenz, vatspyLesen: vatspyLesen, firTreffer: firTreffer,
            grenzenFuerLotse: grenzenFuerLotse, firFlaeche: firFlaeche
        };
    })(typeof window !== 'undefined' ? window : globalThis);
    /* </lm3-rein> */
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ab hier nur das eigene Leaflet (siehe Kopf), nie das des Themes.
            var L = window.LM_L;
            if (!L) { console.error('[LiveMap] eigenes Leaflet 1.9.4 nicht geladen — Karte kann nicht starten'); return; }
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
            // jsDelivr statt raw.githubusercontent.com: Browser-Puffer 7 Tage statt 5 min.
            var VATSPY_BOUNDS_API = 'https://cdn.jsdelivr.net/gh/vatsimnetwork/vatspy-data-project@master/Boundaries.geojson';
            var VATSPY_DAT_API    = 'https://cdn.jsdelivr.net/gh/vatsimnetwork/vatspy-data-project@master/VATSpy.dat';
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
            var vatspyFir        = { firNachWort: {}, uirNachWort: {} };
            var letzteIvaoFirMap = null;   // fuer sofortiges Zeichnen beim Einschalten der Sektoren
            var grenzenNachId    = null;   // Boundaries-id -> [Feature, ...] (kontinental/ozeanisch)

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

            // Netzwerk-Piloten (VATSIM/IVAO): dieselbe Schmalrumpf-Silhouette,
            // grau und kleiner, damit die eigenen VA-Fluege herausstechen.
            function netzFliegerIcon(heading, ivao) {
                var hdg = parseFloat(heading);
                if (!isFinite(hdg)) hdg = 0;
                return L.divIcon({
                    html: '<span class="lm3-netz-flieger' + (ivao ? ' ist-ivao' : '') + '" style="display:block;width:18px;height:18px;transform:rotate(' + Math.round(hdg) + 'deg)">' +
                        '<svg viewBox="0 0 64 64" width="18" height="18" style="stroke-width:3.6"><use href="#lm-k4"/></svg></span>',
                    className: '', iconSize: [18, 18], iconAnchor: [9, 9]
                });
            }
            function buildAircraftIcon(heading) { return netzFliegerIcon(heading, false); }
            function buildIvaoAircraftIcon(heading) { return netzFliegerIcon(heading, true); }

            function buildAirportCtrlIcon(icao, ctrlList, atisList) {
                var TYPES = { 0:{short:'O',color:'#7f8c8d'}, 2:{short:'D',color:'#3498db'}, 3:{short:'G',color:'#e67e22'}, 4:{short:'T',color:'#e74c3c'}, 5:{short:'A',color:'#27ae60'} };
                var order = [2,3,4,0,5];
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
                var order={2:1,3:2,4:3,0:4,5:5};
                ctrlList=ctrlList.slice().sort(function(a,b){return(order[a.facility]||9)-(order[b.facility]||9);});
                var BADGE={0:{label:'OBS',color:'#7f8c8d'},2:{label:'DEL',color:'#2980b9'},3:{label:'GND',color:'#d35400'},4:{label:'TWR',color:'#c0392b'},5:{label:'APP',color:'#27ae60'}};
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
                if(!grenzenNachId){
                    grenzenNachId={};
                    firBoundsGeoJson.features.forEach(function(feature){
                        var id=String((feature.properties||{}).id||feature.id||'').toUpperCase();
                        if(id)(grenzenNachId[id]=grenzenNachId[id]||[]).push(feature);
                    });
                }
                var claimedFeatures={};
                var ctrlFeatureMap={};
                var schonGezeichnet=[];
                function _norm(s){return s.replace(/-/g,'_');}
                Object.keys(activeFirMap).forEach(function(mapKey){
                    var info=activeFirMap[mapKey];
                    var root=info.root||mapKey.split('_')[0];
                    var isSubKey=mapKey.indexOf('_')!==-1;
                    var resolvedRoot=firPrefixMap[root]||root;
                    var features=[];
                    /* Radar-Verfahren: VATSpy-Grenzkennungen (laengster Praefix, Sammelposition, _FSS = Ozean) */
                    (info.kennungen||[]).forEach(function(k){
                        var ft=window.LM3.firFlaeche(grenzenNachId[String(k).toUpperCase()],info.callsign);
                        if(ft&&features.indexOf(ft)===-1)features.push(ft);
                    });
                    if(features.length&&info.firName&&!firNameCache[root])firNameCache[root]=info.firName;
                    /* Rueckfall: die bisherige Grob-Zuordnung, wenn VATSpy keinen Eintrag kennt */
                    /* Phase 1: exact sub-key match (sectored callsigns like UNKL_N_CTR) */
                    if(features.length===0&&isSubKey){var f=featureById[mapKey]||featureById[mapKey.replace(/_/g,'-')];if(f){features=[f];claimedFeatures[mapKey]=true;claimedFeatures[mapKey.replace(/_/g,'-')]=true;}}
                    /* Phase 2: broad normalised search — runs for ALL keys when Phase 1 missed */
                    if(features.length===0){var nR=_norm(root),nRe=_norm(resolvedRoot);Object.keys(featureById).forEach(function(fK){if(claimedFeatures[fK])return;var nk=_norm(fK),ft=featureById[fK];if(nk===nRe||nk===nR||nk==='K'+nR||nR==='K'+nk){if(features.indexOf(ft)===-1)features.push(ft);}});}
                    /* Phase 3: startsWith fallback for sub-sector GeoJSON IDs (e.g. UNKL-1) */
                    if(features.length===0){var nR2=_norm(root),nRe2=_norm(resolvedRoot);Object.keys(featureById).forEach(function(fK){if(claimedFeatures[fK])return;var nk=_norm(fK),ft=featureById[fK];if(nk.indexOf(nRe2+'_')===0||nk.indexOf(nR2+'_')===0){if(features.indexOf(ft)===-1)features.push(ft);}});}
                    /* Phase 4: UIR expansion — resolve UIR (e.g. RU-SC) to constituent FIR polygons */
                    if(features.length===0){var uirFirs=uirToFirsMap[root]||uirToFirsMap[resolvedRoot]||uirToFirsMap[_norm(root)]||null;if(uirFirs){uirFirs.forEach(function(fid){var nfid=_norm(fid);var ft=featureById[nfid]||featureById[fid];if(ft&&features.indexOf(ft)===-1)features.push(ft);});if(features.length)info.isUpper=true;}}
                    features=features.filter(function(ft){return schonGezeichnet.indexOf(ft)===-1;});
                    if(features.length>0){ctrlFeatureMap[mapKey]=features;schonGezeichnet=schonGezeichnet.concat(features);}
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
                        icon:L.divIcon({html:'<div style="background:'+color+';color:#fff;padding:3px 9px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.5px;box-shadow:0 2px 5px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5);white-space:nowrap;text-align:center">'+shortSafe+'<br><span style="font-size:9px;font-weight:400;opacity:0.85">'+firLabelSafe+'</span></div>',className:'lm3-sektor-etikett',iconSize:[labelW,labelH],iconAnchor:[labelW/2,labelH/2]}),
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
                // Netz-Zusatz "IVAO" statt Rahmen: steht ein VATSIM-Kasten daneben, bleiben beide unterscheidbar.
                var w=Math.max((Object.keys(counts).length+1)*18,icao.length*7+30,30)+16,hgt=36;
                return L.divIcon({html:'<div style="width:'+w+'px;height:'+hgt+'px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer"><span style="font-size:9px;font-weight:700;color:#1a1a1a;text-shadow:0 0 3px #fff,0 0 3px #fff;letter-spacing:.3px;line-height:1">'+h(icao)+' <span style="font-size:7px;font-weight:800;color:#5b6472">IVAO</span></span><div style="display:flex;gap:2px;align-items:center">'+dots+'</div></div>',className:'ivao-airport-marker',iconSize:[w,hgt],iconAnchor:[w/2,hgt/2]});
            }

            function buildAirportCtrlPopupIvao(icao, ctrlList, atisList) {
                var order={2:1,3:2,4:3,0:4,5:5};
                ctrlList=ctrlList.slice().sort(function(a,b){return(order[a.facility]||9)-(order[b.facility]||9);});
                var BADGE={0:{label:'OBS',color:'#7f8c8d'},2:{label:'DEL',color:'#2980b9'},3:{label:'GND',color:'#d35400'},4:{label:'TWR',color:'#c0392b'},5:{label:'APP',color:'#27ae60'}};
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

            // Abruf mit Zeitgrenze bis einschliesslich Antwortkoerper: ein haengender Feed
            // blockiert sonst den naechsten Takt (vatsimFetchInFlight bliebe gesetzt).
            // Nicht-200 zaehlt als Fehler. art: 'json' oder 'text'.
            function holeMitFrist(url, ms, art) {
                var ab = (typeof AbortController !== 'undefined') ? new AbortController() : null;
                var t = ab ? setTimeout(function(){ ab.abort(); }, ms || 20000) : null;
                return fetch(url, ab ? { signal: ab.signal } : undefined).then(function(r){
                    if (!r.ok) throw new Error('HTTP ' + r.status + ' ' + url);
                    return art === 'text' ? r.text() : r.json();
                }).finally(function(){ if (t) clearTimeout(t); });
            }
            var firNamesLauf = null, grenzenLauf = null;
            // Nach einem Fehlschlag 5 min Pause: die Dateien sind 2,3 und 0,9 MB gross.
            var PAUSE_NACH_FEHLER_MS = 5 * 60 * 1000, grenzenNichtVor = 0, firNamenNichtVor = 0;
            // Boundaries.geojson einmal laden.
            function ladeGrenzen() {
                if (firBoundsGeoJson) return Promise.resolve();
                if (grenzenLauf) return grenzenLauf;
                if (Date.now() < grenzenNichtVor) return Promise.reject(new Error('Boundaries: Pause nach Fehlschlag'));
                grenzenLauf = holeMitFrist(VATSPY_BOUNDS_API, 60000, 'json').then(function(gj){firBoundsGeoJson=gj;grenzenNachId=null;})
                    .catch(function(e){ grenzenNichtVor = Date.now() + PAUSE_NACH_FEHLER_MS; throw e; })
                    .finally(function(){ grenzenLauf = null; });
                return grenzenLauf;
            }
            function loadFirNames() {
                if (firNameLoaded || Date.now() < firNamenNichtVor) return Promise.resolve();
                if (firNamesLauf) return firNamesLauf;
                return firNamesLauf = holeMitFrist(VATSPY_DAT_API, 60000, 'text').then(function(text){
                    firNameLoaded=true; staticAirportLoaded=true;
                    var section='';
                    text.split('\n').forEach(function(line){
                        line=line.trim();
                        if(!line||line.startsWith(';')) return;
                        if(line.startsWith('[')){section=line.replace(/[\[\]]/g,'').toLowerCase();return;}
                        var parts=line.split('|');
                        if(section==='firs'&&parts.length>=2){
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
                    // Plaetze und FIR-Zuordnung nach dem Radar-Verfahren (reiner Block, getestet).
                    var v=window.LM3.vatspyLesen(text);
                    staticAirportPos=v.plaetze; airportNameCache=v.namen;
                    Object.keys(v.firNamen).forEach(function(k){if(!firNameCache[k])firNameCache[k]=v.firNamen[k];});
                    vatspyFir=v;
                }).catch(function(e){firNameLoaded=false;firNamenNichtVor=Date.now()+PAUSE_NACH_FEHLER_MS;console.warn('[VATSIM] VATSpy.dat nicht geladen, neuer Versuch in 5 min:',e);}).finally(function(){firNamesLauf=null;});
            }

            function loadTransceivers() {
                return holeMitFrist(VATSIM_TRX_API, 20000, 'json').then(function(trxList){
                    ctrlPosCache={};
                    (trxList||[]).forEach(function(entry){
                        if(!entry.callsign||!entry.transceivers||!entry.transceivers.length) return;
                        var trx=entry.transceivers[0],lat=parseFloat(trx.latDeg),lon=parseFloat(trx.lonDeg);
                        if(!isNaN(lat)&&!isNaN(lon)&&(Math.abs(lat)>0.001||Math.abs(lon)>0.001)) ctrlPosCache[entry.callsign.toUpperCase()]=[lat,lon];
                    });
                }).catch(function(err){console.warn('[VATSIM] Transceivers nicht geladen:',err);});
            }

            // Platz zu einem Rufzeichen-Praefix (VATSpy: Funk-Praefix vor ICAO), sonst null.
            function platzZu(prefix) { return staticAirportPos[prefix] || null; }
            // Gruppen-Schluessel = aufgeloeste Stelle, nicht der Praefix: CLT_GND und
            // KCLT_ATIS gehoeren in denselben Kasten. Anzeige: der laengste Code (ICAO).
            function platzSchluessel(prefix) {
                var k = platzZu(prefix);
                return k ? '@' + k[0].toFixed(5) + ',' + k[1].toFixed(5) : prefix;
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
                    holeMitFrist(VATSIM_DATA_API, 20000, 'json'),
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
                    var atisGroups={}, airportGroups={}, centerList=[], anzeige={};
                    // Anzeigename je Platz-Gruppe: der laengste Praefix (KCLT vor CLT).
                    function merkeName(key,raw){if(!anzeige[key]||raw.length>anzeige[key].length)anzeige[key]=raw;}

                    atisRaw.forEach(function(a){
                        // ATIS ausserhalb 117–137 MHz (VOR-ATIS, Beobachter) zaehlt nicht, wie VATSIM Radar.
                        if(!window.LM3.aufSprechfrequenz(a.frequency)) return;
                        var raw=a.callsign.split('_')[0].toUpperCase(), key=platzSchluessel(raw);
                        var pos=platzZu(raw)||ctrlPosCache[a.callsign.toUpperCase()];
                        if(!pos) return;
                        merkeName(key,raw);
                        if(!atisGroups[key]) atisGroups[key]={pos:pos,list:[]};
                        atisGroups[key].list.push(a);
                    });

                    controllers.forEach(function(c){
                        var prefix=c.callsign.split('_')[0].toUpperCase();
                        // Beobachter nur, wenn er auf Sprechfrequenz an einem echten Platz sitzt (ADD_OBS 122.575).
                        if(c.facility===0&&!(window.LM3.aufSprechfrequenz(c.frequency)&&platzZu(prefix))) return;
                        var pos=platzZu(prefix)||ctrlPosCache[c.callsign.toUpperCase()];
                        /* CTR/FSS without position: keep for FIR sector matching (polygon doesn't need pos) */
                        if(!pos&&(c.facility===6||c.facility===1)){centerList.push({ctrl:c,pos:null});return;}
                        if(!pos) return;
                        if(c.facility===6||c.facility===1){centerList.push({ctrl:c,pos:pos});}
                        else{
                            var raw=c.callsign.split('_')[0].toUpperCase();
                            if(!platzZu(raw)){centerList.push({ctrl:c,pos:pos,isTracon:true});}
                            else{var key=platzSchluessel(raw);merkeName(key,raw);if(!airportGroups[key])airportGroups[key]={pos:platzZu(raw),ctrls:[]};airportGroups[key].ctrls.push(c);}
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

                    Object.keys(allAirportKeys).forEach(function(key){
                        var icao=anzeige[key]||key;
                        var group=airportGroups[key]||{pos:atisGroups[key].pos,ctrls:[]};
                        var atisList=atisGroups[key]?atisGroups[key].list:[];
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
                            var traconIcon=L.divIcon({html:'<div style="display:flex;flex-direction:column;align-items:center;white-space:nowrap;pointer-events:auto"><div style="background:'+color+';color:#fff;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:.5px;box-shadow:0 1px 4px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.5);line-height:1.4">'+h(root)+'</div><div style="width:4px;height:4px;border-radius:50%;background:'+color+';margin-top:2px"></div></div>',className:'lm3-tracon',iconSize:[root.length*8+16,26],iconAnchor:[(root.length*8+16)/2,13]});
                            L.marker(entry.pos,{icon:traconIcon,title:c.callsign,zIndexOffset:400}).bindPopup('<div class="vatsim-popup"><div class="vatsim-popup-header"><div class="vatsim-popup-callsign">'+h(c.callsign||'—')+'</div><div class="vatsim-popup-route">TRACON / Approach Control</div></div><div class="vatsim-popup-body">'+vRow('Frequency',safeFreq(c.frequency||'') || '—')+ctrlInfoLine(c)+'</div></div>',{maxWidth:260}).addTo(vatsimCtrlLayer);
                        } else {
                            var mapKey=parts.length>=3?root+'_'+parts[1].toUpperCase():root;
                            var g=window.LM3.grenzenFuerLotse(c.callsign,vatspyFir.firNachWort,vatspyFir.uirNachWort);
                            if(!activeFirMap[mapKey])activeFirMap[mapKey]={callsign:c.callsign,frequency:c.frequency,name:c.name,cid:c.cid,rating:c.rating,logon_time:c.logon_time,visual_range:c.visual_range,color:g.uir?'#8e44ad':color,isUpper:isUpper||g.uir,root:root,network:'VATSIM',kennungen:g.kennungen,firName:g.name};
                        }
                    });

                    // Kein Leeren hier: renderActiveSectors leert selbst, erst wenn die Grenzen da sind.
                    // Schlaegt der Abruf fehl, bleibt der letzte Stand stehen; der naechste Takt holt neu.
                    Promise.all([ladeGrenzen(),loadFirNames()]).then(function(){if(firBoundsGeoJson)renderActiveSectors(activeFirMap);}).catch(function(e){console.warn('[VATSIM] Sektoren-Fehler:',e);});

                    if(typeof applyLayerVisibility==='function') applyLayerVisibility();
                    var statsEl=document.getElementById('vatsimStats'), dotEl=document.getElementById('vatsimNetDot');
                    if(statsEl) statsEl.textContent='\u2708'+pilots.length+'  \uD83C\uDFA7'+ctrlDone;
                    if(dotEl)   { dotEl.classList.add('live'); dotEl.classList.remove('fehler'); }
                    return true;
                }).catch(function(err){
                    console.error('[VATSIM] Fehler:',err);
                    var dv=document.getElementById('vatsimNetDot'); if(dv){ dv.classList.remove('live'); dv.classList.add('fehler'); }
                    return false;
                }).finally(function() {
                    vatsimFetchInFlight = false;
                });
            }

            function loadIvao(map) {
                if (ivaoFetchInFlight) return Promise.resolve(false);
                ivaoFetchInFlight = true;
                // VATSpy auch hier: Plaetze und FIR-Zuordnung gelten fuer IVAO genauso.
                return Promise.all([holeMitFrist(IVAO_DATA_API, 20000, 'json'),loadFirNames()]).then(function(res){
                    var data=res[0];
                    var clients=data.clients||{}, pilots=clients.pilots||[], atcs=clients.atcs||[];
                    var statsEl=document.getElementById('ivaoStats'), dotEl=document.getElementById('ivaoNetDot');
                    if(statsEl) statsEl.textContent='\u2708'+pilots.length+'  \uD83C\uDFA7'+atcs.length;
                    if(dotEl)   { dotEl.classList.add('live'); dotEl.classList.remove('fehler'); }
                    if(!showIvao) return true;

                    ivaoPilotsLayer.clearLayers(); ivaoCtrlLayer.clearLayers();
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
                            var csTeile=cs.split('_'), root=csTeile[0].toUpperCase(), isUpper=!!UPPER_FIR[root];
                            // Teilsektoren getrennt (LON_N_CTR / LON_S_CTR), wie bei VATSIM
                            var ivaoKey=csTeile.length>=3?root+'_'+csTeile[1].toUpperCase():root;
                            if(!ivaoFirMap[ivaoKey]){var _vid=c.userId||'', g=window.LM3.grenzenFuerLotse(cs,vatspyFir.firNachWort,vatspyFir.uirNachWort); ivaoFirMap[ivaoKey]={callsign:cs,frequency:freq,color:(isUpper||g.uir)?'#8e44ad':'#16a085',isUpper:isUpper||g.uir,root:root,network:'IVAO',cid:_vid,cid_link:_vid?'https://tracker.ivao.aero/atc/'+_vid:'',rating:c.rating||0,logon_time:c.createdAt||'',kennungen:g.kennungen,firName:g.name}; }
                        } else if(!isNaN(lat)&&!isNaN(lon)){
                            // nach aufgeloester Stelle wie bei VATSIM (CLT_GND + KCLT_TWR = ein Kasten)
                            var icaoRaw=cs.split('_')[0].toUpperCase(), ivaoPlatz=platzSchluessel(icaoRaw);
                            if(!ivaoAirportGroups[ivaoPlatz])ivaoAirportGroups[ivaoPlatz]={pos:platzZu(icaoRaw)||[lat,lon],ctrls:[],atis:[],name:icaoRaw};
                            if(icaoRaw.length>ivaoAirportGroups[ivaoPlatz].name.length)ivaoAirportGroups[ivaoPlatz].name=icaoRaw;
                            var ivaoVid=c.userId||''; ivaoAirportGroups[ivaoPlatz].ctrls.push({callsign:cs,facility:facility,frequency:freq,name:'',cid:ivaoVid,cid_link:'https://tracker.ivao.aero/atc/'+ivaoVid,rating:c.rating||0,logon_time:c.createdAt||'',network:'IVAO'});
                            if(atisLines.length)ivaoAirportGroups[ivaoPlatz].atis.push({callsign:cs,frequency:freq,text_atis:atisLines});
                        }
                    });

                    Object.keys(ivaoAirportGroups).forEach(function(key){
                        var group=ivaoAirportGroups[key], icao=group.name, pos=group.pos;
                        L.marker(pos,{icon:buildAirportCtrlIconIvao(icao,group.ctrls,group.atis),title:icao,zIndexOffset:490}).bindPopup(buildAirportCtrlPopupIvao(icao,group.ctrls,group.atis),{maxWidth:300}).addTo(ivaoCtrlLayer);
                    });

                    letzteIvaoFirMap=ivaoFirMap;
                    if(vatsimShowSectors) ladeGrenzen().then(function(){renderActiveSectors(ivaoFirMap, ivaoSectorLayer);}).catch(function(e){console.warn('[IVAO] Sektoren-Fehler:',e);});
                    if(typeof applyLayerVisibility==='function') applyLayerVisibility();
                    return true;
                }).catch(function(err){
                    console.error('[IVAO] Error:',err);
                    var di=document.getElementById('ivaoNetDot'); if(di){ di.classList.remove('live'); di.classList.add('fehler'); }
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
                    pilot: 'Pilot', veraltet: 'Angezeigt wird der letzte bekannte Stand.', nochmal: 'Erneut versuchen',
                    flugzeug: 'Flugzeug', hoehe: 'Höhe', kurs: 'Kurs', fortschritt: 'Fortschritt', mehr: 'Mehr Werte',
                    weitereFluege: 'weitere Flüge hier – Klick zoomt hinein',
                    fehler: {
                        phpvms: 'Die laufenden Flüge konnten nicht gelesen werden.',
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
                    pilot: 'Pilot', veraltet: 'Showing the last known state.', nochmal: 'Try again',
                    flugzeug: 'Aircraft', hoehe: 'Altitude', kurs: 'Heading', fortschritt: 'Progress', mehr: 'More values',
                    weitereFluege: 'more flights here – click to zoom in',
                    fehler: {
                        phpvms: 'Running flights could not be read.',
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

            // Silhouetten der sieben Flugzeugklassen (Entwurf 03.11.2026), einmal
            // als Sprite in die Seite; die Marker verweisen mit <use> darauf.
            var LM_SYMBOLE =
                '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>' +
                '<g id="lm-k1"><path d="M30.6 8h2.8l1 10H60v6H34.4l-1.4 22h9v4.5h-9.6l-.6 3.5h-1.6l-.6-3.5H22v-4.5h9l-1.4-22H4v-6h25.6z"/><rect x="24" y="5.2" width="16" height="1.8" rx=".9"/></g>' +
                '<g id="lm-k2"><path d="M32 4c1.6 0 2.5 2 2.5 5v13H61v4l-26.5 2v24l8 2.5v3L32 56.5l-10.5 1V54.5l8-2.5V28L3 26v-4h26.5V9c0-3 .9-5 2.5-5z"/><rect x="17.5" y="15" width="4.6" height="16" rx="2.3"/><rect x="41.9" y="15" width="4.6" height="16" rx="2.3"/><rect x="14.5" y="13.4" width="10.6" height="1.6" rx=".8"/><rect x="38.9" y="13.4" width="10.6" height="1.6" rx=".8"/></g>' +
                '<g id="lm-k3"><path d="M32 6c1.5 0 2.4 2.4 2.4 5.5V26L54 36v3l-19.6-5v14l7.6 5v2.6L32 53.5l-10 2.1V53l7.6-5V34L10 39v-3l19.6-10V11.5C29.6 8.4 30.5 6 32 6z"/><rect x="24.6" y="38" width="3.6" height="9" rx="1.8"/><rect x="35.8" y="38" width="3.6" height="9" rx="1.8"/></g>' +
                '<g id="lm-k4"><path d="M32 3.5c1.9 0 3.2 2.6 3.2 6.2V22l21.6 11.6c.9.5 1.4 1.4 1.4 2.4v2.2L35.2 31.6v12.6l7.4 5.6c.5.4.8 1 .8 1.6v1.8L32 50.6l-11.4 2.6v-1.8c0-.6.3-1.2.8-1.6l7.4-5.6V31.6L5.8 38.2V36c0-1 .5-1.9 1.4-2.4L28.8 22V9.7c0-3.6 1.3-6.2 3.2-6.2z"/><rect x="40.5" y="25.5" width="3.2" height="6.5" rx="1.6"/><rect x="20.3" y="25.5" width="3.2" height="6.5" rx="1.6"/></g>' +
                '<g id="lm-k5"><path d="M32 2c2.3 0 3.6 3 3.6 7v12l23.4 12.6c.8.4 1 1 1 1.8V38L35.6 31v13.4l8 6c.4.3.6.8.6 1.3v2L32 51.6l-12.2 2.1v-2c0-.5.2-1 .6-1.3l8-6V31L4 38v-2.6c0-.8.2-1.4 1-1.8L28.4 21V9c0-4 1.3-7 3.6-7z"/><rect x="40.2" y="23.6" width="4.6" height="9.5" rx="2.3"/><rect x="19.2" y="23.6" width="4.6" height="9.5" rx="2.3"/></g>' +
                '<g id="lm-k6"><path d="M32 2c2.5 0 3.8 3 3.8 7.4v11.4L60 33.6c.6.3 1 1 1 1.7V38L35.8 31v13.4l8 6c.4.3.6.8.6 1.3v2L32 51.6l-12.4 2.1v-2c0-.5.2-1 .6-1.3l8-6V31L3 38v-2.7c0-.7.4-1.4 1-1.7l24.2-12.8V9.4c0-4.4 1.3-7.4 3.8-7.4z"/><rect x="38.4" y="23" width="3.8" height="8" rx="1.9"/><rect x="46.6" y="27.4" width="3.8" height="8" rx="1.9"/><rect x="21.8" y="23" width="3.8" height="8" rx="1.9"/><rect x="13.6" y="27.4" width="3.8" height="8" rx="1.9"/></g>' +
                '<g id="lm-k7"><circle cx="32" cy="26" r="21" fill="none" stroke-width="1.6" opacity=".55"/><ellipse cx="32" cy="26" rx="6.5" ry="10"/><rect x="30.8" y="34" width="2.4" height="20" rx="1.2"/><rect x="26" y="52" width="12" height="2.2" rx="1.1"/></g>' +
                '</defs></svg>';

            // Ein Symbol als SVG-Text: Klasse → Silhouette, Groesse in px, Kontur
            // in Bildschirm-Pixeln gleich dick, egal wie gross das Symbol ist.
            function symbolSvg(info, px, kontur) {
                return '<svg viewBox="0 0 64 64" width="' + px + '" height="' + px + '" style="stroke-width:' +
                    (kontur * 64 / px).toFixed(2) + '"><use href="#lm-' + info.symbol + '"/></svg>';
            }

            function starteVaFluege(map) {
                var cfg = window.LIVE_MAP_UI || {};
                var zustand = {
                    fluege: [], geplant: [], flughaefen: {}, auswahl: null, phase: 'ALL', tab: 'aktiv',
                    folgen: !!cfg.defaultFollowFlight, ersteAnsicht: true, fehler: null, geladen: false,
                    sort: { aktiv: { k: 'phase', r: 'auf' }, geplant: { k: 'zeit', r: 'auf' } }
                };
                // Sortierwahl je Reiter, gilt oben und unten; im Browser gemerkt
                try {
                    var gemerkt = JSON.parse(localStorage.getItem('livemap_sort') || 'null');
                    ['aktiv', 'geplant'].forEach(function (t) {
                        if (gemerkt && gemerkt[t] && typeof gemerkt[t].k === 'string') zustand.sort[t] = { k: gemerkt[t].k, r: gemerkt[t].r === 'ab' ? 'ab' : 'auf' };
                    });
                } catch (e) {}
                var marker = {};            // id → L.marker
                var flugEbene = L.layerGroup().addTo(map);
                var spurEbene = L.layerGroup().addTo(map);
                var spurDaten = { id: null, spur: [], route: [] };
                var abrufLaeuft = false, abrufTimer = null, spurTimer = null;

                var el = {
                    wrap: document.getElementById('lm-root'),
                    tafel: document.getElementById('va-flights-panel'),
                    kopf: document.getElementById('lm3-tafel-kopf'),
                    inhalt: document.getElementById('lm3-tafel-inhalt'),
                    liste: document.getElementById('lm3-liste'),
                    meldung: document.getElementById('lm3-meldung'),
                    zahl: document.getElementById('lm3-zahl'),
                    zahlGeplant: document.getElementById('lm3-zahl-geplant'),
                    nAktiv: document.getElementById('lm3-n-aktiv'),
                    nGeplant: document.getElementById('lm3-n-geplant'),
                    tabAktiv: document.getElementById('lm3-tab-aktiv'),
                    tabGeplant: document.getElementById('lm3-tab-geplant'),
                    kopfAktiv: document.getElementById('lm3-kopf-aktiv'),
                    kopfGeplant: document.getElementById('lm3-kopf-geplant'),
                    phase: document.getElementById('lm3-phase'),
                    filterZeile: document.getElementById('lm3-filter'),
                    detail: document.getElementById('lm3-detail'),
                    rechts: document.querySelector('#lm-root .lm3-rechts'),
                    punkt: document.getElementById('lm3-status-punkt'),
                    folgen: document.getElementById('btnFollowFlight')
                };
                if (el.wrap && !document.getElementById('lm-k1')) el.wrap.insertAdjacentHTML('afterbegin', LM_SYMBOLE);
                function schmal() { return !!(el.wrap && el.wrap.classList.contains('lm3--schmal')); }
                // Tafel unten (Verwaltung): feste Tabelle unter der Karte, immer offen.
                var tafelUnten = !!(el.wrap && el.wrap.classList.contains('lm3--tafel-unten'));

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
                    if (el.kopfAktiv) el.kopfAktiv.hidden = tab !== 'aktiv';
                    if (el.kopfGeplant) el.kopfGeplant.hidden = tab !== 'geplant';
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
                            logo: b.airline && b.airline.logo ? b.airline.logo : null,
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
                                // Kurze Stoerung: letzten guten Stand stehen lassen (als
                                // veraltet markiert). Schluessel/Adresse falsch: leeren.
                                if (!LM3.fehlerIstVoruebergehend(zustand.fehler)) zustand.fluege = [];
                            } else {
                                zustand.fehler = null;
                                zustand.warnung = d.warnung || null;
                                zustand.fluege = d.fluege;
                                if (d.flughaefen && typeof d.flughaefen === 'object') zustand.flughaefen = d.flughaefen;
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
                function weg(f) { return LM3.fortschritt(zustand.flughaefen[f.dep], zustand.flughaefen[f.arr], f.pos); }

                // ── Zeichnen ──
                function zeichneAlles() { zeichneKopf(); zeichneListe(); zeichneMarker(); zeichneDetail(); folge(); }

                function zeichneKopf() {
                    var n = zustand.fluege.length;
                    if (el.zahl) el.zahl.textContent = zustand.fehler && !n ? '!' : String(n);
                    if (el.zahlGeplant) el.zahlGeplant.textContent = String(zustand.geplant.length);
                    if (el.nAktiv) el.nAktiv.textContent = String(n);
                    if (el.nGeplant) el.nGeplant.textContent = String(zustand.geplant.length);
                    if (el.punkt) el.punkt.className = 'lm3-punkt ' + (zustand.fehler ? 'ist-fehler' : (n ? 'ist-live' : ''));
                    if (el.meldung) {
                        var code = zustand.fehler || zustand.warnung;
                        if (code) {
                            el.meldung.hidden = false;
                            el.meldung.textContent = (LM_T.fehler[code] || LM_T.fehler.netz) +
                                (zustand.fehler && zustand.fluege.length ? ' ' + LM_T.veraltet : '');
                            var nochmal = document.createElement('button');
                            nochmal.type = 'button';
                            nochmal.className = 'lm3-nochmal';
                            nochmal.textContent = LM_T.nochmal;
                            nochmal.addEventListener('click', function () { planeAbruf(0); });
                            el.meldung.appendChild(nochmal);
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
                // Logo aus den Flugdaten; fehlt es (z. B. Quelle „Beide“), das der
                // phpVMS-Airline mit den Buchstaben des Rufzeichens (AIRLINE_LOGOS).
                function logoFuer(f) {
                    if (f.logo) return f.logo;
                    var k = (LM3.rufzeichenSchluessel(f.callsign).match(/^[A-Z]{3}/) || [''])[0];
                    return (k && typeof AIRLINE_LOGOS !== 'undefined' && AIRLINE_LOGOS) ? (AIRLINE_LOGOS[k] || null) : null;
                }
                // Ohne Logo (oder wenn es nicht laedt): Airline-Buchstaben aus dem Rufzeichen
                function logoHtml(url, alt, cs) {
                    var u = safeAssetUrl(url);
                    var k = (LM3.rufzeichenSchluessel(cs).match(/^[A-Z]+/) || [''])[0].slice(0, 3);
                    if (!u) return '<span class="lm3-logo ist-text">' + lmEsc(k) + '</span>';
                    return '<span class="lm3-logo" data-k="' + lmEsc(k) + '"><img src="' + lmEsc(u) + '" alt="' + lmEsc(alt || '') + '" loading="lazy" onerror="var p=this.parentNode;p.classList.add(\'ist-text\');p.textContent=p.getAttribute(\'data-k\')||\'\';"></span>';
                }

                function markiereSort() {
                    ['aktiv', 'geplant'].forEach(function (t) {
                        var kopf = t === 'aktiv' ? el.kopfAktiv : el.kopfGeplant, s = zustand.sort[t];
                        if (!kopf) return;
                        [].forEach.call(kopf.querySelectorAll('[data-sort]'), function (k) {
                            var an = k.getAttribute('data-sort') === s.k;
                            k.classList.toggle('ist-sortiert', an);
                            if (an) k.setAttribute('data-richtung', s.r); else k.removeAttribute('data-richtung');
                            k.setAttribute('aria-pressed', an ? 'true' : 'false');
                        });
                    });
                }
                [el.kopfAktiv, el.kopfGeplant].forEach(function (kopf, i) {
                    if (!kopf) return;
                    var t = i ? 'geplant' : 'aktiv';
                    kopf.addEventListener('click', function (ev) {
                        var k = ev.target.closest && ev.target.closest('[data-sort]');
                        if (!k) return;
                        var s = zustand.sort[t], neu = k.getAttribute('data-sort');
                        zustand.sort[t] = { k: neu, r: s.k === neu && s.r === 'auf' ? 'ab' : 'auf' };
                        try { localStorage.setItem('livemap_sort', JSON.stringify(zustand.sort)); } catch (e) {}
                        markiereSort();
                        zeichneListe();
                    });
                });

                function zeichneListe() {
                    if (!el.liste) return;
                    var scroll = el.liste.scrollTop;
                    el.liste.textContent = '';
                    if (!zustand.geladen) { el.liste.appendChild(leer(LM_T.laden)); return; }
                    if (zustand.tab === 'geplant') {
                        if (!zustand.geplant.length) { el.liste.appendChild(leer(LM_T.keineGeplant)); return; }
                        LM3.sortiereNach(zustand.geplant, zustand.sort.geplant.k, zustand.sort.geplant.r, null)
                            .forEach(function (g) { el.liste.appendChild(zeileGeplant(g)); });
                    } else {
                        if (zustand.fehler && !zustand.fluege.length) return;
                        var liste = sichtbareFluege();
                        if (!liste.length) { el.liste.appendChild(leer(zustand.fluege.length ? LM_T.keineFilter : LM_T.keine)); return; }
                        LM3.sortiereNach(liste, zustand.sort.aktiv.k, zustand.sort.aktiv.r, weg)
                            .forEach(function (f) { el.liste.appendChild(zeileAktiv(f)); });
                    }
                    el.liste.scrollTop = scroll;
                }

                function zeileAktiv(f) {
                    var pos = f.pos || {};
                    var phase = LM3.phaseForDisplay(f.phase, f.shadow_segment);
                    var w = weg(f);
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'lm3-zeile lm3-g-akt' + (zustand.auswahl === f.id ? ' ist-gewaehlt' : '') + (f.status === 'offline' ? ' ist-offline' : '');
                    b.setAttribute('role', 'listitem');
                    b.style.setProperty('--ph', LM3.phaseColor(phase));
                    b.innerHTML =
                        '<span class="lm3-flug">' + logoHtml(logoFuer(f), '', f.callsign) + '<b>' + lmEsc(f.callsign || (LM_T.pilot + ' ' + f.pilot)) + '</b></span>' +
                        '<span>' + lmEsc(f.dep || '?') + ' <span class="lm3-pfeil">›</span> ' + lmEsc(f.arr || '?') + '</span>' +
                        '<span class="lm3-sp-typ">' + lmEsc(f.aircraft_icao || '') + '</span>' +
                        '<span class="lm3-sp-alt">' + lmEsc(LM3.hoeheText(pos.alt_ft, LM_SPRACHE)) + '</span>' +
                        '<span class="lm3-sp-gs">' + (pos.gs_kt != null ? Math.round(pos.gs_kt) : '') + '</span>' +
                        // Rest-Strecke und Fortschritt (Balken = geflogener Anteil)
                        '<span class="lm3-sp-dist">' + (w ? '<span class="lm3-rest">' + Math.round(w.rest) + ' nm</span>' +
                            '<i class="lm3-fort" aria-hidden="true"><i style="width:' + Math.round(w.anteil * 100) + '%"></i></i>' : '') + '</span>' +
                        '<span class="lm3-sp-eta">' + lmEsc(LM3.etaFuerFlug(f, w, Date.now())) + '</span>' +
                        '<span class="lm3-sp-phase"><span class="lm3-pille">' + lmEsc(LM3.phaseLabel(phase)) + '</span></span>' +
                        '<span class="lm3-sp-pilot">' + lmEsc(f.pilot_name || '') + '</span>';
                    b.addEventListener('click', function () { waehle(f.id, true); });
                    return b;
                }
                function zeileGeplant(g) {
                    var d = document.createElement('div');
                    d.className = 'lm3-zeile lm3-g-plan';
                    d.setAttribute('role', 'listitem');
                    d.innerHTML =
                        '<span class="lm3-flug">' + logoHtml(logoFuer(g), '', g.callsign) + '<b>' + lmEsc(g.callsign || '—') + '</b></span>' +
                        '<span>' + lmEsc(g.dep || '?') + ' <span class="lm3-pfeil">›</span> ' + lmEsc(g.arr || '?') + '</span>' +
                        '<span class="lm3-sp-zeit">' + lmEsc(g.zeit || '') + '</span>' +
                        '<span class="lm3-sp-pilot">' + lmEsc(g.pilot) + '</span>';
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
                    var info = LM3.klassenInfo(LM3.flugzeugKlasse(f.aircraft_icao));
                    var px = Math.round(info.px * (LM3.amBoden(f.phase) ? 0.85 : 1));
                    var hdg = f.pos && f.pos.hdg_true != null ? Math.round(f.pos.hdg_true) : 0;
                    var rahmen = px + 12;                 // Platz fuer den Ring
                    var versatz = (rahmen - px) / 2;
                    var mitLabel = gewaehlt || (cfg.rufzeichenZeigen !== false && map.getZoom() >= 6);
                    return L.divIcon({
                        className: 'lm3-marker' + (gewaehlt ? ' ist-gewaehlt' : '') + (f.status === 'offline' ? ' ist-offline' : ''),
                        html: (gewaehlt ? '<span class="lm3-ring" style="left:0;top:0;width:' + rahmen + 'px;height:' + rahmen + 'px"></span>' : '') +
                            '<span class="lm3-flieger" style="--ph:' + LM3.phaseColor(phase) + ';left:' + versatz + 'px;top:' + versatz + 'px;width:' + px + 'px;height:' + px + 'px;transform:rotate(' + hdg + 'deg)">' +
                            symbolSvg(info, px, 1.3) + '</span>' +
                            (mitLabel ? '<span class="lm3-mlabel" style="top:' + (rahmen + 1) + 'px">' + lmEsc(f.callsign || f.pilot) + '</span>' : ''),
                        iconSize: [rahmen, rahmen], iconAnchor: [rahmen / 2, rahmen / 2]
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
                        // VA-Flieger immer ueber Netz-Symbolen (Lotsen 400–500): sonst faengt die
                        // Klickflaeche eines Platzsymbols den Klick auf das Flugzeug ab (gemessen
                        // 03.11.2026: GSG101 unter LFJL, GSG220 unter EDDV).
                        m.setZIndexOffset(gew ? 11000 : 10000);
                    });
                    Object.keys(marker).forEach(function (id) {
                        if (!sichtbar[id]) { flugEbene.removeLayer(marker[id]); delete marker[id]; }
                    });
                    lmEntwirrenPlanen();
                }

                // ── Auswahl, Spur, Route ──
                function waehle(id, ausListe) {
                    zustand.auswahl = id;
                    var f = findeFlug(id);
                    spurDaten = { id: id, spur: [], route: [] };
                    spurEbene.clearLayers();
                    if (typeof routeLineLayer !== 'undefined') routeLineLayer.clearLayers();
                    // Schmal teilen sich Tafel und Detail das Blatt unten.
                    if (schmal()) { setzeTafel(false); schliesseKaesten(); }
                    zeichneListe(); zeichneMarker(); zeichneDetail();
                    // Breit: neuer Flug zeigt das ganze Detail; passt ein offener Kasten
                    // nicht mehr daneben, geht der Kasten zu (nichts abgeschnitten).
                    if (!schmal() && el.detail) { el.detail.classList.remove('ist-klein'); if (!rechtsPasst()) schliesseKaesten(); }
                    if (f && f.pos) {
                        // Aus der Weltansicht heranholen, damit Spur und Lage lesbar sind.
                        if (map.getZoom() < 5) map.setView(lageFuer(f), 5, { animate: false });
                        zentriere(lageFuer(f), true);
                    }
                    holeSpur(id);
                    if (spurTimer) clearInterval(spurTimer);
                    spurTimer = setInterval(function () { if (!document.hidden) holeSpur(id); }, 30000);
                }
                function schliesseDetail() {
                    zustand.auswahl = null;
                    spurDaten = { id: null, spur: [], route: [] };
                    spurEbene.clearLayers();
                    if (typeof routeLineLayer !== 'undefined') routeLineLayer.clearLayers();
                    if (spurTimer) { clearInterval(spurTimer); spurTimer = null; }
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

                // ── Detailkasten (Aufbau wie der 4.7.9-„Crew Pass“) ──
                // Felder, die die Quelle nicht liefert, fehlen — keine Striche.
                function zelle(name, wert, breit, extra) {
                    if (wert == null || wert === '') return '';
                    return '<div' + (breit ? ' class="lm3-d-breit"' : '') + '><dt>' + lmEsc(name) + '</dt><dd>' + lmEsc(wert) + '</dd>' + (extra || '') + '</div>';
                }
                function zeichneDetail() {
                    if (!el.detail) return;
                    var f = zustand.auswahl ? findeFlug(zustand.auswahl) : null;
                    if (!f) { el.detail.hidden = true; el.detail.innerHTML = ''; return; }
                    var pos = f.pos || {};
                    var phase = LM3.phaseForDisplay(f.phase, f.shadow_segment);
                    var dep = zustand.flughaefen[f.dep] || {}, arr = zustand.flughaefen[f.arr] || {};
                    var w = weg(f);
                    var flugzeug = [f.aircraft_icao, f.registration].filter(Boolean).join(' · ');
                    var stand = '';
                    if (f.dep_gate || f.arr_gate) stand = (f.dep_gate || '–') + ' → ' + (f.arr_gate || '–');

                    var raster =
                        zelle(LM_T.pilot, f.pilot_name) +
                        zelle(LM_T.flugzeug, flugzeug) +
                        zelle(LM_T.hoehe, pos.alt_ft != null ? LM3.zahl(pos.alt_ft, LM_SPRACHE) + ' ft' : null) +
                        zelle('GS', pos.gs_kt != null ? Math.round(pos.gs_kt) + ' kt' : null) +
                        zelle(LM_T.kurs, pos.hdg_true != null ? Math.round(pos.hdg_true) + '°' : null) +
                        zelle('V/S', pos.vs_fpm != null ? LM3.vsText(pos.vs_fpm) + ' fpm' : null) +
                        zelle(LM_T.stand, stand) +
                        (w ? zelle(LM_T.fortschritt, Math.round(w.geflogen) + ' / ' + Math.round(w.gesamt) + ' nm', true,
                            '<div class="lm3-balken"><i style="width:' + Math.round(w.anteil * 100) + '%"></i></div>') : '');

                    // Mehr Werte nur, wenn die Quelle sie liefert (AeroACARS).
                    var mehr =
                        zelle('IAS', pos.ias_kt != null ? Math.round(pos.ias_kt) + ' kt' : null) +
                        zelle('Mach', pos.mach != null && pos.mach > 0.4 ? Number(pos.mach).toFixed(3) : null) +
                        zelle('AGL', pos.agl_ft != null && pos.agl_ft < 5000 ? LM3.zahl(pos.agl_ft, LM_SPRACHE) + ' ft' : null) +
                        zelle('Wind', pos.wind_dir_deg != null ? Math.round(pos.wind_dir_deg) + '° / ' + Math.round(pos.wind_speed_kt || 0) + ' kt' : null) +
                        zelle('QNH', pos.qnh_hpa != null ? LM3.fmtQnhBoth(pos.qnh_hpa) : null, true) +
                        zelle('OAT', pos.oat_c != null ? Math.round(pos.oat_c) + ' °C' : null) +
                        zelle('Fuel', pos.fuel_total_kg != null ? LM3.tonnen(pos.fuel_total_kg) + ' t' : null) +
                        zelle('TOW', pos.total_weight_kg != null ? LM3.tonnen(pos.total_weight_kg) + ' t' : null) +
                        zelle('Sim', f.sim ? LM3.formatSimLabel(f.sim) : null) +
                        zelle('ACARS', f.acars);
                    var badge = function (text, an) { return '<span class="lm3-badge' + (an ? ' ist-an' : '') + '">' + lmEsc(text) + '</span>'; };
                    var badges = '';
                    if (pos.gear_position != null || pos.flaps_position != null) {
                        badges = badge('Gear DN', (pos.gear_position || 0) > 0.5) +
                            badge('Flaps ' + Math.round((pos.flaps_position || 0) * 100) + '%', (pos.flaps_position || 0) > 0) +
                            (pos.spoilers_armed != null ? badge('SPL armed', pos.spoilers_armed === true) : '') +
                            (pos.parking_brake != null ? badge('Park brk', pos.parking_brake === true) : '') +
                            (pos.engines_running != null ? badge('ENG ' + pos.engines_running, pos.engines_running > 0) : '');
                    }
                    var ap = LM3.apFlags(pos);
                    if (ap) badges += ap.split(' ').map(function (a) { return badge(a, true); }).join('');
                    var warn = (pos.stall_warning ? '<span class="lm3-flag">⚠ STALL</span>' : '') + (pos.overspeed_warning ? '<span class="lm3-flag">⚠ OVERSPEED</span>' : '');
                    var mehrOffen = !!(el.detail.querySelector('details') && el.detail.querySelector('details').open);

                    var logoUrl = safeAssetUrl(logoFuer(f));
                    var scrollVorher = el.detail.hidden ? 0 : el.detail.scrollTop;
                    el.detail.style.setProperty('--ph', LM3.phaseColor(phase));
                    el.detail.innerHTML =
                        '<header class="lm3-d-kopf">' +
                          (logoUrl ? '<span class="lm3-d-logo"><img src="' + lmEsc(logoUrl) + '" alt="" onerror="this.parentNode.remove()"></span>' : '') +
                                '<button type="button" class="lm3-d-cs" data-lm3-auf>' + lmEsc(f.callsign || (LM_T.pilot + ' ' + f.pilot)) + '</button>' +
                          '<button type="button" class="lm3-zu" data-lm3-zu aria-label="' + lmEsc(LM_T.schliessen) + '">✕</button>' +
                        '</header>' +
                        '<div class="lm3-d-route">' +
                          '<div class="lm3-d-ap"><div class="lm3-d-icao">' + lmEsc(f.dep || '?') + '</div>' + (dep.name ? '<div class="lm3-d-name">' + lmEsc(dep.name) + '</div>' : '') + '</div>' +
                          '<div class="lm3-d-mitte">✈' + (w ? '<small>' + Math.round(w.rest) + ' nm</small>' : '') + '</div>' +
                          '<div class="lm3-d-ap"><div class="lm3-d-icao">' + lmEsc(f.arr || '?') + '</div>' + (arr.name ? '<div class="lm3-d-name">' + lmEsc(arr.name) + '</div>' : '') + '</div>' +
                        '</div>' + warn +
                        '<dl class="lm3-d-raster">' + raster + '</dl>' +
                        (mehr || badges ? '<details class="lm3-d-mehr"' + (mehrOffen ? ' open' : '') + '><summary>' + lmEsc(LM_T.mehr) + '</summary>' +
                            (mehr ? '<dl class="lm3-d-raster">' + mehr + '</dl>' : '') + (badges ? '<div class="lm3-badges">' + badges + '</div>' : '') + '</details>' : '') +
                        '<div class="lm3-d-fuss"><span class="lm3-pille">' + lmEsc(LM3.phaseLabel(phase)) + '</span>' +
                          (f.status === 'offline' ? '<span>' + lmEsc(LM_T.offline) + '</span>' : (f.last_seen ? '<span>' + lmEsc(LM_SPRACHE === 'de' ? 'vor ' + LM3.ageString(f.last_seen) : LM3.ageString(f.last_seen) + ' ago') + '</span>' : '')) +
                          '<span class="lm3-weit"></span>' +
                          (safeUrl(f.pirep_url) ? '<a class="lm3-d-link" href="' + lmEsc(safeUrl(f.pirep_url)) + '">PIREP ↗</a>' : '') +
                          '<button type="button" class="lm3-knopf' + (zustand.folgen ? ' active' : '') + '" data-lm3-folgen aria-pressed="' + (zustand.folgen ? 'true' : 'false') + '">' + lmEsc(zustand.folgen ? LM_T.folgt : LM_T.folgen) + '</button>' +
                        '</div>';
                    el.detail.hidden = false;
                    el.detail.scrollTop = scrollVorher;
                    var zu = el.detail.querySelector('[data-lm3-zu]');
                    if (zu) zu.addEventListener('click', schliesseDetail);
                    // Eingeklappt (Kasten daneben braucht den Platz): Rufzeichen klappt auf.
                    var auf = el.detail.querySelector('[data-lm3-auf]');
                    if (auf) auf.addEventListener('click', function () {
                        if (!el.detail.classList.contains('ist-klein')) return;
                        el.detail.classList.remove('ist-klein');
                        schliesseKaesten();
                    });
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
                }
                if (el.folgen) el.folgen.addEventListener('click', function () { setzeFolgen(!zustand.folgen); });
                syncFolgenKnopf();
                // Wer die Karte selbst bewegt, uebernimmt — Folgen geht aus.
                // Auch Zoomen zaehlt (Rad, +/−, Doppelklick, Zwei-Finger, Tasten, "+n"),
                // sonst holt der naechste Abruf die Ansicht zurueck.
                function nutzerBewegt() { if (zustand.folgen) setzeFolgen(false); }
                map.on('dragstart dblclick boxzoomstart lm3nutzerzoom', nutzerBewegt);
                map.on('keydown', function (e) { if (/^(\+|-|=|_|Add|Subtract)$/.test(e.originalEvent && e.originalEvent.key || '')) nutzerBewegt(); });
                (function (c) {
                    c.addEventListener('wheel', nutzerBewegt, { passive: true });
                    c.addEventListener('touchstart', function (e) { if (e.touches && e.touches.length > 1) nutzerBewegt(); }, { passive: true });
                    c.querySelectorAll('.leaflet-control-zoom a').forEach(function (a) { a.addEventListener('click', nutzerBewegt); });
                })(map.getContainer());
                // Nach jedem Verschieben die Marker in die sichtbare Weltkopie holen,
                // nach dem Zoomen Rufzeichen ein-/ausblenden (ab Zoom 6).
                map.on('moveend zoomend', function () { zeichneMarker(); });

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
                // Freie Kartenflaeche = alles ausser dem, was Tafel, rechte Spalte
                // und Blatt gerade belegen (aus den echten Rechtecken gemessen).
                function freiraum() {
                    var karte = map.getContainer().getBoundingClientRect();
                    var links = 50, rechts = 30, oben = 30, unten = 30;
                    var belegt = function (e) { return e && !e.hidden && e.offsetParent !== null ? e.getBoundingClientRect() : null; };
                    if (!schmal()) {
                        var t = tafelUnten ? null : belegt(el.tafel);
                        if (t) oben = Math.max(oben, t.bottom - karte.top + 20);
                        var r = belegt(el.detail) || null;
                        [].forEach.call(el.wrap.querySelectorAll('.lm3-box'), function (b) { if (!b.hidden) r = r || b.getBoundingClientRect(); });
                        if (r) rechts = Math.max(rechts, karte.right - r.left + 20);
                    } else {
                        var unterkante = karte.bottom;
                        [el.detail, tafelUnten ? null : el.tafel].forEach(function (e) { var q = belegt(e); if (q) unterkante = Math.min(unterkante, q.top); });
                        unten = Math.max(unten, karte.bottom - unterkante + 20);
                        oben = 60;
                    }
                    return { paddingTopLeft: [links, oben], paddingBottomRight: [rechts, unten] };
                }
                // Punkt in die Mitte der FREIEN Kartenflaeche legen.
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

                // Passt alles in der rechten Spalte ohne Abschneiden? (volle Hoehen
                // der sichtbaren Teile + Abstaende gegen die Spaltenhoehe)
                function rechtsPasst() {
                    var r = el.rechts;
                    if (!r) return true;
                    var summe = 0, n = 0;
                    [].forEach.call(r.children, function (k) {
                        if (k.hidden || k.offsetParent === null) return;
                        summe += k.classList.contains('lm3-detail') && k.classList.contains('ist-klein') ? k.getBoundingClientRect().height : k.scrollHeight;
                        n++;
                    });
                    return summe + Math.max(0, n - 1) * 8 <= r.clientHeight + 1;
                }
                // Kasten neben dem Detail: reicht die Hoehe nicht fuer beide, klappt das
                // Detail auf seine Kopfzeile ein (Auswahl und Spur bleiben).
                function passeRechts() {
                    if (!el.detail || el.detail.hidden) return;
                    el.detail.classList.remove('ist-klein');
                    var kastenOffen = [].some.call(el.wrap.querySelectorAll('.lm3-box'), function (b) { return !b.hidden; });
                    if (!schmal() && kastenOffen && !rechtsPasst()) el.detail.classList.add('ist-klein');
                }

                // ── Tafel auf/zu ──
                function setzeTafel(offen) {
                    if (!el.inhalt || !el.kopf) return;
                    if (tafelUnten) { el.inhalt.hidden = false; return; }
                    el.inhalt.hidden = !offen;
                    el.kopf.setAttribute('aria-expanded', offen ? 'true' : 'false');
                }
                // Schmal teilen sich Kaesten, Tafel und Detail eine Spalte: es ist
                // immer nur eines offen.
                function schliesseKaesten() {
                    [].forEach.call(el.wrap.querySelectorAll('.lm3-box'), function (b) { b.hidden = true; });
                    [].forEach.call(el.wrap.querySelectorAll('.lm3-wz[aria-expanded]'), function (k) { k.setAttribute('aria-expanded', 'false'); });
                }
                if (el.kopf && !tafelUnten) el.kopf.addEventListener('click', function () {
                    var offen = !!el.inhalt.hidden;
                    if (offen && schmal()) { if (zustand.auswahl) schliesseDetail(); schliesseKaesten(); }
                    setzeTafel(offen);
                });

                markiereSort();
                setzeTab('aktiv');
                zeichneKopf();
                zeichneListe();
                holeFluege().then(function () { planeAbruf(); });

                return { setzeTafel: setzeTafel, schliesseDetail: schliesseDetail, zustand: zustand, schmal: schmal, passeRechts: passeRechts };
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

            // ── Entwirren: nichts auf der Karte liegt uebereinander ──
            // Rangfolge: VA-Symbol (+ Rufzeichen des gewaehlten Flugs) > Lotsenkasten/TRACON
            // > VA-Rufzeichen > Sektor-Etikett > Netz-Flieger. Wer einen schon platzierten trifft, wird
            // unsichtbar (visibility, nicht display: so bleibt er messbar und kommt beim
            // Hineinzoomen wieder). Gleicher Flughafen in VATSIM und IVAO: nebeneinander.
            var lmEntwirrenAngefordert = false;
            function lmEntwirrenPlanen() {
                if (lmEntwirrenAngefordert) return;
                lmEntwirrenAngefordert = true;
                requestAnimationFrame(function () { lmEntwirrenAngefordert = false; lmEntwirren(); });
            }
            function lmEntwirren() {
                var map = lm3Karte;
                if (!map) return;
                var pane = map.getPane('markerPane');
                if (!pane) return;
                var icons = Array.prototype.slice.call(pane.children);

                // 1. zuruecksetzen (nur schreiben)
                pane.querySelectorAll('.lm3-verdeckt').forEach(function (el) { el.classList.remove('lm3-verdeckt'); });
                pane.querySelectorAll('.lm3-mehr').forEach(function (el) { el.parentNode.removeChild(el); });
                pane.querySelectorAll('.lm3-sektor-etikett > div').forEach(function (el) { el.style.transform = ''; });
                var stationen = { vatsim: {}, ivao: {} };
                icons.forEach(function (el) {
                    var netz = el.classList.contains('vatsim-airport-marker') ? 'vatsim' : el.classList.contains('ivao-airport-marker') ? 'ivao' : null;
                    if (!netz) return;
                    var innen = el.firstElementChild;
                    if (innen) innen.style.marginLeft = '';
                    var icao = el.getAttribute('title');
                    if (icao) stationen[netz][icao] = el;
                });
                // 2. Paare nebeneinander (Breiten aus dem Stil, kein Layout-Lesen noetig)
                Object.keys(stationen.vatsim).forEach(function (icao) {
                    var v = stationen.vatsim[icao], i = stationen.ivao[icao];
                    if (!i || !v.firstElementChild || !i.firstElementChild) return;
                    var wv = parseFloat(v.firstElementChild.style.width) || 0, wi = parseFloat(i.firstElementChild.style.width) || 0;
                    v.firstElementChild.style.marginLeft = (-(wi + 2) / 2) + 'px';
                    i.firstElementChild.style.marginLeft = ((wv + 2) / 2) + 'px';
                });

                // 3. messen (nur lesen)
                var rahmen = map.getContainer().getBoundingClientRect();
                function rechteck(el) {
                    var r = el.getBoundingClientRect();
                    if (!r.width || !r.height) return null;
                    if (r.right < rahmen.left || r.left > rahmen.right || r.bottom < rahmen.top || r.top > rahmen.bottom) return null;
                    return { l: r.left, t: r.top, r: r.right, b: r.bottom };
                }
                // Lotsenkasten: nur der sichtbare Inhalt (Kuerzel + Abzeichen), nicht der Leerraum der Box
                function inhalt(el) {
                    var innen = el.firstElementChild;
                    if (!innen || getComputedStyle(innen).display === 'none') return null;
                    var teile = Array.prototype.slice.call(innen.children).map(rechteck).filter(Boolean);
                    if (!teile.length) return null;
                    return teile.reduce(function (a, b) { return { l: Math.min(a.l, b.l), t: Math.min(a.t, b.t), r: Math.max(a.r, b.r), b: Math.max(a.b, b.b) }; });
                }
                var feste = [], vaFlieger = [], kaesten = [], rufzeichen = [], etiketten = [], netzflieger = [];
                icons.forEach(function (el) {
                    if (el.style.display === 'none') return;
                    if (el.classList.contains('lm3-marker')) {
                        var sym = el.querySelector('.lm3-flieger'), lab = el.querySelector('.lm3-mlabel');
                        var rs = sym && rechteck(sym), rb = rechteck(el);
                        var gew = el.classList.contains('ist-gewaehlt');
                        var rl = lab && rechteck(lab);
                        if (rs) vaFlieger[gew ? 'unshift' : 'push']({ el: el, r: rs, box: rb || rs, label: rl && gew ? rl : null });
                        if (rl && !gew) rufzeichen.push({ el: lab, r: rl });
                    } else if (el.classList.contains('vatsim-airport-marker') || el.classList.contains('ivao-airport-marker')) {
                        var ri = inhalt(el);
                        if (ri) kaesten.push({ el: el, r: ri, gewicht: el.querySelectorAll('span[style*="inline-flex"]').length });
                    } else if (el.classList.contains('lm3-tracon')) {
                        var rt = rechteck(el.firstElementChild || el);
                        if (rt) kaesten.push({ el: el, r: rt, gewicht: 1 });
                    } else if (el.classList.contains('lm3-sektor-etikett')) {
                        // Teilsektoren (EDGG dreifach) liegen dicht beieinander; die Flaeche bleibt sichtbar.
                        var re = rechteck(el.firstElementChild || el);
                        if (re) etiketten.push({ el: el, r: re });
                    } else if (el.querySelector('.lm3-netz-flieger')) {
                        var rn = rechteck(el.querySelector('.lm3-netz-flieger'));
                        if (rn) netzflieger.push({ el: el, r: rn });
                    } else {
                        var ro = rechteck(el);
                        if (ro) feste.push(ro);   // Unbekanntes bleibt stehen und zaehlt als Hindernis
                    }
                });
                kaesten.sort(function (a, b) { return b.gewicht - a.gewicht; });

                // 4. platzieren, dann schreiben
                var belegt = feste.slice(), weg = [];
                function schneidet(r, o) { return r.l < o.r + 1 && r.r > o.l - 1 && r.t < o.b + 1 && r.b > o.t - 1; }
                function trifft(r) {
                    for (var k = 0; k < belegt.length; k++) if (schneidet(r, belegt[k])) return belegt[k];
                    return null;
                }
                // VA-Flieger: der vordere bleibt, verdeckte zaehlen als "+n" an ihm (der gewaehlte steht vorn)
                var gastgeber = [];
                vaFlieger.forEach(function (x) {
                    for (var k = 0; k < gastgeber.length; k++) {
                        if (schneidet(x.r, gastgeber[k].r)) { gastgeber[k].mehr++; weg.push(x.el); return; }
                    }
                    x.mehr = 0;
                    gastgeber.push(x);
                });
                gastgeber.forEach(function (x) {
                    // mit "+n" belegt der Flieger seinen ganzen Rahmen (dort sitzt die Zahl)
                    belegt.push(x.mehr ? { l: x.box.l, t: x.box.t - 3, r: x.box.r + 3, b: x.box.b } : x.r);
                    if (x.label) belegt.push(x.label);
                });
                [kaesten, rufzeichen].forEach(function (gruppe) {
                    gruppe.forEach(function (x) {
                        if (trifft(x.r)) weg.push(x.el); else belegt.push(x.r);
                    });
                });
                // Sektor-Etikett: erst ein Stueck neben die Mitte ruecken (bleibt in der Flaeche),
                // erst ohne freien Platz ausblenden.
                var RUECKE = [[0, 0], [0, -40], [0, 40], [-70, 0], [70, 0], [-70, -40], [70, -40], [-70, 40], [70, 40]], gerueckt = [];
                etiketten.forEach(function (x) {
                    for (var k = 0; k < RUECKE.length; k++) {
                        var d = RUECKE[k], r = { l: x.r.l + d[0], t: x.r.t + d[1], r: x.r.r + d[0], b: x.r.b + d[1] };
                        if (r.r < rahmen.left || r.l > rahmen.right || r.b < rahmen.top || r.t > rahmen.bottom) continue;
                        if (trifft(r)) continue;
                        belegt.push(r);
                        if (k) gerueckt.push({ el: x.el, d: d });
                        return;
                    }
                    weg.push(x.el);
                });
                netzflieger.forEach(function (x) {
                    if (trifft(x.r)) weg.push(x.el); else belegt.push(x.r);
                });
                gerueckt.forEach(function (g) { if (g.el.firstElementChild) g.el.firstElementChild.style.transform = 'translate(' + g.d[0] + 'px,' + g.d[1] + 'px)'; });
                weg.forEach(function (el) { el.classList.add('lm3-verdeckt'); });
                gastgeber.forEach(function (x) {
                    if (!x.mehr) return;
                    var z = document.createElement('span');
                    z.className = 'lm3-mehr';
                    z.textContent = '+' + x.mehr;
                    z.title = x.mehr + ' ' + LM_T.weitereFluege;
                    L.DomEvent.on(z, 'click', function (ev) {
                        L.DomEvent.stop(ev);
                        map.fire('lm3nutzerzoom');
                        if (x.el._leaflet_pos) map.setView(map.layerPointToLatLng(x.el._leaflet_pos), Math.min(map.getZoom() + 2, map.getMaxZoom()));
                    });
                    x.el.appendChild(z);
                });
            }

            (function lm3Start() {
                var cfg = window.LIVE_MAP_UI || {};
                var mapEl = document.getElementById('map');
                var wrap = document.getElementById('lm-root');
                if (!mapEl || !wrap) {
                    console.error('[LiveMap] Karten-Element fehlt — Karte kann nicht starten');
                    return;
                }

                // Breite des Widgets (nicht des Fensters!) bestimmt den Aufbau:
                // im GSG-Rahmen nimmt die Seitenleiste ~250 px weg.
                function messeBreite() {
                    var b = wrap.getBoundingClientRect().width;
                    wrap.classList.toggle('lm3--schmal', b < 760);
                }
                // Hoehe: Fenster minus das, was ueber dem Widget steht (Navigations-
                // leiste des Themes) und was unten fest am Fenster klebt (Cookie-
                // Leiste), begrenzt durch Mindest-/Hoechsthoehe aus der Verwaltung.
                var flaeche = document.getElementById('lm3-flaeche') || wrap;
                function messeHoehe() {
                    var oben = flaeche.getBoundingClientRect().top + (window.pageYOffset || 0);
                    var untenFest = 0;
                    [].forEach.call(document.body.querySelectorAll('body > *'), function (e) {
                        if (e.contains(wrap)) return;
                        var st = getComputedStyle(e);
                        if (st.position !== 'fixed' || st.display === 'none' || st.visibility === 'hidden') return;
                        var r = e.getBoundingClientRect();
                        if (r.height > 0 && r.height < window.innerHeight / 2 && r.bottom >= window.innerHeight - 1) untenFest = Math.max(untenFest, r.height);
                    });
                    // tiefer geschachtelte feste Leiste unten (wie messeObenFrei)
                    for (var e = document.elementFromPoint && document.elementFromPoint(window.innerWidth / 2, window.innerHeight - 2);
                        e && e !== document.body && !wrap.contains(e); e = e.parentElement) {
                        if (getComputedStyle(e).position !== 'fixed') continue;
                        var r = e.getBoundingClientRect();
                        if (r.height < window.innerHeight / 2) untenFest = Math.max(untenFest, r.height);
                        break;
                    }
                    // Tafel unten: kein Rollbalken in der Tafel (der verschob unter Windows
                    // die Zeilen gegen den Kopf, Abnahme 5.2.2), sie zaehlt mit Signatur.
                    var unten = wrap.classList.contains('lm3--tafel-unten') && wrap.querySelector('.lm3-unten');
                    var tafelH = null;
                    if (unten && unten.offsetParent !== null) {
                        var sig = wrap.querySelector('.lm3-signatur');
                        tafelH = unten.getBoundingClientRect().height + (sig ? sig.getBoundingClientRect().height : 0);
                    }
                    var neu = window.LM3.kartenHoehe({ fenster: window.innerHeight, oben: oben, untenFest: untenFest,
                        tafel: tafelH, min: cfg.minHoehe, max: cfg.maxHoehe }) + 'px';
                    var geaendert = flaeche.style.height !== neu;
                    flaeche.style.height = neu;
                    schliesseLuecke();
                    messeObenFrei();
                    return geaendert;
                }
                // Unter dem Widget folgt im Theme oft ein Polster bis zur Fusszeile (SPTheme:
                // 93 px). Die Karte reicht bis an den Fensterrand, beim Scrollen kam darunter
                // ein leeres Band (Thomas 12.11.2026). Gemessen wird der naechste sichtbare
                // Kasten unter dem Widget; den Ausgleich bekommt die aeusserste Huelle, die nur
                // das Widget enthaelt — am Widget selbst endete der Kartenrahmen mitten in der
                // Tafel. Vorher immer zuruecksetzen, damit die Luecke frisch gemessen wird.
                // Waehrend der Ausgleich steht, ist der Uebergang der Huelle aus: sonst misst
                // das Zuruecksetzen mitten in der Animation und jedes transitionend rechnet neu.
                var huelle = null, huelleAlt = '', huelleUebergang = '';
                function zaehltAlsInhalt(e) {
                    var st = getComputedStyle(e);
                    return e.offsetHeight > 0 && st.position !== 'fixed' && st.position !== 'absolute';
                }
                function gibHuelleFrei() {
                    if (!huelle) return;
                    huelle.style.marginBottom = huelleAlt;
                    huelle.getBoundingClientRect();   // erst den Abstand setzen, dann den Uebergang zurueck — sonst animiert er
                    huelle.style.transition = huelleUebergang;
                    huelle = null;
                }
                function schliesseLuecke() {
                    gibHuelleFrei();
                    var k = wrap.getBoundingClientRect(), naechst = null, naechstEl = null, grenze = null;
                    for (var a = wrap; a && a !== document.body && !naechst; a = a.parentElement) {
                        for (var s = a.nextElementSibling; s; s = s.nextElementSibling) {
                            var st = getComputedStyle(s);
                            if (st.position === 'fixed' || st.display === 'none') continue;
                            var r = s.getBoundingClientRect();
                            if (!r.height || r.right <= k.left || r.left >= k.right || r.top < k.bottom - 1) continue;
                            if (!naechst || r.top < naechst.top) { naechst = r; naechstEl = s; grenze = a.parentElement; }
                        }
                    }
                    if (!naechst) return;
                    var h = wrap;
                    while (h.parentElement && h.parentElement !== grenze && [].filter.call(h.parentElement.children, zaehltAlsInhalt).length === 1) h = h.parentElement;
                    var jetzt = parseFloat(getComputedStyle(h).marginBottom) || 0;
                    var luecke = Math.round(naechst.top - k.bottom);
                    var soll = window.LM3.lueckenAusgleich(luecke, jetzt);
                    if (soll === jetzt) return;
                    huelle = h; huelleAlt = h.style.marginBottom; huelleUebergang = h.style.transition;
                    h.style.transition = 'none';
                    h.style.marginBottom = soll + 'px';
                    // Nachmessen: Haelt das Theme den Kasten fest (Seven: Fusszeile per
                    // min-vh-100 unten, ebenso eine laengere Nachbarspalte), braechte der
                    // Ausgleich nur leeren Scrollraum. Behalten wird nur, was nachgerueckt ist.
                    var nachher = Math.round(naechstEl.getBoundingClientRect().top - wrap.getBoundingClientRect().bottom);
                    var wirksam = window.LM3.wirksamerAusgleich(jetzt, soll, luecke, nachher);
                    if (wirksam === jetzt) gibHuelleFrei();
                    else if (wirksam !== soll) h.style.marginBottom = wirksam + 'px';
                }
                // Feste Leiste des Themes ueber der Kartenoberkante (Cookie-Hinweis oben bei
                // Seven/Disposable, beim Scrollen die Navigationsleiste): Tafel, Zoom und
                // Knoepfe ruecken um die verdeckte Hoehe nach unten, statt darunter zu liegen.
                function messeObenFrei() {
                    var k = flaeche.getBoundingClientRect(), frei = 0;
                    [].forEach.call(document.body.querySelectorAll('body > *'), function (e) {
                        if (e.contains(wrap)) return;
                        var st = getComputedStyle(e);
                        if (st.position !== 'fixed' || st.display === 'none' || st.visibility === 'hidden' || +st.opacity === 0) return;
                        var r = e.getBoundingClientRect();
                        if (!r.height || r.height >= window.innerHeight / 2 || r.top > k.top + 1) return;
                        if (r.right <= k.left || r.left >= k.right) return;
                        frei = Math.max(frei, Math.min(r.bottom - k.top, k.height / 2));
                    });
                    // Tiefer geschachtelte feste Koepfe (SPTheme: .page-header ist kein Kind
                    // von <body>): was an der Kartenoberkante obenauf liegt, bis zum festen
                    // Vorfahren hochlaufen.
                    // ab der sichtbaren Oberkante (gescrollt steht die Karte z. B. bei -0,5 px)
                    var y = Math.max(k.top, 0) + 2;
                    if (k.bottom > y && y < window.innerHeight && document.elementFromPoint) {
                        [0.2, 0.5, 0.8].forEach(function (anteil) {
                            var e = document.elementFromPoint(k.left + k.width * anteil, y);
                            for (; e && e !== document.body && !wrap.contains(e); e = e.parentElement) {
                                var pos = getComputedStyle(e).position;
                                if (pos !== 'fixed' && pos !== 'sticky') continue;
                                var r = e.getBoundingClientRect();
                                if (r.height < window.innerHeight / 2) frei = Math.max(frei, Math.min(r.bottom - k.top, k.height / 2));
                                break;
                            }
                        });
                    }
                    wrap.style.setProperty('--lm-oben-frei', Math.max(0, Math.round(frei)) + 'px');
                }
                // Hell/Dunkel: Einstellung, sonst das Theme (Seven und SPTheme setzen
                // data-bs-theme am <html>; manche Themes eine Klasse am <body>).
                function istDunkel() {
                    if (cfg.themeModus === 'dark') return true;
                    if (cfg.themeModus === 'light') return false;
                    var h = document.documentElement, b = document.body;
                    // Seven/SPTheme: data-bs-theme; Disposable_v3 (Dunkel-Schalter): body data-theme
                    var attr = (h.getAttribute('data-bs-theme') || (b && b.getAttribute('data-bs-theme')) ||
                        h.getAttribute('data-theme') || (b && b.getAttribute('data-theme')) || '').toLowerCase();
                    if (attr) return attr === 'dark';
                    return [h, b].some(function (e) { return e && /(^|\s)(dark|dark-mode|theme-dark|darkmode)(\s|$)/.test(e.className || ''); });
                }
                function setzeDunkel() { wrap.classList.toggle('ist-dunkel', istDunkel()); }
                messeBreite();
                setzeDunkel();
                messeHoehe();

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
                // Schmal passt die Welt erst ab Zoom 1 hinein (Fluege EU + Pazifik).
                if (wrap && wrap.classList.contains('lm3--schmal')) map.setMinZoom(1);
                window._liveMapInstance = map;
                // Zoom oben links: dort hat das Raster eine eigene Spalte dafuer.
                L.control.zoom({ position: 'topleft' }).addTo(map);

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
                    // CARTO verlangt seit dem 26.08.2026 einen Schluessel (sonst
                    // Wasserzeichen). Ohne Schluessel bietet der Umschalter die
                    // CARTO-Stile gar nicht erst an (5.1).
                    var cartoKey = String(cfg.cartoApiKey || '');
                    var keys = cartoKey ? ['positron', 'osm', 'dark'] : ['osm'];
                    if (allowSatellite) keys.push('satellite');
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
                    // Ohne eigene Wahl folgt der Kartenstil dem Theme: dunkel → CARTO
                    // Dark Matter (nur mit Schluessel), hell → Standard der Verwaltung.
                    var themeWahl = function () {
                        var std = String(cfg.defaultBasemap || 'osm').toLowerCase();
                        if (wrap.classList.contains('ist-dunkel') && ebenen.dark && (std === 'positron' || std === 'osm')) return 'dark';
                        return ebenen[std] ? std : 'osm';
                    };
                    var wahl = gespeichert && ebenen[gespeichert] ? gespeichert : themeWahl();
                    if (!ebenen[wahl]) wahl = 'osm';
                    ebenen[wahl].addTo(map);
                    if (wrap) wrap.setAttribute('data-grundkarte', wahl);
                    // Wechselt das Theme spaeter (Schalter in Seven/SPTheme), zieht die
                    // Karte mit — ausser jemand hat selbst einen Stil gewaehlt.
                    window._lmThemeGewechselt = function () {
                        var gew = '';
                        try { gew = String(localStorage.getItem('livemap_basemap') || ''); } catch (e) {}
                        if (gew) return;
                        var neu = themeWahl();
                        if (neu === wahl || !ebenen[neu]) return;
                        map.removeLayer(ebenen[wahl]); ebenen[neu].addTo(map); wahl = neu;
                        wrap.setAttribute('data-grundkarte', neu);
                        if (kartenListe) [].forEach.call(kartenListe.children, function (b) { b.classList.toggle('active', b.getAttribute('data-grundkarte') === neu); });
                    };
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
                // nach updateCtrlZoom registriert: rAF laeuft erst, wenn dessen display-Werte stehen
                map.on('zoomend moveend layeradd layerremove', lmEntwirrenPlanen);
                // Nach einem animierten Zoom gleiten die Symbole noch ~0,25 s (CSS-Uebergang);
                // gemessen wird dann zu frueh. Ein zweiter Lauf nach dem Gleiten.
                var lmNachZoom = null;
                map.on('zoomend', function () { clearTimeout(lmNachZoom); lmNachZoom = setTimeout(lmEntwirrenPlanen, 350); });

                var va = starteVaFluege(map);

                // ── Netz-Schalter (wie bisher) ──
                var btnNetVatsim = document.getElementById('btnNetVatsim'), btnNetIvao = document.getElementById('btnNetIvao');
                // Punkt am Netz-Knopf: gruen, solange VATSIM oder IVAO eingeschaltet ist
                function netzPunkt() { var d = document.getElementById('vatsimDot'); if (d) d.classList.toggle('live', showVatsim || showIvao); }
                netzPunkt();
                // nach dem eigentlichen Umschalten (dieser Lauscher haengt davor)
                [btnNetVatsim, btnNetIvao].forEach(function (b) { if (b) b.addEventListener('click', function () { setTimeout(netzPunkt, 0); }); });
                if (btnNetVatsim) { btnNetVatsim.classList.toggle('active', showVatsim); btnNetVatsim.addEventListener('click', function () { showVatsim = !showVatsim; btnNetVatsim.classList.toggle('active', showVatsim); if (!showVatsim) { map.removeLayer(vatsimPilotsLayer); map.removeLayer(vatsimCtrlLayer); map.removeLayer(vatsimSectorLayer); } else { loadVatsim(map); applyLayerVisibility(); } }); }
                if (btnNetIvao) { btnNetIvao.classList.toggle('active', showIvao); btnNetIvao.addEventListener('click', function () { showIvao = !showIvao; btnNetIvao.classList.toggle('active', showIvao); if (!showIvao) { map.removeLayer(ivaoPilotsLayer); map.removeLayer(ivaoCtrlLayer); map.removeLayer(ivaoSectorLayer); } else { loadIvao(map); applyLayerVisibility(); } }); }
                var btnPilots = document.getElementById('btnVatsimPilots'), btnCtrl = document.getElementById('btnVatsimCtrl'), btnSectors = document.getElementById('btnVatsimSectors');
                if (btnPilots) { btnPilots.classList.toggle('active', vatsimShowPilots); btnPilots.addEventListener('click', function () { vatsimShowPilots = !vatsimShowPilots; btnPilots.classList.toggle('active', vatsimShowPilots); applyLayerVisibility(); }); }
                if (btnCtrl) { btnCtrl.classList.toggle('active', vatsimShowCtrl); btnCtrl.addEventListener('click', function () { vatsimShowCtrl = !vatsimShowCtrl; btnCtrl.classList.toggle('active', vatsimShowCtrl); applyLayerVisibility(); }); }
                if (btnSectors) { btnSectors.classList.toggle('active', vatsimShowSectors); btnSectors.addEventListener('click', function () { vatsimShowSectors = !vatsimShowSectors; btnSectors.classList.toggle('active', vatsimShowSectors); applyLayerVisibility();
                    if (vatsimShowSectors && showIvao && letzteIvaoFirMap) ladeGrenzen().then(function(){ renderActiveSectors(letzteIvaoFirMap, ivaoSectorLayer); }).catch(function(){}); }); }
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
                        // Schmal teilen sich Kasten, Tafel und Detail eine Spalte.
                        if (offen && va && va.schmal()) { va.setzeTafel(false); va.schliesseDetail(); }
                        if (va && !va.schmal()) va.passeRechts();
                    });
                });
                if (cfg.showTopFlightsPanel === false) {
                    var p = document.getElementById('va-flights-panel');
                    if (p) p.hidden = true;
                }
                if (cfg.mobileShowFlightsButton === false) wrap.classList.add('ohne-tafel-mobil');

                // Startzustand der Tafel: breit eingeklappt (Kopfzeile wie 4.7.9),
                // schmal nach Einstellung „Mobil: Flugliste offen".
                function startPanel() {
                    if (!va) return;
                    va.setzeTafel(va.schmal() ? !!cfg.mobileFlightsOpen : false);
                }
                startPanel();
                // Admin-Einstellungen „Mobil: Wetter/Netzwerk offen" (4.7.x): schmal
                // den Aufklapper beim Start oeffnen — nur einen, sie teilen sich den Platz.
                if (wrap && wrap.classList.contains('lm3--schmal') && !cfg.mobileFlightsOpen) {
                    var startAuf = cfg.mobileNetworkOpen ? 'lm3-btn-netz' : (cfg.mobileWeatherOpen ? 'lm3-btn-wetter' : null);
                    var startKnopf = startAuf && document.getElementById(startAuf);
                    if (startKnopf && !startKnopf.hidden) startKnopf.click();
                }

                var letzteSchmal = wrap && wrap.classList.contains('lm3--schmal');
                var neuMessen = function () {
                    messeBreite();
                    var schmal = wrap && wrap.classList.contains('lm3--schmal');
                    if (schmal !== letzteSchmal) { letzteSchmal = schmal; map.setMinZoom(schmal ? 1 : 2); startPanel(); }
                    map.invalidateSize();
                };
                if (window.ResizeObserver && wrap) new ResizeObserver(neuMessen).observe(wrap);
                window.addEventListener('resize', function () { messeHoehe(); neuMessen(); if (va) va.passeRechts(); });
                // Theme-Schalter (hell/dunkel) beobachten.
                if (window.MutationObserver) {
                    var beiThemeWechsel = function () {
                        var vorher = wrap.classList.contains('ist-dunkel');
                        setzeDunkel();
                        if (vorher !== wrap.classList.contains('ist-dunkel') && window._lmThemeGewechselt) window._lmThemeGewechselt();
                    };
                    var mo = new MutationObserver(beiThemeWechsel);
                    mo.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme', 'data-theme', 'class'] });
                    if (document.body) mo.observe(document.body, { attributes: true, attributeFilter: ['data-bs-theme', 'data-theme', 'class'] });
                }
                // Spaet geladene Leisten (Cookie-Hinweis) aendern die freie Hoehe.
                window.addEventListener('load', function () { messeHoehe(); map.invalidateSize(); });
                // Oben frei: beim Scrollen (feste Navigationsleiste) und wenn der Cookie-
                // Hinweis nach dem Klick ausblendet (Klasse + Uebergang, kein childList).
                var obenTimer = null;
                // messeHoehe misst auch „oben frei" und rechnet die Hoehe neu, wenn eine
                // feste Leiste unten (Cookie-Hinweis) verschwindet.
                function obenSpaeter() { clearTimeout(obenTimer); obenTimer = setTimeout(function () { if (messeHoehe()) map.invalidateSize(); }, 120); }
                window.addEventListener('scroll', obenSpaeter, { passive: true });
                document.addEventListener('click', function () { setTimeout(obenSpaeter, 400); }, true);
                document.addEventListener('transitionend', function (ev) { if (!/^margin/.test(ev.propertyName || '')) obenSpaeter(); }, true);
                // Manche Themes haengen die Cookie-Leiste erst nach dem Laden in <body>
                // (SPTheme) — dann neu messen, sonst laege sie ueber dem Tafelkopf unten.
                if (window.MutationObserver && document.body) {
                    var hoeheTimer = null;
                    new MutationObserver(function () {
                        clearTimeout(hoeheTimer);
                        hoeheTimer = setTimeout(function () { messeHoehe(); map.invalidateSize(); }, 300);
                    }).observe(document.body, { childList: true });
                }
                // Tafel unten: neue/weggefallene Fluege oder Reiterwechsel aendern ihre
                // Hoehe → Karte neu aufteilen (nur das Stilattribut der Tafel selbst
                // aendert sich durch messeHoehe, das beobachten wir nicht → keine Schleife).
                var untenBox = wrap && wrap.classList.contains('lm3--tafel-unten') && wrap.querySelector('.lm3-unten');
                if (window.MutationObserver && untenBox) {
                    var untenTimer = null;
                    new MutationObserver(function () {
                        clearTimeout(untenTimer);
                        untenTimer = setTimeout(function () { if (messeHoehe()) map.invalidateSize(); }, 150);
                    }).observe(untenBox, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden'] });
                }
            })();
        });
    </script>
    <!-- lm3:skripte-ende -->
