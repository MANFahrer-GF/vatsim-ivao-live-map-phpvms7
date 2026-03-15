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

                    .map-airline-logo {
                        max-width: 130px;
                        max-height: 40px;
                        height: auto;
                        object-fit: contain;
                        margin-bottom: 4px;
                        display: none;
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

                    .va-status-center {
                        text-align: center;
                    }

                    .va-header-chevron.is-open {
                        transform: rotate(180deg);
                    }

                    /* ── VA Flights Panel Wrapper (TOP-CENTER) ── */
                    #va-flights-panel {
                        position: absolute;
                        top: 10px;
                        left: 50%;
                        transform: translateX(-50%);
                        z-index: 1000;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                        width: max-content;
                        min-width: 200px;
                        max-width: 860px;
                    }

                    /* ── Collapsed Header ── */
                    #va-header-collapsed {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 10px 16px;
                        background: linear-gradient(135deg, var(--lm-flights-header-start) 0%, var(--lm-flights-header-end) 100%);
                        color: #fff;
                        cursor: pointer;
                        user-select: none;
                        border-radius: 10px;
                        box-shadow: 0 4px 16px rgba(0,0,0,0.28);
                        transition: background .2s;
                        white-space: nowrap;
                    }
                    #va-header-collapsed:hover {
                        background: linear-gradient(135deg, var(--lm-flights-header-start) 0%, var(--lm-flights-header-end) 100%);
                    }

                    /* ── Expanded Panel ── */
                    #va-flights-body {
                        margin-top: 0;
                        background: var(--lm-box-bg);
                        border: 1px solid rgba(0,0,0,0.10);
                        border-radius: 10px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.18);
                        overflow: hidden;
                        max-height: 0;
                        opacity: 0;
                        pointer-events: none;
                        transition: max-height .35s cubic-bezier(.4,0,.2,1),
                                    opacity .22s ease;
                        width: 820px;
                    }
                    #va-flights-body.open {
                        max-height: 70vh;
                        opacity: 1;
                        pointer-events: auto;
                    }

                    /* ── Expanded Header (innerhalb Body) ── */
                    .va-header-expanded {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 10px 16px;
                        background: linear-gradient(135deg, var(--lm-flights-header-start) 0%, var(--lm-flights-header-end) 100%);
                        color: #fff;
                        cursor: pointer;
                        user-select: none;
                        border-radius: 10px 10px 0 0;
                    }

                    /* ── Gemeinsame Header-Elemente ── */
                    .va-header-left {
                        display: flex;
                        align-items: center;
                        gap: 16px;
                    }
                    .va-header-stat {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        font-size: 13px;
                        font-weight: 600;
                        letter-spacing: .3px;
                    }
                    .va-header-stat .va-hdr-icon { font-size: 15px; line-height: 1; }
                    .va-header-stat .va-hdr-num {
                        font-weight: 800;
                        font-size: 15px;
                        font-variant-numeric: tabular-nums;
                    }
                    .va-header-divider {
                        width: 1px; height: 18px;
                        background: rgba(255,255,255,0.25);
                        margin: 0 4px;
                    }
                    .va-header-stat.va-stat-active .va-hdr-num  { color: #6fcf7c; }
                    .va-header-stat.va-stat-planned .va-hdr-num { color: #7cb8f0; }

                    .va-header-chevron {
                        font-size: 18px;
                        color: rgba(255,255,255,0.6);
                        transition: transform .22s;
                        flex-shrink: 0;
                    }

                    /* ── Tabs ── */
                    .va-tabs {
                        display: flex;
                        border-bottom: 1px solid #e0e6ec;
                        background: #f8f9fa;
                    }
                    .va-tab {
                        flex: 1;
                        padding: 9px 12px;
                        font-size: 11px;
                        font-weight: 700;
                        letter-spacing: .5px;
                        text-transform: uppercase;
                        color: #999;
                        text-align: center;
                        border-bottom: 2px solid transparent;
                        cursor: pointer;
                        transition: color .15s, border-color .15s;
                        user-select: none;
                    }
                    .va-tab:hover { color: #555; }
                    .va-tab.active { color: #1a3a6b; border-bottom-color: #3498db; }
                    .va-tab-count {
                        display: inline-block;
                        font-size: 9px;
                        font-weight: 800;
                        border-radius: 999px;
                        padding: 2px 7px;
                        margin-left: 5px;
                        min-width: 20px;
                        text-align: center;
                        line-height: 1.5;
                    }
                    .va-tab.active .va-tab-count { background: #3498db; color: #fff; }
                    .va-tab:not(.active) .va-tab-count { background: #e0e6ec; color: #666; }

                    /* ── Tab-Panels ── */
                    .va-tab-panel { display: none; }
                    .va-tab-panel.active { display: block; }

                    /* ── Thead ── */
                    .va-thead {
                        padding: 7px 16px;
                        background: #f8f9fb;
                        border-bottom: 1px solid #e8ecf0;
                        font-size: 10px;
                        font-weight: 800;
                        color: #8894a5;
                        letter-spacing: .7px;
                        text-transform: uppercase;
                    }

                    /* ── Grid: Active ── */
                    .va-g-act {
                        display: grid;
                        grid-template-columns: 148px 110px 58px 56px 85px 95px 1fr;
                        align-items: center;
                        gap: 0 6px;
                    }

                    /* ── Grid: Planned ── */
                    .va-g-plan {
                        display: grid;
                        grid-template-columns: 1fr auto;
                        align-items: center;
                        gap: 0 16px;
                    }

                    /* ── Row ── */
                    .va-row {
                        padding: 9px 16px;
                        border-bottom: 1px solid #f0f0f0;
                        font-size: 13px;
                        color: #222;
                        align-items: center;
                        transition: background .12s;
                        cursor: pointer;
                        min-height: 48px;
                    }
                    .va-row:last-child { border-bottom: none; }
                    .va-row:hover { background: #f0f6ff; }
                    .va-row.va-row-live { background: #f0f9f0; }
                    .va-row.va-row-live:hover { background: #e4f3e4; }
                    .va-row.active-flight { background: #e8f0fd !important; }

                    /* ── Logo Box ── */
                    .va-logo-box {
                        width: 40px; height: 18px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        overflow: hidden;
                        flex-shrink: 0;
                        background: #fff;
                        border-radius: 4px;
                        border: 1px solid rgba(0,0,0,0.07);
                    }
                    .va-logo-box img {
                        max-width: 100%; max-height: 100%;
                        object-fit: contain; display: block;
                    }

                    /* ── Zellen ── */
                    .va-c-flight {
                        display: flex; align-items: center; gap: 7px;
                        min-width: 0;
                        font-weight: 800;
                        color: #1a3a6b;
                        letter-spacing: .4px;
                        font-size: 13px;
                    }
                    .va-c-flight span:last-child {
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                    }
                    .va-c-route { font-size: 12px; color: #444; white-space: nowrap; }
                    .va-c-route .va-icao { font-weight: 700; color: #333; }
                    .va-c-route .va-arr  { color: #bbb; margin: 0 4px; font-size: 11px; }
                    .va-c-route .va-dist-hint { color: #aaa; font-size: 10px; margin-left: 3px; }
                    .va-c-pilot-name {
                        font-size: 13px; font-weight: 600; color: #333;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; line-height: 1.35;
                    }
                    .va-c-pilot-rank {
                        font-size: 9px; font-weight: 600; color: #8894a5;
                        letter-spacing: .3px; text-transform: uppercase; line-height: 1.35;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                    }
                    .va-c-alt  { font-size: 12px; font-weight: 600; color: #333; white-space: nowrap; }
                    .va-c-spd  { font-size: 12px; color: #555; white-space: nowrap; }
                    .va-c-dist { font-size: 12px; color: #333; white-space: nowrap; font-variant-numeric: tabular-nums; }
                    .va-c-etd  { font-size: 13px; color: #333; text-align: center; font-weight: 600; font-variant-numeric: tabular-nums; }

                    /* ── Status-Badge (Panel-intern) ── */
                    .va-st {
                        font-size: 10px; font-weight: 700;
                        padding: 3px 8px; border-radius: 5px;
                        text-align: center; white-space: nowrap; display: inline-block;
                    }
                    .va-st-fly   { background: #e8f5e9; color: #2e7d32; }
                    .va-st-taxi  { background: #fff3e0; color: #e65100; }
                    .va-st-board { background: #e3f2fd; color: #1565c0; }
                    .va-st-desc  { background: #e0f2f1; color: #00695c; }
                    .va-st-other { background: #f3f4f6; color: #666; }

                    /* ── Scroll ab 5 Zeilen ── */
                    .va-scroll-wrap { position: relative; }
                    .va-scroll-body {
                        max-height: 260px;
                        overflow-y: auto;
                        overflow-x: hidden;
                    }
                    .va-scroll-body::-webkit-scrollbar { width: 5px; }
                    .va-scroll-body::-webkit-scrollbar-track { background: #f5f5f5; }
                    .va-scroll-body::-webkit-scrollbar-thumb { background: #c0c8d4; border-radius: 4px; }
                    .va-scroll-wrap::after {
                        content: '';
                        position: absolute; bottom: 0; left: 0; right: 0;
                        height: 36px; pointer-events: none;
                        background: linear-gradient(transparent, rgba(255,255,255,0.95));
                        border-radius: 0 0 10px 10px;
                    }
                    .va-scroll-wrap.no-scroll::after { display: none; }
                    .va-scroll-hint {
                        position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
                        font-size: 9px; font-weight: 700; color: #aaa; letter-spacing: .5px;
                        text-transform: uppercase; z-index: 2; white-space: nowrap;
                        pointer-events: none;
                    }
                    .va-scroll-hint::before { content: '▼ '; font-size: 8px; }
                    .va-scroll-wrap.no-scroll .va-scroll-hint { display: none; }

                    /* ── Loading / Empty-State ── */
                    .va-table-info {
                        padding: 16px 12px;
                        text-align: center;
                        font-size: 11px;
                        color: #999;
                    }

                    /* ── Dark-Map-Modus ── */
                    .dark-map-panel #va-header-collapsed,
                    .dark-map-panel .va-header-expanded {
                        background: linear-gradient(135deg, #0e1825 0%, #152235 100%);
                    }
                    .dark-map-panel #va-flights-body {
                        background: rgba(22,30,44,0.97);
                        border-color: rgba(255,255,255,0.08);
                    }
                    .dark-map-panel .va-tabs {
                        background: #1a2333;
                        border-color: #2d3748;
                    }
                    .dark-map-panel .va-tab { color: #6a7a90; }
                    .dark-map-panel .va-tab.active { color: #7cb8f0; border-bottom-color: #3498db; }
                    .dark-map-panel .va-thead { background: #1e2530; color: #5a6a80; border-color: #2a3240; }
                    .dark-map-panel .va-row { border-color: #232d3d; color: #ccc; }
                    .dark-map-panel .va-row:hover { background: #1e2a3a; }
                    .dark-map-panel .va-row.va-row-live { background: #152a1e; }
                    .dark-map-panel .va-c-flight { color: #7eb8f7; }
                    .dark-map-panel .va-scroll-wrap::after {
                        background: linear-gradient(transparent, rgba(22,30,44,0.95));
                    }

                    /* ── Crew Boarding Pass (desktop base styles) ── */
                    #va-boarding-pass {
                        display: none !important;
                        visibility: hidden;
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        z-index: 1001;
                        width: 320px;
                        max-width: 100%;
                        background: #fff;
                        border-radius: 10px;
                        box-shadow: 0 6px 24px rgba(0,0,0,0.22), 0 2px 6px rgba(0,0,0,0.10);
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                        overflow: hidden;
                    }
                    #va-boarding-pass.bp-visible {
                        display: block !important;
                        visibility: visible;
                    }
                    .bp-head {
                        background: linear-gradient(135deg, #1a2a4a 0%, #243b6a 100%);
                        padding: 10px 12px 8px;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 8px;
                    }
                    .bp-head-left {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        min-width: 0;
                    }
                    .bp-logo-wrap {
                        background: rgba(255,255,255,0.95);
                        border-radius: 6px;
                        padding: 4px 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        height: 34px;
                        min-width: 60px;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.25);
                    }
                    .bp-logo-wrap img {
                        max-height: 28px;
                        max-width: 80px;
                        object-fit: contain;
                        display: block;
                    }
                    .bp-logo-wrap.no-logo {
                        font-size: 11px;
                        font-weight: 800;
                        color: rgba(255,255,255,0.85);
                        letter-spacing: 1px;
                    }
                    .bp-callsign {
                        font-size: 14px;
                        font-weight: 800;
                        color: #fff;
                        letter-spacing: 1px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .bp-close {
                        background: none;
                        border: none;
                        cursor: pointer;
                        color: rgba(255,255,255,0.5);
                        font-size: 15px;
                        line-height: 1;
                        padding: 0;
                        flex-shrink: 0;
                        transition: color .15s;
                    }
                    .bp-close:hover { color: #fff; }
                    .bp-route {
                        background: #f8f9fb;
                        padding: 12px 16px 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0;
                        border-bottom: 2px solid #eef0f4;
                    }
                    .bp-icao { text-align: center; flex: 1; }
                    .bp-icao-code {
                        font-size: 22px;
                        font-weight: 900;
                        color: #1a2a4a;
                        letter-spacing: 1px;
                        line-height: 1;
                    }
                    .bp-icao-label {
                        font-size: 10px;
                        color: #667;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: .3px;
                        margin-top: 3px;
                        white-space: normal;
                        line-height: 1.3;
                        max-width: 110px;
                        word-break: break-word;
                    }
                    .bp-arrow {
                        flex-shrink: 0;
                        padding: 0 12px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 4px;
                    }
                    .bp-arrow-icon { font-size: 26px; color: #3498db; line-height: 1; }
                    .bp-arrow-dist {
                        font-size: 10px;
                        color: #3498db;
                        font-weight: 700;
                        white-space: nowrap;
                        background: #e8f4fd;
                        padding: 2px 7px;
                        border-radius: 10px;
                    }
                    .bp-grid {
                        padding: 8px 14px 10px;
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 6px 10px;
                    }
                    .bp-cell-label {
                        font-size: 8px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .6px;
                        color: #aab;
                        line-height: 1;
                        margin-bottom: 2px;
                    }
                    .bp-cell-value {
                        font-size: 12px;
                        font-weight: 700;
                        color: #1a2a4a;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .bp-footer {
                        background: #f4f6fa;
                        border-top: 1px solid #e8ecf0;
                        padding: 7px 14px;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                    }
                    .bp-status {
                        font-size: 10px;
                        font-weight: 800;
                        padding: 3px 10px;
                        border-radius: 4px;
                        letter-spacing: .4px;
                        text-transform: uppercase;
                    }
                    .bp-crew-label {
                        font-size: 8px;
                        font-weight: 700;
                        color: #bbb;
                        letter-spacing: 1px;
                        text-transform: uppercase;
                    }
                    .bp-progress-wrap { margin-top: 4px; }
                    .bp-progress-bar-bg {
                        height: 5px;
                        background: #e8ecf3;
                        border-radius: 3px;
                        overflow: hidden;
                        margin-top: 3px;
                    }
                    .bp-progress-bar-fill {
                        height: 100%;
                        background: linear-gradient(90deg, #3498db, #2ecc71);
                        border-radius: 3px;
                        transition: width .4s ease;
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

                    .weather-btn-full {
                        flex: 0 0 100%;
                        max-width: 100%;
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

                    .weather-chevron,
                    .vatsim-chevron {
                        font-size: 10px;
                        margin-left: 4px;
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

                    .map-network-toggle-row {
                        display: flex;
                        gap: 5px;
                        margin-bottom: 8px;
                    }

                    .map-network-toggle-btn {
                        flex: 1;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 5px;
                        padding: 4px 0;
                        border-radius: 5px;
                        border: none;
                        cursor: pointer;
                        font-size: 10px;
                        font-weight: 700;
                        letter-spacing: .4px;
                        color: #fff;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
                        transition: opacity .2s;
                    }

                    .map-network-toggle-btn-vatsim {
                        background: #1abc9c;
                    }

                    .map-network-toggle-btn-ivao {
                        background: #e67e22;
                        opacity: .45;
                    }

                    .map-network-dot {
                        width: 6px;
                        height: 6px;
                        border-radius: 50%;
                        background: #fff;
                        display: inline-block;
                        flex-shrink: 0;
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

                    .vatsim-btn-full {
                        flex: 0 0 100%;
                        max-width: 100%;
                    }

                    .vatsim-btn-follow {
                        margin-top: 4px;
                    }

                    #btnFollowFlight { border-color: #d0d0d0; background: #f7f7f7; }
                    #btnFollowFlight i, #btnFollowFlight span { color: #aaa; }
                    #btnFollowFlight.active { border-color: #27ae60; background: #eafaf1; }
                    #btnFollowFlight.active i, #btnFollowFlight.active span { color: #27ae60; }

                    .map-network-stats-row {
                        display: flex;
                        gap: 6px;
                        margin-top: 6px;
                        font-size: 10px;
                        color: #555;
                    }

                    .map-network-stat {
                        flex: 1;
                        min-width: 0;
                        text-align: center;
                        padding: 3px 4px;
                        border-radius: 3px;
                        color: #555;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .map-network-stat-vatsim {
                        background: rgba(26,188,156,0.15);
                        border: 1px solid rgba(26,188,156,0.3);
                    }

                    .map-network-stat-ivao {
                        background: rgba(230,126,34,0.15);
                        border: 1px solid rgba(230,126,34,0.3);
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
