# README.md — Changes for v4.5.0

## Section: Features → Live Network Data

Replace the existing FIR Sectors bullet with:

```markdown
- **FIR Sectors** — active airspace boundaries as clickable coloured polygons with controller info (both networks)
- **UIR Support** *(new in v4.5.0)* — composite Upper Information Regions (e.g. RU-SC Caucasus Radar) automatically resolved to their constituent FIR polygons from VATSpy data
- **4-phase sector matching** *(new in v4.5.0)* — sub-sector callsigns (UNKL_N_CTR, UUWV_E_CTR) now correctly matched to GeoJSON boundaries worldwide
```

## Section: Understanding Map Markers → FIR Sectors

Replace with:

```markdown
### FIR Sectors

Active FIR/UIR sectors appear as semi-transparent coloured polygons. A label in the centre shows the controller callsign and frequency.

- Teal tones → Center controllers (CTR)
- Purple tones → Upper Airspace / UIR

**UIR sectors** (e.g. `RU-SC_FSS` Caucasus Radar) are composite regions covering multiple FIRs. The map automatically resolves them to the individual FIR polygons using VATSpy's `[UIRs]` data and draws them as a single group.
```

## Section: The Interface → 🗺️ FIR Sectors

Replace with:

```markdown
#### 🗺️ FIR Sectors

Toggles active airspace boundaries as coloured dashed polygons. Only sectors with an active controller are shown. Clicking a sector opens a popup with controller details.

UIR (Upper Information Region) callsigns like `RU-SC_FSS` or `RU-NW_FSS` are automatically expanded to their constituent FIR boundaries and displayed as a combined sector group.
```
