# Changelog

All notable changes to this project are documented in this file.

---

## [4.6.4] — 2026-04-22

### Settings Persistence Hotfix

- Fixed the recurring problem where the OpenWeatherMap API key and other Live Map admin settings silently reverted to defaults between sessions.
- Root cause: v4.6.1 moved all settings into `storage/app/kvp.json` (Spatie Valuestore flat file) and the admin page actively deleted the durable database backup rows on every load. Any event that wiped, corrupted, or lost `kvp.json` (hoster deploy, permission reset, Spatie Valuestore concurrent-write race, cache maintenance) erased every setting with no way to recover.

### What Changed Internally

- `SettingsController::persistLiveMapSetting` now dual-writes:
  - fast read path: `kvp_save(livemap.*)`
  - durable backup: rows in the `settings` table under group `livemap_module`
- All three read paths are now self-healing:
  - `SettingsController::lmGet`
  - `WeatherProxyController::lmGet`
  - `lmSetting` helper in `live_map.blade.php`
- When `kvp.json` is missing but the durable DB row is present, the DB value is returned and `kvp` is re-seeded on the fly.
- Removed the destructive `deleteLegacySettingsRows()` / `migrateLegacySettingsToKvpAndCleanup()` pair and replaced it with a non-destructive `ensureDurableBackup()` that bidirectionally reconciles both stores on admin page load.

### Packaging and Metadata

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.4"`

---

## [4.6.3] — 2026-04-04

### Weather Proxy Resilience

- Added server-side upstream fallback chain for weather tiles:
  - `pressure_new` -> `precipitation_new` -> `clouds_new`
- Kept legacy layer alias handling so old widget requests still resolve safely:
  - `thunder_new` -> `pressure_new`
  - `weather_new` -> `precipitation_new`
- Added diagnostic response headers for easier troubleshooting:
  - `X-LiveMap-Upstream-Layer`
  - `X-LiveMap-Fallback`
- Extended proxy warning logs with attempted upstream layers.

### Admin Key Persistence Fix

- Saving Live Map settings with an empty OWM API key field now keeps the existing stored key.
- Added explicit checkbox option to intentionally remove the stored API key.
- OWM key validation now runs only when the key value was actually changed.

### Packaging and Metadata

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.3"`
- Updated release docs/examples to `v4.6.3`.

---

## [4.6.2] — 2026-03-17

### Weather Layer Compatibility Hotfix

- Replaced the old storms primary layer (`thunder_new`) with `pressure_new` for current OWM tile compatibility.
- Added storms fallback chain in frontend:
  - `pressure_new` -> `precipitation_new` -> `clouds_new`
- Added weather proxy backward-compatibility aliases so older clients still work:
  - `thunder_new` -> `pressure_new`
  - `weather_new` -> `precipitation_new`
- Updated Live Map weather UI/admin wording from storm/thunder-specific labels to pressure-based proxy wording.

### Packaging

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.2"`

---

## [4.6.1] — 2026-03-15

### Compatibility and Stability Hotfixes

- Fixed Blade compatibility issue causing:
  - `Method Illuminate\View\Factory::getName does not exist`
- Replaced view-name introspection with safe include resolution (`View::exists` fallback candidates).

### Settings Scope and Admin Cleanup

- Live Map settings now persist in module-internal `kvp` keys (`livemap.*`).
- Added migration from legacy `acars.livemap_*` values.
- Added cleanup of legacy Live Map rows from global `Admin -> Settings` to avoid duplicated configuration surfaces.

### UI/Interaction Fixes

- Restored missing desktop flights panel styles.
- Restored missing desktop boarding-pass styles.
- Made FIR/UIR label markers non-interactive to prevent click interception on aircraft.
- Fixed follow-mode interaction side effect where manual zoom/pan could become unresponsive.

### Admin UX Improvements

- ACARS `Live Time` note in Live Map admin now reads the current core value dynamically.
- Warning now appears only for unsafe value (`<= 0`).
- Safe values display a minimal informational line.

### Packaging

- Moved release workflow to versioned ZIP naming to reduce stale-cache confusion with generic package names.
- Release/install path documented as browser/admin flow only (`/update` + Admin Clear Caches), no SSH required.
- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.1"`

---

## [4.6.0] — 2026-03-14

### Admin UX Simplification

- Reduced admin color controls to 3 essential fields:
  - `Primary UI Color`
  - `Accent UI Color`
  - `Box Background Color`
- Kept full visual coverage by mapping these 3 values internally to all required UI elements.
- Updated admin wording/tooltips in English for clearer behavior expectations.

