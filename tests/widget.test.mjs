// Tests fuer die reinen Hilfsfunktionen der Live-Karte (live_map_scripts.blade.php,
// Block zwischen /* <lm3-rein> */ und /* </lm3-rein> */). Ohne Browser, ohne Pakete:
//
//     node --test tests/
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
const skript = fs.readFileSync(path.join(HIER, '..', 'live_map_scripts.blade.php'), 'utf8');
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

test('Geplante Fluege ohne die, die schon live fliegen', () => {
  const geplant = [{ callsign: 'DLH400' }, { callsign: 'CFG1' }, { callsign: '' }];
  const aktiv = [{ callsign: 'dlh400' }];
  assert.deepEqual(klar(LM3.geplanteOhneAktive(geplant, aktiv).map((g) => g.callsign)), ['CFG1', '']);
});

test('Sortierung: offline nach unten, sonst nach Rufzeichen', () => {
  const s = LM3.sortiere([{ callsign: 'B', status: 'offline' }, { callsign: 'C', status: 'online' }, { callsign: 'A', status: 'online' }]);
  assert.deepEqual(klar(s.map((x) => x.callsign)), ['A', 'C', 'B']);
});
