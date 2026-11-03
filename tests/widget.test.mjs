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

test('Sortierung: offline nach unten, sonst nach Rufzeichen', () => {
  const s = LM3.sortiere([{ callsign: 'B', status: 'offline' }, { callsign: 'C', status: 'online' }, { callsign: 'A', status: 'online' }]);
  assert.deepEqual(klar(s.map((x) => x.callsign)), ['A', 'C', 'B']);
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
