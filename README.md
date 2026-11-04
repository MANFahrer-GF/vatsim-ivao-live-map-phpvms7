# VATSIM + IVAO Live Map for phpVMS 7

Version: **5.1.0** (2026-11-03)

Interactive live map widget for phpVMS 7 with VATSIM/IVAO traffic, FIR/UIR sectors, VA flight panels, weather overlays, and an admin-driven configuration module.

## Highlights

- Dual-network map: VATSIM + IVAO pilots/controllers
- FIR + UIR sector rendering from VATSpy data
- VA Active + Planned flights panel with mobile layout support; position selectable in the admin: top (collapsible over the map), bottom (fixed table below the map) or off
- Multi-flight follow mode (fit all active aircraft)
- OpenWeatherMap server-side proxy (API key stays server-side)
- Admin settings page (`/admin/livemap`) for layout, weather, network, mobile, colors
- Mobile cleanup: single mobile **Flights** button + separate Network side tab
- Simplified color system: only 3 admin colors (Primary, Accent, Box Background)
- CARTO Basemaps API key support with a status box that verifies the key
- Bogus 0/0 positions are ignored instead of drawing phantom aircraft
- Route line to the destination crosses the date line instead of wrapping around the world
- Pacific ATC sectors (Anchorage, Oakland Oceanic, Magadan …) are shown whole across the date line
- 5.1: flight panel position (top / bottom / off) in the admin, VATSIM/IVAO controllers and FIR assignment the VATSIM Radar way, detail box folds beside a controller box, compact 4.7.9 look again, source "Both" (AeroACARS + phpVMS merged), aircraft silhouettes by class, own Leaflet 1.9.4, views inside the module (theme file is a single `@include`), runs under Seven, SPTheme and Disposable_v3
- 5.0: flight data always fetched through the module (`/livemap/api/fluege`), source phpVMS (default) or AeroACARS Live; own Leaflet map, new layout, map fills the window

## Package Contents

This repository ships two deployable parts:

1. `LiveMap/` (phpVMS module, contains all views, styles and scripts)
2. `live_map.blade.php` (theme file, a single `@include('livemap::widget')`)

For convenience, versioned full packages contain both, for example:

- `LiveMap-full-package-v5.1.0.zip`

## Installation (FTP + phpVMS admin, no SSH)

Install this release as a **full package**: the module and the theme file belong together. The full step-by-step guide is `INSTALL.txt` in the ZIP. `<phpvms-root>` is the folder that contains `.env`, `app/`, `modules/` and `resources/`.

### 1. Upload the module (upload first, swap last)

1. Upload `LiveMap/` with FTP as `<phpvms-root>/modules/LiveMap_new/`.
2. Check that `modules/LiveMap_new/module.json` and `modules/LiveMap_new/Providers/LiveMapServiceProvider.php` exist.
3. Rename `modules/LiveMap` to `modules/LiveMap_old` (first install: skip), then `modules/LiveMap_new` to `modules/LiveMap`.
4. First install only: **Admin → Modules**, LiveMap under *Not Installed Modules* → **Activate Module**.

Never overwrite the live `LiveMap/` folder while the site is running: a half-uploaded module stops every page.

### 2. Upload the theme file into every theme you use

The active theme is shown under **Admin → Settings → General → Current Theme**. phpVMS draws the map wherever a theme calls `{{ Widget::liveMap() }}` (in every standard theme: the page `/livemap`) and takes `widgets/live_map.blade.php` from the active theme; this one-line file hands everything over to the module.

| Theme | Upload `live_map.blade.php` to |
|---|---|
| Seven | `<phpvms-root>/resources/views/layouts/seven/widgets/` |
| SPTheme | `<phpvms-root>/resources/views/layouts/SPTheme/widgets/` |
| Disposable_v3 | `<phpvms-root>/resources/views/layouts/Disposable_v3/widgets/` |
| default / beta | `<phpvms-root>/resources/views/layouts/beta/widgets/` |
| any other | `<phpvms-root>/resources/views/layouts/<Theme>/widgets/` (create `widgets/` if missing) |

Folder names are case-sensitive. A theme that extends another one still gets its own copy. From 5.1 on, `live_map_styles.blade.php` and `live_map_scripts.blade.php` are **no longer needed** — delete them in that `widgets/` folder.

### 3. Clear caches

**Admin → Maintenance → Clear all caches** (use "all": it also rebuilds a cached route list, otherwise the map can stay empty).

### 4. Configure Live Map

Open **Admin → Live Map** (open for every user with admin panel access):

1. Keep the source **phpVMS** (AeroACARS Live is German Sky Group only).
1. Paste your OpenWeatherMap API key (or leave empty and disable the weather box).
2. Keep **Enable server-side weather proxy** ON (recommended — your key never reaches the browser).
3. Pick your default basemap, weather layer, network toggles, and UI colors.
4. Click **Save Settings**.

