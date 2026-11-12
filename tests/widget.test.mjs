// Tests fuer die reinen Hilfsfunktionen der Live-Karte (LiveMap/Resources/views/partials/scripts.blade.php,
// Block zwischen /* <lm3-rein> */ und /* </lm3-rein> */). Ohne Browser, ohne Pakete:
//
//     node --test tests/widget.test.mjs
//
// Der Phasen-Test vergleicht mit einer Abschrift der Webapp-Datei
// (tests/fixtures/phaseColors.webapp.ts) — nicht mit einer eigenen Liste.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const HIER = path.dirname(fileURLToPath(import.meta.url));
const skript = fs.readFileSync(path.join(HIER, '..', 'LiveMap', 'Resources', 'views', 'partials', 'scripts.blade.php'), 'utf8');
const a = skript.indexOf('/* <lm3-rein> */');
const b = skript.indexOf('/* </lm3-rein> */');
assert.ok(a > 0 && b > a, 'Block <lm3-rein> nicht gefunden');
const umgebung = { globalThis: {} };
umgebung.globalThis = umgebung;
vm.runInNewContext(skript.slice(a, b), umgebung);
const LM3 = umgebung.LM3;
const klar = (x) => JSON.parse(JSON.stringify(x)); // Felder aus dem vm-Kontext vergleichbar machen

const webapp = fs.readFileSync(path.join(HIER, 'fixtures', 'phaseColors.webapp.ts'), 'utf8');
function objektAus(name) {
  const m = webapp.match(new RegExp('const ' + name + '[^=]*=\\s*\\{([\\s\\S]*?)\\n\\};'));
  assert.ok(m, name + ' nicht in der Webapp-Abschrift');
  const aus = {};
  for (const z of m[1].matchAll(/^\s*([A-Z_]+):\s*"([^"]*)"/gm)) aus[z[1]] = z[2];
  return aus;
}

test('Phasenfarben gleich der Webapp (alle Schluessel, alle Werte)', () => {
  const soll = objektAus('PHASE_COLOR');
  assert.ok(Object.keys(soll).length >= 19);
  assert.deepEqual({ ...LM3.PHASE_COLOR }, soll);
});

test('Phasennamen gleich der Webapp', () => {
  assert.deepEqual({ ...LM3.PHASE_LABEL }, objektAus('PHASE_LABEL'));
});

test('Phasenreihenfolge (Filter) gleich der Webapp', () => {
  const m = webapp.match(/PHASE_LIST:\s*Phase\[\]\s*=\s*\[([\s\S]*?)\];/);
  const soll = [...m[1].matchAll(/"([A-Z_]+)"/g)].map((x) => x[1]);
  assert.equal(soll.length, 17);
  assert.deepEqual([...LM3.PHASE_LIST], soll);
});

test('Rueckfallfarbe und Namen unbekannter Phasen wie die Webapp', () => {
  assert.match(webapp, /\?\? "#94a3b8"/);
  assert.equal(LM3.phaseColor('GIBT_ES_NICHT'), '#94a3b8');
  assert.equal(LM3.phaseColor(null), '#94a3b8');
  assert.equal(LM3.phaseLabel('GO_AROUND'), 'GO AROUND'); // Webapp schreibt nur Wortanfaenge gross
  assert.equal(LM3.phaseLabel(null), '—');
  assert.equal(LM3.phaseLabel('ON_BLOCK'), 'Blocks On');
  assert.equal(LM3.phaseLabel('PIREP_SUBMITTED'), 'Pirep eingereicht');
  // "toString" ist kein Phasenname — darf nicht aus dem Prototyp kommen.
  assert.equal(LM3.phaseColor('toString'), '#94a3b8');
});

test('Anzeige-Phase: Level nur im Steig-/Sinkflug mit Level-Segment', () => {
  assert.equal(LM3.phaseForDisplay('CLIMB', 'level'), 'LEVEL');
  assert.equal(LM3.phaseForDisplay('DESCENT', 'Level'), 'LEVEL');
  assert.equal(LM3.phaseForDisplay('CRUISE', 'level'), 'CRUISE');
  assert.equal(LM3.phaseForDisplay('CLIMB', null), 'CLIMB');
  assert.equal(LM3.phaseForDisplay(null, 'level'), '—');
});

test('Formate wie die Webapp (Sim, QNH, Alter, V/S, AP)', () => {
  assert.equal(LM3.formatSimLabel('MSFS2024'), 'MSFS 2024');
  assert.equal(LM3.formatSimLabel('XPLANE12'), 'X-Plane 12');
  assert.equal(LM3.formatSimLabel('my_sim'), 'my sim');
  assert.equal(LM3.fmtQnhBoth(1013.25), '1013 hPa / 29.92 inHg');
  assert.equal(LM3.ageString(1000, 1000 + 59_000), '59s');
  assert.equal(LM3.ageString(0, 61_000), '1m');
  assert.equal(LM3.ageString(0, 2 * 3600_000), '2h');
  assert.equal(LM3.vsText(-760), '-760 ↓');
  assert.equal(LM3.vsText(1200), '+1200 ↑');
  assert.equal(LM3.vsText(50), '+50 →');
  assert.equal(LM3.vsText(null), '—');
  assert.equal(LM3.apFlags({ ap_master: true, ap_alt: true, ap_app: false }), 'AP ALT');
});

