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