### Mobile UI Cleanup

- Removed legacy extra floating **mobile Network button**.
- Kept one floating **Flights** mobile button and Network access via the panel/tab.
- Improved active/inactive state visibility of the Flights button.
- Removed obsolete `mobile_show_network_button` setting from admin flow.

### Reliability and Behavior

- Preserved weather proxy diagnostics with clearer status usage in release docs.
- Maintained blank tile fallback behavior to avoid repeated 502 console spam when OWM upstream fails.
- Kept improved multi-flight follow/fit behavior for better framing when multiple aircraft are active.

### Documentation and Release Assets

- Rewrote `README.md` for current architecture (module + blade + admin-driven setup).
- Updated `RELEASE_NOTES.md` for v4.6.0.
- Added explicit operational warning:
  - **Do not set phpVMS ACARS Live Time to `0`** in production.
  - Recommended minimum: `1`, because the same core setting impacts stale/stuck PIREP cleanup/cancellation.
- Added module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.0"`

---

## [4.5.0] — 2026-03-08

### Bug Fixes — FIR Sector Rendering

Three bugs in the `renderActiveSectors` function prevented FIR sector polygons from appearing for large parts of the world, most visibly in Russia, CIS, Central Asia, and the Caucasus region.

#### Fix 1 — Sub-sector callsign matching too narrow

Controllers using sub-sector callsigns (e.g. `UNKL_N_CTR`, `UUWV_E_CTR`) were processed through a narrow exact-match path (`isSubKey` branch) that only checked for an exact GeoJSON feature ID like `UNKL_N` or `UNKL-N`. When neither existed, the code fell back to a minimal root lookup — but never ran the broad search that already worked for simple callsigns like `UNKL_CTR`.

The matching logic now uses a **4-phase cascade** that runs for all callsigns:

| Phase | Method | Example |
|-------|--------|---------|
| 1 | Exact sub-key match | `UNKL_N` → GeoJSON `UNKL-N` |
| 2 | Broad normalised search | `UNKL` → scans all GeoJSON features |
| 3 | startsWith fallback | `UNKL` → matches `UNKL-1`, `UNKL-2` |
| 4 | UIR expansion *(new)* | `RU-SC` → resolves to `URRV`, `UGGG`, `UDDD`, `UBBA` |

A `_norm()` helper normalises hyphens to underscores on both sides of every comparison, preventing silent mismatches between `firPrefixMap` (hyphens) and GeoJSON keys (underscores).

#### Fix 2 — CTR/FSS controllers without position silently dropped

Controllers like `RU-SC_FSS` have no airport in VATSpy's static data and often no transceiver entry. The code's `if(!pos) return;` skipped them entirely — before they could even reach the FIR sector matching logic. CTR and FSS controllers are now kept in the processing pipeline with `pos: null`, since the FIR polygon itself does not require a controller position.

#### Fix 3 — UIR (Upper Information Region) support

Callsigns like `RU-SC_FSS`, `RU-EC_FSS`, `RU-NW_FSS` refer to **UIRs** — composite airspace regions that consist of multiple FIRs. The `[UIRs]` section of VATSpy.dat was not parsed at all.

The code now:
1. Parses the `[UIRs]` section during `loadFirNames()` into a `uirToFirsMap` lookup table
2. In Phase 4 of the matching cascade, resolves a UIR callsign to its constituent FIR IDs
3. Draws all constituent FIR polygons as a single sector group
4. Automatically marks UIR sectors as Upper Airspace (dashed purple styling)

### Affected Regions

- **Russia** — UNKL, URWW, URMM, UUWV, ULLL, UWSG and all other U-prefix FIRs
- **Russian UIRs** — RU-SC (Caucasus), RU-EC (East Central), RU-NW (Northwest), RU-WS (West Siberia), etc.
- **Central Asia** — UACC, UAAA, UATT, UTAA, UTDD, UZTT
- **Caucasus** — UBBB, UGTB, UGEE, UDDD
- **Any FIR worldwide** using hyphenated sub-sector IDs in VATSpy GeoJSON
- **Any UIR worldwide** defined in the `[UIRs]` section of VATSpy.dat

---

## [4.0.0] — 2026-02-28

### New Features