test('Datumsgrenze: Spur zaehlt Laengen fortlaufend weiter, kein Sprung ueber die Welt', () => {
  const roh = [[60, 175], [61, 179.5], [61.2, -179.5], [61, -175]];
  const aus = LM3.fortlaufend(roh);
  assert.deepEqual(klar(aus.map((p) => p[1])), [175, 179.5, 180.5, 185]);
  for (let i = 1; i < aus.length; i++) assert.ok(Math.abs(aus[i][1] - aus[i - 1][1]) < 180);
});

test('Datumsgrenze: ganze Spur in die Weltkopie der Karte, Form bleibt', () => {
  const spur = LM3.fortlaufend([[61, -150], [58, -175], [56, -190]]);   // PANC → westwaerts
  const verschoben = LM3.verschiebeNahe(spur, 170);                     // Karte steht bei 170° O
  assert.equal(verschoben[2][1], 170);
  assert.deepEqual(klar(verschoben.map((p) => p[1])), [210, 185, 170]);
  assert.deepEqual(klar(LM3.verschiebeNahe(spur, -185)), klar(spur));              // schon richtig: unveraendert
  assert.equal(LM3.neben(-179, 179), 181);
  assert.equal(LM3.neben(10, 8), 10);
});

test('Phasenfilter nimmt die ROHE Phase (wie die Webapp)', () => {
  const f = [{ id: 1, phase: 'CLIMB', shadow_segment: 'level' }, { id: 2, phase: 'CRUISE' }];
  assert.deepEqual(klar(LM3.filterNachPhase(f, 'CLIMB').map((x) => x.id)), [1]);
  assert.deepEqual(klar(LM3.filterNachPhase(f, 'LEVEL').map((x) => x.id)), []);
  assert.equal(LM3.filterNachPhase(f, 'ALL').length, 2);
});

test('Geplante Fluege: AeroACARS „ITY 4532“ ist phpVMS „ITY4532“', () => {
  assert.equal(LM3.rufzeichenSchluessel(' ity 4532 '), 'ITY4532');
  assert.equal(LM3.rufzeichenSchluessel(null), '');
  const geplant = [{ callsign: 'ITY4532' }, { callsign: 'ITY4533' }];
  assert.deepEqual(klar(LM3.geplanteOhneAktive(geplant, [{ callsign: 'ITY 4532' }]).map((g) => g.callsign)), ['ITY4533']);
});

test('Hoehe wie die 4.7.9-Tafel: ab 18 000 ft Flugflaeche', () => {
  assert.equal(LM3.hoeheText(37000, 'en'), 'FL370');
  assert.equal(LM3.hoeheText(18000, 'en'), 'FL180');
  assert.equal(LM3.hoeheText(17999, 'en'), LM3.zahl(17999, 'en') + ' ft');
  assert.equal(LM3.hoeheText(null), '');
});

test('Distanz in nm (Grosskreis) und Fortschritt geflogen/gesamt', () => {
  // FRA–JFK ~ 3343 nm (Grosskreis)
  const fraJfk = LM3.distanzNm(50.0333, 8.5706, 40.6398, -73.7789);
  assert.ok(Math.abs(fraJfk - 3343) < 15, String(fraJfk));
  assert.equal(LM3.distanzNm(50, 8, 50, 8), 0);
  const dep = { lat: 50.0333, lon: 8.5706 }, arr = { lat: 40.6398, lon: -73.7789 };
  const halb = LM3.fortschritt(dep, arr, { lat: 55.5, lon: -30 });
  assert.ok(halb.anteil > 0.3 && halb.anteil < 0.7, String(halb.anteil));
  assert.ok(Math.abs(halb.geflogen + halb.rest - halb.gesamt) < 1e-6);
  assert.equal(LM3.fortschritt(dep, arr, dep).anteil, 0);
  // weiter weg vom Ziel als der Abflug: nie negativ
  assert.equal(LM3.fortschritt(dep, arr, { lat: 52, lon: 20 }).geflogen, 0);
  // fehlende oder 0/0-Orte: kein Fortschritt statt Unsinn
  assert.equal(LM3.fortschritt(null, arr, dep), null);
  assert.equal(LM3.fortschritt(dep, arr, { lat: 0, lon: 0 }), null);
  assert.equal(LM3.fortschritt(dep, dep, dep), null);
});

test('Flugzeugklasse nach ICAO-Typ, unbekannt → Schmalrumpf', () => {
  const soll = { C172: 'kolben', AT76: 'turboprop', CL60: 'bizjet', A320: 'schmal', B738: 'schmal', A21N: 'schmal',
    B77W: 'gross', A359: 'gross', A388: 'vier', B744: 'vier', H135: 'heli', ' b77w ': 'gross', ZZZZ: 'schmal', '': 'schmal' };
  for (const [typ, k] of Object.entries(soll)) assert.equal(LM3.flugzeugKlasse(typ), k, typ);
  assert.equal(LM3.flugzeugKlasse(null), 'schmal');
  assert.equal(LM3.flugzeugKlasse('toString'), 'schmal'); // nicht aus dem Prototyp
  // jede Klasse hat ein Symbol k1..k7 und eine Groesse; groessere Flugzeuge groesser
  const k = LM3.KLASSEN;
  assert.deepEqual(Object.keys(k).map((x) => k[x].symbol).sort(), ['k1', 'k2', 'k3', 'k4', 'k5', 'k6', 'k7']);
  assert.ok(k.kolben.px < k.schmal.px && k.schmal.px < k.gross.px && k.gross.px < k.vier.px);
  assert.equal(LM3.klassenInfo('gibtsnicht'), k.schmal);
});

