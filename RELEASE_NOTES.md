## v5.3.0 - Shared Cockpit on the map

Release date: 2026-11-13

---

## English

### Added

* **Shared Cockpit on the map.** When two pilots fly one flight together with the SharedCockpit module, the map shows one aircraft instead of two. The host's flight shows "Shared Cockpit · with <name> · Host PF" (or PM) behind the pilot name in the flight board and as "Crew" in the flight detail. In the board the text is cut at the column edge, the full text appears when you point at it. This works with the data sources phpVMS, live and both.
* **Without the SharedCockpit module nothing changes.** If the module is not installed or no crew is flying, the map looks exactly like 5.2.3.

### Update from 5.2.x or 5.1.x (FTP, no SSH)

1. Upload the new `LiveMap/` as `<phpvms>/modules/LiveMap_new/`, rename the old `modules/LiveMap` to `LiveMap_old`, then `LiveMap_new` to `LiveMap`.
2. The theme file `live_map.blade.php` stays as it is.
3. Admin → Maintenance → *Clear all caches*.

Install ZIP attached: `LiveMap-full-package-v5.3.0.zip`.

---

## Deutsch

### Neu

* **Shared Cockpit auf der Karte.** Fliegen zwei Piloten mit dem Modul SharedCockpit einen Flug gemeinsam, zeigt die Karte ein Flugzeug statt zwei. Am Flug des Gastgebers steht in der Flugtafel hinter dem Pilotennamen und im Flugdetail unter „Crew“: „Shared Cockpit · mit <Name> · Gastgeber PF“ (oder PM). In der Tafel wird der Text am Spaltenrand abgeschnitten, der volle Text erscheint, wenn man mit der Maus darauf zeigt. Das gilt für die Datenquellen phpVMS, live und beide.
* **Ohne das Modul SharedCockpit ändert sich nichts.** Ist das Modul nicht installiert oder fliegt gerade keine Crew, sieht die Karte genau aus wie in 5.2.3.

### Aktualisieren von 5.2.x oder 5.1.x (FTP, ohne SSH)

1. Das neue `LiveMap/` als `<phpvms>/modules/LiveMap_new/` hochladen, das alte `modules/LiveMap` in `LiveMap_old` umbenennen, dann `LiveMap_new` in `LiveMap`.
2. Die Theme-Datei `live_map.blade.php` bleibt, wie sie ist.
3. Admin → Maintenance → *Clear all caches*.

Installations-ZIP im Anhang: `LiveMap-full-package-v5.3.0.zip`.
