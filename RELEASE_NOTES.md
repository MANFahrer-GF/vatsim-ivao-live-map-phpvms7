## v4.7.7 - 0/0 is not a position

Release date: 2026-09-01

Fixes the finding from **@ProAvia**: an aircraft parked at 0/0 in the Gulf of Guinea, "100%" progress and 7247 of 2408 nm (FA852 KSEA-KFLL, P3Dv5).

---

## English

### What happened

A simulator reports **0/0** while it does not yet know where the aircraft is - P3D before the airport is loaded, MSFS while loading. That is not pilot error and not really a simulator fault either: phpVMS accepts the position like any other ACARS row. In the `acars` table, `lat` and `lon` even **default to `0.00000`**, so a client that has not reported yet is parked there.

One row produced four symptoms:

- the aircraft sat in the Gulf of Guinea,
- "distance flown" became departure -> 0/0, roughly 7,200 nm from KSEA, hence **7247 / 2408**,
- progress therefore read **100%**,
- and the map pulled out to half the world, because the core centres on `layerFlights.getBounds()` and the phantom point sits inside those bounds.

### What changed

Such positions now count as **no position**: no marker, no distance, no progress, no auto-zoom. The flight itself does **not** disappear - it stays in the list with `-` for altitude, speed and distance, and a tooltip saying the simulator has not reported a position yet. As soon as a real position arrives, everything is back on the next refresh; the state does not stick.

Two more places, same cause, found during QA and fixed in the same release:

- **The flown track** drawn when you click a flight (`L.Geodesic.fromGeoJson`) ran across the Atlantic and back if a **single** 0/0 point sat in the log - measured on a 13-point track: 193 line points instead of 177.
- **Switching to a flight without a position** left the previous flight's route line on the map, while the boarding pass already showed the new flight.

### Notes for anyone touching this code

- The check requires **both** coordinates to be near zero (0.05 deg, about 5.5 km). Checking latitude alone throws away real flights: **0N/9.7E is Libreville**, 0N/32.5E is Entebbe.
- `map.removeLayer()` alone is not enough. The marker has to be removed **from the core's flight group**, or it stays inside that group's `getBounds()` and keeps pulling the view apart although nobody can see it.
- The track filter sits on the method, **not** on the `layeradd` event: the core sets the points only after `addTo(map)`, so at event time the line is still empty.

### Verified

Measured against a running phpVMS 7.0.8 with real ACARS flights, not on paper. Before, on v4.7.6: marker at 0/0, row "6800 / 0", map centred in the Atlantic, track with the detour. After, across five core refresh cycles: phantom gone, the real flight keeps its marker, 120/273 nm and 44% progress. Counter-checks: a genuine equator position (0N/9.7E) is untouched, a track without a 0/0 point comes out with exactly the same 177 points, and with every flight at 0/0 the map simply stays where it is.

### Known limit

If an ACARS client keeps accumulating its own "distance flown" across the 0/0 jump, that number reappears once real positions arrive. This happens in the client / `acars.distance`, not in the map, and it is deliberately **not** papered over with a plausibility heuristic - that would also discard genuine diversions and holdings.

---

## Deutsch

### Was passiert ist

Ein Simulator meldet **0/0**, solange er nicht weiss, wo die Maschine steht - P3D, bevor der Flugplatz geladen ist, MSFS waehrend des Ladens. Das ist weder Pilotenfehler noch wirklich ein Fehler des Simulators: phpVMS nimmt die Position entgegen wie jede andere ACARS-Zeile. In der Tabelle `acars` haben `lat` und `lon` sogar den **Vorgabewert `0.00000`** - wer nichts schickt, sitzt dort.

Aus dieser einen Zeile folgten vier Erscheinungen:

- der Flieger sass im Golf von Guinea,
- die "geflogene" Strecke war Abflugort -> 0/0, rund 7.200 nm ab KSEA, daher **7247 / 2408**,
- der Fortschritt stand deshalb auf **100 %**,
- und die Karte zog sich auf die halbe Welt auf, weil der Kern ueber `layerFlights.getBounds()` zentriert und der Phantom-Punkt in diesem Rahmen liegt.

### Was sich geaendert hat

