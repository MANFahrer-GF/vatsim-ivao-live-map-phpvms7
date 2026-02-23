# VATSIM + IVAO Live Map for phpVMS 7

A feature-rich live map widget for phpVMS 7 that integrates real-time **VATSIM and IVAO** data alongside your Virtual Airline flights — with a VA flights panel, route lines, weather overlays, dark mode, and much more.

![Live Map Screenshot](screenshot.png)

**Live Demo:** [german-sky-group.eu/livemap](https://german-sky-group.eu/livemap)

---

## Features

- **VATSIM Pilots** — Real-time aircraft positions with callsign, route, aircraft type, altitude, speed, heading and pilot name
- **VATSIM Controllers** — Airport markers with colour-coded badges (Delivery, Ground, Tower, Approach/ATIS, Center)
- **IVAO Pilots** — Real-time IVAO aircraft in orange, shown simultaneously alongside VATSIM
- **IVAO Controllers** — Airport badges with distinctive orange-outline IVAO style
- **FIR Sectors** — Active airspace boundaries as clickable coloured polygons with controller info (both networks)
- **TRACON / Approach Control** — Auto-merged into nearby airport markers (within 80 km)
- **ATIS** — Collapsible full ATIS text inside airport popup (60-char preview + "Show full ATIS")
- **VA Active Flights Panel** — Top-centre collapsible panel listing all active phpVMS/ACARS flights with route, altitude, speed, distance, status and pilot name
- **VA Info Card** — Dedicated flight info card (top-right) filled directly from ACARS API data when clicking a panel row
- **Route Line** — Click any aircraft to show a dashed red line to its destination airport
- **Follow Flight** — Keeps the map centred on all active VA flights simultaneously
- **Weather Overlays** — 6 OWM layers: Clouds, Radar, Storms, Wind, Temperature, Combo + opacity slider
- **Dark Map** — Night mode with persistent state (localStorage)
- **Airline Logos** — Loaded from your phpVMS airline database (no external CDN)
- **Badge Legend** — Visual reference for all controller badge types and colours
- **Airport Names** — Full airport names from VATSpy data in controller popups

---

## Requirements

- phpVMS 7 (any recent version)
- HTTPS (required for VATSIM/IVAO APIs and OWM weather tiles)
- OpenWeatherMap API key (free) — **optional**, only needed for weather overlays

---

## Installation

Copy `live_map.blade.php` to the correct path for your active theme:

| Theme | Path |
|-------|------|
| seven (default) | `resources/views/layouts/seven/widgets/live_map.blade.php` |
| beta | `resources/views/layouts/beta/widgets/live_map.blade.php` |
| default | `resources/views/layouts/default/widgets/live_map.blade.php` |
| SPTheme | `resources/views/layouts/SPTheme/widgets/live_map.blade.php` |
| Disposable_v3 | `resources/views/layouts/Disposable_v3/widgets/live_map.blade.php` |

> The file is identical for all themes — only the installation path differs.

After copying, open the file and set your OpenWeatherMap API key (see section below). Everything else works out of the box with no additional configuration required.

---

## OpenWeatherMap API Key (optional)

Weather overlays require a free API key from [openweathermap.org](https://openweathermap.org/api). The free tier is sufficient.

Open `live_map.blade.php` and find this line near the top of the `<script>` section:

```javascript
var OWM_API_KEY = "YOUR_OPENWEATHERMAP_API_KEY_HERE";
```

Replace `YOUR_OPENWEATHERMAP_API_KEY_HERE` with your actual key.

**Tip:** Use `Ctrl+F` and search for `YOUR_OPENWEATHERMAP_API_KEY_HERE` to find the line instantly.

Without a key, all weather overlay buttons are hidden automatically — the Dark Map button and all other controls still work normally. No errors are thrown.

---

## IVAO

IVAO data is fetched from the public IVAO Tracker API (`https://api.ivao.aero/v2/tracker/whazzup`). **No API key required.**

Pilot and controller counts are always loaded in the background regardless of whether the IVAO network toggle is active — so you always see live stats even when the layer is turned off.

---

## Airline Logos

Logos are loaded directly from your phpVMS **airline database** — not from external CDNs. This means logos are always accurate and up-to-date (e.g. Lufthansa's current blue crane, not the old yellow one still found on most CDNs).

**How it works:**
1. The first 3 letters of a VATSIM callsign are used as the ICAO prefix (e.g. `DLH187` → `DLH`)
2. phpVMS looks up that ICAO in your Airlines table and uses the uploaded logo

**Requirements:**
- Airline created in **Admin → Airlines**
- ICAO code matches the callsign prefix (e.g. `DLH` for Lufthansa)
- Logo uploaded in the airline settings

---

## Admin Panel Configuration (Recommended)

Set your map's default position and behaviour in **Admin → ACARS**:

| Setting | Recommended (German VA) | Description |
|---------|------------------------|-------------|
| Center Coords | `51.1657,10.4515` | Geographic centre of Germany |
| Default Zoom | `5` | Shows Germany + neighbouring countries |
| Live Time | `0` | Only show flights currently in progress |
| Refresh Interval | `60` | Position update interval in seconds |

---

## Control Panel — Button Guide

### VATSIM / IVAO Network Toggles
Two buttons at the top of the control panel switch each network on and off independently. Both can be active simultaneously — VATSIM pilots appear in **blue**, IVAO pilots in **orange**, making them immediately distinguishable.

Stats (pilots online / controllers online) are always shown for both networks even when the layer is toggled off, so you always have a live overview.

- **VATSIM** refreshes every 30 seconds
- **IVAO** refreshes every 15 seconds

### Pilots
Toggles all active pilot aircraft for the currently enabled networks. Each aircraft marker shows:
- Callsign, departure → arrival, aircraft type
- Altitude, groundspeed, heading, pilot name
- Airline logo (if available in your phpVMS database)
- Click → dashed red line drawn to the destination airport

### Controllers
Toggles airport markers with colour-coded controller badges. Applies to both VATSIM and IVAO depending on which networks are enabled.

**VATSIM badge colours:**

| Badge | Colour | Facility |
|-------|--------|----------|
| **D** | Blue | Delivery |
| **G** | Orange | Ground |
| **T** | Red | Tower |
| **Ai** | Green | Approach + ATIS combined |
| **A** | Green | Approach only |
| **i** | Light blue | ATIS only |
| **C** | Teal | Center / FIR |

**IVAO badge style:** same layout with orange outline to visually distinguish IVAO positions.

Click any airport marker to open a popup showing: frequency, controller name, CID/VID, rating, time online, and full ATIS text (collapsible).

### FIR Sectors
Toggles active airspace boundaries as coloured dashed polygons. Only sectors with an active controller are shown. Clicking a sector opens a popup with controller details and a list of active sub-sectors.

### Follow Flight
Keeps the map view centred on your VA's active ACARS flights:
- **1 pilot online** → pans to that aircraft
- **Multiple pilots** → fits all active flights into view simultaneously
- **0 pilots** → returns to the default position set in Admin → ACARS

Toggle to **Free Scroll** at any time to browse the map manually without being snapped back.

### VA Active Flights Panel
A collapsible panel at the **top-centre** of the map listing all active phpVMS/ACARS flights. The toggle button shows a live count badge (red when flights are active, grey when empty).

**Columns:**

| Column | Description |
|--------|-------------|
| Flight | Callsign / flight number |
| Route | DEP → ARR airport ICAO |
| Aircraft | Registration and type |
| Altitude | Current altitude in ft |
| Speed | Groundspeed in knots |
| Distance | Flown / planned in nmi (e.g. `573 / 4895 nmi`) |
| Status | Flight phase (En Route, Boarding, Landed, etc.) |
| Pilot | Pilot name from phpVMS user record |

**Clicking a row:**
- Zooms the map to the aircraft position
- Opens the **VA Info Card** (top-right) with full flight details
- Draws a dashed red route line to the destination airport
- Highlights the row as active (persists across panel refreshes)

The panel refreshes at the same interval as your phpVMS `acars.update_interval` setting.

### VA Info Card
When a row is clicked in the VA flights panel, a dedicated info card appears in the **top-right corner** showing:
- Departure → Arrival route
- Callsign, aircraft registration and type
- Altitude, groundspeed
- Pilot name
- Flight status badge
- Airline logo (if available)

The card has a **✕ close button** and also closes when clicking anywhere on the map. If you click a real VATSIM/IVAO marker directly, the standard phpVMS info card takes over and the VA card hides automatically.

### Route Line
- **VATSIM / IVAO aircraft** — click to show a dashed red line from the aircraft to its flight plan destination
- **VA aircraft (panel click)** — same behaviour, destination read from phpVMS ACARS data
- A **red ICAO badge** marks the destination airport at the end of the line
- Click anywhere on the map to dismiss the line

### Weather Layers
Six OWM overlay buttons (requires API key):

| Button | Layer |
|--------|-------|
| Clouds | Cloud coverage |
| Radar | Precipitation / rain radar |
| Storms | Thunderstorm cells |
| Wind | Wind speed and direction |
| Temp | Surface temperature |
| Combo | Clouds + Radar + Storms together |

**Dark Map** — applies a CSS night filter to the base map. State is saved in localStorage and restored on the next page load.

**Opacity slider** — adjusts the transparency of all active weather layers simultaneously.

---

## Configuration Variables

All variables are at the top of the `<script>` section in the file:

| Variable | Default | Description |
|----------|---------|-------------|
| `OWM_API_KEY` | `"YOUR_..."` | OpenWeatherMap API key (optional) |
| `VATSIM_REFRESH_MS` | `30000` | VATSIM data refresh interval in ms |
| `IVAO_REFRESH_MS` | `15000` | IVAO data refresh interval in ms |

The VA flights panel refresh interval is taken automatically from your phpVMS `acars.update_interval` admin setting.

---

## Compatibility

Tested with:
- phpVMS 7 (dev branch)
- Themes: seven, Disposable_v3, SPTheme
- ACARS clients: vmsACARS, smartCARS 3

---

## Credits

- Weather overlay concept inspired by: [Rick Winkelman (Air Berlin Virtual)](https://github.com/ncd200/Weather-Overlay-on-the-Live_Map)
- VATSIM live data: [VATSIM Network](https://vatsim.net)
- IVAO live data: [IVAO Network](https://ivao.aero)
- Airport positions and FIR boundaries: [VATSpy Data Project](https://github.com/vatsimnetwork/vatspy-data-project)
- Map library: [Leaflet](https://leafletjs.com)
- Weather tiles: [OpenWeatherMap](https://openweathermap.org)
- Virtual airline platform: [phpVMS](https://phpvms.net)
- VATSIM/IVAO integration, design & development: [German Sky Group](https://german-sky-group.eu)

---

## License

MIT License — free to use and modify. Attribution appreciated but not required.