test('Jedes Klassensymbol ist im Sprite definiert', () => {
  for (const s of ['k1', 'k2', 'k3', 'k4', 'k5', 'k6', 'k7']) assert.match(skript, new RegExp('id="lm-' + s + '"'), s);
});

test('Am Boden: Rollen und Parkposition, nicht Start/Landung', () => {
  for (const p of ['TAXI_OUT', 'taxi_in', 'BOARDING', 'ARRIVED']) assert.equal(LM3.amBoden(p), true, p);
  for (const p of ['TAKEOFF', 'CRUISE', 'LANDING', null]) assert.equal(LM3.amBoden(p), false, String(p));
});

test('Geplante Fluege ohne die, die schon live fliegen', () => {
  const geplant = [{ callsign: 'DLH400' }, { callsign: 'CFG1' }, { callsign: '' }];
  const aktiv = [{ callsign: 'dlh400' }];
  assert.deepEqual(klar(LM3.geplanteOhneAktive(geplant, aktiv).map((g) => g.callsign)), ['CFG1', '']);
});

// Sortierung der Tafel (Thomas 03.11.2026: „blöd sortiert“ — GSG7 stand nach GSG650)
const restVon = (f) => (f.rest == null ? null : { rest: f.rest });
const cs = (l) => klar(l.map((x) => x.callsign));

test('Sortierung Standard: Phase in Flugreihenfolge, darin Rest-Strecke, offline unten', () => {
  const fl = [
    { callsign: 'X1', phase: 'CRUISE', rest: 900 },
    { callsign: 'X2', phase: 'TAXI_OUT', rest: null },
    { callsign: 'X3', phase: 'CRUISE', rest: 100, status: 'offline' },
    { callsign: 'X4', phase: 'APPROACH', rest: 20 },
    { callsign: 'X5', phase: 'CRUISE', rest: 300 },
    { callsign: 'X6', phase: 'GANZ_NEU', rest: 5 },
    { callsign: 'X7', phase: 'ON_BLOCK', rest: null }
  ];
  assert.deepEqual(cs(LM3.sortiereNach(fl, 'phase', 'auf', restVon)), ['X2', 'X5', 'X1', 'X4', 'X7', 'X6', 'X3']);
  // Gegenprobe: umgedreht — Phasen rueckwaerts, offline bleibt trotzdem unten
  assert.deepEqual(cs(LM3.sortiereNach(fl, 'phase', 'ab', restVon)), ['X6', 'X7', 'X4', 'X5', 'X1', 'X2', 'X3']);
  assert.equal(LM3.phasenRang('ON_BLOCK'), LM3.phasenRang('BLOCKS_ON'));
  assert.ok(LM3.phasenRang('LEVEL') > LM3.phasenRang('PIREP_SUBMITTED'), 'unbekannt ans Ende, nicht nach vorn');
});

test('Sortierung: Rufzeichen mit Zahlen natuerlich, fehlende Werte immer hinten', () => {
  const fl = [{ callsign: 'GSG650' }, { callsign: 'GSG7' }, { callsign: 'GSG101' }];
  assert.deepEqual(cs(LM3.sortiereNach(fl, 'flug', 'auf', null)), ['GSG7', 'GSG101', 'GSG650']);
  assert.deepEqual(cs(LM3.sortiereNach(fl, 'flug', 'ab', null)), ['GSG650', 'GSG101', 'GSG7']);
  const h = [{ callsign: 'A', pos: { alt_ft: 3000 } }, { callsign: 'B', pos: null }, { callsign: 'C', pos: { alt_ft: 35000 } }];
  assert.deepEqual(cs(LM3.sortiereNach(h, 'hoehe', 'auf', null)), ['A', 'C', 'B']);
  assert.deepEqual(cs(LM3.sortiereNach(h, 'hoehe', 'ab', null)), ['C', 'A', 'B']);
  // Zahlendifferenz 2 darf nicht als „fehlt“ gelten
  const g = [{ callsign: 'A', pos: { gs_kt: 512 } }, { callsign: 'B', pos: { gs_kt: 510 } }];
  assert.deepEqual(cs(LM3.sortiereNach(g, 'gs', 'ab', null)), ['A', 'B']);
  assert.deepEqual(cs(LM3.sortiereNach(g, 'gs', 'auf', null)), ['B', 'A']);
  // Gleichstand: Rufzeichen entscheidet, Eingabe bleibt unveraendert
  const gleich = [{ callsign: 'Z', pos: { gs_kt: 1 } }, { callsign: 'Y', pos: { gs_kt: 1 } }];
  assert.deepEqual(cs(LM3.sortiereNach(gleich, 'gs', 'ab', null)), ['Y', 'Z']);
  assert.deepEqual(cs(gleich), ['Z', 'Y']);
});

test('Kurze Stoerung haelt den letzten Stand, falscher Schluessel nicht', () => {
  for (const c of ['nicht_erreichbar', 'netz', 'ungueltig', 'phpvms']) assert.equal(LM3.fehlerIstVoruebergehend(c), true, c);
  for (const c of ['abgelehnt', 'kein_schluessel', 'adresse']) assert.equal(LM3.fehlerIstVoruebergehend(c), false, c);
});

