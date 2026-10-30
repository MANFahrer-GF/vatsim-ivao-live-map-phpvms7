<style>
                    /* ══════════════════════════════════════════════════════
                       Live-Karte v5 — GSG-Gestaltung
                       Marken-Tokens (gsg-marke-logo-2026): Navy #0B1F3A,
                       Rot #D01722, Gold #E8B131, Hellblau #8FB4D6,
                       Grau #5B6B7C. Hell/Dunkel folgt SPTheme
                       (`data-bs-theme` am <html>).
                       ══════════════════════════════════════════════════════ */
                    .lm-hidden { display: none !important; }

                    .lm3 {
                        --gsg-navy: #0B1F3A;
                        --gsg-black: #111111;
                        --gsg-red: #D01722;
                        --gsg-gold: #E8B131;
                        --gsg-lightblue: #8FB4D6;
                        --gsg-mute: #5B6B7C;
                        --lm-primary: {{ $flightsHeaderStart }};
                        --lm-accent: {{ $flightsHeaderEnd }};
                        --lm-flaeche: {{ $boxBackgroundRgba }};
                        --lm-text: #0B1F3A;
                        --lm-text-2: #5B6B7C;
                        --lm-linie: rgba(11, 31, 58, 0.12);
                        --lm-zeile-hover: rgba(11, 31, 58, 0.05);
                        --lm-schatten: 0 10px 28px rgba(11, 31, 58, 0.22), 0 2px 6px rgba(11, 31, 58, 0.12);
                        --lm-rand: 12px;
                        --lm-radius: 12px;
                        position: relative;
                        width: 100%;
                        /* Fuellt das Fenster unter dem Seitenkopf; die Hoehe aus dem Widget-Aufruf
                           (Standard 800px) liess auf 1440er-Schirmen ein Drittel leer. */
                        height: clamp(440px, calc(100vh - 200px), 1400px);
                        overflow: hidden;
                        border-radius: inherit;
                        color: var(--lm-text);
                        font-variant-numeric: tabular-nums;
                        isolation: isolate;
                    }
                    [data-bs-theme="dark"] .lm3 {
                        --lm-flaeche: rgba(14, 33, 60, 0.97);
                        --lm-text: #E8EEF6;
                        --lm-text-2: #9DB0C6;
                        --lm-linie: rgba(143, 180, 214, 0.18);
                        --lm-zeile-hover: rgba(143, 180, 214, 0.08);
                        --lm-schatten: 0 10px 28px rgba(0, 0, 0, 0.5), 0 2px 6px rgba(0, 0, 0, 0.35);
                    }
                    .lm3 #map { position: absolute; inset: 0; width: 100%; height: 100%; transition: filter .3s ease; background: #dfe6ee; }
                    [data-bs-theme="dark"] .lm3 #map { background: #0d1726; }
                    .lm3 .dark-map { filter: brightness(0.55) contrast(1.15) saturate(1.05); }
                    .lm3 button { font: inherit; }
                    .lm3 button:focus-visible, .lm3 select:focus-visible { outline: 2px solid var(--gsg-gold); outline-offset: 2px; }

                    /* ── gemeinsame Flaeche: Panel, Detail, Aufklapper ── */
                    .lm3-panel, .lm3-detail, .lm3-box {
                        position: absolute;
                        z-index: 800;
                        background: var(--lm-flaeche);
                        border: 1px solid var(--lm-linie);
                        border-radius: var(--lm-radius);
                        box-shadow: var(--lm-schatten);
                        backdrop-filter: blur(6px);
                        -webkit-backdrop-filter: blur(6px);
                    }

                    /* ── Panel Live-Fluege (links) ── */
                    .lm3-panel {
                        top: var(--lm-rand);
                        left: var(--lm-rand);
                        bottom: var(--lm-rand);
                        width: 344px;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                    }
                    .lm3-panel.ist-zu { bottom: auto; }
                    .lm3-panel.ist-zu .lm3-panel-inhalt { display: none; }
                    .lm3-panel.ist-zu #lm3-panel-zu svg { transform: rotate(180deg); }
                    .lm3-panel-kopf {
                        position: relative;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 8px;
                        padding: 14px 12px 12px 16px;
                        background: var(--lm-primary);
                        color: #fff;
                    }
                    /* Marken-Akzent: Schwarz-Rot-Gold als feine Linie */
                    .lm3-panel-kopf::before {
                        content: "";
                        position: absolute; left: 0; right: 0; top: 0; height: 3px;
                        background: linear-gradient(90deg, var(--gsg-black) 0 33.3%, var(--gsg-red) 33.3% 66.6%, var(--gsg-gold) 66.6%);
                    }
                    .lm3-titel {
                        margin: 0;
                        display: flex; align-items: center; gap: 8px;
                        font-size: 15px; font-weight: 700; letter-spacing: .01em; line-height: 1.2;
                        color: #fff;
                    }
                    .lm3-zahl {
                        min-width: 24px; padding: 1px 8px; border-radius: 999px;
                        background: rgba(255,255,255,.16); font-size: 12px; font-weight: 700; text-align: center;
                    }
                    .lm3-punkt { width: 8px; height: 8px; border-radius: 50%; background: var(--gsg-mute); flex: none; }
                    .lm3-punkt.ist-live { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.25); }
                    .lm3-punkt.ist-fehler { background: var(--gsg-red); box-shadow: 0 0 0 3px rgba(208,23,34,.3); }
                    .lm3-ikon {
                        display: inline-flex; align-items: center; justify-content: center;
                        width: 30px; height: 30px; border-radius: 8px; border: 0;
                        background: rgba(255,255,255,.12); color: #fff; cursor: pointer;
                    }
                    .lm3-ikon:hover { background: rgba(255,255,255,.22); }
                    .lm3-ikon svg { transition: transform .2s ease; }
                    .lm3-panel-inhalt { display: flex; flex-direction: column; min-height: 0; flex: 1 1 auto; }

                    .lm3-tabs { display: flex; gap: 4px; padding: 10px 12px 0; border-bottom: 1px solid var(--lm-linie); }
                    .lm3-tabs button {
                        flex: 1 1 0; border: 0; background: none; cursor: pointer;
                        padding: 8px 6px 10px; color: var(--lm-text-2); font-size: 13px; font-weight: 600;
                        border-bottom: 3px solid transparent; margin-bottom: -1px;
                    }
                    .lm3-tabs button[aria-selected="true"] { color: var(--lm-text); border-bottom-color: var(--gsg-red); }
                    .lm3-tabzahl { font-weight: 700; color: var(--lm-text-2); margin-left: 2px; }
                    .lm3-filter { display: flex; align-items: center; gap: 10px; padding: 10px 12px 8px 16px; }
                    .lm3-filter[hidden] { display: none; }
                    .lm3-filter label { margin: 0; font-size: 12px; font-weight: 600; color: var(--lm-text-2); }
                    .lm3-filter select {
                        flex: 1 1 auto; min-width: 0; height: 32px; padding: 0 8px; font-size: 13px;
                        color: var(--lm-text); background: transparent; border: 1px solid var(--lm-linie); border-radius: 8px;
                    }
                    [data-bs-theme="dark"] .lm3-filter select option { color: #0B1F3A; }
                    .lm3-meldung {
                        margin: 8px 12px 0; padding: 10px 12px; border-radius: 8px; font-size: 13px; line-height: 1.4;
                        background: rgba(208,23,34,.08); border: 1px solid rgba(208,23,34,.35); color: var(--lm-text);
                    }
                    .lm3-meldung[hidden] { display: none; }
                    .lm3-nochmal { display: block; margin-top: 8px; padding: 5px 12px; border-radius: 999px; border: 1px solid var(--lm-primary); background: transparent; color: var(--lm-text); font-size: 12px; font-weight: 700; cursor: pointer; }
                    .lm3-liste { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 8px 8px 10px; }
                    .lm3-leer { margin: 18px 8px; font-size: 13px; color: var(--lm-text-2); text-align: center; }

                    /* ── eine Zeile = ein Flug (Felder der Flugtafel) ── */
                    .lm3-zeile {
                        display: grid; gap: 3px; width: 100%; text-align: left;
                        padding: 10px 10px 10px 13px; margin: 0 0 6px;
                        background: transparent; color: var(--lm-text);
                        border: 1px solid var(--lm-linie); border-left: 4px solid var(--ph, var(--gsg-mute));
                        border-radius: 10px; cursor: pointer;
                    }
                    .lm3-zeile:hover { background: var(--lm-zeile-hover); }
                    .lm3-zeile.ist-gewaehlt { background: var(--lm-zeile-hover); border-color: var(--gsg-gold); border-left-color: var(--ph, var(--gsg-gold)); box-shadow: 0 0 0 1px var(--gsg-gold) inset; }
                    .lm3-zeile.ist-offline { opacity: .6; }
                    .lm3-zeile-geplant { cursor: default; border-left-color: var(--gsg-lightblue); }
                    .lm3-z1 { display: flex; align-items: center; gap: 8px; min-width: 0; }
                    .lm3-cs { font-size: 14px; font-weight: 700; letter-spacing: .02em; }
                    .lm3-alter { margin-left: auto; font-size: 11px; color: var(--lm-text-2); }
                    .lm3-pille {
                        display: inline-flex; align-items: center; gap: 5px;
                        padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap;
                        background: color-mix(in srgb, var(--ph) 20%, transparent); color: var(--lm-text);
                        border: 1px solid color-mix(in srgb, var(--ph) 60%, transparent);
                    }
                    .lm3-pille::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--ph); }
                    .lm3-z2 { font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                    .lm3-z3 { font-size: 12px; color: var(--lm-text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                    .lm3-pfeil { color: var(--gsg-red); font-weight: 700; }
                    .lm3-trenner { margin: 0 6px; color: var(--lm-text-2); font-weight: 400; }
                    .lm3-off { font-style: normal; font-size: 11px; color: var(--gsg-red); font-weight: 700; }
                    .lm3-werte { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 4px 6px; margin-top: 4px; }
                    .lm3-wert { display: flex; flex-direction: column; min-width: 0; }
                    .lm3-wert span { font-size: 10px; font-weight: 600; letter-spacing: .04em; color: var(--lm-text-2); }
                    .lm3-wert b { font-size: 12px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .lm3-wert.ist-warn b { color: #b45309; }
                    .lm3-wert-ap { grid-column: span 2; }
                    [data-bs-theme="dark"] .lm3-wert.ist-warn b { color: #fbbf24; }

                    /* ── Flugdetail (rechts) ── */
                    .lm3-detail {
                        top: calc(var(--lm-rand) + 48px);
                        right: var(--lm-rand);
                        width: 332px;
                        max-height: calc(100% - var(--lm-rand) - 48px - 96px);
                        overflow-y: auto; overscroll-behavior: contain;
                        border-top: 4px solid var(--ph, var(--gsg-mute));
                        padding: 0 0 12px;
                    }
                    .lm3-detail[hidden], .lm3.hat-aufklapper .lm3-detail { display: none; }
                    /* Hinweis, dass das Detail weiterscrollt: weicher Abschluss unten */
                    .lm3-detail::after { content: ""; position: sticky; display: block; bottom: -12px; height: 22px; margin-top: -10px;
                        background: linear-gradient(to bottom, transparent, var(--lm-flaeche)); pointer-events: none; }
                    .lm3-d-kopf { display: flex; justify-content: space-between; gap: 10px; padding: 14px 14px 10px 16px; }
                    .lm3-d-cs { font-size: 20px; font-weight: 800; line-height: 1.1; letter-spacing: .02em; }
                    .lm3-d-route { margin-top: 4px; font-size: 14px; font-weight: 700; }
                    .lm3-d-stand { margin-top: 2px; font-size: 12px; color: var(--lm-text-2); }
                    .lm3-d-rechts { display: flex; align-items: flex-start; gap: 6px; }
                    .lm3-d-phase {
                        padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap;
                        background: color-mix(in srgb, var(--ph) 22%, transparent); border: 1px solid color-mix(in srgb, var(--ph) 65%, transparent);
                    }
                    .lm3-zu {
                        width: 28px; height: 28px; border: 0; border-radius: 8px; cursor: pointer;
                        background: var(--lm-zeile-hover); color: var(--lm-text); font-size: 18px; line-height: 1;
                    }
                    .lm3-d-meta { padding: 0 16px 10px; font-size: 12px; color: var(--lm-text-2); line-height: 1.5; }
                    .lm3-d-pilot { display: block; margin-top: 2px; font-weight: 700; color: var(--lm-text); }
                    .lm3-flags { display: flex; gap: 6px; padding: 0 16px 10px; }
                    .lm3-flag { padding: 2px 8px; border-radius: 6px; background: var(--gsg-red); color: #fff; font-size: 11px; font-weight: 700; }
                    .lm3-kacheln { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; padding: 0 12px 0 16px; }
                    .lm3-kachel { display: flex; flex-direction: column; padding: 8px 8px 7px; border-radius: 8px; background: var(--lm-zeile-hover); border: 1px solid var(--lm-linie); min-width: 0; }
                    .lm3-kachel span { font-size: 10px; font-weight: 700; letter-spacing: .05em; color: var(--lm-text-2); }
                    .lm3-kachel b { font-size: 15px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .lm3-kachel small { font-size: 10px; color: var(--lm-text-2); }
                    .lm3-kachel.ist-warn b { color: #b45309; }
                    .lm3-kv { display: grid; grid-template-columns: max-content 1fr; gap: 5px 14px; margin: 12px 0 0; padding: 10px 16px 0; border-top: 1px solid var(--lm-linie); font-size: 12.5px; }
                    .lm3-kv dt { font-weight: 600; color: var(--lm-text-2); }
                    .lm3-kv dd { margin: 0; font-weight: 600; text-align: right; }
                    .lm3-kv dd small { display: block; font-size: 11px; font-weight: 600; color: var(--lm-text-2); }
                    .lm3-badges { display: flex; flex-wrap: wrap; gap: 5px; padding: 12px 16px 0; }
                    .lm3-badge { padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid var(--lm-linie); color: var(--lm-text-2); }
                    .lm3-badge.ist-an { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }
                    [data-bs-theme="dark"] .lm3-badge.ist-an { background: var(--gsg-lightblue); border-color: var(--gsg-lightblue); color: #0B1F3A; }
                    .lm3-badge.ist-unbekannt { border-style: dashed; }
                    .lm3-d-fuss { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 16px 0; font-size: 12px; color: var(--lm-text-2); }
                    .lm3-knopf {
                        padding: 6px 14px; border-radius: 999px; border: 1px solid var(--lm-primary); cursor: pointer;
                        background: var(--lm-primary); color: #fff; font-size: 12px; font-weight: 700;
                    }

                    /* ── Werkzeuge (oben rechts) ── */
                    .lm3-werkzeuge {
                        position: absolute; z-index: 850; top: var(--lm-rand); right: var(--lm-rand);
                        display: flex; gap: 6px;
                    }
                    .lm3-wz {
                        display: inline-flex; align-items: center; gap: 7px; height: 36px; padding: 0 13px;
                        border-radius: 999px; border: 1px solid var(--lm-linie); cursor: pointer;
                        background: var(--lm-flaeche); color: var(--lm-text); font-size: 13px; font-weight: 700;
                        box-shadow: var(--lm-schatten); white-space: nowrap;
                    }
                    .lm3-wz:hover { border-color: var(--gsg-gold); }
                    .lm3-wz[aria-expanded="true"], .lm3-wz.active { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }
                    .lm3-wz-fluege { display: none; }
                    .lm3-wz { position: relative; }
                    .lm3--mittel .lm3-wz .vatsim-dot, .lm3--schmal .lm3-wz .vatsim-dot { position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; }
                    .lm3-wz .vatsim-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--gsg-mute); display: inline-block; }

                    /* ── Aufklapper Netz / Wetter ── */
                    .lm3-box {
                        top: calc(var(--lm-rand) + 48px); right: var(--lm-rand); width: 300px;
                        max-height: calc(100% - var(--lm-rand) - 48px - 96px); overflow-y: auto; padding: 14px 14px 12px;
                    }
                    .lm3-box[hidden] { display: none; }
                    .lm3-box-titel { font-size: 13px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--lm-text-2); margin-bottom: 10px; }
                    .lm3-reihe { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }
                    .lm3-raster { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
                    .lm3-breit { grid-column: 1 / -1; }
                    .lm3-schalter {
                        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
                        min-height: 34px; padding: 6px 10px; border-radius: 8px; cursor: pointer;
                        border: 1px solid var(--lm-linie); background: transparent; color: var(--lm-text); font-size: 12.5px; font-weight: 700;
                    }
                    .lm3-schalter:hover { border-color: var(--gsg-gold); }
                    .lm3-schalter.active { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }
                    [data-bs-theme="dark"] .lm3-schalter.active { background: var(--gsg-lightblue); border-color: var(--gsg-lightblue); color: #0B1F3A; }
                    .lm3 .map-network-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--gsg-mute); display: inline-block; }
                    .lm3-statistik { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 10px; font-size: 11.5px; color: var(--lm-text-2); text-align: center; }
                    .lm3-regler { display: flex; align-items: center; gap: 10px; margin-top: 12px; font-size: 12px; font-weight: 600; color: var(--lm-text-2); }
                    .lm3-regler label { margin: 0; }
                    .lm3-regler input { flex: 1 1 auto; accent-color: var(--gsg-red); }
                    .lm3 #weather-unavailable-note { margin-top: 10px; font-size: 12px; line-height: 1.4; }

                    /* ── Leaflet im Widget ── */
                    .lm3 .leaflet-control-zoom, .lm3 .leaflet-control-layers { border: 1px solid var(--lm-linie) !important; border-radius: 10px !important; box-shadow: var(--lm-schatten) !important; overflow: hidden; }
                    .lm3 .leaflet-bottom.leaflet-right { margin: 0 var(--lm-rand) 6px 0; }
                    .lm3 .leaflet-bottom.leaflet-right .leaflet-control { margin-right: 0; }
                    .lm3 .leaflet-control-attribution { border-radius: 6px 0 0 0; font-size: 10px; }

                    /* Flugzeug-Marker: Phasenfarbe, Navy-Kontur, Kurs gedreht */
                    .lm3-marker { background: none; border: 0; }
                    .lm3-flieger { display: block; width: 34px; height: 34px; padding: 2px; transform-origin: 50% 50%; filter: drop-shadow(0 1px 2px rgba(11,31,58,.55)); }
                    .lm3-flieger svg { display: block; width: 30px; height: 30px; fill: var(--ph); stroke: #0B1F3A; stroke-width: 1.3; stroke-linejoin: round; }
                    .lm3-marker.ist-gewaehlt .lm3-flieger { filter: drop-shadow(0 0 0 #E8B131) drop-shadow(0 0 4px #E8B131) drop-shadow(0 1px 2px rgba(11,31,58,.6)); }
                    .lm3-marker.ist-offline .lm3-flieger { opacity: .5; }
                    .lm3-mlabel {
                        position: absolute; left: 50%; top: 36px; transform: translateX(-50%);
                        padding: 2px 7px; border-radius: 6px; background: #0B1F3A; color: #fff;
                        font-size: 11px; font-weight: 700; white-space: nowrap; box-shadow: 0 1px 4px rgba(0,0,0,.35);
                    }
                    .lm3 path.lm3-spur { stroke: #D01722; stroke-opacity: 1; stroke-linecap: round; stroke-linejoin: round; }
                    .lm3 path.lm3-spur-schatten { stroke: #ffffff; stroke-opacity: .85; stroke-linecap: round; stroke-linejoin: round; }
                    .lm3 path.lm3-route { stroke: #0B1F3A; stroke-opacity: .55; }
                    .lm3[data-grundkarte="dark"] path.lm3-route, .lm3[data-grundkarte="satellite"] path.lm3-route { stroke: #8FB4D6; stroke-opacity: .8; }
                    .lm3[data-grundkarte="dark"] path.lm3-spur-schatten { stroke: #0B1F3A; }

                    /* ── mittel (760–1099 px Widget-Breite) ── */
                    .lm3--mittel .lm3-panel { width: 300px; }
                    .lm3--mittel .lm3-detail { width: 316px; }
                    .lm3--mittel .lm3-kachel { padding: 7px 6px 6px; }
                    .lm3--mittel .lm3-kachel b { font-size: 14px; }
                    .lm3--mittel .lm3-wz span:not(.vatsim-dot) { display: none; }
                    .lm3--mittel .lm3-wz { width: 38px; padding: 0; justify-content: center; }

                    /* ── schmal (< 760 px): Liste und Detail als Blatt von unten ── */
                    .lm3--schmal { --lm-rand: 8px; height: clamp(440px, calc(100svh - 180px), 900px); }
                    .lm3--schmal .lm3-werkzeuge { left: var(--lm-rand); right: var(--lm-rand); }
                    .lm3--schmal .lm3-wz-fluege { display: inline-flex; margin-right: auto; }
                    .lm3--schmal .lm3-wz { height: 44px; }
                    .lm3--schmal .lm3-wz:not(.lm3-wz-fluege) { width: 44px; padding: 0; justify-content: center; }
                    .lm3--schmal .lm3-zu { width: 44px; height: 44px; }
                    .lm3--schmal .lm3-wz:not(.lm3-wz-fluege) span:not(.vatsim-dot) { display: none; }
                    .lm3--schmal .lm3-wz-fluege[aria-pressed="true"] { background: var(--lm-primary); border-color: var(--lm-primary); color: #fff; }
                    .lm3--schmal .lm3-panel,
                    .lm3--schmal .lm3-detail {
                        top: auto; left: var(--lm-rand); right: var(--lm-rand); bottom: var(--lm-rand);
                        width: auto; max-width: 480px; margin-inline: auto;
                        max-height: min(72%, calc(100% - 72px));
                    }
                    .lm3--schmal .lm3-panel { height: auto; }
                    .lm3--schmal .lm3-panel.ist-zu { display: none; }
                    .lm3--schmal .lm3-panel #lm3-panel-zu { display: none; }
                    .lm3--schmal.hat-detail .lm3-panel { display: none; }
                    .lm3--schmal .lm3-box { left: var(--lm-rand); right: var(--lm-rand); width: auto; max-height: calc(100% - 60px - 30px); }
                    .lm3--schmal .leaflet-control-zoom { display: none; }
                    .lm3--schmal .leaflet-bottom.leaflet-right { top: 62px; bottom: auto; margin: 0 var(--lm-rand) 0 0; }
                    .lm3--schmal .lm3-werte { grid-template-columns: repeat(4, minmax(0, 1fr)); }
                    .lm3--schmal .lm3-kacheln { padding-right: 16px; }

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

                {{-- Leaflet 1.8.0 Grundstile (BSD-2-Clause, (c) Vladimir Agafonkin, CloudMade).
                     Nur ein Rueckfall: Das Skript schaltet den Block ein (media="all"), wenn die
                     Seite selbst keine Leaflet-Stile mitbringt. Bisher kamen sie ueber
                     `render_live_map` des Kerns; v5 baut die Karte selbst. --}}
                <style id="lm3-leaflet-css" media="not all">
/* required styles */

.leaflet-pane,
.leaflet-tile,
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-tile-container,
.leaflet-pane > svg,
.leaflet-pane > canvas,
.leaflet-zoom-box,
.leaflet-image-layer,
.leaflet-layer {
	position: absolute;
	left: 0;
	top: 0;
	}
.leaflet-container {
	overflow: hidden;
	}
.leaflet-tile,
.leaflet-marker-icon,
.leaflet-marker-shadow {
	-webkit-user-select: none;
	   -moz-user-select: none;
	        user-select: none;
	  -webkit-user-drag: none;
	}
/* Prevents IE11 from highlighting tiles in blue */
.leaflet-tile::selection {
	background: transparent;
}
/* Safari renders non-retina tile on retina better with this, but Chrome is worse */
.leaflet-safari .leaflet-tile {
	image-rendering: -webkit-optimize-contrast;
	}
/* hack that prevents hw layers "stretching" when loading new tiles */
.leaflet-safari .leaflet-tile-container {
	width: 1600px;
	height: 1600px;
	-webkit-transform-origin: 0 0;
	}
.leaflet-marker-icon,
.leaflet-marker-shadow {
	display: block;
	}
/* .leaflet-container svg: reset svg max-width decleration shipped in Joomla! (joomla.org) 3.x */
/* .leaflet-container img: map is broken in FF if you have max-width: 100% on tiles */
.leaflet-container .leaflet-overlay-pane svg {
	max-width: none !important;
	max-height: none !important;
	}
.leaflet-container .leaflet-marker-pane img,
.leaflet-container .leaflet-shadow-pane img,
.leaflet-container .leaflet-tile-pane img,
.leaflet-container img.leaflet-image-layer,
.leaflet-container .leaflet-tile {
	max-width: none !important;
	max-height: none !important;
	width: auto;
	padding: 0;
	}

.leaflet-container.leaflet-touch-zoom {
	-ms-touch-action: pan-x pan-y;
	touch-action: pan-x pan-y;
	}
.leaflet-container.leaflet-touch-drag {
	-ms-touch-action: pinch-zoom;
	/* Fallback for FF which doesn't support pinch-zoom */
	touch-action: none;
	touch-action: pinch-zoom;
}
.leaflet-container.leaflet-touch-drag.leaflet-touch-zoom {
	-ms-touch-action: none;
	touch-action: none;
}
.leaflet-container {
	-webkit-tap-highlight-color: transparent;
}
.leaflet-container a {
	-webkit-tap-highlight-color: rgba(51, 181, 229, 0.4);
}
.leaflet-tile {
	filter: inherit;
	visibility: hidden;
	}
.leaflet-tile-loaded {
	visibility: inherit;
	}
.leaflet-zoom-box {
	width: 0;
	height: 0;
	-moz-box-sizing: border-box;
	     box-sizing: border-box;
	z-index: 800;
	}
/* workaround for https://bugzilla.mozilla.org/show_bug.cgi?id=888319 */
.leaflet-overlay-pane svg {
	-moz-user-select: none;
	}

.leaflet-pane         { z-index: 400; }

.leaflet-tile-pane    { z-index: 200; }
.leaflet-overlay-pane { z-index: 400; }
.leaflet-shadow-pane  { z-index: 500; }
.leaflet-marker-pane  { z-index: 600; }
.leaflet-tooltip-pane   { z-index: 650; }
.leaflet-popup-pane   { z-index: 700; }

.leaflet-map-pane canvas { z-index: 100; }
.leaflet-map-pane svg    { z-index: 200; }

.leaflet-vml-shape {
	width: 1px;
	height: 1px;
	}
.lvml {
	behavior: url(#default#VML);
	display: inline-block;
	position: absolute;
	}


/* control positioning */

.leaflet-control {
	position: relative;
	z-index: 800;
	pointer-events: visiblePainted; /* IE 9-10 doesn't have auto */
	pointer-events: auto;
	}
.leaflet-top,
.leaflet-bottom {
	position: absolute;
	z-index: 1000;
	pointer-events: none;
	}
.leaflet-top {
	top: 0;
	}
.leaflet-right {
	right: 0;
	}
.leaflet-bottom {
	bottom: 0;
	}
.leaflet-left {
	left: 0;
	}
.leaflet-control {
	float: left;
	clear: both;
	}
.leaflet-right .leaflet-control {
	float: right;
	}
.leaflet-top .leaflet-control {
	margin-top: 10px;
	}
.leaflet-bottom .leaflet-control {
	margin-bottom: 10px;
	}
.leaflet-left .leaflet-control {
	margin-left: 10px;
	}
.leaflet-right .leaflet-control {
	margin-right: 10px;
	}


/* zoom and fade animations */

.leaflet-fade-anim .leaflet-popup {
	opacity: 0;
	-webkit-transition: opacity 0.2s linear;
	   -moz-transition: opacity 0.2s linear;
	        transition: opacity 0.2s linear;
	}
.leaflet-fade-anim .leaflet-map-pane .leaflet-popup {
	opacity: 1;
	}
.leaflet-zoom-animated {
	-webkit-transform-origin: 0 0;
	    -ms-transform-origin: 0 0;
	        transform-origin: 0 0;
	}
svg.leaflet-zoom-animated {
	will-change: transform;
}

.leaflet-zoom-anim .leaflet-zoom-animated {
	-webkit-transition: -webkit-transform 0.25s cubic-bezier(0,0,0.25,1);
	   -moz-transition:    -moz-transform 0.25s cubic-bezier(0,0,0.25,1);
	        transition:         transform 0.25s cubic-bezier(0,0,0.25,1);
	}
.leaflet-zoom-anim .leaflet-tile,
.leaflet-pan-anim .leaflet-tile {
	-webkit-transition: none;
	   -moz-transition: none;
	        transition: none;
	}

.leaflet-zoom-anim .leaflet-zoom-hide {
	visibility: hidden;
	}


/* cursors */

.leaflet-interactive {
	cursor: pointer;
	}
.leaflet-grab {
	cursor: -webkit-grab;
	cursor:    -moz-grab;
	cursor:         grab;
	}
.leaflet-crosshair,
.leaflet-crosshair .leaflet-interactive {
	cursor: crosshair;
	}
.leaflet-popup-pane,
.leaflet-control {
	cursor: auto;
	}
.leaflet-dragging .leaflet-grab,
.leaflet-dragging .leaflet-grab .leaflet-interactive,
.leaflet-dragging .leaflet-marker-draggable {
	cursor: move;
	cursor: -webkit-grabbing;
	cursor:    -moz-grabbing;
	cursor:         grabbing;
	}

/* marker & overlays interactivity */
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-image-layer,
.leaflet-pane > svg path,
.leaflet-tile-container {
	pointer-events: none;
	}

.leaflet-marker-icon.leaflet-interactive,
.leaflet-image-layer.leaflet-interactive,
.leaflet-pane > svg path.leaflet-interactive,
svg.leaflet-image-layer.leaflet-interactive path {
	pointer-events: visiblePainted; /* IE 9-10 doesn't have auto */
	pointer-events: auto;
	}

/* visual tweaks */

.leaflet-container {
	background: #ddd;
	outline-offset: 1px;
	}
.leaflet-container a {
	color: #0078A8;
	}
.leaflet-zoom-box {
	border: 2px dotted #38f;
	background: rgba(255,255,255,0.5);
	}


/* general typography */
.leaflet-container {
	font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-size: 0.75rem;
	line-height: 1.5;
	}


/* general toolbar styles */

.leaflet-bar {
	box-shadow: 0 1px 5px rgba(0,0,0,0.65);
	border-radius: 4px;
	}
.leaflet-bar a {
	background-color: #fff;
	border-bottom: 1px solid #ccc;
	width: 26px;
	height: 26px;
	line-height: 26px;
	display: block;
	text-align: center;
	text-decoration: none;
	color: black;
	}
.leaflet-bar a,
.leaflet-control-layers-toggle {
	background-position: 50% 50%;
	background-repeat: no-repeat;
	display: block;
	}
.leaflet-bar a:hover,
.leaflet-bar a:focus {
	background-color: #f4f4f4;
	}
.leaflet-bar a:first-child {
	border-top-left-radius: 4px;
	border-top-right-radius: 4px;
	}
.leaflet-bar a:last-child {
	border-bottom-left-radius: 4px;
	border-bottom-right-radius: 4px;
	border-bottom: none;
	}
.leaflet-bar a.leaflet-disabled {
	cursor: default;
	background-color: #f4f4f4;
	color: #bbb;
	}

.leaflet-touch .leaflet-bar a {
	width: 30px;
	height: 30px;
	line-height: 30px;
	}
.leaflet-touch .leaflet-bar a:first-child {
	border-top-left-radius: 2px;
	border-top-right-radius: 2px;
	}
.leaflet-touch .leaflet-bar a:last-child {
	border-bottom-left-radius: 2px;
	border-bottom-right-radius: 2px;
	}

/* zoom control */

.leaflet-control-zoom-in,
.leaflet-control-zoom-out {
	font: bold 18px 'Lucida Console', Monaco, monospace;
	text-indent: 1px;
	}

.leaflet-touch .leaflet-control-zoom-in, .leaflet-touch .leaflet-control-zoom-out  {
	font-size: 22px;
	}


/* layers control */

.leaflet-control-layers {
	box-shadow: 0 1px 5px rgba(0,0,0,0.4);
	background: #fff;
	border-radius: 5px;
	}
.leaflet-control-layers-toggle {
	background-image: url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27%3E%3Cpath d=%27M12 3l9 5-9 5-9-5z%27 fill=%27none%27 stroke=%27%230B1F3A%27 stroke-width=%271.8%27 stroke-linejoin=%27round%27/%3E%3Cpath d=%27M3 13l9 5 9-5%27 fill=%27none%27 stroke=%27%230B1F3A%27 stroke-width=%271.8%27 stroke-linejoin=%27round%27/%3E%3C/svg%3E");
	width: 36px;
	height: 36px;
	}
.leaflet-retina .leaflet-control-layers-toggle {
	background-image: none;
	background-size: 26px 26px;
	}
.leaflet-touch .leaflet-control-layers-toggle {
	width: 44px;
	height: 44px;
	}
.leaflet-control-layers .leaflet-control-layers-list,
.leaflet-control-layers-expanded .leaflet-control-layers-toggle {
	display: none;
	}
.leaflet-control-layers-expanded .leaflet-control-layers-list {
	display: block;
	position: relative;
	}
.leaflet-control-layers-expanded {
	padding: 6px 10px 6px 6px;
	color: #333;
	background: #fff;
	}
.leaflet-control-layers-scrollbar {
	overflow-y: scroll;
	overflow-x: hidden;
	padding-right: 5px;
	}
.leaflet-control-layers-selector {
	margin-top: 2px;
	position: relative;
	top: 1px;
	}
.leaflet-control-layers label {
	display: block;
	font-size: 13px;
	font-size: 1.08333em;
	}
.leaflet-control-layers-separator {
	height: 0;
	border-top: 1px solid #ddd;
	margin: 5px -10px 5px -6px;
	}

/* Default icon URLs */
.leaflet-default-icon-path { /* used only in path-guessing heuristic, see L.Icon.Default */
	background-image: none;
	}


/* attribution and scale controls */

.leaflet-container .leaflet-control-attribution {
	background: #fff;
	background: rgba(255, 255, 255, 0.8);
	margin: 0;
	}
.leaflet-control-attribution,
.leaflet-control-scale-line {
	padding: 0 5px;
	color: #333;
	line-height: 1.4;
	}
.leaflet-control-attribution a {
	text-decoration: none;
	}
.leaflet-control-attribution a:hover,
.leaflet-control-attribution a:focus {
	text-decoration: underline;
	}
.leaflet-control-attribution svg {
	display: inline !important;
	}
.leaflet-left .leaflet-control-scale {
	margin-left: 5px;
	}
.leaflet-bottom .leaflet-control-scale {
	margin-bottom: 5px;
	}
.leaflet-control-scale-line {
	border: 2px solid #777;
	border-top: none;
	line-height: 1.1;
	padding: 2px 5px 1px;
	white-space: nowrap;
	overflow: hidden;
	-moz-box-sizing: border-box;
	     box-sizing: border-box;

	background: #fff;
	background: rgba(255, 255, 255, 0.5);
	}
.leaflet-control-scale-line:not(:first-child) {
	border-top: 2px solid #777;
	border-bottom: none;
	margin-top: -2px;
	}
.leaflet-control-scale-line:not(:first-child):not(:last-child) {
	border-bottom: 2px solid #777;
	}

.leaflet-touch .leaflet-control-attribution,
.leaflet-touch .leaflet-control-layers,
.leaflet-touch .leaflet-bar {
	box-shadow: none;
	}
.leaflet-touch .leaflet-control-layers,
.leaflet-touch .leaflet-bar {
	border: 2px solid rgba(0,0,0,0.2);
	background-clip: padding-box;
	}


/* popup */

.leaflet-popup {
	position: absolute;
	text-align: center;
	margin-bottom: 20px;
	}
.leaflet-popup-content-wrapper {
	padding: 1px;
	text-align: left;
	border-radius: 12px;
	}
.leaflet-popup-content {
	margin: 13px 24px 13px 20px;
	line-height: 1.3;
	font-size: 13px;
	font-size: 1.08333em;
	min-height: 1px;
	}
.leaflet-popup-content p {
	margin: 17px 0;
	margin: 1.3em 0;
	}
.leaflet-popup-tip-container {
	width: 40px;
	height: 20px;
	position: absolute;
	left: 50%;
	margin-top: -1px;
	margin-left: -20px;
	overflow: hidden;
	pointer-events: none;
	}
.leaflet-popup-tip {
	width: 17px;
	height: 17px;
	padding: 1px;

	margin: -10px auto 0;
	pointer-events: auto;

	-webkit-transform: rotate(45deg);
	   -moz-transform: rotate(45deg);
	    -ms-transform: rotate(45deg);
	        transform: rotate(45deg);
	}
.leaflet-popup-content-wrapper,
.leaflet-popup-tip {
	background: white;
	color: #333;
	box-shadow: 0 3px 14px rgba(0,0,0,0.4);
	}
.leaflet-container a.leaflet-popup-close-button {
	position: absolute;
	top: 0;
	right: 0;
	border: none;
	text-align: center;
	width: 24px;
	height: 24px;
	font: 16px/24px Tahoma, Verdana, sans-serif;
	color: #757575;
	text-decoration: none;
	background: transparent;
	}
.leaflet-container a.leaflet-popup-close-button:hover,
.leaflet-container a.leaflet-popup-close-button:focus {
	color: #585858;
	}
.leaflet-popup-scrolled {
	overflow: auto;
	border-bottom: 1px solid #ddd;
	border-top: 1px solid #ddd;
	}

.leaflet-oldie .leaflet-popup-content-wrapper {
	-ms-zoom: 1;
	}
.leaflet-oldie .leaflet-popup-tip {
	width: 24px;
	margin: 0 auto;

	-ms-filter: "progid:DXImageTransform.Microsoft.Matrix(M11=0.70710678, M12=0.70710678, M21=-0.70710678, M22=0.70710678)";
	filter: progid:DXImageTransform.Microsoft.Matrix(M11=0.70710678, M12=0.70710678, M21=-0.70710678, M22=0.70710678);
	}

.leaflet-oldie .leaflet-control-zoom,
.leaflet-oldie .leaflet-control-layers,
.leaflet-oldie .leaflet-popup-content-wrapper,
.leaflet-oldie .leaflet-popup-tip {
	border: 1px solid #999;
	}


/* div icon */

.leaflet-div-icon {
	background: #fff;
	border: 1px solid #666;
	}


/* Tooltip */
/* Base styles for the element that has a tooltip */
.leaflet-tooltip {
	position: absolute;
	padding: 6px;
	background-color: #fff;
	border: 1px solid #fff;
	border-radius: 3px;
	color: #222;
	white-space: nowrap;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
	pointer-events: none;
	box-shadow: 0 1px 3px rgba(0,0,0,0.4);
	}
.leaflet-tooltip.leaflet-interactive {
	cursor: pointer;
	pointer-events: auto;
	}
.leaflet-tooltip-top:before,
.leaflet-tooltip-bottom:before,
.leaflet-tooltip-left:before,
.leaflet-tooltip-right:before {
	position: absolute;
	pointer-events: none;
	border: 6px solid transparent;
	background: transparent;
	content: "";
	}

/* Directions */

.leaflet-tooltip-bottom {
	margin-top: 6px;
}
.leaflet-tooltip-top {
	margin-top: -6px;
}
.leaflet-tooltip-bottom:before,
.leaflet-tooltip-top:before {
	left: 50%;
	margin-left: -6px;
	}
.leaflet-tooltip-top:before {
	bottom: 0;
	margin-bottom: -12px;
	border-top-color: #fff;
	}
.leaflet-tooltip-bottom:before {
	top: 0;
	margin-top: -12px;
	margin-left: -6px;
	border-bottom-color: #fff;
	}
.leaflet-tooltip-left {
	margin-left: -6px;
}
.leaflet-tooltip-right {
	margin-left: 6px;
}
.leaflet-tooltip-left:before,
.leaflet-tooltip-right:before {
	top: 50%;
	margin-top: -6px;
	}
.leaflet-tooltip-left:before {
	right: 0;
	margin-right: -12px;
	border-left-color: #fff;
	}
.leaflet-tooltip-right:before {
	left: 0;
	margin-left: -12px;
	border-right-color: #fff;
	}

/* Printing */
	
@media print {
	/* Prevent printers from removing background-images of controls. */
	.leaflet-control {
		-webkit-print-color-adjust: exact;
		color-adjust: exact;
		}
	}

                </style>
