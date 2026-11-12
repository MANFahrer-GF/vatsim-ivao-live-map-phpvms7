# LiveMap Module (phpVMS 7)

Version: **5.2.2**

This module provides:

1. The live map itself (views inside the module, theme file is a single `@include`)
2. Admin settings page at `/admin/livemap` (every user with admin panel access)
3. Flight data at `/livemap/api/fluege` and a server-side OpenWeatherMap tile proxy

## Install (FTP + phpVMS admin, no SSH)

1. Upload this folder as `modules/LiveMap_new/`, then rename it to `modules/LiveMap`
   (old folder first to `LiveMap_old`). First install: Admin -> Modules -> Activate Module.
2. Upload `live_map.blade.php` from the package into
   `resources/views/layouts/<Theme>/widgets/` of every theme you use, and delete
   `live_map_styles.blade.php` / `live_map_scripts.blade.php` there.
3. Admin -> Maintenance -> Clear all caches.
4. Open Admin -> Live Map and save once.

Full guide: `INSTALL.txt` in the release ZIP.

## Important

- For secure weather usage, keep **Weather Proxy** enabled.
- In phpVMS ACARS settings, keep **Live Time >= 1** (avoid `0` on production).
