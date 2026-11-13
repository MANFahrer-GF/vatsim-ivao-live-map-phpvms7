## v5.2.3 - No empty band below the flight board

Release date: 2026-11-13

---

## English

### Changed

* **No empty band below the flight board.** Some themes (for example SPTheme) put a large padding below the widget, on German Sky Group 93 px up to the footer. LiveMap now measures the next visible box below the widget and reduces the gap to 16 px. Then it measures again: if the content below does not move up (footer held by the theme, longer column next to it), the change is taken back, so no empty scroll space is left.
* **Board on top or no board.** The signature below the map now counts towards the map height. Before, the widget was 27 px too tall and extended past the window edge. With the board below the map the height is calculated as in 5.2.2.

### Update from 5.2.x or 5.1.x (FTP, no SSH)

1. Upload the new `LiveMap/` as `<phpvms>/modules/LiveMap_new/`, rename the old `modules/LiveMap` to `LiveMap_old`, then `LiveMap_new` to `LiveMap`.
2. The theme file `live_map.blade.php` stays as it is.
3. Admin → Maintenance → *Clear all caches*.

Install ZIP attached: `LiveMap-full-package-v5.2.3.zip`.

---

## Deutsch

### Geändert

* **Kein leeres Band mehr unter der Flugtafel.** Manche Themes (zum Beispiel SPTheme) setzen unter das Widget ein großes Polster, bei German Sky Group 93 px bis zur Fußzeile. LiveMap misst jetzt den nächsten sichtbaren Kasten unter dem Widget und verkleinert den Abstand auf 16 px. Danach misst es nach: Rückt der Inhalt darunter nicht nach (Fußzeile vom Theme festgehalten, längere Spalte daneben), wird die Änderung zurückgenommen, damit kein leerer Scrollraum bleibt.
* **Tafel oben oder ohne Tafel.** Die Signatur unter der Karte zählt jetzt bei der Kartenhöhe mit. Vorher war das Widget um 27 px zu hoch und ragte über den Fensterrand. Mit der Tafel unter der Karte wird die Höhe gerechnet wie in 5.2.2.

### Aktualisieren von 5.2.x oder 5.1.x (FTP, ohne SSH)

1. Das neue `LiveMap/` als `<phpvms>/modules/LiveMap_new/` hochladen, das alte `modules/LiveMap` in `LiveMap_old` umbenennen, dann `LiveMap_new` in `LiveMap`.
2. Die Theme-Datei `live_map.blade.php` bleibt, wie sie ist.
3. Admin → Maintenance → *Clear all caches*.

Installations-ZIP im Anhang: `LiveMap-full-package-v5.2.3.zip`.