test('Jeder Fehlercode des Servers hat im Widget einen deutschen und englischen Text', () => {
  const controller = fs.readFileSync(path.join(HIER, '..', 'LiveMap', 'Http', 'Controllers', 'LiveFlightsController.php'), 'utf8');
  const codes = new Set([...controller.matchAll(/'fehler'\s*=>\s*'([a-z_]+)'/g)].map((m) => m[1]));
  for (const c of ['ungueltig', 'phpvms', 'abgelehnt', 'nicht_erreichbar', 'adresse', 'kein_schluessel']) codes.add(c);
  assert.ok(codes.size >= 6);
  const bloecke = [...skript.matchAll(/fehler:\s*\{([^}]*)\}/g)].map((m) => m[1]);
  assert.equal(bloecke.length, 2, 'zwei Sprachen erwartet');
  for (const block of bloecke) for (const c of codes) assert.match(block, new RegExp('\\b' + c + ':'), c);
  // und jeder Code ist entweder "kurze Stoerung" (Stand halten) oder nicht — nie undefiniert
  for (const c of codes) assert.equal(typeof LM3.fehlerIstVoruebergehend(c), 'boolean', c);
});

// ── VATSIM/IVAO-Lotsen nach dem Radar-Verfahren (Vorlage aeroacars-live atcKarte.ts) ──
// Zeilen woertlich aus VATSpy.dat (vatspy-data-project, Stand 03.11.2026), nicht ausgedacht.
const VATSPY = [
  '[Countries]', 'Germany|ED|Center',
  '[Airports]',
  'KCLT|Charlotte- Douglas Intl NC|35.214011|-80.943125|CLT|KZTL|0',
  'CYYZ|Toronto-Pearson Intl|43.677222|-79.630556|YYZ|CZYZ|1',
  'EGHI|Solent|50.950261|-1.356803|SOLENT|EGTT|1',
  '[FIRs]',
  'ADR|Adria Radar||ADR',
  'ADR|Adria Radar (Upper FL335+)|ADR_U|ADR',
  'ADR-E|Adria Radar (East)|ADR_E|ADR-E',
  'ADR-E|Adria Radar (Upper East FL335+)|ADR_UE|ADR-E',
  'BIRD|Reykjavik||BIRD',
  'BIRD|Reykjavik|BIRD_S1|BIRD',
  'BIRD-E|Reykjavik ACC (East) - Reykjavik|BIRD_E|BIRD-E',
  'BIRD-E|Reykjavik ACC (East) - Reykjavik|BIRD_E1|BIRD-E',
  'EGTT|London|LON|EGTT',
  'LPPO|Santa Maria|LPAZ|LPPO',
  'KZNY|New York|NY|KZNY',
  '[UIRs]',
  'AFRE|East Africa Control|HAAA,HCSM,HHAA,HSSS,HJJJ',
  'AFRE_E|East Africa Control|HAAA,HCSM',
].join('\n');

test('Sprechfunk nur 117–137 MHz (Beobachter auf 199.998 zaehlt nicht)', () => {
  for (const f of ['118.305', '122.575', '117.000', '137.000']) assert.equal(LM3.aufSprechfrequenz(f), true, f);
  for (const f of ['199.998', '109.200', '116.999', '137.001', '', null]) assert.equal(LM3.aufSprechfrequenz(f), false, String(f));
});

test('VATSpy-Plaetze: Funk-Praefix vor ICAO, Behelf bleibt erreichbar', () => {
  const v = LM3.vatspyLesen(VATSPY);
  // CLT_GND und KCLT_ATIS zeigen auf dieselbe Stelle
  assert.deepEqual(klar(v.plaetze.CLT), [35.214011, -80.943125]);
  assert.deepEqual(klar(v.plaetze.KCLT), [35.214011, -80.943125]);
  assert.deepEqual(klar(v.plaetze.YYZ), [43.677222, -79.630556]);
  assert.deepEqual(klar(v.plaetze.CYYZ), [43.677222, -79.630556]);
  assert.deepEqual(klar(v.plaetze.SOLENT), [50.950261, -1.356803]);
  assert.equal(v.namen.KCLT, 'Charlotte- Douglas Intl NC');
  assert.equal(v.plaetze.ZZZZ, undefined);
});

test('FIR-Zuordnung: laengster Praefix gewinnt, Teilsektor statt ganzer FIR', () => {
  const v = LM3.vatspyLesen(VATSPY);
  const g = (ruf) => klar(LM3.grenzenFuerLotse(ruf, v.firNachWort, v.uirNachWort).kennungen);
  assert.deepEqual(g('ADR_E_CTR'), ['ADR-E']);
  assert.deepEqual(g('ADR_UE_CTR'), ['ADR-E']);
  assert.deepEqual(g('ADR_CTR'), ['ADR']);
  assert.deepEqual(g('BIRD_E1_CTR'), ['BIRD-E']);
  assert.deepEqual(g('BIRD_CTR'), ['BIRD']);
  assert.deepEqual(g('LON_S_CTR'), ['EGTT']);
  assert.deepEqual(g('LPPO_FSS'), ['LPPO']);
  assert.deepEqual(g('LPAZ_CTR'), ['LPPO']);   // Santa Maria funkt unter dem Praefix LPAZ
  assert.equal(LM3.grenzenFuerLotse('LON_S_CTR', v.firNachWort, v.uirNachWort).name, 'London');
  // Sammelposition aus [UIRs]: die laengste passende
  const afre = LM3.grenzenFuerLotse('AFRE_E_CTR', v.firNachWort, v.uirNachWort);
  assert.deepEqual(klar(afre.kennungen), ['HAAA', 'HCSM']);
  assert.equal(afre.uir, true);
  assert.deepEqual(g('AFRE_CTR'), ['HAAA', 'HCSM', 'HHAA', 'HSSS', 'HJJJ']);
  assert.deepEqual(g('ZZZZ_CTR'), []);
});