Settings are now stored in the phpVMS database (`settings` table, group `livemap_module`). They will survive `/update`, cache clears, deploys, and hoster storage resets.

No SSH/CLI commands are required for any of the above.

## Upgrading from an Older Version

- If you were on v4.6.1 – v4.6.4, your existing settings live in `storage/app/kvp.json`. On the **first** visit to Admin → Live Map after upgrading, the module promotes those values into the DB automatically. Nothing to do.
- If you were on v4.6.0 or earlier, your settings live in the legacy phpVMS settings rows. They are picked up by the same read path — no action required.
- **From 4.x / 5.0 to 5.1:** deploy `LiveMap/`, copy the new `live_map.blade.php` into every theme in use, delete the old `live_map_scripts.blade.php` and `live_map_styles.blade.php` there, then Admin → Maintenance → **Clear all caches**. Hard-refresh the browser (`Ctrl+F5`).
- Before 5.1, `LiveMap/` and all three widget files had to be deployed together; with 5.1 only `LiveMap/` plus the one-line `live_map.blade.php` are needed.

## Troubleshooting

**"Settings reset to defaults every day"** — You are on v4.6.1 – v4.6.4. Upgrade to v4.6.5.

**"Map loads and loads and loads"** — Either (a) no OWM key is configured *and* you are running pre-v4.6.5 widget files, so the frontend keeps requesting blank tiles, or (b) your theme still has a pre-v4.6.0 `live_map_scripts.blade.php` with HTTP tile URLs. Upgrade to 5.1 (no theme scripts any more) or redeploy all three widget files from v4.6.5.

**"Mixed content warnings in browser console"** — Same cause as above. v4.6.5 uses HTTPS for all tile sources.

**"Weather Proxy Status: API Key Missing"** — Paste the key in Admin → Live Map and click Save Settings. It will persist from now on.

## Flight Source

Admin → Live Map → source:

- **phpVMS** (default): the running PIREPs of this site.
- **AeroACARS Live** (German Sky Group only): flights from the live server of German Sky Group's own ACARS client. It needs a read key that only German Sky Group has, so other VAs use **phpVMS**. The key stays on the server. Optional **VA prefix** limits the list to the flights of one VA.
- **Both**: AeroACARS flights plus phpVMS-only flights. An AeroACARS flight and a phpVMS flight with the same pilot and the same callsign are shown as one flight (registration, PIREP link and logo come from the PIREP). If one source fails, the other is shown with a notice.

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

On narrow screens the flights list, the flight details and the toolbar panels
share one column, so only one is open at a time. Admin options (section
*Phones and narrow screens*) choose whether the flights list is shown and what
is open at start.

## Colors

Colour settings (phase, ATC and route colours stay fixed as data colours):

1. **Accent colour** and **Accent colour (dark mode)**: active toolbar buttons and switches, the selected tab, the selected flight and the tint of hovered and selected rows
2. **Panel background** (light mode only): flights list, flight details and toolbar panels

## Security

- External API output is sanitized before DOM rendering
- Weather key can be protected server-side via proxy mode
- See `SECURITY.md` for policy and reporting

## Compatibility

- phpVMS 7 (current maintained branches)
- Themes: Seven, SPTheme, Disposable_v3; other themes work by copying `live_map.blade.php` into their `widgets/` folder (the map brings its own Leaflet, independent of the theme)

## Release Files

- `CHANGELOG.md` -> full change history
- `RELEASE_NOTES.md` -> current release summary for GitHub release text

Use versioned ZIP file names to avoid browser/CDN cache confusion with generic names.

## Sources and Attribution

- **VATSpy Data Project** ([vatsimnetwork/vatspy-data-project](https://github.com/vatsimnetwork/vatspy-data-project)): FIR/UIR boundaries and airport/position data, loaded in the browser via jsDelivr.
- **VATSIM Radar:** the rules for assigning controllers to positions and FIRs (voice-frequency gate 117–137 MHz, controllers grouped by resolved facility, longest prefix, sub-sectors, collective positions from `[UIRs]`, `_FSS` = oceanic area) are adopted via AeroACARS live. VATSIM Radar content is licensed CC BY-NC 4.0.
- Not used: TRACON areas of the SimAware TRACON Project (no CORS header on the release asset; jsDelivr only offers single files without a prefix index) and VATGlasses altitude sectors (28 MB, CC BY-NC-SA, third-party service). TRACONs keep the green shield.

## Credits

- VATSIM Network
- IVAO Network
- VATSpy data project
- VATSIM Radar (controller/FIR assignment rules)
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
