# LiveMap Module (phpVMS 7)

Version: **4.6.0**

This module provides:

1. Admin settings page at `/admin/livemap`
2. Server-side OpenWeatherMap tile proxy at `/livemap/weather-tile/{layer}/{z}/{x}/{y}.png`

## Install

1. Copy folder `LiveMap` to your phpVMS installation:
   - `Modules/LiveMap`
2. Deploy matching blade widget files from this release:
   - `live_map.blade.php`
   - `live_map_styles.blade.php`
   - `live_map_scripts.blade.php`
3. Open `/update` in your browser to refresh modules/routes.
4. Open Admin -> Live Map and save once.
5. Use Admin -> Clear Caches (no SSH required).

## Important

- For secure weather usage, keep **Weather Proxy** enabled.
- In phpVMS ACARS settings, keep **Live Time >= 1** (avoid `0` on production).

## Key Benefit

With weather proxy enabled, the OWM key remains server-side and is not exposed in browser DevTools.