test('Doppelflaeche: _FSS bekommt die ozeanische, CTR die kontinentale', () => {
  const land = { properties: { id: 'KZNY', oceanic: '0' } }, meer = { properties: { id: 'KZNY', oceanic: '1' } };
  assert.equal(LM3.firFlaeche([land, meer], 'NY_FSS'), meer);
  assert.equal(LM3.firFlaeche([meer, land], 'NY_CTR'), land);
  assert.equal(LM3.firFlaeche([land], 'NY_FSS'), land);
  assert.equal(LM3.firFlaeche([], 'NY_CTR'), null);
});

test('Widget gruppiert Lotsen nach aufgeloester Stelle und laesst Beobachter ohne Sprechfunk weg', () => {
  // Quelltext-Pruefung der UI-Verdrahtung (der Teil laeuft nur im Browser)
  assert.match(skript, /function platzSchluessel\(prefix\)[\s\S]{0,200}'@'\s*\+\s*k\[0\]\.toFixed\(5\)/);
  assert.doesNotMatch(skript, /normalizeKey\(/);
  assert.match(skript, /c\.facility===0&&!\(window\.LM3\.aufSprechfrequenz\(c\.frequency\)&&platzZu\(prefix\)\)/);
  assert.match(skript, /if\(!window\.LM3\.aufSprechfrequenz\(a\.frequency\)\) return;/);
  assert.doesNotMatch(skript, /raw\.githubusercontent\.com\/vatsimnetwork/);
});

test('ETA-Rechnung: jetzt + Rest/GS in UTC, unter 30 kt leer', () => {
  const alt = process.env.TZ;
  process.env.TZ = 'Asia/Kolkata';                                 // UTC+5:30: Ortszeit waere sichtbar falsch
  try {
    const jetzt = Date.UTC(2026, 10, 12, 17, 40);
    assert.equal(LM3.etaText(450, 450, jetzt), '18:40z');          // 450 nm bei 450 kt = 60 min
    assert.equal(LM3.etaText(100, 30, jetzt), '21:00z');           // Schwelle zaehlt mit
    assert.equal(LM3.etaText(100, 29, jetzt), '');
    assert.equal(LM3.etaText(420, 420, Date.UTC(2026, 10, 12, 23, 30)), '00:30z'); // ueber Mitternacht
    assert.equal(LM3.etaText(null, 400, jetzt), '');
    assert.equal(LM3.etaText(100, NaN, jetzt), '');
    assert.equal(LM3.etaMinuten(200, 400), 30);
  } finally { if (alt === undefined) delete process.env.TZ; else process.env.TZ = alt; }
});

test('ETA je Flug: nur ab Reiseflug, nicht offline, nicht veraltet', () => {
  const jetzt = Date.UTC(2026, 10, 12, 17, 40);
  const weg = { rest: 676 };
  const f = (phase, gs, extra, vs = 2000) => ({ phase, status: 'online', last_seen: jetzt - 5000, pos: { gs_kt: gs, vs_fpm: vs }, ...extra });
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450), weg, jetzt), '19:10z');
  for (const p of ['DESCENT', 'APPROACH', 'FINAL', 'LANDING', 'HOLDING', 'cruise']) assert.notEqual(LM3.etaFuerFlug(f(p, 300), weg, jetzt), '', p);
  // Startlauf und Steigflug: GS noch klein, ETA waere Stunden zu spaet (Abnahme 5.2.0)
  for (const p of ['TAKEOFF_ROLL', 'TAKEOFF', 'CLIMB', 'TAXI_OUT', 'BOARDING', 'TAXI_IN', null]) assert.equal(LM3.etaFuerFlug(f(p, 140), weg, jetzt), '', String(p));
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450, { status: 'offline' }), weg, jetzt), '');
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450, { last_seen: jetzt - 11 * 60000 }), weg, jetzt), '');   // Sim abgestuerzt, PIREP haengt
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450, { last_seen: jetzt - 9 * 60000 }), weg, jetzt), '19:10z');
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450, { last_seen: null }), weg, jetzt), '19:10z');            // ohne Zeitstempel nicht verwerfen
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450), null, jetzt), '');
  // Rueckfall nur bei unbekannter Phase (fremder Client): Geradeausflug, >= 150 kt, >= 10 000 ft
  const g = (phase, gs, vs, alt) => ({ phase, status: 'online', last_seen: jetzt - 5000, pos: { gs_kt: gs, vs_fpm: vs, alt_ft: alt } });
  assert.equal(LM3.etaFuerFlug(g(null, 450, -300, 36000), weg, jetzt), '19:10z');
  assert.equal(LM3.etaFuerFlug(g('XYZ', 450, 0, 36000), weg, jetzt), '19:10z');
  assert.equal(LM3.etaFuerFlug(g(null, 160, 0, 300), weg, jetzt), '');               // Startlauf ohne Phase: zu tief
  assert.equal(LM3.etaFuerFlug(g(null, 140, 0, 36000), weg, jetzt), '');             // zu langsam
  assert.equal(LM3.etaFuerFlug(g(null, 450, 1500, 36000), weg, jetzt), '');          // steigt
  assert.equal(LM3.etaFuerFlug(g(null, 450, null, 36000), weg, jetzt), '');          // ohne V/S kein Rueckfall
  // Bekannte Start-/Steigphasen nie, auch im Waagerechtflug (Abfangen im Steigflug, phpVMS TOF → TAKEOFF)
  for (const p of ['CLIMB', 'TAKEOFF', 'TAKEOFF_ROLL', 'TAXI_OUT', 'TAXI_IN', 'BLOCKS_ON', 'ON_BLOCK'])
    assert.equal(LM3.etaFuerFlug(g(p, 280, 0, 12000), weg, jetzt), '', p);
  assert.equal(LM3.etaFuerFlug(f('CRUISE', 450, { pos: null }), weg, jetzt), '');
  // Anzeige „Level" (Steig-/Sinkphase, Phasen-Engine meldet Geradeausflug): stabile GS,
  // also ETA. GSG 1524 am 12.11.: C172 BGJN→BGAA auf 2300 ft blieb in CLIMB/level.
  const lv = (phase, seg) => ({ phase, shadow_segment: seg, status: 'online', last_seen: jetzt - 5000, pos: { gs_kt: 106, vs_fpm: 7, alt_ft: 2283 } });
  assert.equal(LM3.etaFuerFlug({ ...lv('CLIMB', 'level') }, { rest: 38 }, jetzt), '18:01z');
  assert.equal(LM3.etaFuerFlug(lv('CLIMB', 'climbing'), { rest: 38 }, jetzt), '');
  assert.equal(LM3.etaFuerFlug(lv('CLIMB', null), { rest: 38 }, jetzt), '');
  assert.equal(LM3.etaFuerFlug(lv('TAKEOFF', 'level'), { rest: 38 }, jetzt), '');   // nur Steig-/Sinkphase wird „Level"
  // Jet faengt im Steigflug ab: keine ETA (waere bei langer Reststrecke Stunden zu spaet);
  // ueber 5000 ft AGL wird daraus nach 4–10 min von selbst Cruise.
  const jet = (alt, gs, agl) => ({ phase: 'CLIMB', shadow_segment: 'level', status: 'online', last_seen: jetzt - 5000, pos: { gs_kt: gs, vs_fpm: 0, alt_ft: alt, agl_ft: agl } });
  assert.equal(LM3.etaFuerFlug(jet(10000, 290), { rest: 3300 }, jetzt), '');
  assert.equal(LM3.etaFuerFlug(jet(3000, 210), { rest: 580 }, jetzt), '');            // tief, aber schnell
  assert.equal(LM3.etaFuerFlug(jet(6500, 150, 6000), { rest: 40 }, jetzt), '');       // langsam, aber ueber 5000 ft AGL
  assert.equal(LM3.etaFuerFlug(jet(5500, 110, 2100), { rest: 38 }, jetzt), '18:00z'); // hoher Platz: AGL zaehlt
});

