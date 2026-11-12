# Changelog

All notable changes to this project are documented in this file.

---

## [5.2.0] — 2026-11-12

### Added / Neu
- **Spalten „Typ“ und „ETA“ in der Flugtafel** (Wunsch von Elisa): Typ aus dem ICAO-Code des
  Flugzeugs, ETA = jetzt + Reststrecke / Geschwindigkeit über Grund, ab 50 kt (Startlauf),
  in UTC („17:30z“). Großkreis-Rest, also eine Schätzung; offline leer. Beide Spalten
  sortierbar; schmale Tafel gibt zuerst „Typ“ ab, ganz schmal auch „ETA“.
  *New flight board columns Type and ETA (now + distance to go / ground speed, from 50 kt,
  UTC). Both sortable; narrow boards drop Type first, very narrow ones ETA too.*

### Upgrade
Nur `modules/LiveMap` tauschen (FTP, siehe INSTALL.txt), die Theme-Datei bleibt.
*Only replace `modules/LiveMap`; the theme file stays.*

---

## [5.1.1] — 2026-11-05

### Fixed / Behoben
- **Einstellungen wurden nicht übernommen** (gemeldet von Disposable Hero im phpVMS-Discord): Auf
  Seiten mit phpVMS im Produktionsmodus und echtem Cache (file, redis …) kam ein
  abgewähltes Häkchen nach dem Speichern zurück, ein neuer OpenWeatherMap-Schlüssel
  schien zu fehlen und die Karte zeigte kein Wetter – bis zu 24 h oder bis zum Leeren der
  Caches. Ursache: phpVMS cacht `setting($key)` unter `settings.<key wie übergeben>`
  (`settings.acars.livemap_…`), das Modul löschte nur `settings.acars_livemap_…`.
  Speichern löscht jetzt beide Schlüssel; Test in `tests/live_flights_probe.php` über den
  echten `setting()`-Helfer.
  *Settings saved under Admin → Live Map did not show up on sites running phpVMS in
  production with a real cache: the module cleared the wrong cache key. Saving now clears
  the key phpVMS really uses.*

### Upgrade
Nur `modules/LiveMap` tauschen (FTP, siehe INSTALL.txt), die Theme-Datei bleibt.
Bereits hängende Werte: Admin → Live Map einmal öffnen und speichern.
*Only replace `modules/LiveMap`; the theme file stays. Values already stuck: open
Admin → Live Map once and click Save Settings.*

---

## [5.1.0] — 2026-11-04

Optik wieder kompakt wie 4.7.9, Technik von 5.0; läuft unter Seven, SPTheme und Disposable_v3.
*Compact look of 4.7.9 again, on the 5.0 technology; works under Seven, SPTheme and Disposable_v3.*

### Changed / Geändert
- **Kompakte Optik wie 4.7.9, moderner gestaltet.** Flugtafel mit „Aktiv | Geplant“
  (Reiter, oben aufklappbar, Spalten Flug mit Logo / Route / Höhe / GS / Rest mit
  Fortschrittsbalken / Phase / Pilot; schmale Fenster geben Spalten ab), Detailkasten wie der
  4.7.9-Crew-Pass (Route mit Flughafennamen, Pilot, Flugzeug, Höhe, GS, Kurs,
  V/S, Fortschrittsbalken, „Mehr Werte“, PIREP-Link, Folgen). Felder ohne
  Daten werden weggelassen. Die Technik von 5.0 (Daten über das Modul) bleibt.
  *Compact flight board and detail box as in 4.7.9, 5.0 data path kept.*
- **Neugestaltung (Thomas 03.11.2026: „altbacken“, Liste unten „blöd
  abgeschnitten und blöd sortiert“):** flache Flächen mit Haarlinie statt
  Verlaufsbalken, Werkzeuge als eine Knopfgruppe, getönte Phasen-Pillen,
  Detailkopf mit Phasen-Streifen; die Verwaltungsfarbe „Kopf der Flugliste“ ist
  die Akzentfarbe (hell). Schriftgrößen und Zeilenhöhen wie 4.7.9. Lage unten
  ohne Aufklapp-Kopf (die Reiterzeile ist der Kopf), keine Höhenbegrenzung.
  Spaltenköpfe sortieren (Standard Phase in Flugreihenfolge, darin Rest-Strecke;
  Offline unten; fehlende Werte hinten; Rufzeichen natürlich, GSG7 vor GSG101),
  Wahl in `localStorage` (`livemap_sort`). Ohne Logo: Airline-Buchstaben;
  fehlt das Logo im Flug, das der phpVMS-Airline (`AIRLINE_LOGOS`). Grüner Punkt an
  jedem eingeschalteten Schalter (Netz, Piloten, Lotsen, FIR-Sektoren, Wetter); bei
  VATSIM/IVAO grau = lädt, rot = Fehler. Popups dunkel lesbar.
  *Redesign: flat surfaces, grouped tools, tinted phases, admin header colour as
  accent; bottom list without a fold-out header and without height limit;
  sortable columns (default phase in flight order, then distance to go), stored
  in the browser; airline letters / airline logo fallback; green dot on every switch that is on
  (grey loading / red error on VATSIM/IVAO); dark popups readable.*
- **Datenquelle „Beide“** (neben phpVMS und AeroACARS Live): AeroACARS- und
  phpVMS-Flug mit demselben Piloten (phpVMS-Konto `users.id`) und demselben
  Rufzeichen sind ein Flug (Registrierung, PIREP-Link und Logo kommen vom PIREP). Fällt eine Quelle aus,
  zeigt die Karte die andere mit einem Hinweis.
  *Source "Both": same pilot + same callsign = one flight; if one source fails,
  the other is shown with a notice.*
- **Flugzeugsymbole nach Klasse:** sieben Silhouetten (Kolben, Turboprop,
  Bizjet, Schmalrumpf, Großraum, Vierstrahler, Hubschrauber), die Nase zeigt in
  Flugrichtung, am Boden etwas kleiner, Farbe = Flugphase. Unbekannter Typ =
  Schmalrumpf. Netz-Flieger (VATSIM/IVAO) bleiben graue Schmalrümpfe.
  *Seven aircraft silhouettes by class, nose in flight direction, smaller on the ground.*
- **Theme-unabhängig durch eigenes Leaflet 1.9.4** (gepinnt, mit
  Subresource-Integrity), das die Leaflet-Version des Themes nicht berührt.
  *Own pinned Leaflet 1.9.4 with SRI, independent of the theme's Leaflet.*
- **Ansichten liegen im Modul** (`livemap::widget`). Die Theme-Datei
  `live_map.blade.php` besteht nur noch aus einem `@include`; ein Update
  betrifft künftig nur `modules/LiveMap`. `live_map_styles.blade.php` und
  `live_map_scripts.blade.php` entfallen.
  *Views now ship inside the module; the theme file is a single `@include`.*
- **Kartenelemente entwirrt:** VA-Flieger liegen vor Lotsenkasten, vor
  Rufzeichen, vor Netz-Fliegern; gestapelte VA-Flüge werden zu einem Symbol mit
  „+n“; VATSIM- und IVAO-Kasten desselben Flughafens stehen nebeneinander. Der
  IVAO-Kasten hat keinen orangen Rahmen mehr, sondern den grauen Zusatz „IVAO“.
  *Map elements untangled (stacking order, "+n" for stacked flights, VATSIM/IVAO
  boxes side by side, IVAO without orange frame).*
- **Zoomen beendet „Folgen“** (Mausrad, Knöpfe, Doppelklick, Tasten, „+n“).
  *Any zoom ends follow mode.*