#### VA Planned Flights Panel
- **New "Planned" tab** alongside the existing "Active Flights" tab in the VA panel
- Displays scheduled bids fetched from phpVMS `/api/user/bids`
- **2-column grid layout** — route display spans the full panel width, details in compact columns
- **Boarding pass–style flight info card** with animated progress bar and aircraft icon
- Airline logos per flight loaded from phpVMS database
- Pilot name, flight number, aircraft type, departure/destination with full airport names
- Scheduled departure time with UTC display
- Click any planned flight to open the matching pilot marker on the map (if active)

#### Mobile Responsive Design
- **Dedicated toggle button bar** for small screens (✈ Flights · Network)
- VA panel, network selector and weather overlay box collapse into hidden drawers on mobile
- Side-tab controls remain accessible without blocking the map
- Dynamic table height adapts to viewport
- Boarding pass card switches to single-column layout on narrow screens
- All button and icon sizing adjusted for touch targets

#### IVAO Controller Rating Badges
- Full IVAO rating badge system: OBS → DEL → GND → TWR → APP → CTR → FSS → SUP → ADM
- **VID links** to official IVAO tracker (`https://www.ivao.aero/Member.aspx?Id=…`)
- Unified popup design for VATSIM and IVAO controllers (dark title bar, white content area)
- Online time display for IVAO controllers

#### Portable Domain Support
- Removed all hardcoded domain references (`german-sky-group.eu`)
- phpVMS API calls now use `window.location.origin` — works on **any** phpVMS installation without changes
- `PHPVMS_BASE` config variable added for transparency
- Clear config comment block for other admins

### Security — Critical Fixes

**12 XSS vulnerabilities patched** — all external API data (VATSIM, IVAO, phpVMS) was previously injected into `innerHTML` without escaping.

New security helper functions:

```javascript
h(str)           // HTML-escape all innerHTML output
safeUrl(url)     // Accept only HTTPS URLs without special characters
safeCallsign(s)  // Allow only A–Z, 0–9, _, - (max 20 chars)
safeFreq(s)      // Allow only digits and dot (max 8 chars)
```

| Severity | Issue | Fix |
|----------|-------|-----|
| 🔴 Critical | XSS via `innerHTML` — VATSIM/IVAO/phpVMS data (12 locations) | `h()` wrapper on all output |
| 🔴 Critical | CSS injection via `querySelector` with unsanitised callsign | `safeCallsign()` whitelist |
| 🔴 Critical | Open redirect / `javascript:` URI in IVAO VID link `href` | `safeUrl()` HTTPS-only validation |
| 🟡 Medium | Frequency string injected without sanitising (4 locations) | `safeFreq()` numeric-only filter |
| 🟡 Medium | Rating type coercion — string vs. number comparison | `parseInt()` coercion before comparisons |
| 🟡 Medium | Blade variables (center lat/lng, zoom) inserted as raw strings | `parseFloat()` / `parseInt()` with safe fallbacks |
| 🟢 Low | `target="_blank"` links missing `rel="noopener noreferrer"` | Added to all external links |

### Bug Fixes
- Fixed: Boarding pass card flickering on tab switch (CSS `visibility` instead of `display` toggle)
- Fixed: VATSIM/IVAO controller popup rating badge not rendering at OBS level
- Fixed: Weather/network panel not expanding on mobile due to `overflow: hidden` on parent container
- Fixed: Airline logo fallback when logo URL returns 404
- Fixed: Planned flights panel showing stale data after bid changes without manual refresh
- Fixed: `⚠ Unavailable` error message replaced with `⚠ phpVMS API not reachable` for clarity

---

## [3.0.1] — 2026-02-24

### Bug Fixes & Code Quality
- Removed: All 9 `console.log` debug statements from production code (intentional `console.warn`/`console.error` in error handlers retained)
- Improved: Airline logo output changed from `{!! json_encode() !!}` (unescaped) to `@json()` (Blade-escaped, safer)
- Fixed: Outdated comment referencing jsDelivr CDN (logo source had changed but comment was never updated)

---

## [3.0.0] — 2026-02-24

### New Features

#### IVAO Network Integration
- **Dual-network display** — VATSIM and IVAO can now be shown simultaneously on the same map
- **IVAO pilot markers** — orange SVG aircraft icons (visually distinct from VATSIM blue)
- **IVAO controller markers** — airport badges with orange outline and "IV" label
- **IVAO FIR sectors** — same VATSpy GeoJSON, rendered in a distinct darker teal colour
- **IVAO stats always loaded** — pilot/controller counts shown in the stats bar even when the IVAO layer is toggled off, matching VATSIM behaviour
- **Independent refresh cycles** — VATSIM 30 s, IVAO 15 s; run independently without interference
- **Network toggle buttons** — VATSIM (teal) and IVAO (orange) buttons; shared layer controls (Pilots / Controllers / FIR Sectors) apply to both active networks

