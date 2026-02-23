# Changelog

All notable changes to this project are documented in this file.

---

## [2.0.0] — 2026-02-23

### New Features
- **VA Flight Route Line** — Clicking your own VA aircraft now shows a dashed red line to the destination airport, parsed directly from the phpVMS flight info card
- **VA Aircraft Icon** — phpVMS aircraft replaced with a distinctive white/red/blue SVG icon; rotation handled by leaflet-rotatedmarker (correct heading)
- **Dark Map persistent** — Dark mode state is now saved to localStorage and restored on next page load
- **TRACON auto-merge** — TRACON / Approach Control facilities are automatically merged into the nearest airport marker (within 80 km) instead of rendering as separate markers
- **Airport full names** — Full airport names from VATSpy data shown in controller popups
- **ATIS collapsible** — ATIS text shows a 60-character preview with "Show full ATIS" toggle
- **Route line destination badge** — A red ICAO label is shown at the destination airport when a route line is active
- **Badge legend** — Visual reference panel in the VATSIM box showing all badge types and colours

### Improvements
- Controller zoom thresholds lowered: badges visible from zoom 3, labels from zoom 5 (better for large countries like USA)
- Default start state: only Controllers active; Pilots and FIR Sectors off by default
- Airline logos loaded from phpVMS database instead of external CDNs (always up-to-date)
- Airport marker click area enlarged (36 px height) for easier interaction
- APP/TRACON badge changed from orange to green; combined APP+ATIS shows "Ai" badge
- Map click handler registered once globally (no listener accumulation)
- `showRouteLine` no longer registers a new click listener on every call

### Bug Fixes
- Fixed: Dark Map button had no effect when OWM API key was missing (button was inside the key-guard early-return block)
- Fixed: VA route line not shown on second click (lastDrawnArr scope bug — variable was local to observer closure, not resettable by map click handler)
- Fixed: Duplicate `layeradd` handlers were overwriting each other (icon replace + click handler merged into single handler)
- Fixed: Controller zoom visibility comment described wrong thresholds
- Fixed: Dead variable `vaCallsignSet` causing silent ReferenceError after refactor
- Fixed: `vatsimShowSectors` declared between functions instead of with other state variables

### Removed
- Removed: VA duplicate filter (VATSIM pilots with same callsign hidden) — unreliable because pilots use different callsigns on VATSIM vs phpVMS
- Removed: External phpVMS API call for VA flight data (required authentication, caused "Nicht erreichbar" error)

---

## [1.0.0] — 2026-02-20

### Initial Release

- Real-time VATSIM pilot positions with popup (callsign, route, aircraft, altitude, speed, heading, pilot name)
- VATSIM controller markers with colour-coded facility badges (DEL, GND, TWR, APP, CTR)
- FIR sector boundaries as coloured polygons from VATSpy GeoJSON
- Controller positions from VATSIM Transceivers API (accurate, matches vatSpy)
- Airport positions from VATSpy.dat (~7000 airports, no more wrong positions)
- Key normalisation: EWR ↔ KEWR, AU Y-prefix airports, Pacific P-prefix airports
- Pilot route line (dashed red) on aircraft click
- Follow Flight toggle intercepting phpVMS panTo/setView/flyTo
- OWM weather overlays: Clouds, Radar, Storms, Wind, Temperature, Combo
- Dark Map (CSS filter night mode)
- Weather opacity slider
- Airline logos in VATSIM pilot popups
- VATSIM live indicator dot with pilot/controller counts
- 30-second VATSIM refresh interval
