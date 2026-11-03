{{-- Leaflet bringt das Modul selbst mit (5.1). Seven laedt kein Leaflet-CSS
     (vendor.css ist dort auskommentiert) — ohne diese Zeile zerfiel die Karte
     in einen Kachelstapel (gemessen test.kant.ovh, 03.11.2026). Die Klassen
     `.leaflet-*` sind absichtlich global; ein zweites Leaflet-CSS des Themes
     stoert nicht. --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha384-sHL9NAb7lN7rfvG5lfHpm643Xkcjzp4jFvuavGOndn6pjVqS6ny56CAt3nsEVT4H" crossorigin="anonymous">
<style>
                    /* ══════════════════════════════════════════════════════
                       Live-Karte 5.1 — alle eigenen Regeln unter .lm3
                       (#lm-root). Optik wie 4.7.9, Farben aus den Einstellungen.
                       ══════════════════════════════════════════════════════ */
                    .lm-hidden { display: none !important; }
                    .lm3 .lm-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

                    .lm3 {
                        --lm-primary: {{ $flightsHeaderStart }};
                        --lm-accent: {{ $flightsHeaderEnd }};
                        --lm-flaeche: {{ $boxBackgroundRgba }};
                        --lm-flaeche-2: #f6f8fb;
                        --lm-text: #1a2a4a;
                        --lm-text-2: #667085;
                        --lm-linie: #e6eaf0;
                        --lm-zeile-hover: #f0f6ff;
                        --lm-gewaehlt: #e8f0fd;
                        --lm-schatten: 0 6px 24px rgba(0,0,0,.22), 0 2px 6px rgba(0,0,0,.10);
                        --lm-gold: #E8B131;
                        --lm-rand: 10px;
                        --lm-unten: 26px;   /* Platz fuer die Leaflet-Quellenzeile */
                        --lm-zoom: 44px;    /* Spalte der Zoom-Knoepfe */
                        --lm-rechts: 300px;
                        position: relative;
                        width: 100%;
                        overflow: hidden;
                        border-radius: inherit;
                        color: var(--lm-text);
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        font-size: 13px;
                        line-height: 1.35;
                        font-variant-numeric: tabular-nums;
                        isolation: isolate;
                        text-align: left;
                    }
                    .lm3.ist-dunkel {
                        --lm-flaeche: rgba(22, 30, 44, 0.97);
                        --lm-flaeche-2: #1e2530;
                        --lm-text: #e3e9f2;
                        --lm-text-2: #8b9bb0;
                        --lm-linie: #2a3444;
                        --lm-zeile-hover: #1e2a3a;
                        --lm-gewaehlt: #1d2d45;
                        --lm-schatten: 0 6px 24px rgba(0,0,0,.5), 0 2px 6px rgba(0,0,0,.35);
                    }
                    .lm3 *, .lm3 *::before, .lm3 *::after { box-sizing: border-box; }
                    /* Die Kartenflaeche traegt die Hoehe; Startwert, das Skript setzt sie aus
                       Fenster minus Seitenkopf (Einstellung Mindest-/Hoechsthoehe). */
                    .lm3-flaeche { position: relative; height: clamp({{ $lmMinHoehe }}px, calc(100vh - 160px), {{ $lmMaxHoehe }}px); overflow: hidden; }
                    .lm3 #map { position: absolute; inset: 0; width: 100%; height: 100%; transition: filter .3s ease; background: #dfe6ee; z-index: 0; }
                    .lm3.ist-dunkel #map { background: #0d1726; }
                    .lm3 .dark-map { filter: brightness(0.55) contrast(1.15) saturate(1.05); }
                    .lm3 button { font: inherit; margin: 0; }
                    .lm3 button:focus-visible, .lm3 select:focus-visible, .lm3 a:focus-visible { outline: 2px solid var(--lm-gold); outline-offset: 2px; }

                    /* ── Raster ueber der Karte ── */
                    .lm3-ui {
                        position: absolute; inset: 0; z-index: 1000; pointer-events: none;
                        display: grid;
                        grid-template-columns: var(--lm-zoom) minmax(0, 1fr) var(--lm-rechts);
                        column-gap: var(--lm-rand);
                        padding: var(--lm-rand) var(--lm-rand) var(--lm-unten) var(--lm-rand);
                    }
                    .lm3-mitte {
                        grid-column: 2; min-width: 0; min-height: 0;
                        display: flex; justify-content: center; align-items: flex-start;
                        container-type: inline-size; container-name: lmmitte;
                    }
                    .lm3-rechts {
                        grid-column: 3; min-width: 0; min-height: 0;
                        display: flex; flex-direction: column; align-items: stretch; gap: 8px;
                    }
                    .lm3-tafel, .lm3-wz, .lm3-box, .lm3-detail { pointer-events: auto; }

                    /* ── Tafel (wie 4.7.9: oben mittig, eingeklappt nur die Kopfzeile) ── */
                    .lm3-tafel {
                        display: flex; flex-direction: column; max-height: 100%; max-width: 100%;
                        width: max-content; min-width: 0;
                        border-radius: 10px; box-shadow: var(--lm-schatten); overflow: hidden;
                        background: var(--lm-flaeche);
                        --lm-sp: 148px 104px 60px 44px 84px 92px minmax(72px, 1fr);
                    }
                    .lm3-tafel-kopf {
                        flex: 0 0 auto; display: flex; align-items: center; gap: 14px;
                        padding: 9px 16px; border: 0; cursor: pointer; user-select: none; width: 100%;
                        background: linear-gradient(135deg, var(--lm-primary) 0%, var(--lm-accent) 100%); color: #fff;
                        font-size: 13px; font-weight: 600; letter-spacing: .2px; white-space: nowrap;
                    }
                    .lm3-kopf-stat { display: inline-flex; align-items: center; gap: 6px; }
                    .lm3-kopf-stat svg { width: 14px; height: 14px; fill: currentColor; opacity: .85; flex: 0 0 auto; }
                    .lm3-kopf-stat b { font-weight: 800; font-size: 15px; }
                    .lm3-n-aktiv { color: #6fcf7c; }
                    .lm3-n-geplant { color: #7cb8f0; }
                    .lm3-kopf-trenner { width: 1px; height: 18px; background: rgba(255,255,255,.25); }
                    .lm3-chevron { margin-left: auto; font-size: 16px; color: rgba(255,255,255,.7); transition: transform .2s; }
                    .lm3-tafel-kopf[aria-expanded="true"] .lm3-chevron { transform: rotate(180deg); }
                    .lm3-punkt { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.45); display: inline-block; }
                    .lm3-punkt.ist-live { background: #6fcf7c; box-shadow: 0 0 0 3px rgba(111,207,124,.25); }
                    .lm3-punkt.ist-fehler { background: #ef4444; }
                    .lm3-tafel-inhalt { display: flex; flex-direction: column; min-height: 0; flex: 1 1 auto; }
                    .lm3-tafel-inhalt[hidden] { display: none; }

                    .lm3-reiter { display: flex; align-items: stretch; border-bottom: 1px solid var(--lm-linie); background: var(--lm-flaeche-2); flex: 0 0 auto; }
                    .lm3-reiter [role="tab"] {
                        flex: 1 1 0; padding: 8px 12px; border: 0; white-space: nowrap; border-bottom: 2px solid transparent; background: none; cursor: pointer;
                        font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--lm-text-2);
                    }
                    .lm3-reiter [role="tab"][aria-selected="true"] { color: var(--lm-text); border-bottom-color: #3498db; }
                    .lm3-tabzahl { display: inline-block; font-size: 9px; font-weight: 800; border-radius: 999px; padding: 2px 7px; margin-left: 4px; background: var(--lm-linie); color: var(--lm-text-2); }
                    [aria-selected="true"] > .lm3-tabzahl { background: #3498db; color: #fff; }
                    .lm3-filter { display: flex; align-items: center; padding: 0 8px; margin: 0; }
                    .lm3-filter[hidden] { display: none; }
                    .lm3-filter select { font-size: 11px; padding: 2px 4px; border-radius: 6px; border: 1px solid var(--lm-linie); background: var(--lm-flaeche); color: var(--lm-text); max-width: 120px; }

                    .lm3-thead {
                        flex: 0 0 auto; padding: 6px 14px; background: var(--lm-flaeche-2); border-bottom: 1px solid var(--lm-linie);
                        font-size: 10px; font-weight: 800; color: var(--lm-text-2); letter-spacing: .6px; text-transform: uppercase;
                    }
                    .lm3-thead[hidden] { display: none; }
                    .lm3-g-akt { display: grid; grid-template-columns: var(--lm-sp); column-gap: 6px; align-items: center; }
                    .lm3-g-plan { display: grid; grid-template-columns: 148px 104px 70px minmax(72px, 1fr); column-gap: 6px; align-items: center; }
                    .lm3-zeilen { overflow-y: auto; overflow-x: hidden; min-height: 0; max-height: 196px; flex: 0 1 auto; }
                    .lm3-zeile {
                        width: 100%; padding: 6px 14px; min-height: 34px; border: 0; border-bottom: 1px solid var(--lm-linie);
                        background: none; color: var(--lm-text); text-align: left; cursor: pointer; font-size: 12.5px;
                    }
                    .lm3-zeile:last-child { border-bottom: 0; }
                    .lm3-zeile:hover { background: var(--lm-zeile-hover); }
                    .lm3-zeile.ist-gewaehlt { background: var(--lm-gewaehlt); }
                    .lm3-zeile.ist-offline { opacity: .55; }
                    .lm3-zeile.lm3-g-plan { cursor: default; }
                    .lm3-zeile > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                    .lm3-flug { display: flex !important; align-items: center; gap: 6px; }
                    .lm3-flug b { font-weight: 700; overflow: hidden; text-overflow: ellipsis; }
                    .lm3-logo { width: 40px; height: 18px; flex: 0 0 40px; display: inline-flex; align-items: center; justify-content: center; background: #fff; border-radius: 4px; border: 1px solid rgba(0,0,0,.07); overflow: hidden; }
                    .lm3-logo img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
                    .lm3-logo.ist-leer { background: transparent; border-color: transparent; }
                    .lm3-pfeil { color: var(--lm-text-2); }
                    .lm3-pille { display: inline-block; max-width: 100%; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; background: var(--ph, #94a3b8); color: #0b1f3a; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
                    .lm3-leer { padding: 14px 12px; margin: 0; text-align: center; font-size: 11.5px; color: var(--lm-text-2); }
                    .lm3-meldung { margin: 0; padding: 7px 14px; font-size: 11.5px; color: #b42318; background: #fef3f2; border-bottom: 1px solid var(--lm-linie); }
                    .lm3.ist-dunkel .lm3-meldung { background: #3a1d1d; color: #fda29b; }
                    .lm3-meldung[hidden] { display: none; }
                    .lm3-nochmal { margin-left: 8px; border: 1px solid currentColor; background: none; color: inherit; border-radius: 6px; padding: 1px 8px; cursor: pointer; font-size: 11px; }

                    /* ── Tafel unten: feste Tabelle unter der Karte, ganze Breite ── */
                    .lm3-unten { container-type: inline-size; container-name: lmmitte; border-top: 1px solid var(--lm-linie); background: var(--lm-flaeche); }
                    .lm3-unten .lm3-tafel {
                        width: 100%; max-width: none; max-height: none; border-radius: 0; box-shadow: none;
                        --lm-sp: minmax(150px, 1.4fr) minmax(110px, 1fr) 70px 54px 96px 110px minmax(100px, 1.2fr);
                    }
                    .lm3-unten .lm3-tafel-kopf { cursor: default; user-select: auto; }
                    .lm3-unten .lm3-g-plan { grid-template-columns: minmax(150px, 1.4fr) minmax(110px, 1fr) 96px minmax(100px, 1.2fr); }
                    .lm3-unten .lm3-zeilen { max-height: 360px; }

                    /* Tafel gibt Spalten ab, statt ueber die rechte Spalte zu wachsen
                       (Selektoren mit .lm3 davor, damit sie auch die Tafel unten treffen) */
                    @container lmmitte (max-width: 720px) {
                        .lm3 .lm3-tafel { --lm-sp: 140px 100px 60px 44px 84px minmax(80px, 1fr); }
                        .lm3 .lm3-tafel .lm3-sp-pilot { display: none; }
                    }
                    @container lmmitte (max-width: 600px) {
                        .lm3 .lm3-tafel { --lm-sp: 140px 100px 60px 44px minmax(80px, 1fr); }
                        .lm3 .lm3-tafel .lm3-sp-pilot, .lm3-tafel .lm3-sp-dist { display: none; }
                        .lm3 .lm3-g-plan { grid-template-columns: 140px 100px minmax(60px, 1fr); }
                        .lm3 .lm3-g-plan .lm3-sp-pilot { display: none; }
                    }
                    @container lmmitte (max-width: 480px) {
                        .lm3 .lm3-tafel { --lm-sp: minmax(0, 1fr) 96px minmax(70px, 88px); }
                        .lm3 .lm3-tafel .lm3-sp-pilot, .lm3-tafel .lm3-sp-dist, .lm3-tafel .lm3-sp-alt, .lm3-tafel .lm3-sp-gs { display: none; }
                        .lm3 .lm3-g-plan { grid-template-columns: minmax(0, 1fr) 96px; }
                        .lm3 .lm3-g-plan .lm3-sp-zeit { display: none; }
                        .lm3 .lm3-tafel-kopf { gap: 10px; padding: 9px 12px; }
                        .lm3 .lm3-reiter [role="tab"] { padding: 8px 6px; }
                        /* Rufzeichen vor Logo: schmal bleibt das Rufzeichen lesbar */
                        .lm3 .lm3-zeile .lm3-logo { display: none; }
                    }

                    /* ── Werkzeuge (oben rechts) ── */
                    .lm3-werkzeuge { flex: 0 0 auto; display: flex; justify-content: flex-end; gap: 6px; pointer-events: none; }
                    .lm3-wz {
                        position: relative; display: inline-flex; align-items: center; justify-content: center;
                        width: 36px; height: 36px; padding: 0; border-radius: 10px; cursor: pointer;
                        border: 1px solid var(--lm-linie); background: var(--lm-flaeche); color: var(--lm-text); box-shadow: var(--lm-schatten);
                    }
                    .lm3-wz svg { width: 18px; height: 18px; }
                    .lm3-wz-text { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
                    .lm3-wz:hover { border-color: var(--lm-gold); }
                    .lm3-wz[aria-expanded="true"], .lm3-wz.active { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }
                    .lm3-wz[hidden] { display: none; }
                    .lm3-wz .vatsim-dot { position: absolute; top: 5px; right: 5px; width: 7px; height: 7px; border-radius: 50%; background: #98a2b3; }

                    /* ── Kaesten Netz / Wetter / Kartenstil ── */
                    .lm3-box {
                        flex: 0 1 auto; min-height: 0; overflow-y: auto; padding: 12px;
                        background: var(--lm-flaeche); border-radius: 10px; box-shadow: var(--lm-schatten); border: 1px solid var(--lm-linie);
                    }
                    .lm3-box[hidden] { display: none; }
                    .lm3-box-titel { font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--lm-text-2); margin-bottom: 8px; }
                    .lm3-reihe { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px; }
                    .lm3-raster { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
                    .lm3-breit { grid-column: 1 / -1; }
                    .lm3-schalter {
                        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
                        min-height: 30px; padding: 4px 8px; border-radius: 7px; cursor: pointer;
                        border: 1px solid var(--lm-linie); background: transparent; color: var(--lm-text); font-size: 12px; font-weight: 600;
                    }
                    .lm3-schalter:hover { border-color: var(--lm-gold); }
                    .lm3-schalter.active { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }
                    .lm3.ist-dunkel .lm3-schalter.active { background: #7cb8f0; border-color: #7cb8f0; color: #0b1f3a; }
                    .lm3 .map-network-dot { width: 8px; height: 8px; border-radius: 50%; background: #98a2b3; display: inline-block; }
                    .lm3-statistik { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 8px; font-size: 11px; color: var(--lm-text-2); text-align: center; }
                    .lm3-regler { display: flex; align-items: center; gap: 10px; margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--lm-text-2); }
                    .lm3-regler label { margin: 0; }
                    .lm3-regler input { flex: 1 1 auto; min-width: 0; }
                    .lm3-hinweis { margin: 8px 0 0; font-size: 11px; color: var(--lm-text-2); }
                    .lm3 #weather-unavailable-note { margin-top: 8px; font-size: 11.5px; line-height: 1.4; }
                    .lm3.ist-dunkel .map-network-legend { border-top-color: var(--lm-linie); }
                    .lm3.ist-dunkel .map-network-legend-label { color: var(--lm-text-2); }

                    /* ── Detailkasten (wie der 4.7.9-„Crew Pass“, rechts) ── */
                    .lm3-detail {
                        flex: 0 1 auto; min-height: 0; overflow-y: auto; overflow-x: hidden;
                        background: var(--lm-flaeche); border-radius: 10px; box-shadow: var(--lm-schatten);
                    }
                    .lm3-detail[hidden] { display: none; }
                    .lm3-d-kopf { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: linear-gradient(135deg, var(--lm-primary) 0%, var(--lm-accent) 100%); color: #fff; position: sticky; top: 0; z-index: 1; }
                    .lm3-d-logo { height: 30px; min-width: 56px; max-width: 96px; padding: 3px 7px; border-radius: 6px; background: rgba(255,255,255,.95); display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
                    .lm3-d-logo img { max-height: 24px; max-width: 82px; object-fit: contain; display: block; }
                    .lm3-d-cs { font-size: 14px; font-weight: 800; letter-spacing: .3px; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1 1 auto;
                        border: 0; padding: 0; background: none; color: inherit; text-align: left; cursor: default; }
                    /* Eingeklappt: nur die Kopfzeile, Rufzeichen klappt wieder auf */
                    .lm3-detail.ist-klein > :not(.lm3-d-kopf) { display: none !important; }
                    .lm3-detail.ist-klein .lm3-d-cs { cursor: pointer; }
                    .lm3-detail.ist-klein .lm3-d-cs::after { content: ' ▾'; opacity: .75; }
                    .lm3-zu { flex: 0 0 auto; width: 26px; height: 26px; border-radius: 6px; border: 0; background: rgba(255,255,255,.14); color: #fff; cursor: pointer; font-size: 15px; line-height: 1; }
                    .lm3-zu:hover { background: rgba(255,255,255,.26); }
                    .lm3-d-route { display: flex; align-items: center; padding: 10px 12px; background: var(--lm-flaeche-2); border-bottom: 1px solid var(--lm-linie); }
                    .lm3-d-ap { flex: 1 1 0; min-width: 0; text-align: center; }
                    .lm3-d-icao { font-size: 20px; font-weight: 900; letter-spacing: 1px; line-height: 1; color: var(--lm-text); }
                    .lm3-d-name { font-size: 9.5px; font-weight: 600; text-transform: uppercase; color: var(--lm-text-2); margin-top: 3px; line-height: 1.25; overflow-wrap: anywhere; }
                    .lm3-d-mitte { flex: 0 0 auto; padding: 0 6px; text-align: center; color: var(--lm-text-2); font-size: 16px; }
                    .lm3-d-mitte small { display: block; font-size: 9.5px; font-weight: 700; }
                    .lm3-d-raster { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 10px; padding: 8px 12px 10px; margin: 0; }
                    .lm3-d-raster > div { min-width: 0; }
                    .lm3-d-raster dt { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--lm-text-2); margin: 0 0 1px; }
                    .lm3-d-raster dd { font-size: 12px; font-weight: 700; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .lm3-d-breit { grid-column: 1 / -1; }
                    .lm3-balken { height: 5px; border-radius: 3px; background: var(--lm-linie); margin-top: 3px; overflow: hidden; }
                    .lm3-balken i { display: block; height: 100%; background: var(--ph, #10b981); border-radius: 3px; }
                    .lm3-d-mehr { border-top: 1px solid var(--lm-linie); padding: 0 12px; }
                    .lm3-d-mehr summary { cursor: pointer; padding: 6px 0; font-size: 11px; font-weight: 700; color: var(--lm-text-2); }
                    .lm3-d-mehr .lm3-d-raster { padding: 0 0 8px; }
                    .lm3-badges { display: flex; flex-wrap: wrap; gap: 4px; padding-bottom: 8px; }
                    .lm3-badge { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 5px; border: 1px solid var(--lm-linie); color: var(--lm-text-2); }
                    .lm3-badge.ist-an { background: #10b981; border-color: #10b981; color: #fff; }
                    .lm3-flag { display: inline-block; margin: 6px 12px 0; padding: 2px 8px; border-radius: 5px; background: #ef4444; color: #fff; font-size: 10.5px; font-weight: 800; }
                    .lm3-d-fuss { display: flex; align-items: center; gap: 8px; padding: 7px 12px; background: var(--lm-flaeche-2); border-top: 1px solid var(--lm-linie); font-size: 11px; color: var(--lm-text-2); }
                    .lm3-d-fuss .lm3-pille { font-size: 11px; padding: 2px 10px; }
                    .lm3-d-fuss .lm3-weit { margin-left: auto; }
                    .lm3-d-link { color: var(--lm-text); font-weight: 700; text-decoration: none; white-space: nowrap; }
                    .lm3-d-link:hover { text-decoration: underline; }
                    .lm3-knopf { border: 1px solid var(--lm-linie); background: var(--lm-flaeche); color: var(--lm-text); border-radius: 6px; padding: 2px 8px; cursor: pointer; font-size: 11px; font-weight: 700; white-space: nowrap; }
                    .lm3-knopf.active { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }

                    /* ── Leaflet im Widget ── */
                    .lm3 .leaflet-control-zoom { border: 1px solid var(--lm-linie) !important; border-radius: 10px !important; box-shadow: var(--lm-schatten) !important; overflow: hidden; }
                    .lm3 .leaflet-control-zoom a { width: 32px !important; height: 32px !important; line-height: 32px !important; }
                    .lm3.ist-dunkel .leaflet-control-zoom a { background: #1e2530; color: #e3e9f2; border-color: #2a3444; }
                    .lm3 .leaflet-top.leaflet-left .leaflet-control { margin: var(--lm-rand) 0 0 var(--lm-rand); }
                    .lm3 .leaflet-control-attribution { font-size: 10px; line-height: 16px; }
                    .lm3.ist-dunkel .leaflet-control-attribution { background: rgba(22,30,44,.8); color: #8b9bb0; }
                    .lm3.ist-dunkel .leaflet-control-attribution a { color: #a9c3e0; }

                    /* ── Flugzeugsymbole: Silhouette nach Klasse, Fuellung = Phase ── */
                    .lm3-marker { background: none; border: 0; }
                    .lm3-flieger { position: absolute; left: 0; top: 0; display: block; transform-origin: 50% 50%; }
                    .lm3-flieger svg { display: block; width: 100%; height: 100%; overflow: visible; }
                    /* Fuellung und Kontur am <svg>: in den Inhalt von <use> greifen keine
                       Selektoren, nur geerbte Werte. */
                    .lm3-flieger svg { fill: var(--ph); stroke: #0b1f3a; stroke-linejoin: round; paint-order: stroke; }
                    .lm3[data-grundkarte="dark"] .lm3-flieger svg, .lm3[data-grundkarte="satellite"] .lm3-flieger svg { stroke: #f1f5f9; }
                    .lm3-marker.ist-offline .lm3-flieger { opacity: .5; }
                    .lm3-ring { position: absolute; border-radius: 50%; border: 2px solid var(--lm-gold); box-shadow: 0 0 0 2px rgba(11,31,58,.35); pointer-events: none; }
                    .lm3 .lm3-verdeckt { visibility: hidden !important; pointer-events: none !important; }
                    .lm3 .lm3-verdeckt * { visibility: hidden !important; }
                    .lm3-mehr {
                        position: absolute; right: -2px; top: -2px; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 8px;
                        background: var(--lm-gold); color: #0b1f3a; border: 1px solid #0b1f3a;
                        font-size: 9.5px; font-weight: 800; line-height: 14px; text-align: center; cursor: zoom-in; pointer-events: auto;
                    }
                    .lm3-mlabel {
                        position: absolute; left: 50%; transform: translateX(-50%);
                        padding: 1px 6px; border-radius: 5px; background: rgba(11,31,58,.88); color: #fff;
                        font-size: 10.5px; font-weight: 700; white-space: nowrap; pointer-events: none;
                    }
                    .lm3-netz-flieger svg { display: block; overflow: visible; }
                    .lm3-netz-flieger svg { fill: #9aa5b4; stroke: #4b5563; stroke-linejoin: round; paint-order: stroke; }
                    .lm3-netz-flieger.ist-ivao svg { fill: #b9a58c; }
                    .lm3 path.lm3-spur { stroke: #D01722; stroke-opacity: 1; stroke-linecap: round; stroke-linejoin: round; }
                    .lm3 path.lm3-spur-schatten { stroke: #ffffff; stroke-opacity: .85; stroke-linecap: round; stroke-linejoin: round; }
                    .lm3 path.lm3-route { stroke: #0B1F3A; stroke-opacity: .55; }
                    .lm3[data-grundkarte="dark"] path.lm3-route, .lm3[data-grundkarte="satellite"] path.lm3-route { stroke: #8FB4D6; stroke-opacity: .8; }
                    .lm3[data-grundkarte="dark"] path.lm3-spur-schatten { stroke: #0B1F3A; }

                    /* ── schmal (< 760 px Widget-Breite): eine Spalte, Blatt unten ── */
                    .lm3--schmal { --lm-unten: 22px; }
                    .lm3--schmal .lm3-ui { display: flex; flex-direction: column; gap: 8px; padding: 8px 8px var(--lm-unten) 8px; }
                    .lm3--schmal .lm3-mitte, .lm3--schmal .lm3-rechts { display: contents; }
                    /* display: contents erzeugt keinen Container, deshalb misst hier die ganze Ebene */
                    .lm3--schmal .lm3-ui { container-type: inline-size; container-name: lmmitte; }
                    .lm3--schmal .lm3-mitte { container-type: normal; container-name: none; }
                    /* Werkzeuge neben dem Zoom, nicht darueber; die Zeile ist so hoch wie
                       der Zoom, damit nichts Folgendes (Kasten, Tafel) an ihn heranwaechst. */
                    .lm3--schmal .lm3-werkzeuge { order: 1; margin-left: var(--lm-zoom); min-height: 70px; align-items: flex-start; }
                    .lm3--schmal .lm3-box { order: 2; margin-left: var(--lm-zoom); flex: 0 1 auto; }
                    .lm3--schmal .lm3-ui::after { content: ''; order: 3; flex: 1 1 0; min-height: 0; }
                    .lm3--schmal .lm3-tafel { order: 4; width: 100%; flex: 0 1 auto; }
                    .lm3--schmal .lm3-detail { order: 5; flex: 0 1 auto; max-height: 58%; }
                    .lm3--schmal .lm3-zeilen { max-height: none; }
                    .lm3--schmal .lm3-unten .lm3-zeilen { max-height: 360px; }
                    .lm3--schmal.ohne-tafel-mobil .lm3-tafel { display: none; }
                    /* ── uebernommen: Wetterebenen, Netz-Legende, VATSIM/IVAO-Popups ── */
                    .owm-clouds-layer,
                    .owm-precip-layer,
                    .owm-storms-layer,
                    .owm-wind-layer,
                    .owm-temp-layer,
                    .owm-thunder-layer {
                        mix-blend-mode: multiply;
                        filter: contrast(3) saturate(4) brightness(0.8);
                    }

                    .map-network-legend {
                        margin-top: 8px;
                        padding-top: 8px;
                        border-top: 1px solid #e0e0e0;
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 3px 8px;
                    }

                    .map-network-legend-item {
                        display: flex;
                        align-items: center;
                        gap: 5px;
                    }

                    .map-network-legend-label {
                        font-size: 10px;
                        color: #666;
                    }

                    .map-network-legend-badge {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 14px;
                        height: 14px;
                        border-radius: 3px;
                        color: #fff;
                        font-size: 8px;
                        font-weight: 800;
                        flex-shrink: 0;
                    }

                    .map-network-legend-badge-delivery {
                        background: #3498db;
                    }

                    .map-network-legend-badge-ground {
                        background: #e67e22;
                    }

                    .map-network-legend-badge-tower {
                        background: #e74c3c;
                    }

                    .map-network-legend-badge-appatis {
                        width: 20px;
                        height: 14px;
                        background: #27ae60;
                        font-weight: 900;
                    }

                    .map-network-legend-ai-suffix {
                        font-style: italic;
                        font-size: 9px;
                    }

                    .map-network-legend-badge-center {
                        background: #1abc9c;
                    }

                    .map-network-legend-badge-atis {
                        border-radius: 50%;
                        background: #5dade2;
                        font-style: italic;
                        font-weight: 900;
                    }

                    .bp-progress-bar-fill {
                        width: 0%;
                    }

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