Solche Positionen gelten jetzt als **nicht vorhanden**: kein Marker, keine Strecke, kein Fortschritt, kein Auto-Zoom. Der Flug selbst verschwindet **nicht** - er bleibt in der Liste, mit `-` bei Hoehe, Tempo und Strecke und einem Hinweis, dass der Simulator noch keine Position meldet. Sobald eine echte Position eintrifft, ist im naechsten Durchlauf alles wieder da; der Zustand klebt nicht.

Zwei weitere Stellen, gleiche Ursache, in der QS gefunden und mit derselben Version behoben:

- **Die geflogene Spur** beim Klick auf einen Flug (`L.Geodesic.fromGeoJson`) lief quer ueber den Atlantik und zurueck, sobald ein **einziger** 0/0-Punkt im Flugweg stand - an 13 Punkten nachgemessen: 193 Linienpunkte statt 177.
- **Beim Umschalten auf einen Flug ohne Position** blieb die Linie des vorherigen Fluges stehen, waehrend der Boarding-Pass schon den neuen zeigte.

### Fuer alle, die an diesem Code arbeiten

- Die Pruefung verlangt, dass **beide** Werte nahe 0 liegen (0,05 Grad, rund 5,5 km). Wer nur den Breitengrad prueft, wirft echte Fluege weg: **0N/9,7O ist Libreville**, 0N/32,5O ist Entebbe.
- `map.removeLayer()` allein genuegt nicht. Der Marker muss **aus der Flug-Gruppe des Kerns** heraus, sonst bleibt er in deren `getBounds()` und zieht die Ansicht weiter auseinander, obwohl ihn niemand mehr sieht.
- Der Spur-Filter sitzt an der Methode, **nicht** am `layeradd`-Ereignis: Der Kern setzt die Punkte erst nach `addTo(map)`, zum Ereigniszeitpunkt ist die Linie noch leer.

### Nachgemessen

An einer laufenden phpVMS-7.0.8-Installation mit echten ACARS-Fluegen geprueft, nicht auf dem Papier. Vorher mit v4.7.6: Marker bei 0/0, Zeile "6800 / 0", Kartenmitte im Atlantik, Spur mit Umweg. Nachher ueber fuenf Aktualisierungen des Kerns: Phantom weg, der echte Flug behaelt Marker, 120/273 nm und 44 % Fortschritt. Gegenproben: eine echte Aequator-Position (0N/9,7O) bleibt unangetastet, ein Flugweg ohne 0/0-Punkt ergibt exakt dieselben 177 Punkte, und mit allen Fluegen auf 0/0 bleibt die Karte einfach stehen.

### Bekannte Grenze

Rechnet ein ACARS-Programm seine "geflogene" Strecke ueber den 0/0-Sprung hinweg selbst weiter, taucht diese Zahl wieder auf, sobald echte Positionen kommen. Das entsteht im Programm bzw. in `acars.distance`, nicht in der Karte, und es wird bewusst **nicht** mit einer Plausibilitaetsregel uebertuencht - die wuerde auch echte Ausweichfluege und Warteschleifen verwerfen.

### Upgrade

1. Deploy all four parts from the ZIP - the module folder and the **three** widget blade files together (see `INSTALL.txt`, step 1: upload first, swap last).
2. Admin -> Maintenance -> Clear Caches.
3. Hard-refresh the browser.

No settings change, no migration.

---

## v4.6.6 - Mixed-Content Hotfix

Release date: 2026-04-22

## Summary

Silences the "hundreds of mixed-content warnings" symptom seen on installs where a second, older `live_map.js` (from a custom theme, old widget copy, or another phpVMS module) is running in parallel to our widget and still uses `http://` tile URLs.

## What Changed

