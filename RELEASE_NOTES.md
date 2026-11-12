## v5.2.2 - Bottom flight board: even columns, larger font, map grows

Release date: 2026-11-12

---

## English

### Changed

* **Flight board below the map, on wide screens.** From about 1000 px board width the columns are spread evenly instead of leaving a lot of space after Flight and Route. Pilot names and routes keep a minimum width. The font is larger (rows 15 px, header 12 px). On narrower screens the board looks exactly like 5.2.1. The board above the map does not change.
* **Map height follows the board.** With only a few flights the map now grows down to the window edge instead of leaving empty space under the board. With many flights the map keeps at least 60 % of the height and the list continues on the page (scroll the page). Your admin minimum and maximum height still apply. The board no longer has its own scroll bar.

### Fixed

* **ETA for low, slow level flight.** ACARS clients keep small aircraft that fly level below 5,000 ft above ground in the phase "Level" instead of Cruise, so they never got an ETA. Level flight below 5,000 ft above ground at up to 180 kt now gets an ETA. Jets levelling off during the climb still do not.

### Update from 5.2.x or 5.1.x (FTP, no SSH)

1. Upload the new `LiveMap/` as `<phpvms>/modules/LiveMap_new/`, rename the old `modules/LiveMap` to `LiveMap_old`, then `LiveMap_new` to `LiveMap`.
2. The theme file `live_map.blade.php` stays as it is.
3. Admin → Maintenance → *Clear all caches*.

Install ZIP attached: `LiveMap-full-package-v5.2.2.zip`.

---

## Deutsch

### Geändert

* **Flugtafel unter der Karte, auf breiten Bildschirmen.** Ab etwa 1000 px Tafelbreite sind die Spalten gleichmäßig verteilt, statt hinter „Flug“ und „Route“ viel Platz zu lassen. Pilotennamen und Routen behalten eine Mindestbreite. Die Schrift ist größer (Zeilen 15 px, Kopf 12 px). Auf schmaleren Bildschirmen sieht die Tafel genau aus wie in 5.2.1. Die Tafel über der Karte ändert sich nicht.
* **Kartenhöhe folgt der Tafel.** Bei wenigen Flügen wächst die Karte jetzt bis zum Fensterrand, statt unter der Tafel leeren Platz zu lassen. Bei vielen Flügen behält die Karte mindestens 60 % der Höhe, die Liste geht auf der Seite weiter (Seite scrollen). Die Mindest- und Höchsthöhe aus dem Admin gelten weiter. Die Tafel hat keinen eigenen Rollbalken mehr.

### Behoben

* **ETA im niedrigen, langsamen Geradeausflug.** ACARS-Clients lassen kleine Flugzeuge, die unter 5000 ft über Grund geradeaus fliegen, in der Phase „Level“ statt Cruise. Deshalb bekamen sie nie eine ETA. Geradeausflug unter 5000 ft über Grund mit höchstens 180 kt bekommt jetzt eine ETA. Jets, die im Steigflug abfangen, weiterhin nicht.

### Aktualisieren von 5.2.x oder 5.1.x (FTP, ohne SSH)

1. Das neue `LiveMap/` als `<phpvms>/modules/LiveMap_new/` hochladen, das alte `modules/LiveMap` in `LiveMap_old` umbenennen, dann `LiveMap_new` in `LiveMap`.
2. Die Theme-Datei `live_map.blade.php` bleibt, wie sie ist.
3. Admin → Maintenance → *Clear all caches*.

Install-ZIP angehängt: `LiveMap-full-package-v5.2.2.zip`.

**Full changelog:** https://github.com/MANFahrer-GF/vatsim-ivao-live-map-phpvms7/compare/v5.2.1...v5.2.2