test('Tafel-Raster: jede Stufe passt in die Breite, ab der sie gilt', () => {
  // Mindestbreite = feste/Mindest-px der Spalten + 6 px Spaltenabstand + 28 px
  // Zeilen-Innenabstand + 2 px Rahmen. Abnahme 5.2.0: Typ/ETA machten die Tafel
  // breiter als die Stufe, Pilot/Phase wurden abgeschnitten.
  const css = fs.readFileSync(path.join(HIER, '..', 'LiveMap', 'Resources', 'views', 'partials', 'styles.blade.php'), 'utf8');
  const spalten = (sp) => sp.split(/\s+(?![^()]*\))/).filter(Boolean);
  const mindest = (sp) => {
    const t = spalten(sp);
    const px = t.reduce((s, x) => { const m = x.match(/^(?:minmax\()?(\d+)px/); return s + (m ? +m[1] : 0); }, 0);
    return px + 6 * (t.length - 1) + 28 + 2;
  };
  const stufen = [...css.matchAll(/@container lmmitte \(max-width: (\d+)px\)\s*\{([\s\S]*?)\n\s*\}\n/g)]
    .map((m) => ({ max: +m[1], sp: (m[2].match(/--lm-sp:\s*([^;]+);/) || [])[1] }));
  assert.ok(stufen.length >= 3, 'Stufen nicht gefunden');
  const voll = [...css.matchAll(/^\s*--lm-sp:\s*([^;]+);/gm)].map((m) => m[1]);   // oben und unten, ausserhalb der Stufen
  assert.equal(voll.length, 2);
  for (const sp of voll) assert.ok(mindest(sp) <= stufen[0].max + 1, 'volles Raster ' + mindest(sp) + ' px > ' + (stufen[0].max + 1));
  // Tafel unten ab 1000 px (5.2.2): eigenes Raster mit Mindestbreiten fuer lange Namen
  const breit = css.match(/@container lmmitte \(min-width: (\d+)px\)\s*\{[\s\S]*?--lm-sp:\s*([^;]+);/);
  assert.ok(breit, 'Raster ab 1000 px nicht gefunden');
  assert.equal(spalten(breit[2]).length, 9, 'Raster ab ' + breit[1] + ' px: 9 Spalten');
  assert.ok(mindest(breit[2]) <= +breit[1], 'Raster ab ' + breit[1] + ' px braucht ' + mindest(breit[2]) + ' px');
  // Neue Spalten (5.2) fallen zuerst: wo Pilot/Rest/Hoehe/GS fehlen, fehlen auch
  // Typ und ETA; Pilot faellt nicht frueher als in 5.1.1 (Stufe 720 px).
  const versteckt = (m) => new Set([...m.matchAll(/\.lm3-sp-(\w+)/g)].map((x) => x[1]));
  const bloecke = [...css.matchAll(/@container lmmitte \(max-width: (\d+)px\)\s*\{([\s\S]*?)\n\s*\}\n/g)];
  for (const [, max, inhalt] of bloecke) {
    const weg = versteckt(inhalt.split('\n').filter((z) => z.includes('display: none') && z.includes('.lm3-tafel')).join(' '));
    if (['pilot', 'dist', 'alt', 'gs'].some((k) => weg.has(k))) assert.ok(weg.has('typ') && weg.has('eta'), 'Stufe ' + max + ' versteckt alte Spalten vor Typ/ETA');
    if (weg.has('pilot')) assert.ok(+max <= 720, 'Pilot faellt schon bei ' + max + ' px (5.1.1: 720)');
    assert.equal(spalten(stufen.find((x) => x.max === +max).sp).length, 9 - weg.size, 'Stufe ' + max + ': Raster und sichtbare Spalten');
  }
  for (let i = 0; i < stufen.length - 1; i++) {
    assert.ok(stufen[i].sp, 'Stufe ' + stufen[i].max + ' ohne Raster');
    assert.ok(mindest(stufen[i].sp) <= stufen[i + 1].max + 1, 'Stufe ' + stufen[i].max + ': ' + mindest(stufen[i].sp) + ' px > ' + (stufen[i + 1].max + 1));
  }
});

test('Tafel unten: Raster ab 1000 px trifft nur unten, Zeilen ohne eigenen Rollbalken', () => {
  // Abnahme 5.2.2: das breite Raster und die 15-px-Schrift gelten nur fuer die
  // Lage unten; ein innerer Rollbalken (15a5bc4) verschob unter Windows die
  // Zeilen gegen den Kopf.
  const css = fs.readFileSync(path.join(HIER, '..', 'LiveMap', 'Resources', 'views', 'partials', 'styles.blade.php'), 'utf8');
  const block = css.match(/@container lmmitte \(min-width: \d+px\)\s*\{([\s\S]*?)\n\s*\}\n/);
  assert.ok(block, 'Block ab 1000 px nicht gefunden');
  const selektoren = [...block[1].matchAll(/^\s*([^{}\n]+?)\s*\{/gm)].map((m) => m[1]);
  assert.ok(selektoren.length >= 4, 'Regeln im Block nicht gefunden');
  for (const s of selektoren) for (const teil of s.split(',')) assert.match(teil.trim(), /^\.lm3-unten\s/, 'Regel ab 1000 px ausserhalb der Tafel unten: ' + teil.trim());
  const grund = css.match(/^\s*\.lm3-unten \.lm3-zeilen\s*\{([^}]*)\}/m);
  assert.ok(grund, '.lm3-unten .lm3-zeilen nicht gefunden');
  assert.match(grund[1], /overflow:\s*visible/, 'Zeilen unten muessen overflow: visible haben');
  for (const r of [...css.matchAll(/\.lm3-unten \.lm3-zeilen\s*\{([^}]*)\}/g)].map((m) => m[1])) {
    assert.doesNotMatch(r, /overflow(-y)?:\s*(auto|scroll)/, 'Zeilen unten mit eigenem Rollbalken');
    assert.match(r, /max-height:\s*none/, 'Zeilen unten mit Hoehendeckel');
  }
});

