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