- **Raster ohne Überlappung** der Bedienelemente bei allen Fenstergrößen;
  schmaler Modus unter 760 px Widget-Breite (eine Spalte, nur Kasten, Tafel
  oder Detail ist offen).
  *Layout grid: no overlapping controls at any window size; narrow mode below 760 px.*
- **Dunkelmodus** erkennt `data-bs-theme` (Seven, SPTheme) und `data-theme`
  (Disposable_v3) und folgt Theme-Wechseln zur Laufzeit; neue Einstellung
  Hell/Dunkel/Auto.
  *Dark mode detects both theme attributes; new light/dark/auto setting.*
- **Höhe aus der Fensterhöhe** mit Mindest- und Höchsthöhe (Verwaltung,
  Standard 420/1000 px) statt fester Werte.
  *Map height follows the window within a configurable minimum/maximum.*
- **Ohne CARTO-Schlüssel ist die Standardkarte OpenStreetMap**; die
  CARTO-Stile erscheinen dann nicht im Umschalter (Hinweis im Kasten „Kartenstil“).
  *Without a CARTO key the default map is OpenStreetMap.*

- **Lage der Flugtafel statt „Layout-Modus“** (Admin → Live Map, Schlüssel
  `acars.livemap_flights_panel`): *oben* (aufklappbar über der Karte, wie 4.7.9),
  *unten* (feste Tabelle unter der Karte, ganze Breite, außerhalb des
  Kartenbereichs) oder *aus*. Die alten Schlüssel `acars.livemap_old_style` und
  `acars.livemap_show_top_flights_panel` werden weiter mitgeschrieben. Fehlt die
  neue Einstellung (Update von 4.7.x), wird die Lage aus den alten Schlüsseln
  abgeleitet: ist `old_style` an oder `show_top_flights_panel` ausdrücklich aus,
  gilt *aus*, sonst *oben*. **Neue Installation (keiner der Schlüssel gesetzt): *unten*,
  der empfohlene Standard** (Thomas 04.11.2026). Neue Teildatei `partials/tafel.blade.php`.
  *Flight panel position replaces "Layout mode": top (collapsible over the map),
  bottom (fixed full-width table below the map, outside the map area) or off.
  The old keys are still written; without the new setting (update from 4.7.x)
  the position is derived from them: old_style on, or show_top_flights_panel
  explicitly off = off, otherwise top. **A new install (none of the keys set) starts at
  bottom, the recommended default.** New partial `partials/tafel.blade.php`.*
- **Gestaltung:** Reicht die Höhe der rechten Spalte nicht für Kasten und
  Detail, klappt der Detailkasten auf seine Kopfzeile ein (Klick auf das
  Rufzeichen ▾ klappt ihn auf, der Kasten daneben geht zu); ein neuer Flug
  schließt einen Kasten, der nicht mehr daneben passt. Die schmale Tafel zeigt
  keine Logos, damit das Rufzeichen lesbar bleibt. Der Tafelkopf zeigt
  Live-Punkt und Zahlen.
  *Detail box collapses to its header row when a box beside it does not fit
  (callsign ▾ expands it); narrow board without logos; board header with live dot and counts.*
- **Kartenelemente unter den Bedienelementen:** Lotsenkästen und
  Sektor-Etiketten liegen unter Tafel, Zoom und Werkzeugen, wie in 4.7.9.
  VA-Flieger liegen über allen Lotsen-Symbolen (gemessen: GSG101 lag unter
  LFJL, ein Klick öffnete das Lotsen-Popup statt der Flugkarte).
  *VA aircraft above all controller symbols (a controller symbol caught the click).*
  *Map elements (controller boxes, sector labels) sit below the controls (board, zoom, tools), as in 4.7.9.*
- **VATSIM/IVAO — Sprechfunk-Riegel 117–137 MHz:** Ein ATIS außerhalb dieses
  Bereichs und ein Beobachter ohne Sprechfrequenz zählen nicht. Ein Beobachter
  auf einer Sprechfrequenz an einem echten Platz erscheint als Kasten mit
  grauem „O“.
  *Voice-frequency gate 117–137 MHz: ATIS outside it and observers without a voice frequency do not count; an observer on a voice frequency at a real position shows as a box with a grey "O".*
- **VATSIM/IVAO — Lotsen nach aufgelöster Stelle gruppiert:** CLT_GND und
  KCLT_ATIS sind ein Kasten „KCLT“. Beim Auflösen der VATSpy-Plätze steht der
  Funk-Präfix vor dem ICAO-Code, ein echter Platz vor einem Behelf.
  *Controllers grouped by resolved facility (CLT_GND + KCLT_ATIS = one KCLT box).*
- **VATSIM/IVAO — FIR-Zuordnung nach dem Verfahren von VATSIM Radar:**
  längster Präfix, Teilsektoren, Sammelpositionen aus `[UIRs]` (die längste
  passende), `_FSS` = ozeanische Fläche. Die alte Grobzuordnung bleibt als
  Rückfall. IVAO läuft über denselben Weg (Teilsektoren getrennt, Plätze nach
  aufgelöster Stelle gruppiert) und lädt VATSpy und Grenzen jetzt selbst
  (Sektoren erschienen vorher nur mit VATSIM).
  *FIR assignment as VATSIM Radar does it: longest prefix, sub-sectors, collective positions from [UIRs] (longest match), _FSS = oceanic area; old coarse matching kept as fallback; IVAO uses the same path and loads VATSpy and boundaries itself (sectors used to need VATSIM).*
- **VATSIM/IVAO — Robustheit:** Abrufe mit Zeitgrenze (20 s, VATSpy und
  Grenzen 60 s, jeweils bis die Antwort ganz gelesen ist), Sektoren werden
  erst nach geladenen Grenzen geleert, VATSpy und Grenzen werden nach einem
  Fehlschlag nach 5 Minuten neu versucht. Die Quellen kommen über jsDelivr
  (Browser-Puffer 7 Tage statt 5 min). Sektor-Etiketten werden gerückt und,
  wenn kein Platz bleibt, ausgeblendet, statt sich zu überlappen.
  *Fetches with a timeout covering the whole response (20 s; VATSpy/boundaries 60 s), sectors cleared only after boundaries have loaded, VATSpy/boundaries retried 5 minutes after a failure; sources via jsDelivr (7-day browser cache instead of 5 min); sector labels are nudged or hidden instead of overlapping.*

### Not adopted / Bewusst nicht übernommen
- **TRACON-Flächen aus dem SimAware-TRACON-Project.** Gemessen am 03.11.2026:
  Das Release-Asset (1,77 MB) liefert GitHub ohne CORS-Freigabe, der Browser
  kann es also nicht laden; jsDelivr hat nur 1389 Einzeldateien (4,4 MB) ohne
  Präfix-Index; es bliebe nur ein Abruf über den Server jeder VA. Daher bleibt
  der grüne TRACON-Schild.
  *TRACON areas from the SimAware TRACON Project are not adopted. Measured 03.11.2026: the release asset (1.77 MB) is served by GitHub without a CORS header, so browsers cannot load it; jsDelivr only has 1389 single files (4.4 MB) without a prefix index; the only way left would be a fetch through every VA's server. The green TRACON shield stays.*
- **VATGlasses-Höhensektoren:** 28 MB, Lizenz CC BY-NC-SA, Fremddienst.
  *VATGlasses altitude sectors: 28 MB, licence CC BY-NC-SA, third-party service.*

### Added / Neu — Einstellungen (Admin → Live Map)
- Lage der Flugtafel (oben / unten / aus), Datenquelle „Beide“, VA-Kennung (nur AeroACARS-Flüge dieser VA zeigen, z. B.
  `gsg`; leer = alle), Hell/Dunkel/Auto, Mindest- und Höchsthöhe der Karte,
  Rufzeichen neben den Flugzeugen ab Zoom 6 (an/aus).
  *New settings: flight panel position (top / bottom / off), source "Both", VA prefix, light/dark/auto, min/max height, callsign labels.*

