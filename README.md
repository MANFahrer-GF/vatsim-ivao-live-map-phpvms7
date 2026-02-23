# VATSIM Live Map for phpVMS 7

A fully featured live map override for [phpVMS 7](https://github.com/phpvms/phpvms) that adds real-time VATSIM integration, weather overlays, and a modern popup design.

> **Weather overlay concept** based on [Weather Overlay on the Live Map](https://github.com/ncd200/Weather-Overlay-on-the-Live_Map) by **Rick Winkelman (Air Berlin Virtual)**

---

## 🌍 Live Demo

**[Live Karte - German Sky Group](https://german-sky-group.eu/livemap)**

---

## 📦 Installation

This repo contains a **single file**: `live_map.blade.php`

Copy it to the correct path depending on your theme:

| Theme | Path |
|-------|------|
| **seven** (default) | `resources/views/layouts/seven/widgets/live_map.blade.php` |
| **beta** | `resources/views/layouts/beta/widgets/live_map.blade.php` |
| **default** | `resources/views/layouts/default/widgets/live_map.blade.php` |
| **SPTheme** | `resources/views/layouts/SPTheme/widgets/live_map.blade.php` |
| **Disposable_v3** | `resources/views/layouts/Disposable_v3/widgets/live_map.blade.php` |

> Not sure which theme you use? Check your phpVMS Admin → Settings → General → Theme.

---

## 🔑 OpenWeatherMap API Key (required for weather overlays)

1. Register for free at [openweathermap.org](https://home.openweathermap.org/users/sign_up)
2. Open `live_map.blade.php` and find:
```javascript
var OWM_API_KEY = "YOUR_OPENWEATHERMAP_API_KEY_HERE";
```
3. Replace with your key.

> ⚠️ Without a valid key the weather buttons are hidden automatically — the map works fully without weather.

---

## ✨ Features

### VATSIM Integration
- Live pilots with aircraft icons rotated by heading
- Controllers with color-coded badges (DEL / GND / TWR / APP / CTR)
- FIR/UIR sector boundaries from VATSpy data
- TRACON / Approach Control automatically merged into the nearest airport marker
- ATIS collapsed by default, expandable — no more giant popups
- Full airport name in controller popups
- Refreshes every 30 seconds

### Badge Legend (built into VATSIM panel)
| Badge | Color | Meaning |
|-------|-------|---------|
| **D** | 🔵 Blue | Delivery |
| **G** | 🟠 Orange | Ground |
| **T** | 🔴 Red | Tower |
| **Ai** | 🟢 Green | Approach + ATIS |
| **C** | 🩵 Teal | Center / FIR |
| **i** | 💙 Light Blue | ATIS only |

### Own VA Flights
- Large distinctive aircraft icon — always visible above VATSIM traffic
- Click any aircraft → dashed route line to destination airport
- Follow Flight toggle — auto-pan or scroll freely

### Weather Overlays
- Clouds, Radar, Storms, Wind, Temperature, Combo layers
- Opacity slider + Dark map toggle

### Popup Design
- Card-style popups for pilots, controllers and FIR sectors
- Airline logos loaded from your own phpVMS database (always current)

---

## ⚙️ Configuration

Find these variables near the top of the `<script>` block:

```javascript
// VATSIM refresh interval in ms (VATSIM policy minimum = 15000)
var VATSIM_REFRESH_MS = 30000;

// Default layer visibility on page load
var vatsimShowPilots  = false;   // Pilots
var vatsimShowCtrl    = true;    // Controllers
var vatsimShowSectors = false;   // FIR Sectors
```

---

## 🗺️ Data Sources

| Source | Purpose |
|--------|---------|
| [VATSIM Data API v3](https://data.vatsim.net/v3/vatsim-data.json) | Live pilots & controllers |
| [VATSpy Data Project](https://github.com/vatsimnetwork/vatspy-data-project) | Airport positions & FIR boundaries |
| [VATSIM Transceivers](https://data.vatsim.net/v3/transceivers-data.json) | Fallback controller positions |
| [OpenWeatherMap](https://openweathermap.org/api/weathermaps) | Weather overlays |
| phpVMS database | Airline logos |

---

## 📋 Requirements

- phpVMS 7 (any recent version)
- HTTPS (required for VATSIM API and weather tiles)
- Optional: Free [OpenWeatherMap API key](https://home.openweathermap.org/users/sign_up)

---

## 🙏 Credits

- **Weather overlay concept:** [Rick Winkelman (Air Berlin Virtual)](https://github.com/ncd200/Weather-Overlay-on-the-Live_Map)
- **VATSIM integration & design:** German Sky Group

---

## 📄 License

MIT — free to use, modify and share. Credit appreciated.
