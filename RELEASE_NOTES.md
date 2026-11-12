## v5.2.1 - Flight board no longer cut off, ETA from cruise

Release date: 2026-11-12

---

## English

### Fixed

* **Flight board cut off at some window widths.** The two new columns of 5.2.0 made the board wider than its space, and the Pilot or Phase column was cut off on the right. The board now drops columns in time: first Type, then ETA. Below that it shows exactly the columns 5.1.1 showed.
* **ETA from cruise onwards.** During the take-off roll and climb the ground speed is still low, so the ETA was hours too late. It now appears in cruise, holding, descent, approach, final and landing. If an ACARS client reports an unknown phase, level flight at cruise speed above 10,000 ft counts as cruise.
* **No ETA for silent flights.** If a flight has not reported for 10 minutes (for example the simulator crashed and the PIREP is still open), the ETA stays empty. With the source phpVMS, "last seen" now comes from the last position instead of the PIREP.

### Update from 5.2.0 or 5.1.x (FTP, no SSH)

1. Upload the new `LiveMap/` as `<phpvms>/modules/LiveMap_new/`, rename the old `modules/LiveMap` to `LiveMap_old`, then `LiveMap_new` to `LiveMap`.
2. The theme file `live_map.blade.php` stays as it is.
3. Admin → Maintenance → *Clear all caches*.

Install ZIP attached: `LiveMap-full-package-v5.2.1.zip`.

---

## Deutsch

### Behoben

* **Flugtafel in manchen Fensterbreiten abgeschnitten.** Die zwei neuen Spalten aus 5.2.0 machten die Tafel breiter als ihren Platz, „Pilot“ oder „Phase“ wurden rechts abgeschnitten. Die Tafel gibt jetzt rechtzeitig Spalten ab: zuerst „Typ“, dann „ETA“. Darunter zeigt sie genau die Spalten von 5.1.1.
* **ETA erst ab Reiseflug.** Im Startlauf und Steigflug ist die Geschwindigkeit über Grund noch klein, die ETA lag Stunden zu spät. Sie erscheint jetzt im Reiseflug, in der Warteschleife, im Sinkflug, Anflug, Endanflug und bei der Landung. Meldet ein ACARS-Client eine unbekannte Phase, zählt Geradeausflug mit Reisegeschwindigkeit über 10 000 ft als Reiseflug.
* **Keine ETA für stumme Flüge.** Hat sich ein Flug seit 10 Minuten nicht gemeldet (etwa weil der Simulator abgestürzt ist und der PIREP noch offen ist), bleibt die ETA leer. Bei Quelle phpVMS kommt „zuletzt gesehen“ jetzt aus der letzten Position statt aus dem PIREP.

### Aktualisieren von 5.2.0 oder 5.1.x (FTP, ohne SSH)

1. Das neue `LiveMap/` als `<phpvms>/modules/LiveMap_new/` hochladen, das alte `modules/LiveMap` in `LiveMap_old` umbenennen, dann `LiveMap_new` in `LiveMap`.
2. Die Theme-Datei `live_map.blade.php` bleibt, wie sie ist.
3. Admin → Maintenance → *Clear all caches*.

Install-ZIP angehängt: `LiveMap-full-package-v5.2.1.zip`.

**Full changelog:** https://github.com/MANFahrer-GF/vatsim-ivao-live-map-phpvms7/compare/v5.2.0...v5.2.1
