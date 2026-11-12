## v5.2.0 - Aircraft type and ETA in the flight board

Release date: 2026-11-12

---

## English

### New

* **Two new columns in the flight board: Type and ETA.** Type shows the aircraft's ICAO code (A320, B77W, AT76 …). ETA is the estimated arrival time: now plus the distance to go divided by the ground speed. It appears from 50 kt (take-off roll) and is shown in UTC, e.g. `17:30z`, like the schedule times in phpVMS. The distance is measured as a great circle, so it is an estimate. Flights that went offline show no ETA. Both columns can be sorted. On narrow boards Type is dropped first, very narrow boards drop ETA too. Thanks to Elisa for the idea.

### Update from 5.1.x (FTP, no SSH)

1. Upload the new `LiveMap/` as `<phpvms>/modules/LiveMap_new/`, rename the old `modules/LiveMap` to `LiveMap_old`, then `LiveMap_new` to `LiveMap`.
2. The theme file `live_map.blade.php` stays as it is.
3. Admin → Maintenance → *Clear all caches*.

Install ZIP attached: `LiveMap-full-package-v5.2.0.zip`.

---

## Deutsch

### Neu

* **Zwei neue Spalten in der Flugtafel: Typ und ETA.** Typ zeigt den ICAO-Code des Flugzeugs (A320, B77W, AT76 …). ETA ist die geschätzte Ankunftszeit: jetzt plus Reststrecke geteilt durch die Geschwindigkeit über Grund. Sie erscheint ab 50 kt (Startlauf) und steht in UTC, z. B. `17:30z`, wie die Flugplanzeiten in phpVMS. Die Reststrecke wird als Großkreis gerechnet, die ETA ist also eine Schätzung. Flüge, die offline gegangen sind, zeigen keine ETA. Beide Spalten lassen sich sortieren. Auf schmalen Tafeln fällt zuerst „Typ“ weg, ganz schmal auch „ETA“. Danke an Elisa für die Idee.

### Aktualisieren von 5.1.x (FTP, ohne SSH)

1. Das neue `LiveMap/` als `<phpvms>/modules/LiveMap_new/` hochladen, das alte `modules/LiveMap` in `LiveMap_old` umbenennen, dann `LiveMap_new` in `LiveMap`.
2. Die Theme-Datei `live_map.blade.php` bleibt, wie sie ist.
3. Admin → Maintenance → *Clear all caches*.

Install-ZIP angehängt: `LiveMap-full-package-v5.2.0.zip`.

**Full changelog:** https://github.com/MANFahrer-GF/vatsim-ivao-live-map-phpvms7/compare/v5.1.1...v5.2.0