### Changed / Geändert — Verwaltungsseite (Admin → Live Map)
- **Neu geordnet und erklärt, nur Englisch** (öffentliches Modul): Statuszeile oben
  (Flüge, Kartengrundlage, Wetter, ACARS-Live-Zeit), dann Flugdaten, Flugliste, Karte,
  Kartengrundlage, Wetter, VATSIM/IVAO, Handys. Jede Einstellung hat einen kurzen Hilfetext,
  der sagt, was sie auf der Karte wirklich tut; die AeroACARS-Felder erscheinen nur bei
  Quelle AeroACARS oder Beide. Speichern-Leiste bleibt beim Scrollen sichtbar.
  *Reordered and explained, English only: status row, seven sections, a short help text per
  setting; AeroACARS fields only for that source; sticky save bar.*
- **Farbe „Accent UI Color“ entfernt:** sie wirkte seit der Neugestaltung nirgends mehr
  (`--lm-accent` ohne Verwender). „Primary UI Color“ heißt jetzt **Accent colour**;
  sie und **Panel background** gelten nur im hellen Modus (dunkel feste Farben).
  *Removed the unused "Accent UI Color"; "Primary UI Color" is now "Accent colour"; both
  colours apply in light mode only.*
- „Show mobile Flights button“ heißt **Show the flights list** (es gibt keinen Knopf; aus
  blendet die Liste auf schmalen Bildschirmen aus). Hinweise: „Open the flights list at start“
  wirkt nur bei Lage oben; von Netzwerk/Wetter öffnet beim Start nur eines.
  *"Show mobile Flights button" is now "Show the flights list"; notes on what the mobile
  start options really do.*

### Fixed / Behoben — Verwaltungsseite
- **Speichern ohne OpenWeatherMap-Schlüssel ging nicht**, solange der Wetter-Proxy an war
  (Standard) — eine VA ohne Wetter konnte keine Einstellung ändern. Jetzt wird gespeichert,
  die Karte zeigt dann einfach kein Wetter. Der Wetterstatus ist ohne Schlüssel „Off“
  statt eines roten Alarms.
  *Saving without an OpenWeatherMap key failed while the weather proxy was on (default).
  It now saves and the map simply has no weather; the status shows "Off" instead of a red alert.*
- Die Ablehnungsmeldung bei falschem CARTO-Schlüssel war deutsch, jetzt englisch.
  *The CARTO key rejection message is now in English.*

### Added / Neu — Farben (Admin → Live Map → Map)
- **Akzentfarbe hell und dunkel einstellbar** (Thomas 04.11.2026: „Was ist, wenn man eine
  grüne Seite hat?“). Neues Feld *Accent colour (dark mode)* (Standard `#7CB8F0` wie bisher).
  Die Tönung von überfahrenen und gewählten Zeilen kommt jetzt aus dem Akzent statt aus festem
  Blau (hell: kräftige Fassung des Akzents, damit Marineblau nicht grau tönt, GSG bleibt
  gemessen wie vorher); die Schrift auf dem Akzent wird nach WCAG-Kontrast weiß oder dunkel.
  Fest bleiben bewusst die Datenfarben: Phasen (wie die Webapp), Lotsenrollen, Route.
  *Accent colour for light and dark mode; row tints and text on the accent follow it, so a
  green site gets a green map. Phase, ATC and route colours stay as data colours.*
- **AeroACARS Live / Beide als „German Sky Group only“ gekennzeichnet**: der Lese-Schlüssel
  kommt nur von GSG, fremde VAs nutzen phpVMS.
  *AeroACARS Live and Both are marked "German Sky Group only".*
- **Dezente Signatur unter der Karte** für alle Besucher: „Crafted with ♥ in Germany by
  Thomas Kant“ (ohne Spendenlink; der bleibt in der Verwaltung).
  *A small credit line below the map, without the donation link.*

### Changed / Geändert — Zugang zur Verwaltung
- **Admin → Live Map für alle VA-Admins:** öffnet jetzt für jeden mit Zugang zum
  Admin-Bereich (`ability:admin,admin-access`, wie der phpVMS-Kern), vorher nur Rolle `admin`.
  *Admin → Live Map now opens for every user with admin panel access, not only the role "admin".*

### Upgrade (FTP + phpVMS-Admin, ohne SSH)
1. `LiveMap/` per FTP als `modules/LiveMap_new/` hochladen, dann umbenennen
   (Schritte in INSTALL.txt). Erstinstallation: Admin → Modules → *Activate Module*.
2. Die Theme-Datei `live_map.blade.php` in den Ordner `widgets/` **jedes benutzten
   Themes** hochladen (`resources/views/layouts/<Theme>/widgets/`, Pfade für Seven,
   SPTheme, Disposable_v3 und beta in INSTALL.txt).
3. Die alten Dateien `live_map_scripts.blade.php` und `live_map_styles.blade.php`
   im selben Ordner löschen (werden nicht mehr benutzt).
4. Admin → Maintenance → *Clear all caches*.

*Upgrade (FTP and phpVMS admin, no SSH): upload `LiveMap/` as `modules/LiveMap_new/`
and rename it (see INSTALL.txt); upload `live_map.blade.php` into the `widgets/`
folder of every theme in use; delete the old `live_map_scripts.blade.php` and
`live_map_styles.blade.php` there; Admin → Maintenance → "Clear all caches".*

---

## [5.0.0] — 2026-10-30

AeroACARS 3.0, Pflichtenheft P-11.11 (A-75, N-12, F-020).