#### VA Active Flights Panel
- **Collapsible top-centre panel** showing all active phpVMS/ACARS flights
- Columns: Flight · Route · Aircraft · Altitude · Speed · Distance · Status · Pilot
- **Live count badge** on the toggle button (red when flights active, grey when empty)
- **Distance column** showing flown / planned distance in nmi (`573 / 4895 nmi`)
- **Pilot name** column with first name + last name initial from phpVMS user data
- Refreshes at the same interval as your phpVMS `acars.update_interval` setting
- Status texts translated from phpVMS German locale to English (Unterwegs → En Route, Gelandet → Landed, etc.)
- Row highlight persists across refreshes (active flight stays visually selected)
- Dark map mode automatically darkens the panel (MutationObserver)

#### VA Info Card
- **New dedicated `#va-info-card`** (top-right) filled directly from ACARS API data
- Works independently of the phpVMS Rivets binding — no timing hacks, no marker simulation
- Displays: route, callsign, aircraft registration/type, altitude, speed, pilot name, status badge
- Shows airline logo from phpVMS database
- Close button (✕) on the card
- Clicking the map closes the card and clears the route line
- When a real map marker is clicked, the Rivets card takes over and the VA card hides automatically (MutationObserver)

### Improvements
- All visible UI text is now in English (was previously German in some labels and status texts)
- Stats bar shows `...` while loading (was `—`), `⚠ Error` on failure (was German `⚠ Fehler`)
- VA panel position moved from top-left to top-centre
- Panel width adapts to column count; max-height 400 px with scroll for many flights

### Bug Fixes
- Fixed: VA route line briefly showing wrong destination when clicking a second aircraft quickly (sequence counter + mandatory 150 ms Rivets delay)
- Fixed: Stats box height mismatch between VATSIM and IVAO boxes when text wrapped (nowrap + ellipsis)
- Fixed: IVAO stats showing `—` when network was toggled off (stats now always updated on fetch)

---

## [2.0.0] — 2026-02-23

### New Features
- **VA Flight Route Line** — clicking a VA aircraft shows a dashed red line to the destination airport
- **VA Aircraft Icon** — phpVMS aircraft replaced with a distinctive white/blue SVG icon; rotation handled by leaflet-rotatedmarker
- **Dark Map persistent** — dark mode state saved to localStorage and restored on reload
- **TRACON auto-merge** — TRACON / Approach Control facilities merged into the nearest airport marker (within 80 km)
- **Airport full names** — full airport names from VATSpy data shown in controller popups
- **ATIS collapsible** — ATIS text shows a 60-character preview with "Show full ATIS" toggle
- **Route line destination badge** — red ICAO label shown at the destination airport when route line is active
- **Badge legend** — visual reference panel showing all badge types and colours

### Improvements
- Controller zoom thresholds lowered: badges visible from zoom 3, labels from zoom 5
- Default start state: Controllers active, Pilots and FIR Sectors off
- Airline logos loaded from phpVMS database (no external CDNs)
- Airport marker click area enlarged to 36 px for easier interaction
- APP/TRACON badge changed from orange to green; combined APP+ATIS shows "Ai" badge

### Bug Fixes
- Fixed: Dark Map button had no effect when OWM API key was missing
- Fixed: VA route line not shown on second click (scope bug in `lastDrawnArr`)
- Fixed: Duplicate `layeradd` handlers overwriting each other
- Fixed: Dead variable `vaCallsignSet` causing silent ReferenceError

---

## [1.0.0] — 2026-02-20

### Initial Release

- Real-time VATSIM pilot positions with popup (callsign, route, aircraft, altitude, speed, heading, pilot name)
- VATSIM controller markers with colour-coded facility badges (DEL, GND, TWR, APP, CTR)
- FIR sector boundaries as coloured polygons from VATSpy GeoJSON
- Controller positions from VATSIM Transceivers API
- Airport positions from VATSpy.dat (~7000 airports)
- Key normalisation: EWR ↔ KEWR, AU Y-prefix, Pacific P-prefix airports
- Pilot route line (dashed red) on aircraft click
- Follow Flight toggle
- OWM weather overlays: Clouds, Radar, Storms, Wind, Temperature, Combo + opacity slider
- Dark Map (CSS filter night mode)
- Airline logos in VATSIM pilot popups
- VATSIM live indicator dot with pilot/controller counts
- 30-second VATSIM refresh interval