- `live_map_scripts.blade.php` now wraps `L.TileLayer.initialize()` and `L.TileLayer.setUrl()` with a tiny patch that upgrades any `http://` URL to `https://` before Leaflet ever creates a request.
- The patch runs once per page load, is idempotent (safe to include even if it's already applied), and requires no configuration.
- No other behaviour change — all v4.6.5 fixes (DB-only persistence, weather-skip, exception sanitisation) remain in place.

## Upgrade (No SSH)

Replace `live_map_scripts.blade.php` in your theme widgets folder with the v4.6.6 version. The module code in `LiveMap/` did not change (other than the version bump), so re-deploying the module is optional but recommended for consistency.

1. Download the install ZIP.
2. Upload `live_map_scripts.blade.php` (and the rest of the package for consistency) to your theme widgets folder, overwriting existing files.
3. phpVMS Admin → Maintenance → Clear Caches.
4. Hard-refresh your browser.

The 500+ `Mixed Content: ... was automatically upgraded to HTTPS` lines in DevTools should disappear completely on the next page load.

---

## v4.6.5 - DB-only Settings + Page Load Fix + Security Hardening

Release date: 2026-04-22

## Summary

Three fixes in one:

1. **Settings now live fully in the database.** No more reliance on `storage/app/kvp.json`, which was the root cause of settings silently reverting to defaults.
2. **Live Map page no longer stalls when no OWM key is configured.** Previously, each pageload fired hundreds of proxy tile requests that round-tripped only to return blank SVGs.
3. **Upstream weather errors are now sanitised** before hitting the error cache / logs, preventing leakage of API-key fragments or log-injection payloads.

## 1) DB-only Settings

- Admin saves go straight into the phpVMS `settings` table under group `livemap_module`.
- Reads use `setting()` directly (DB + phpVMS's built-in cache), with a one-way legacy fall-through that promotes any remaining `kvp.json` values into the DB once, on the next admin page load.
- No migration is required — the existing `settings` table is reused.

## 2) Page Load Fix

- The widget now receives a `weatherAvailable` flag.
- If no OWM key is configured, the frontend skips every weather tile request entirely instead of firing them off and getting blank SVGs back.
- The default weather layer is also forced to `none` server-side in this case, so the weather controls stay visibly inactive.

## 3) Security Hardening

- All upstream OWM exception messages are now passed through a sanitiser before reaching the error cache or the Laravel log:
  - `appid=…` substrings redacted
  - Full URLs replaced with `[url]`
  - Control characters removed
  - Length capped at 200 chars

## Upgrade (No SSH)

1. Replace the `LiveMap/` module directory **and** the three `live_map*.blade.php` widget files in your theme with the v4.6.5 versions. If the widget files are left on an older version you will keep seeing mixed-content warnings and over-eager weather requests in the browser console.
2. Admin → Maintenance → Clear Caches.
3. Open Admin → Live Map once to trigger the one-time kvp→DB migration.
4. Re-enter the OWM key if it was lost. It now persists across cache clears, deploys, and hoster storage resets.

---

## v4.6.4 - Settings Persistence Hotfix

Release date: 2026-04-22

## Summary

Fixes the recurring problem where the OpenWeatherMap API key and all Live Map admin settings silently reverted to factory defaults between sessions (typically after a phpVMS `/update`, a cache clear, a deploy, or a hoster-initiated storage reset).

## Root Cause

Since v4.6.1, settings were stored **only** in `storage/app/kvp.json` (a single flat JSON file managed by Spatie Valuestore) and the durable database backup rows were proactively deleted on every admin page load. If `kvp.json` was lost or corrupted for any reason — hoster deploy wiping `storage/app/`, file permission reset, concurrent-write race in Spatie Valuestore, cache maintenance — there was no fallback and every setting reverted to its code default.

## What v4.6.4 Changes

### 1) Dual-Write Persistence

- every admin save now writes to **both** stores:
  - fast read path: `kvp` (`storage/app/kvp.json`)
  - durable backup: module-owned rows in the `settings` table (group `livemap_module`)

### 2) Self-Healing Reads

- admin page, weather tile proxy, and live map frontend all:
  - read `kvp` first
  - fall back to the durable DB row if `kvp.json` was wiped
  - automatically re-seed `kvp.json` from the DB row on the next read

### 3) No More Destructive Cleanup

- the admin page no longer deletes legacy `acars.livemap_*` rows on load
- those rows (now `group=livemap_module`) are the recovery source of truth

### 4) No DB Migration Required

- the fix uses the existing phpVMS `settings` table — **no** new tables, **no** new migrations, **no** SSH needed
- upgrade in-place by replacing the module files and hitting **Admin -> Clear Caches**

## Upgrade Instructions (No SSH)

1. Replace the `LiveMap/` module directory and the three `live_map*.blade.php` widget files with the v4.6.4 versions.
2. Visit **Admin -> Maintenance -> Clear Caches** in phpVMS.
3. Open **Admin -> Live Map** once. Any value still present in `kvp.json` is mirrored into the durable DB backup automatically.
4. Re-enter the OWM API key if it was lost during the previous reset — it will now survive future cache clears and deploys.

---

## v4.6.3 - Weather Proxy Resilience Hotfix

Release date: 2026-04-04

## Summary

This hotfix hardens weather tile delivery when OpenWeatherMap layer availability changes and reduces recurring overlay failures from older client requests.

## Key Fixes

### 1) Server-side Upstream Fallback Chain

- weather proxy now attempts compatible layers when the primary pressure layer fails:
  - `pressure_new`
  - `precipitation_new`
  - `clouds_new`

### 2) Legacy Request Compatibility Kept

- older widget requests are still resolved safely:
  - `thunder_new` -> `pressure_new`
  - `weather_new` -> `precipitation_new`

### 3) Better Proxy Diagnostics

- added response headers for visibility in browser/network tools:
  - `X-LiveMap-Upstream-Layer`
  - `X-LiveMap-Fallback`
- warning logs now include attempted upstream layer sequence.

### 4) API Key Persistence in Admin

- leaving the API key input empty no longer clears the stored key on save
- added explicit checkbox to remove the stored key intentionally
- key validation now runs only when a new/different key is submitted

## Upgrade Instructions (No SSH)

Install this release as a **full package** (module + all three widget files).

1. Deploy module folder: `Modules/LiveMap`
2. Deploy widget files to your active theme:
   - `live_map.blade.php`
   - `live_map_styles.blade.php`
   - `live_map_scripts.blade.php`
3. Open `/update` in browser.
4. Open **Admin -> Live Map** once.
5. In Admin, run **Clear Caches**.
6. Hard refresh browser cache (`Ctrl+F5`).

No SSH/CLI commands are required.

---

## v4.6.2 - Weather Layer Compatibility Hotfix

Release date: 2026-03-17

## Summary

This hotfix aligns Live Map weather overlays with currently supported OpenWeatherMap tile behavior and keeps legacy client requests compatible.

## Key Fixes

### 1) Storm Layer Compatibility

- changed storms primary tile from `thunder_new` to `pressure_new`
- updated storms fallback chain to:
  - `pressure_new`
  - `precipitation_new`
  - `clouds_new`

### 2) Proxy Backward Compatibility

- weather proxy now accepts legacy layer requests and resolves them safely:
  - `thunder_new` -> `pressure_new`
  - `weather_new` -> `precipitation_new`

### 3) UI/Admin Wording Cleanup

- weather button and admin labels now describe this layer as pressure-based storm proxy behavior.

## Upgrade Instructions (No SSH)

Install this release as a **full package** (module + all three widget files).

1. Deploy module folder: `Modules/LiveMap`
2. Deploy widget files to your active theme:
   - `live_map.blade.php`
   - `live_map_styles.blade.php`
   - `live_map_scripts.blade.php`
3. Open `/update` in browser.
4. Open **Admin -> Live Map** once.
5. In Admin, run **Clear Caches**.
6. Hard refresh browser cache (`Ctrl+F5`).

No SSH/CLI commands are required.

---

## v4.6.1 - Stability Hotfix Rollup

Release date: 2026-03-15

## Summary

This hotfix release stabilizes template compatibility, admin setting scope, map interactions, and click behavior after v4.6.0.

## Key Fixes

### 1) Blade Compatibility Fix

- removed deprecated/invalid `View::getName()` usage from `live_map.blade.php`
- added robust include resolution with `View::exists(...)` fallbacks for split files:
  - `live_map_styles.blade.php`
  - `live_map_scripts.blade.php`

### 2) Live Map Settings Scope Cleanup

- moved Live Map setting storage to module-internal keys (`kvp` with `livemap.*`)
- added automatic migration from legacy `acars.livemap_*` values
- added cleanup logic to remove old Live Map entries from global `Admin -> Settings`

### 3) Flights Panel and Boarding Pass UI Recovery

- restored missing desktop CSS for top flights panel
- restored missing desktop CSS for top-right boarding pass card
- fixed panel visibility regressions for modern mode

### 4) Marker Click Reliability

- made FIR/UIR label markers non-interactive to prevent click interception over aircraft markers
- improved aircraft marker click-through consistency for opening the boarding pass

### 5) Map Zoom/Scroll Interaction Fix

- removed follow-mode map method overrides that could block manual zoom/pan
- manual user interactions now remain responsive when network layers are enabled

### 6) Admin Note UX Improvement

- `ACARS Live Time` note now reads real value dynamically
- warning is shown only for unsafe value (`<= 0`)
- safe values show a minimal, non-intrusive info line

### 7) Packaging Policy

- release process now uses versioned ZIP names as primary artifacts
- avoids confusion with stale browser/OS cache on generic zip names

## Upgrade Instructions (No SSH)

Install this release as a **full package** (module + all three widget files).

1. Deploy module folder: `Modules/LiveMap`
2. Deploy widget files to your active theme:
   - `live_map.blade.php`
   - `live_map_styles.blade.php`
   - `live_map_scripts.blade.php`
3. Open `/update` in browser.
4. Open **Admin -> Live Map** once (this triggers legacy setting migration/cleanup).
5. In Admin, run **Clear Caches**.
6. Hard refresh browser cache (`Ctrl+F5`).

No SSH/CLI commands are required.

## Compatibility

- phpVMS 7
- SPTheme and Disposable_v3 (plus compatible custom themes)

## Support

Crafted with ♥ in Germany by Thomas Kant - Support via PayPal:

[https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2](https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2)

---

# v4.6.0 - Admin UX Simplification, Mobile Cleanup, and Safe Config Defaults

Release date: 2026-03-14

## Summary

This release focuses on operational reliability and easier administration.

- simplified admin color controls
- cleaned up mobile controls
- improved weather proxy diagnostics
- clarified phpVMS ACARS `Live Time` recommendation (critical)

## Key Changes

### 1) Simplified Admin Colors (Reduced to 3)

Color settings are now intentionally minimal:

1. Primary UI Color
2. Accent UI Color
3. Box Background Color

Internal mapping preserves full UI coverage while reducing confusion.

### 2) Mobile UI Cleanup

- removed extra floating mobile Network button
- retained one mobile floating button (`Flights`)
- Network remains accessible through the Network panel/tab
- stronger active/inactive visual feedback for the Flights button

### 3) Layout / Config Consistency

- layout mode remains single-choice (Modern vs Old Style) to avoid conflicts
- stale legacy mobile-network-button option removed

### 4) Weather Proxy Reliability

- server-side OpenWeatherMap key handling remains default path
- admin status panel surfaces upstream error context
- fallback blank tiles avoid repeated 502 console spam when upstream fails

### 5) Follow-Mode Behavior

- improved multi-flight follow logic (fit all active flights)
- avoids poor framing when two or more active aircraft are spread out

## Important Operational Note (phpVMS Core)

`ACARS -> Live Time` should be set to **1 or greater**.

Do not use `0` in production. In phpVMS core, this value also affects stale/stuck PIREP cancellation/removal routines. A zero value can interfere with that housekeeping behavior.

## Upgrade Instructions

1. Update module folder: `Modules/LiveMap`
2. Update widget file: `resources/views/layouts/<your_theme>/widgets/live_map.blade.php`
3. Open `/update` in the browser.
4. In Admin, use **Clear Caches**.
5. Hard refresh browser cache (`Ctrl+F5`)

## Download Artifacts

- `LiveMap-module.zip`
- `LiveMap-full-package.zip`

## Compatibility

- phpVMS 7
- SPTheme and Disposable_v3 (plus compatible custom themes)

## Support

Crafted with ♥ in Germany by Thomas Kant - Support via PayPal:

[https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2](https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2)

Why donations are useful for small projects:

- They fund hosting and ongoing development effort.
- They accelerate fixes, polishing, and release quality.
- They help keep niche community projects alive and maintained.
