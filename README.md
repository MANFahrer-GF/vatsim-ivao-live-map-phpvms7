# VATSIM + IVAO Live Map for phpVMS 7

Version: **4.6.6** (2026-04-22)

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

For convenience, versioned full packages contain both, for example:

- `LiveMap-full-package-20260422-xxxxxx-v4.6.5.zip`

## Installation (No SSH)

Install this release as a **full package** — the module code and the three widget blade files must always be on the same version. Mixing a new module with old widget files is the #1 cause of "loads and loads", mixed-content warnings, and stale settings.

### 1. Deploy the module

Copy the `LiveMap/` directory to your phpVMS installation:

```
<phpvms-root>/modules/LiveMap/
```

### 2. Deploy the three widget files

Copy all **three** blade files into your active phpVMS theme's widgets directory. Example paths:

```
resources/views/layouts/SPTheme/widgets/live_map.blade.php
resources/views/layouts/SPTheme/widgets/live_map_styles.blade.php
resources/views/layouts/SPTheme/widgets/live_map_scripts.blade.php
```

or for Disposable v3:

```
resources/views/layouts/Disposable_v3/widgets/live_map.blade.php
resources/views/layouts/Disposable_v3/widgets/live_map_styles.blade.php
resources/views/layouts/Disposable_v3/widgets/live_map_scripts.blade.php
```

All three files are required. If you only replace one, the frontend and the backend will disagree about which settings store / which tile URLs are in use.

### 3. Run phpVMS update + cache clear

In the browser, open:

```
https://<your-site>/update
```

Then in **Admin → Maintenance** click **Clear Caches**.

### 4. Configure Live Map

Open **Admin → Live Map**:

1. Paste your OpenWeatherMap API key (or leave empty and disable the weather box).
2. Keep **Enable server-side weather proxy** ON (recommended — your key never reaches the browser).
3. Pick your default basemap, weather layer, network toggles, and UI colors.
4. Click **Save Settings**.

Settings are now stored in the phpVMS database (`settings` table, group `livemap_module`). They will survive `/update`, cache clears, deploys, and hoster storage resets.

No SSH/CLI commands are required for any of the above.

## Upgrading from an Older Version

- If you were on v4.6.1 – v4.6.4, your existing settings live in `storage/app/kvp.json`. On the **first** visit to Admin → Live Map after upgrading, the module promotes those values into the DB automatically. Nothing to do.
- If you were on v4.6.0 or earlier, your settings live in the legacy phpVMS settings rows. They are picked up by the same read path — no action required.
- Always deploy `LiveMap/` **and** all three widget files together. Hard-refresh the browser (`Ctrl+F5`) after deploy.

## Troubleshooting

**"Settings reset to defaults every day"** — You are on v4.6.1 – v4.6.4. Upgrade to v4.6.5.

**"Map loads and loads and loads"** — Either (a) no OWM key is configured *and* you are running pre-v4.6.5 widget files, so the frontend keeps requesting blank tiles, or (b) your theme still has a pre-v4.6.0 `live_map_scripts.blade.php` with HTTP tile URLs. Redeploy **all three** widget files from v4.6.5.

**"Mixed content warnings in browser console"** — Same cause as above. v4.6.5 uses HTTPS for all tile sources.

**"Weather Proxy Status: API Key Missing"** — Paste the key in Admin → Live Map and click Save Settings. It will persist from now on.

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

Use versioned ZIP file names to avoid browser/CDN cache confusion with generic names.

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