### Changed / Geändert
- **Datenquelle der Flüge ist eine Einstellung.** *phpVMS* (Standard, laufende
  PIREPs dieser Seite) oder *AeroACARS Live* (dieselben Flüge, Felder und
  Phasen wie die Admin-Webapp auf live.kant.ovh). Abgerufen wird immer über
  das Modul (`/livemap/api/fluege`, Puffer 8 s); der Lese-Schlüssel für
  AeroACARS Live bleibt auf dem Server. Nur eine feste Feldliste verlässt den
  Server, Pilotennamen gekürzt („Kenji M.").
  *Flight data source is now a setting (phpVMS or AeroACARS Live), always
  fetched server-side; the read key never reaches the browser.*
- **Eigene Karte statt `render_live_map` des phpVMS-Kerns.** Keine doppelten
  VA-Flieger, keine zweite Grundebene ohne CARTO-Schlüssel mehr.
  *Own Leaflet map instead of the core helper.*
- **Neue Gestaltung nach den GSG-Markenregeln** (Navy/Rot/Gold, hell und
  dunkel nach SPTheme): Flugliste links mit den Feldern der Flugtafel,
  Flugdetail rechts mit den Feldern des Webapp-Flugdetails, Werkzeuge oben
  rechts (Netzwerk, Wetter, Karte, Folgen). Unter 760 px Widget-Breite
  Liste und Detail als Blatt von unten.
  *Redesign following the GSG brand rules, responsive by widget width.*
- Die Farben „Mobile Flights Button" (aktiv/inaktiv) wirken nicht mehr: die
  Werkzeugleiste nutzt Primär-/Akzentfarbe. Die Höhe aus dem Widget-Aufruf
  wird nicht mehr benutzt (Karte füllt das Fenster).
  *Mobile button colours and the widget height argument are no longer used.*
- Phasen, Farben, Namen, Reihenfolge und „Level"-Anzeige wie die Webapp
  (Test vergleicht mit einer Abschrift von `phaseColors.ts`).

### Security / Sicherheit
- Der Lese-Schlüssel geht nur an `https://live.kant.ovh` (außerhalb der
  Produktion zusätzlich an einen Prüfserver auf 127.0.0.1/localhost); keine
  Weiterleitungen, Puffer an Adresse und Schlüssel gebunden.
  *The read key is only ever sent to live.kant.ovh; no redirects.*

### Fixed / Behoben
- Kurze Störungen der Quelle lassen den letzten guten Stand stehen
  (als veraltet markiert, mit „Erneut versuchen"); falscher Schlüssel leert.
- Kartenhöhe folgt dem Fenster (440–1400 px) statt fest 800 px.
- Unter Zoom 3 verschwand die ganze Marker-Ebene (Flughafen-Lotsen-Logik
  blendete das Elternelement aus — das war die Ebene selbst).
  *Below zoom 3 the whole marker pane was hidden.*
- Spur und Marker eines Flugs über 180° liegen immer in der Weltkopie, in der
  die Karte gerade steht.

---

## [4.7.9] — 2026-09-25

### Fixed
- **VATSIM-/IVAO-Sektoren an der Datumsgrenze waren zerschnitten.** Die
  VATSpy-Grenzen teilen Pazifik-FIRs (Anchorage, Oakland Oceanic, Magadan,
  Nadi, Auckland …) bei genau ±180° in zwei Stücke. Leaflet zeigte davon
  zwei Fehler: Der Rand zog an 180° eine senkrechte Naht mitten durch den
  Sektor, und beim Verschieben der Karte über die Datumsgrenze fehlte die
  andere Hälfte — Leaflet zeichnet Flächen nur in *einer* Weltkopie.
  Sektoren, die ±180° berühren, werden jetzt als Fläche ohne Rand plus
  eigenem Umriss ohne Schnittkante gezeichnet, zusätzlich um ±360°
  verschoben, samt Beschriftung. Alle anderen Sektoren werden unverändert
  gezeichnet.

---

## [4.7.8] — 2026-09-25

### Fixed
- **Die rote Ziel-Linie lief an der Datumsgrenze quer über die ganze Karte.**
  Ein Flug über den Pazifik (z. B. über die Aleuten, 179° O → 179° W) bekam
  statt einer kurzen Linie zum Ziel eine Gerade einmal um die Welt. Leaflet
  verbindet zwei Punkte auf dem kurzen Weg in *Längengraden* — von 179° O nach
  179° W sind das 358°. Das Ziel wird jetzt um volle 360° neben das Flugzeug
  gelegt (181° statt −179°); Leaflet zeichnet Längen außerhalb ±180 in der
  Nachbarkopie der Welt weiter. Die geflogene Spur des Kerns (`L.Geodesic`)
  war nicht betroffen.

---

## [4.7.7] — 2026-09-01

### Fixed
- **Ein Flug stand bei 0°/0° im Golf von Guinea, mit „100 %" Fortschritt und
  7.247 von 2.408 nm.** Gemeldet am 01.09.2026 (FA852 KSEA→KFLL, P3Dv5).

  Der Simulator meldet **0/0**, solange er nicht weiss, wo die Maschine steht —
  bei P3D, bevor der Flugplatz geladen ist, bei MSFS waehrend des Ladens. Das
  ist keine Fehlbedienung des Piloten und kein Fehler seines ACARS-Programms:
  phpVMS nimmt diese Position entgegen wie jede andere. In der Tabelle `acars`
  stehen `lat` und `lon` sogar mit **Vorgabewert `0.00000`** — wer nichts
  schickt, sitzt dort.

  Sichtbar war das dreifach, und alles drei ging auf dieselbe Zeile zurueck:
  * der Flieger sass mitten im Atlantik,
  * die „geflogene" Strecke war der Abstand **Abflugort → 0/0** (KSEA sind rund
    7.200 nm) und damit groesser als die geplante,
  * der Fortschritt stand deshalb auf 100 %.

  Ausserdem zog es die **ganze Karte auseinander**: Der Kern zentriert ueber
  `layerFlights.getBounds()`, und in diesen Rahmen ging 0/0 mit ein — der echte
  Flug und der Phantom-Punkt zusammen ergaben die halbe Weltkarte.

  Solche Positionen gelten jetzt als **nicht vorhanden**: kein Marker, keine
  Strecke, kein Fortschritt, kein Auto-Zoom dorthin. Der Flug selbst
  verschwindet **nicht** — er bleibt in der Liste stehen, mit `—` in Hoehe,
  Tempo und Strecke und dem Hinweis „No position reported by the simulator yet".
  Sobald der Sim eine echte Position schickt, ist alles wieder da.

  ⚠ Die Pruefung verlangt, dass **beide** Werte nahe 0 liegen (0,05° ≈ 5,5 km).
  Wer nur den Breitengrad prueft, wirft echte Fluege weg: **0°N/9,7°E ist
  Libreville**, 0°N/32,5°E ist Entebbe. Als Gegenprobe mitgetestet.

  ⚠ `map.removeLayer()` allein genuegt hier nicht. Der Marker muss **aus der
  Flug-Gruppe des Kerns** entfernt werden, sonst bleibt er in deren `getBounds()`
  und zieht die Ansicht weiter auseinander, obwohl man ihn nicht mehr sieht.

  Nachgemessen an einer laufenden phpVMS-7.0.8-Installation mit zwei echten
  ACARS-Fluegen (einer bei 0/0, einer ueber Jamaika) ueber fuenf
  Aktualisierungen des Kerns: Der Phantom-Marker bleibt weg, der echte Flug
  behaelt Marker, Strecke (120/273 nm) und Fortschritt (44 %). Sobald der Sim
  eine echte Position schickt, ist der Flug im selben Durchlauf wieder
  vollstaendig da — der Zustand klebt nicht.

- **Die geflogene Spur lief ebenfalls in den Golf — dieselbe Ursache, andere
  Stelle.** Beim Klick auf einen Flug zeichnet der Kern die Spur ueber
  `L.Geodesic.fromGeoJson`. Ein **einziger** 0/0-Punkt im Flugweg genuegt, und
  die Linie laeuft quer ueber den Atlantik und zurueck (an 13 Punkten
  nachgemessen: 193 Linienpunkte statt 177). Der Filter sitzt an der Methode,
  **nicht** am `layeradd`-Ereignis — der Kern setzt die Punkte erst nach
  `addTo(map)`, zum Ereigniszeitpunkt ist die Linie noch leer.

  Gegenprobe: derselbe Flugweg ohne den 0/0-Punkt ergibt exakt dieselben 177
  Punkte — der Filter nimmt nur den Phantom-Punkt mit. Bleiben nach dem Filtern
  weniger als zwei Punkte uebrig, wird gar keine Linie gezeichnet (getestet,
  keine Fehler).

- **Beim Umschalten auf einen Flug ohne Position blieb die Linie des vorherigen
  stehen.** Der Boarding-Pass zeigte schon den neuen Flug, die Karte noch die
  Strecke des alten — sie sah damit aus, als gehoere sie zum neuen. Die Linie
  wird jetzt in beiden Faellen geraeumt.

---

## [4.7.6] — 2026-08-27

### Fixed
- **Die Einstellungen standen doppelt: unter `Admin → Live Map` und ein zweites
  Mal in den Kern-Einstellungen.** Die Einstellungsseite von phpVMS filtert nach
  dem **Typ**, nicht nach der Gruppe:

  ```php
  $settings = Setting::where('type', '!=', 'hidden')->orderBy('order')->get();
  $settings = $settings->groupBy('group');
  ```

  Unsere 30 Zeilen erschienen damit als eigener Abschnitt `livemap_module`
  mitten in den Kern-Einstellungen. Zwei Formulare für dieselben Werte — und das
  im Kern **ohne unsere Prüfungen**: Ein dort eingetragener CARTO- oder
  Wetter-Schlüssel wird nie gegen den Anbieter geprüft, und die Statusbox weiss
  nichts davon.

  Die Zeilen tragen jetzt `type = 'hidden'`; bestehende Installationen werden
  beim Öffnen der Modulseite einmalig umgestellt.

  Das bestand vermutlich seit v4.6.5, als die Einstellungen in die Datenbank
  wanderten — gemeldet von zwei VAs am 27.08.2026.

  ⚠ `SettingRepository::retrieve()` gibt bei `hidden` den **rohen Text** zurück
  statt eines umgewandelten Wertes. Das ist hier gefahrlos, weil das Modul
  normalisiert speichert (`'1'`/`'0'`, `number_format`) — `'0'` ist in PHP
  falsch, `'1'` wahr, `'0.60'` rechnet wie eine Zahl. Am Bestand nachgemessen:
  alle 17 Wahrheitswerte behalten ihren Zustand, die 6 ausgeschalteten bleiben
  aus. Wer hier je etwas anderes als `'1'`/`'0'` speichert, muss diese
  Entscheidung neu prüfen.

---

## [4.7.5] — 2026-08-27

Zwei Befunde von fremden VAs, beide kosteten Stunden — und beide gingen auf
uns zurück, nicht auf ihre Installation.

### Fixed
- **Ein frisch erzeugter OpenWeatherMap-Schlüssel liess sich nicht speichern.**
  OWM antwortet auf einen neuen Schlüssel bis zu **zwei Stunden** lang mit
  HTTP 401 — er ist richtig, nur noch nicht freigeschaltet. Das Speichern brach
  dann ab, und der Betreiber konnte seinen korrekten Schlüssel überhaupt nicht
  hinterlegen: *„keeps saying openweathermap rejected the key, but it's
  definitely right"*, zwanzig Minuten später lief er.

  Der Schlüssel wird jetzt **gespeichert** und die Meldung erklärt die
  Freischaltzeit, statt zu blockieren. Ob er wirkt, sagt ohnehin die Statusbox,
  und sie prüft erneut, sobald sich der Wert ändert.

### Changed
- **`INSTALL.txt`: erst hochladen, dann umbenennen.** Die bisherige Anleitung
  sagte „Delete or rename the existing LiveMap/ folder" und dann hochladen. Auf
  einer laufenden Seite ist das ein Fenster von Minuten, in dem `module.json`
  schon da ist und `Providers/` noch nicht — phpVMS findet das Modul, kann den
  Provider nicht laden und wirft auf **jeder** Seite einen Fatalfehler,
  einschliesslich `/admin`. Der Betreiber ist dann aus der eigenen Seite
  ausgesperrt.

  Am 27.08.2026 genau so passiert. Der Weg zurück (in der Tabelle `modules`
  `enabled` auf 0) stand nirgends in unserer Anleitung; ein anderer VA-Betreiber
  hat ihn herausgefunden.

  Neu: in einen Nebenordner hochladen, Vollständigkeit prüfen, dann in **einem**
  Umbenennen tauschen. Dazu ein Abschnitt „If your site is down".
- **`INSTALL.txt` liegt jetzt im Repo**, nicht nur im Paket. Sie ist Teil des
  Produkts und gehört versioniert wie der Code — die alte Fassung existierte
  ausschliesslich in den erzeugten ZIPs.

---

## [4.7.4] — 2026-08-27

Behebt den Befund von **ProAvia**: „I still see the api key required message on
page load. If I open the map type box, select another map style and then select
Carto Light it loads fine."

### Fixed
- **Wasserzeichen beim Laden, obwohl der Schlüssel hinterlegt ist.** Auf der
  Karte lagen **zwei** Grundebenen. Die Widget-Datei reicht
  `providers: {'CartoDB.Positron': {}}` an `render_live_map` durch, und der
  phpVMS-Kern baut daraus eine **eigene** Kachelebene:

  ```js
  for (r in i.providers) n.tileLayer.provider(r, i.providers[r]).addTo(o)
  ```

  Deren Adresse kommt aus dem Katalog von `leaflet-providers` — und der hat für
  CartoDB **keinen Platz für einen Schlüssel**. An dieser Ebene kam kein
  Modul-Code vorbei, und sie lag obenauf. Nach dem Umschalten der Kartenart
  verschwand sie, deshalb war die Karte danach sauber.

  Der Schlüssel wird jetzt an `L.TileLayer.prototype.getTileUrl` angehängt — die
  eine Stelle, durch die **jede** Kachel geht, gleich wer die Ebene gebaut hat.
  Mit einem Wachposten auf `window.L`, falls das Theme Leaflet später lädt.

### Changed
- **Die doppelte Grundebene wird entfernt.** Sie kostete jede Kachel zweimal,
  also doppeltes CARTO-Kontingent (frei sind 5 Mio./Monat). `providers` einfach
  wegzulassen geht nicht — bei leerer Liste setzt der Kern von sich aus
  `Esri.WorldStreetMap` ein. Entfernt werden nur CARTO-Ebenen ohne die Marke des
  Moduls; eine selbst hinzugefügte Grundkarte bleibt.

  Am 27.08.2026 auf der laufenden Karte nachgemessen: vorher zwei Grundebenen,
  danach eine, 20 von 20 Kacheln geladen, 0 ohne Schlüssel.

---

## [4.7.3] — 2026-08-26

### Added
- **The status box now names the source of the key** — `(from this page)` or
  `(from .env)`. Previously it only said `SET`, which left the more useful
  question open: a key coming from `.env` looks exactly like one entered here,
  and that is precisely when the "remove key" checkbox appears to do nothing.
- **A note when `.env` is being overridden**: if `CARTO_API_KEY` is set but the
  key was cleared on the settings page, the box says so and explains that a value
  set on the page wins.
- **Instructions for entering the key in `.env`**, right under the field —
  the line to add, the cache clear afterwards, and the precedence rule.

---

## [4.7.2] — 2026-08-26

Three faults in the CARTO key handling from v4.7.0/v4.7.1. If you entered a key
in either of those versions, **enter it again after upgrading** — it was never
stored.

### Fixed
- **The key was never saved.** `acars.carto_api_key` had no entry in the module's
  settings definitions, and `update()` persists only what is defined there —
  everything else is dropped, without an error. The field accepted input, the
  page reported success, and the value went nowhere. The OpenWeatherMap key was
  never affected; it has had its definition since v4.6.5.
- **"Remove Currently Stored API Key On Save" had no effect**, for the same
  reason. A delete that does not delete is worse than none: it reads as removed
  while the key is still served on every page.
- **HTTP 500 on the second visit to the settings page.** The cached verification
  verdict was written with `Setting::updateOrCreate(['id' => …])`, but the table
  keys on a *derived* id (`Setting::formatKey()`) and `id` is not fillable — so
  the row was inserted without one, and the next write collided on the empty
  primary key (`Duplicate entry '' for key 'PRIMARY'`). This hit every install
  that saved settings twice, whether or not CARTO is used.

### Changed
- **An entry made in the admin UI now beats `CARTO_API_KEY` in `.env`, including
  a deliberately emptied one.** Previously the `.env` value returned as soon as
  the setting was blank, which made removal through the UI impossible. The `.env`
  now applies only while nobody has set a value — the state before first use.

---

## [4.7.1] — 2026-08-26

### Added
- **CARTO Basemap Status box**, next to the existing Weather Proxy status. Shows
  whether a key is stored, whether **CARTO actually accepts it**, and whether the
  default basemap is a CARTO style at all. Green when the key works, red when it
  is ignored or missing while a CARTO style is selected, amber when CARTO could
  not be reached (which says nothing about the key).

  The verdict is stored, not re-fetched on every page load: a check costs three
  requests to CARTO, and on installs without an application cache
  (`CACHE_DRIVER=null`, not unusual with phpVMS) that would run every time the
  page opens. It is re-checked automatically whenever the stored key changes —
  including a change made in `.env`, past the settings page.

### Changed
- The CARTO key moved out of the **Weather** section into its own **Basemap**
  section, with deliberate spacing instead of the framework defaults.

---

## [4.7.0] — 2026-08-26

### Added — CARTO Basemaps API key

CARTO now requires an API key for its basemaps. Since **26 August 2026** the
**raster** service stamps `API KEY REQUIRED` diagonally across every tile —
which is exactly what the *Carto Light* and *Carto Dark* basemaps use. The
vector service is not affected yet; CARTO says it will announce that
separately. OpenStreetMap and Satellite were never affected.

A key is **free up to 5 million tile requests per calendar month**, counted
across both services: <https://carto.com/basemaps/apikey/>

**What was added**

- A `CARTO Basemaps API Key` field in the LiveMap admin settings, with the
  same handling as the OpenWeatherMap key: leaving it empty keeps the stored
  key, and a separate checkbox clears it. The stored value is never
  pre-filled into the form.
- The key is appended **once, where the tile layer is created**, not on each
  basemap entry — so any CARTO style added later is covered automatically.
- Falls back to a `CARTO_API_KEY` entry in `.env`, so the key can be in place
  before anyone opens the settings page.

**Why the key is validated by comparing images**

CARTO answers *every* tile request with HTTP 200 — no key, wrong key, empty
key alike — and the response headers are identical too. A typo would stay
invisible until someone opens the map. What does differ is the image.
Measured on the same tile:

    no key          18,917 B   md5 c4ff8359e505
    wrong key       18,917 B   md5 c4ff8359e505   (identical)
    empty key       18,917 B   md5 c4ff8359e505   (identical)
    valid key       20,251 B   md5 78f84118df46   (different)

So on save the module fetches the tile with the candidate key and compares it
against the un-keyed one. Identical means the key was not accepted. If the
un-keyed tile itself is not stable across two fetches, no verdict is passed
and saving is not blocked — better no judgement than a wrong one.

### Note on attribution

Visible CARTO and OpenStreetMap attribution is a condition of the free tier.
The basemap definitions already carry it; please keep it.

---

## [4.6.7] — 2026-06-15

### Fixed
- **View path registration now only registers existing directories.** `registerViews()` appended `/modules/livemap` to every `view.paths` entry (theme-override paths) which usually don't exist. Live rendering was never affected — the view finder skips missing dirs lazily and falls back to `Resources/views` — but `php artisan view:cache` / `optimize` eager-scans every registered path via the Symfony Finder and threw `DirectoryNotFoundException`. The `$paths` list is now wrapped in `array_filter(…, 'is_dir')`; `Resources/views` always exists so the namespace keeps at least one valid path, and existing theme overrides are still picked up.

## [4.6.6] — 2026-04-22

### Mixed-Content Hotfix (HTTPS Upgrade for All Leaflet Tile Layers)

- Added a defensive monkey-patch in `live_map_scripts.blade.php` that forces
  every `L.TileLayer.initialize(url)` and `L.TileLayer.setUrl(url)` call to
  upgrade `http://` URLs to `https://`.
- Fixes the "hundreds of mixed-content warnings" symptom on installs where
  a second, older `live_map.js` (from a custom theme or another phpVMS
  module) is running in parallel and still uses `http://` tile URLs.
- No configuration change required. The patch is idempotent and only applies
  once per page load.

### Packaging

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.6"`
- Updated install ZIP: `dist/LiveMap-full-package-v4.6.6.zip`

---

## [4.6.5] — 2026-04-22

### Settings Storage Moved Fully to Database

- Admin settings are now written **only** to the phpVMS `settings` table (group `livemap_module`), with no dependency on `storage/app/kvp.json`.
- All three read paths (`SettingsController`, `WeatherProxyController`, `live_map.blade.php`) read from the DB first and only fall through to legacy `kvp.json` values for one-time migration of pre-v4.6.4 installs.
- `ensureDurableBackup()` is now a one-way legacy migration that promotes any remaining `kvp.json` values into the DB on admin page load — idempotent and non-destructive.

### Performance: Skip Weather Tiles When No Key Is Configured

- Frontend now reads a new `weatherAvailable` config flag.
- When no OWM API key is stored, the widget no longer requests a single weather tile. Previously the map fired hundreds of proxy requests that round-tripped for blank SVG tiles, making the page feel like it "loads and loads and loads".
- Default weather layer is forced to `none` server-side when no key is configured, so the control is visibly disabled as well.

### Security Hardening

- Upstream OWM exception messages are now sanitised before being stored in the error cache or written to logs:
  - `appid=…` substrings are redacted
  - Full URLs are stripped
  - Control characters are removed
  - Length is capped
- Prevents accidental leakage of API key substrings or log-injection payloads into admin UI / application logs.

### Packaging

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.5"`

---

## [4.6.4] — 2026-04-22

### Settings Persistence Hotfix

- Fixed the recurring problem where the OpenWeatherMap API key and other Live Map admin settings silently reverted to defaults between sessions.
- Root cause: v4.6.1 moved all settings into `storage/app/kvp.json` (Spatie Valuestore flat file) and the admin page actively deleted the durable database backup rows on every load. Any event that wiped, corrupted, or lost `kvp.json` (hoster deploy, permission reset, Spatie Valuestore concurrent-write race, cache maintenance) erased every setting with no way to recover.

### What Changed Internally

- `SettingsController::persistLiveMapSetting` now dual-writes:
  - fast read path: `kvp_save(livemap.*)`
  - durable backup: rows in the `settings` table under group `livemap_module`
- All three read paths are now self-healing:
  - `SettingsController::lmGet`
  - `WeatherProxyController::lmGet`
  - `lmSetting` helper in `live_map.blade.php`
- When `kvp.json` is missing but the durable DB row is present, the DB value is returned and `kvp` is re-seeded on the fly.
- Removed the destructive `deleteLegacySettingsRows()` / `migrateLegacySettingsToKvpAndCleanup()` pair and replaced it with a non-destructive `ensureDurableBackup()` that bidirectionally reconciles both stores on admin page load.

### Packaging and Metadata

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.4"`

---

## [4.6.3] — 2026-04-04

### Weather Proxy Resilience

- Added server-side upstream fallback chain for weather tiles:
  - `pressure_new` -> `precipitation_new` -> `clouds_new`
- Kept legacy layer alias handling so old widget requests still resolve safely:
  - `thunder_new` -> `pressure_new`
  - `weather_new` -> `precipitation_new`
- Added diagnostic response headers for easier troubleshooting:
  - `X-LiveMap-Upstream-Layer`
  - `X-LiveMap-Fallback`
- Extended proxy warning logs with attempted upstream layers.

### Admin Key Persistence Fix

- Saving Live Map settings with an empty OWM API key field now keeps the existing stored key.
- Added explicit checkbox option to intentionally remove the stored API key.
- OWM key validation now runs only when the key value was actually changed.

### Packaging and Metadata

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.3"`
- Updated release docs/examples to `v4.6.3`.

---

## [4.6.2] — 2026-03-17

### Weather Layer Compatibility Hotfix

- Replaced the old storms primary layer (`thunder_new`) with `pressure_new` for current OWM tile compatibility.
- Added storms fallback chain in frontend:
  - `pressure_new` -> `precipitation_new` -> `clouds_new`
- Added weather proxy backward-compatibility aliases so older clients still work:
  - `thunder_new` -> `pressure_new`
  - `weather_new` -> `precipitation_new`
- Updated Live Map weather UI/admin wording from storm/thunder-specific labels to pressure-based proxy wording.

### Packaging

- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.2"`

---

## [4.6.1] — 2026-03-15

### Compatibility and Stability Hotfixes

- Fixed Blade compatibility issue causing:
  - `Method Illuminate\View\Factory::getName does not exist`
- Replaced view-name introspection with safe include resolution (`View::exists` fallback candidates).

### Settings Scope and Admin Cleanup

- Live Map settings now persist in module-internal `kvp` keys (`livemap.*`).
- Added migration from legacy `acars.livemap_*` values.
- Added cleanup of legacy Live Map rows from global `Admin -> Settings` to avoid duplicated configuration surfaces.

### UI/Interaction Fixes

- Restored missing desktop flights panel styles.
- Restored missing desktop boarding-pass styles.
- Made FIR/UIR label markers non-interactive to prevent click interception on aircraft.
- Fixed follow-mode interaction side effect where manual zoom/pan could become unresponsive.

### Admin UX Improvements

- ACARS `Live Time` note in Live Map admin now reads the current core value dynamically.
- Warning now appears only for unsafe value (`<= 0`).
- Safe values display a minimal informational line.

### Packaging

- Moved release workflow to versioned ZIP naming to reduce stale-cache confusion with generic package names.
- Release/install path documented as browser/admin flow only (`/update` + Admin Clear Caches), no SSH required.
- Updated module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.1"`

---

## [4.6.0] — 2026-03-14

### Admin UX Simplification

- Reduced admin color controls to 3 essential fields:
  - `Primary UI Color`
  - `Accent UI Color`
  - `Box Background Color`
- Kept full visual coverage by mapping these 3 values internally to all required UI elements.
- Updated admin wording/tooltips in English for clearer behavior expectations.

### Mobile UI Cleanup

- Removed legacy extra floating **mobile Network button**.
- Kept one floating **Flights** mobile button and Network access via the panel/tab.
- Improved active/inactive state visibility of the Flights button.
- Removed obsolete `mobile_show_network_button` setting from admin flow.

### Reliability and Behavior

- Preserved weather proxy diagnostics with clearer status usage in release docs.
- Maintained blank tile fallback behavior to avoid repeated 502 console spam when OWM upstream fails.
- Kept improved multi-flight follow/fit behavior for better framing when multiple aircraft are active.

### Documentation and Release Assets

- Rewrote `README.md` for current architecture (module + blade + admin-driven setup).
- Updated `RELEASE_NOTES.md` for v4.6.0.
- Added explicit operational warning:
  - **Do not set phpVMS ACARS Live Time to `0`** in production.
  - Recommended minimum: `1`, because the same core setting impacts stale/stuck PIREP cleanup/cancellation.
- Added module metadata version:
  - `LiveMap/module.json` -> `"version": "4.6.0"`

---

## [4.5.0] — 2026-03-08

### Bug Fixes — FIR Sector Rendering

Three bugs in the `renderActiveSectors` function prevented FIR sector polygons from appearing for large parts of the world, most visibly in Russia, CIS, Central Asia, and the Caucasus region.

#### Fix 1 — Sub-sector callsign matching too narrow

Controllers using sub-sector callsigns (e.g. `UNKL_N_CTR`, `UUWV_E_CTR`) were processed through a narrow exact-match path (`isSubKey` branch) that only checked for an exact GeoJSON feature ID like `UNKL_N` or `UNKL-N`. When neither existed, the code fell back to a minimal root lookup — but never ran the broad search that already worked for simple callsigns like `UNKL_CTR`.

The matching logic now uses a **4-phase cascade** that runs for all callsigns:

| Phase | Method | Example |
|-------|--------|---------|
| 1 | Exact sub-key match | `UNKL_N` → GeoJSON `UNKL-N` |
| 2 | Broad normalised search | `UNKL` → scans all GeoJSON features |
| 3 | startsWith fallback | `UNKL` → matches `UNKL-1`, `UNKL-2` |
| 4 | UIR expansion *(new)* | `RU-SC` → resolves to `URRV`, `UGGG`, `UDDD`, `UBBA` |

A `_norm()` helper normalises hyphens to underscores on both sides of every comparison, preventing silent mismatches between `firPrefixMap` (hyphens) and GeoJSON keys (underscores).

#### Fix 2 — CTR/FSS controllers without position silently dropped

Controllers like `RU-SC_FSS` have no airport in VATSpy's static data and often no transceiver entry. The code's `if(!pos) return;` skipped them entirely — before they could even reach the FIR sector matching logic. CTR and FSS controllers are now kept in the processing pipeline with `pos: null`, since the FIR polygon itself does not require a controller position.

#### Fix 3 — UIR (Upper Information Region) support

Callsigns like `RU-SC_FSS`, `RU-EC_FSS`, `RU-NW_FSS` refer to **UIRs** — composite airspace regions that consist of multiple FIRs. The `[UIRs]` section of VATSpy.dat was not parsed at all.

The code now:
1. Parses the `[UIRs]` section during `loadFirNames()` into a `uirToFirsMap` lookup table
2. In Phase 4 of the matching cascade, resolves a UIR callsign to its constituent FIR IDs
3. Draws all constituent FIR polygons as a single sector group
4. Automatically marks UIR sectors as Upper Airspace (dashed purple styling)

### Affected Regions

- **Russia** — UNKL, URWW, URMM, UUWV, ULLL, UWSG and all other U-prefix FIRs
- **Russian UIRs** — RU-SC (Caucasus), RU-EC (East Central), RU-NW (Northwest), RU-WS (West Siberia), etc.
- **Central Asia** — UACC, UAAA, UATT, UTAA, UTDD, UZTT
- **Caucasus** — UBBB, UGTB, UGEE, UDDD
- **Any FIR worldwide** using hyphenated sub-sector IDs in VATSpy GeoJSON
- **Any UIR worldwide** defined in the `[UIRs]` section of VATSpy.dat

---

## [4.0.0] — 2026-02-28

### New Features

#### VA Planned Flights Panel
- **New "Planned" tab** alongside the existing "Active Flights" tab in the VA panel
- Displays scheduled bids fetched from phpVMS `/api/user/bids`
- **2-column grid layout** — route display spans the full panel width, details in compact columns
- **Boarding pass–style flight info card** with animated progress bar and aircraft icon
- Airline logos per flight loaded from phpVMS database
- Pilot name, flight number, aircraft type, departure/destination with full airport names
- Scheduled departure time with UTC display
- Click any planned flight to open the matching pilot marker on the map (if active)

#### Mobile Responsive Design
- **Dedicated toggle button bar** for small screens (✈ Flights · Network)
- VA panel, network selector and weather overlay box collapse into hidden drawers on mobile
- Side-tab controls remain accessible without blocking the map
- Dynamic table height adapts to viewport
- Boarding pass card switches to single-column layout on narrow screens
- All button and icon sizing adjusted for touch targets

#### IVAO Controller Rating Badges
- Full IVAO rating badge system: OBS → DEL → GND → TWR → APP → CTR → FSS → SUP → ADM
- **VID links** to official IVAO tracker (`https://www.ivao.aero/Member.aspx?Id=…`)
- Unified popup design for VATSIM and IVAO controllers (dark title bar, white content area)
- Online time display for IVAO controllers

#### Portable Domain Support
- Removed all hardcoded domain references (`german-sky-group.eu`)
- phpVMS API calls now use `window.location.origin` — works on **any** phpVMS installation without changes
- `PHPVMS_BASE` config variable added for transparency
- Clear config comment block for other admins

### Security — Critical Fixes

**12 XSS vulnerabilities patched** — all external API data (VATSIM, IVAO, phpVMS) was previously injected into `innerHTML` without escaping.

New security helper functions:

```javascript
h(str)           // HTML-escape all innerHTML output
safeUrl(url)     // Accept only HTTPS URLs without special characters
safeCallsign(s)  // Allow only A–Z, 0–9, _, - (max 20 chars)
safeFreq(s)      // Allow only digits and dot (max 8 chars)
```

| Severity | Issue | Fix |
|----------|-------|-----|
| 🔴 Critical | XSS via `innerHTML` — VATSIM/IVAO/phpVMS data (12 locations) | `h()` wrapper on all output |
| 🔴 Critical | CSS injection via `querySelector` with unsanitised callsign | `safeCallsign()` whitelist |
| 🔴 Critical | Open redirect / `javascript:` URI in IVAO VID link `href` | `safeUrl()` HTTPS-only validation |
| 🟡 Medium | Frequency string injected without sanitising (4 locations) | `safeFreq()` numeric-only filter |
| 🟡 Medium | Rating type coercion — string vs. number comparison | `parseInt()` coercion before comparisons |
| 🟡 Medium | Blade variables (center lat/lng, zoom) inserted as raw strings | `parseFloat()` / `parseInt()` with safe fallbacks |
| 🟢 Low | `target="_blank"` links missing `rel="noopener noreferrer"` | Added to all external links |

### Bug Fixes
- Fixed: Boarding pass card flickering on tab switch (CSS `visibility` instead of `display` toggle)
- Fixed: VATSIM/IVAO controller popup rating badge not rendering at OBS level
- Fixed: Weather/network panel not expanding on mobile due to `overflow: hidden` on parent container
- Fixed: Airline logo fallback when logo URL returns 404
- Fixed: Planned flights panel showing stale data after bid changes without manual refresh
- Fixed: `⚠ Unavailable` error message replaced with `⚠ phpVMS API not reachable` for clarity

---

## [3.0.1] — 2026-02-24

### Bug Fixes & Code Quality
- Removed: All 9 `console.log` debug statements from production code (intentional `console.warn`/`console.error` in error handlers retained)
- Improved: Airline logo output changed from `{!! json_encode() !!}` (unescaped) to `@json()` (Blade-escaped, safer)
- Fixed: Outdated comment referencing jsDelivr CDN (logo source had changed but comment was never updated)

---

## [3.0.0] — 2026-02-24

### New Features

#### IVAO Network Integration
- **Dual-network display** — VATSIM and IVAO can now be shown simultaneously on the same map
- **IVAO pilot markers** — orange SVG aircraft icons (visually distinct from VATSIM blue)
- **IVAO controller markers** — airport badges with orange outline and "IV" label
- **IVAO FIR sectors** — same VATSpy GeoJSON, rendered in a distinct darker teal colour
- **IVAO stats always loaded** — pilot/controller counts shown in the stats bar even when the IVAO layer is toggled off, matching VATSIM behaviour
- **Independent refresh cycles** — VATSIM 30 s, IVAO 15 s; run independently without interference
- **Network toggle buttons** — VATSIM (teal) and IVAO (orange) buttons; shared layer controls (Pilots / Controllers / FIR Sectors) apply to both active networks

#### VA Active Flights Panel
- **Collapsible top-centre panel** showing all active phpVMS/ACARS flights
- Columns: Flight · Route · Aircraft · Altitude · Speed · Distance · Status · Pilot
- **Live count badge** on the toggle button (red when flights active, grey when empty)
- **Distance column** showing flown / planned distance in nmi (`573 / 4895 nmi`)
- **Pilot name** column with first name + last name initial from phpVMS user data
- Refreshes at the same interval as your phpVMS `acars.update_interval` setting
- Status texts translated from phpVMS German locale to English (Unterwegs → En Route, Gelandet → Landed, etc.)
- Row highlight persists across refreshes (active flight stays visually selected)
- Dark map mode automatically darkens the panel (MutationObserver)

#### VA Info Card
- **New dedicated `#va-info-card`** (top-right) filled directly from ACARS API data
- Works independently of the phpVMS Rivets binding — no timing hacks, no marker simulation
- Displays: route, callsign, aircraft registration/type, altitude, speed, pilot name, status badge
- Shows airline logo from phpVMS database
- Close button (✕) on the card
- Clicking the map closes the card and clears the route line
- When a real map marker is clicked, the Rivets card takes over and the VA card hides automatically (MutationObserver)

### Improvements
- All visible UI text is now in English (was previously German in some labels and status texts)
- Stats bar shows `...` while loading (was `—`), `⚠ Error` on failure (was German `⚠ Fehler`)
- VA panel position moved from top-left to top-centre
- Panel width adapts to column count; max-height 400 px with scroll for many flights

### Bug Fixes
- Fixed: VA route line briefly showing wrong destination when clicking a second aircraft quickly (sequence counter + mandatory 150 ms Rivets delay)
- Fixed: Stats box height mismatch between VATSIM and IVAO boxes when text wrapped (nowrap + ellipsis)
- Fixed: IVAO stats showing `—` when network was toggled off (stats now always updated on fetch)

---

## [2.0.0] — 2026-02-23

### New Features
- **VA Flight Route Line** — clicking a VA aircraft shows a dashed red line to the destination airport
- **VA Aircraft Icon** — phpVMS aircraft replaced with a distinctive white/blue SVG icon; rotation handled by leaflet-rotatedmarker
- **Dark Map persistent** — dark mode state saved to localStorage and restored on reload
- **TRACON auto-merge** — TRACON / Approach Control facilities merged into the nearest airport marker (within 80 km)
- **Airport full names** — full airport names from VATSpy data shown in controller popups
- **ATIS collapsible** — ATIS text shows a 60-character preview with "Show full ATIS" toggle
- **Route line destination badge** — red ICAO label shown at the destination airport when route line is active
- **Badge legend** — visual reference panel showing all badge types and colours

### Improvements
- Controller zoom thresholds lowered: badges visible from zoom 3, labels from zoom 5
- Default start state: Controllers active, Pilots and FIR Sectors off
- Airline logos loaded from phpVMS database (no external CDNs)
- Airport marker click area enlarged to 36 px for easier interaction
- APP/TRACON badge changed from orange to green; combined APP+ATIS shows "Ai" badge

### Bug Fixes
- Fixed: Dark Map button had no effect when OWM API key was missing
- Fixed: VA route line not shown on second click (scope bug in `lastDrawnArr`)
- Fixed: Duplicate `layeradd` handlers overwriting each other
- Fixed: Dead variable `vaCallsignSet` causing silent ReferenceError

---

## [1.0.0] — 2026-02-20

### Initial Release

- Real-time VATSIM pilot positions with popup (callsign, route, aircraft, altitude, speed, heading, pilot name)
- VATSIM controller markers with colour-coded facility badges (DEL, GND, TWR, APP, CTR)
- FIR sector boundaries as coloured polygons from VATSpy GeoJSON
- Controller positions from VATSIM Transceivers API
- Airport positions from VATSpy.dat (~7000 airports)
- Key normalisation: EWR ↔ KEWR, AU Y-prefix, Pacific P-prefix airports
- Pilot route line (dashed red) on aircraft click
- Follow Flight toggle
- OWM weather overlays: Clouds, Radar, Storms, Wind, Temperature, Combo + opacity slider
- Dark Map (CSS filter night mode)
- Airline logos in VATSIM pilot popups
- VATSIM live indicator dot with pilot/controller counts
- 30-second VATSIM refresh interval
