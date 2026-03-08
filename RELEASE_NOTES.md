# v4.5.0 — FIR Sector Matching Fix + UIR Support

Fixes FIR sector polygon rendering for Russia, CIS, Central Asia, Caucasus and any region using sub-sector callsigns or UIR (Upper Information Region) identifiers.

## What was broken

FIR sector polygons were missing for large parts of the world — most visibly in Russia and neighbouring countries. Three separate bugs in `renderActiveSectors` caused this:

1. **Sub-sector callsigns dropped** — `UNKL_N_CTR` never ran the broad GeoJSON search, only a narrow exact-match that usually failed
2. **CTR/FSS without position skipped** — controllers like `RU-SC_FSS` with no airport entry were silently discarded before reaching the matching logic
3. **UIRs not supported** — composite airspace regions (RU-SC, RU-EC, RU-NW, etc.) defined in VATSpy.dat `[UIRs]` section were never parsed

## What's fixed

### 4-Phase Matching Cascade

| Phase | Method | Example |
|-------|--------|---------|
| 1 | Exact sub-key match | `UNKL_N` → GeoJSON `UNKL-N` |
| 2 | Broad normalised search | `UNKL` → scans all GeoJSON features |
| 3 | startsWith fallback | `UNKL` → matches `UNKL-1`, `UNKL-2` |
| 4 | **UIR expansion** | `RU-SC` → resolves to URRV, UGGG, UDDD, UBBA |

### UIR Support

The `[UIRs]` section from VATSpy.dat is now parsed. UIR callsigns are automatically resolved to their constituent FIR polygons and drawn as a combined sector group with Upper Airspace styling.

### Position-less Controllers

CTR and FSS controllers without a transceiver position are now kept in the pipeline — the FIR polygon doesn't need the controller's coordinates.

## Affected Regions

Russia (all U-prefix FIRs), Russian UIRs (RU-SC, RU-EC, RU-NW, RU-WS, etc.), Central Asia (UACC, UAAA, UATT, UTAA, UTDD, UZTT), Caucasus (UBBB, UGTB, UGEE, UDDD), and any FIR/UIR worldwide with hyphenated sub-sector GeoJSON IDs.

## Installation

Copy `live_map.blade.php` to your theme's widgets folder, replacing the previous version:

| Theme | Path |
|-------|------|
| SPTheme | `resources/views/layouts/SPTheme/widgets/live_map.blade.php` |
| Disposable_v3 | `resources/views/layouts/Disposable_v3/widgets/live_map.blade.php` |

Clear browser cache after updating.