test('Kartenhoehe und Lueckenausgleich: Zahlen von GSG (Thomas 12.11.2026)', () => {
  // GSG eingeloggt: Karte ab 158 px, Tafel mit 1 Flug 110 + Signatur 27.
  const p = (fenster, tafel) => LM3.kartenHoehe({ fenster, oben: 158, untenFest: 0, tafel, min: 420, max: 1000 });
  assert.equal(p(753, 137), 442);          // Chrome 1490x753: endet 16 px ueber dem Fensterrand
  assert.equal(p(728, 137), 420);          // Thomas' Edge: Mindesthoehe greift
  assert.equal(p(900, 137), 589);
  assert.equal(p(900, 600), 436);          // viele Fluege: Karte behaelt 60 %
  assert.equal(p(900, null), 726);         // Lage oben: keine Tafel unter der Karte
  assert.equal(LM3.kartenHoehe({ fenster: 3000, oben: 0, tafel: 100, min: 420, max: 1000 }), 1000);
  assert.equal(LM3.kartenHoehe({ fenster: 900, oben: -300, untenFest: 50, tafel: null }), 834);   // gescrollt: oben zaehlt nicht negativ
  // SPTheme: 93 px bis zur Fusszeile, Huelle (row) ohne eigenen Abstand → -77, Luecke danach 16
  assert.equal(LM3.lueckenAusgleich(93, 0), -77);
  assert.equal(LM3.lueckenAusgleich(93, 16), -61);
  assert.equal(LM3.lueckenAusgleich(16, 0), 0);
  assert.equal(LM3.lueckenAusgleich(4, 10), 10);   // engere Themes bleiben, wie sie sind
});

