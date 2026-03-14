# VATSIM + IVAO Live Map for phpVMS 7

Version: **4.6.0** (2026-03-14)

Interactive live map widget for phpVMS 7 with VATSIM/IVAO traffic, FIR/UIR sectors, VA flight panels, weather overlays, and an admin-driven configuration module.

## Highlights

- Dual-network map: VATSIM + IVAO pilots/controllers
- FIR + UIR sector rendering from VATSpy data
- VA Active + Planned flights panel with mobile layout support
- Multi-flight follow mode (fit all active aircraft)
- OpenWeatherMap server-side proxy (API key stays server-side)
- Admin settings page (`/admin/livemap`) for layout, weather, network, mobile, colors
- Mobile cleanup: single mobile **Flights** button + separate Network side tab
- Simplified color system: only 3 admin colors (Primary, Accent, Box Background)

## Package Contents

This repository ships two deployable parts:

1. `LiveMap/` (phpVMS module)
2. `live_map.blade.php` (theme widget template)

For convenience, `LiveMap-full-package.zip` contains both.

## Installation

### Option A (recommended): Full package

1. Extract `LiveMap-full-package.zip`.
2. Copy `LiveMap/` to your phpVMS root under `Modules/LiveMap`.
3. Copy `live_map.blade.php` to your active theme widget path, for example:
   - `resources/views/layouts/SPTheme/widgets/live_map.blade.php`
   - `resources/views/layouts/Disposable_v3/widgets/live_map.blade.php`
4. Open your phpVMS update endpoint in the browser:
   - `/update`
5. Open **Admin -> Live Map** and save settings once.
6. In the phpVMS Admin area, run **Clear Caches** (no SSH needed).

### Option B: Module-only update

If your blade is already current, install only `LiveMap-module.zip`.

## Upgrade Notes

- If you update from older releases, deploy **both** files (`LiveMap/` + `live_map.blade.php`) to avoid UI/config mismatches.
- Hard refresh browser cache after deploy (`Ctrl+F5`).

## Critical phpVMS Setting Note (Important)

`ACARS -> Live Time` should be **1 or greater**.

Do **not** set `Live Time = 0` on production systems.

Reason: in phpVMS core, this setting is also used by automated stale/stuck PIREP cleanup and cancellation logic. Setting it to `0` can interfere with that housekeeping flow.

Recommended baseline:

| Setting | Recommended | Why |
|---|---:|---|
| Center Coords | `51.1657,10.4515` | Example center for Germany |
| Default Zoom | `5` | Regional overview |
| Live Time | `1` | Keeps phpVMS cleanup behavior safe |
| Refresh Interval | `60` | Stable update interval |

## Weather Proxy

When **Enable server-side weather proxy** is ON:

- OpenWeatherMap key is stored server-side
- browser DevTools does not expose the key
- tile failures can be handled with blank fallback tiles to avoid 502 spam

Admin page also shows:

- proxy status
- last OWM error code
- short troubleshooting hint

## Mobile Behavior

Current mobile behavior is intentionally minimal:

- One floating button: **Flights**
- Network control remains in the side/bottom Network panel (no extra green network floating button)
- Admin options control default open/closed states for Flights/Weather/Network sections

## Colors (Reduced)

Admin color settings were simplified to:

1. **Primary UI Color**
2. **Accent UI Color**
3. **Box Background Color**

Mapping:

- Primary: Weather/Network headers, Flights header start, mobile Flights button inactive
- Accent: Flights header end, mobile Flights button active
- Box Background: body area of flights/weather/network panels

## Security

- External API output is sanitized before DOM rendering
- Weather key can be protected server-side via proxy mode
- See `SECURITY.md` for policy and reporting

## Compatibility

- phpVMS 7 (current maintained branches)
- Themes: SPTheme, Disposable_v3 (and any theme using compatible widget path)

## Release Files

- `CHANGELOG.md` -> full change history
- `RELEASE_NOTES.md` -> current release summary for GitHub release text

## Credits

- VATSIM Network
- IVAO Network
- VATSpy data project
- Leaflet
- OpenWeatherMap
- phpVMS

Maintained by German Sky Group / Thomas Kant.

## Support

Crafted with ♥ in Germany by Thomas Kant - Support via PayPal:

[https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2](https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2)

Why donations matter for small projects:

- They help cover hosting, testing, and maintenance costs.
- They make it possible to spend time on bug fixes and compatibility updates.
- They keep community tools active and sustainable over the long term.