test('Sortierung nach ETA und Typ: fehlende Werte hinten', () => {
  const dep = { lat: 50, lon: 8 }, arr = { lat: 52, lon: 13 };
  const f = (id, gs, lat, typ) => ({ id, callsign: id, aircraft_icao: typ, phase: 'CRUISE', pos: { lat, lon: 10, gs_kt: gs } });
  const weg = (x) => LM3.fortschritt(dep, arr, x.pos);
  // C (450 kt, ~160 nm) kommt vor B (300 kt, ~130 nm); A steht
  const liste = [f('A', 0, 50.5, 'B738'), f('B', 300, 51, 'A320'), f('C', 450, 50.2, null),
    { ...f('D', 480, 50.1, 'A333'), phase: 'CLIMB' }];   // D: schnellste Rechnung, aber Steigflug → keine ETA, hinten
  assert.deepEqual(LM3.sortiereNach(liste, 'eta', 'auf', weg).map((x) => x.id), ['C', 'B', 'A', 'D']);
  assert.deepEqual(LM3.sortiereNach(liste, 'eta', 'ab', weg).map((x) => x.id), ['B', 'C', 'A', 'D']);
  assert.deepEqual(LM3.sortiereNach(liste, 'typ', 'auf', weg).map((x) => x.id), ['B', 'D', 'A', 'C']);
});

test('Tafelzeile: echte zeileAktiv liefert die Zellen in Kopf-Reihenfolge, ETA nach Regel', () => {
  const start = skript.indexOf('function zeileAktiv(f) {');
  const ende = skript.indexOf('function zeileGeplant(', start);
  assert.ok(start > 0 && ende > start, 'zeileAktiv nicht gefunden');
  const jetzt = Date.now();
  const knoten = () => ({ style: { setProperty() {} }, setAttribute() {}, addEventListener() {}, innerHTML: '' });
  const ctx = {
    LM3, LM_SPRACHE: 'de', LM_T: { pilot: 'Pilot' }, zustand: { auswahl: null }, waehle() {},
    document: { createElement: knoten },
    logoFuer: () => null, logoHtml: () => '',
    weg: (f) => LM3.fortschritt({ lat: 50, lon: 8 }, { lat: 41, lon: 2 }, f.pos),
    Date,
  };
  const esc = skript.slice(skript.indexOf('function lmEsc(s) {'), skript.indexOf('function lmKurzname('));
  vm.runInNewContext(esc + skript.slice(start, ende) + '; this.zeileAktiv = zeileAktiv;', ctx);
  const zeile = (phase, extra) => ctx.zeileAktiv({ id: 'x', callsign: 'GSG1', dep: 'EDDF', arr: 'LEBL', aircraft_icao: 'A20N', pilot_name: 'Max S.',
    phase, status: 'online', last_seen: jetzt - 5000, pos: { lat: 48, lon: 6, alt_ft: 36000, gs_kt: 450, vs_fpm: 0 }, ...extra }).innerHTML;

  // Spalten der Zeile (oberste Ebene) gegen die Spaltenkoepfe aus tafel.blade.php
  const tafel = fs.readFileSync(path.join(HIER, '..', 'LiveMap', 'Resources', 'views', 'partials', 'tafel.blade.php'), 'utf8');
  const kopfBlock = tafel.slice(tafel.indexOf('id="lm3-kopf-aktiv"'), tafel.indexOf('id="lm3-kopf-geplant"'));
  const kopf = [...kopfBlock.matchAll(/class="lm3-th(?: (lm3-sp-\w+))?" data-sort="(\w+)"/g)].map((m) => m[1] || '');
  const oben = (html) => { const out = []; let tiefe = 0; for (const m of html.matchAll(/<(\/?)span(?: class="([^"]*)")?[^>]*>/g)) { if (m[1]) tiefe--; else { if (tiefe === 0) out.push((m[2] || '').split(' ').find((k) => k.startsWith('lm3-sp-')) || ''); tiefe++; } } return out; };
  const html = zeile('CRUISE');
  assert.deepEqual(oben(html), kopf, 'Zellen der Zeile = Spaltenkoepfe');
  const css = fs.readFileSync(path.join(HIER, '..', 'LiveMap', 'Resources', 'views', 'partials', 'styles.blade.php'), 'utf8');
  for (const sp of [...css.matchAll(/^\s*--lm-sp:\s*([^;]+);/gm), ...css.matchAll(/@container lmmitte \(min-width: \d+px\)\s*\{[\s\S]*?--lm-sp:\s*([^;]+);/g)].map((m) => m[1]))
    assert.equal(sp.split(/\s+(?![^()]*\))/).length, kopf.length, 'volles Raster = Spaltenzahl');

  const zelle = (h, k) => { const m = h.match(new RegExp('<span class="' + k + '">([^<]*)</span>')); return m ? m[1] : null; };
  assert.equal(zelle(html, 'lm3-sp-typ'), 'A20N');
  assert.match(zelle(html, 'lm3-sp-eta'), /^\d\d:\d\dz$/);
  assert.equal(zelle(zeile('CLIMB'), 'lm3-sp-eta'), '');
  assert.equal(zelle(zeile('CRUISE', { status: 'offline' }), 'lm3-sp-eta'), '');
  assert.equal(zelle(zeile('CRUISE', { last_seen: jetzt - 11 * 60000 }), 'lm3-sp-eta'), '');
  assert.equal(zelle(zeile('CRUISE', { aircraft_icao: '<b>' }), 'lm3-sp-typ'), '&lt;b&gt;');   // escaped
});
