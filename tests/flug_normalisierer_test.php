<?php

// Tests fuer LiveMap/Support/FlugNormalisierer.php — ohne Laravel, ohne Pakete:
//
//     php tests/flug_normalisierer_test.php
//
// Endet mit Code 1, sobald eine Pruefung scheitert.

require __DIR__.'/../LiveMap/Support/FlugNormalisierer.php';

use Modules\LiveMap\Support\FlugNormalisierer as N;

$fehler = 0;
$anzahl = 0;
function pruefe(string $name, bool $ok, $ist = null): void
{
    global $fehler, $anzahl;
    $anzahl++;
    if ($ok) {
        echo "ok   $name\n";
    } else {
        $fehler++;
        echo "FAIL $name".($ist !== null ? ' — ist: '.json_encode($ist, JSON_UNESCAPED_UNICODE) : '')."\n";
    }
}

// Ein Eintrag, wie live ihn liefert (flightRowToDto), plus Felder, die NICHT
// auf die oeffentliche Seite gehoeren.
$live = [
    'va_prefix' => 'GSG', 'pilot_id' => '1003', 'display_name' => 'Kenji Mori',
    'callsign' => 'JAL7', 'aircraft_icao' => '', 'dep' => 'panc', 'arr' => 'RJAA',
    'dep_gate' => 'N5', 'arr_gate' => null, 'current_phase' => 'CLIMB', 'status' => 'online',
    'last_seen' => 1793325000000, 'started_at' => 1793314200000, 'shadow_segment' => 'level',
    'acars' => 'AeroACARS',
    'mqtt_password' => 'darf-nie-raus', 'payload_json' => '{"geheim":1}',
    'last_position' => [
        'lat' => 55.49, 'lon' => 170.13, 'alt_ft' => 39000, 'ias_kt' => 260, 'gs_kt' => 505, 'vs_fpm' => 0,
        'hdg_true' => 238, 'mach' => 0.85, 'qnh_hpa' => 1013.25, 'aircraft_icao' => 'B789',
        'aircraft_registration' => 'JA861J', 'simulator' => 'XPLANE12', 'spoilers_armed' => null,
        'ap_master' => true, 'gear_position' => 0, 'interne_notiz' => 'nicht zeigen',
    ],
];
$f = N::ausLive($live);

pruefe('Kennung va/pilot', $f['id'] === 'GSG/1003', $f['id']);
pruefe('Flugzeug aus der Position, wenn die Spalte leer ist (wie pickAircraftLabel)', $f['aircraft_icao'] === 'B789', $f['aircraft_icao']);
pruefe('Flughafen in Grossbuchstaben', $f['dep'] === 'PANC', $f['dep']);
pruefe('rohe Phase + Level-Segment durchgereicht', $f['phase'] === 'CLIMB' && $f['shadow_segment'] === 'level', [$f['phase'], $f['shadow_segment']]);
pruefe('Name gekuerzt: voller Nachname verlaesst den Server nicht', $f['pilot_name'] === 'Kenji M.', $f['pilot_name']);
pruefe('Sim und Kennzeichen aus der Position', $f['sim'] === 'XPLANE12' && $f['registration'] === 'JA861J');
pruefe('Stand Abflug, Ankunft leer', $f['dep_gate'] === 'N5' && $f['arr_gate'] === null);
pruefe('Position mit Werten der Webapp', $f['pos']['alt_ft'] === 39000 && $f['pos']['mach'] === 0.85 && $f['pos']['ap_master'] === true);
pruefe('SPL unbekannt bleibt unbekannt (null, nicht false)', $f['pos']['spoilers_armed'] === null);

$json = json_encode($f);
pruefe('nichts ausser der Feldliste: kein Passwort', strpos($json, 'darf-nie-raus') === false);
pruefe('nichts ausser der Feldliste: kein payload_json', strpos($json, 'geheim') === false);
pruefe('nichts ausser der Feldliste: keine unbekannten Positionsfelder', strpos($json, 'interne_notiz') === false);
pruefe('Feldliste genau festgelegt', array_keys($f) === [
    'id', 'va', 'pilot', 'callsign', 'pilot_name', 'aircraft_icao', 'registration', 'sim', 'acars',
    'dep', 'arr', 'dep_gate', 'arr_gate', 'phase', 'shadow_segment', 'status', 'last_seen', 'started_at', 'pos',
], array_keys($f));

// 0/0 ist keine Position (Golf von Guinea), Gabun schon
$nullinsel = $live;
$nullinsel['last_position']['lat'] = 0.0;
$nullinsel['last_position']['lon'] = 0.0;
pruefe('0/0 → keine Position, Flug bleibt in der Liste', N::ausLive($nullinsel)['pos'] === null);
pruefe('0°N/9.7°O (Libreville) ist gueltig', N::nutzbar(0.0, 9.7));
pruefe('ueber 180 ist ungueltig', !N::nutzbar(10, 181));

// Kennungen, die in eine Adresse gehen
pruefe('Kennung mit Schraegstrich abgelehnt', N::ausLive(array_merge($live, ['pilot_id' => '../x'])) === null);
pruefe('Kennung mit Zeilenumbruch am Ende abgelehnt ("$" erlaubt sonst ein \n)', !N::kennungGueltig("1003\n"));
pruefe('Kennung zu lang abgelehnt', !N::kennungGueltig(str_repeat('a', 41)));
pruefe('Steuerzeichen aus Texten entfernt', N::ausLive(array_merge($live, ['callsign' => "JAL\x007"]))['callsign'] === 'JAL7');
pruefe('Phase mit fremden Zeichen verworfen', N::ausLive(array_merge($live, ['current_phase' => '<b>']))['phase'] === null);
pruefe('status offline bleibt offline, alles andere online', N::ausLive(array_merge($live, ['status' => 'offline']))['status'] === 'offline'
    && N::ausLive(array_merge($live, ['status' => 'weird']))['status'] === 'online');

// Kurzname
pruefe('Kurzname mehrteilig', N::kurzname('Anna Maria Fischer') === 'Anna F.', N::kurzname('Anna Maria Fischer'));
pruefe('Kurzname einteilig', N::kurzname('Kenji') === 'Kenji');
pruefe('Kurzname leer', N::kurzname('  ') === null);

// Spur: unbrauchbare Punkte raus, Laengen bleiben roh (Weiterzaehlen macht das Widget)
$spur = N::spur([['lat' => 61, 'lon' => 179.5], ['lat' => 0, 'lon' => 0], ['lat' => 61, 'lon' => -179.5], 'mist', ['lat' => null, 'lon' => 3]]);
pruefe('Spur ohne 0/0 und ohne kaputte Zeilen', $spur === [[61.0, 179.5], [61.0, -179.5]], $spur);

$route = N::route(['waypoints' => [['ident' => 'PANC', 'lat' => 61.17, 'lon' => -149.99], ['name' => 'NIPPI', 'lat' => 50, 'lon' => 179.9], ['lat' => 'x']]]);
pruefe('Route mit ident/name', $route === [[61.17, -149.99, 'PANC'], [50.0, 179.9, 'NIPPI']], $route);
pruefe('leere Route', N::route(['waypoints' => [], 'count' => 0, 'source' => null]) === []);

// Quelle phpVMS: Status → Phase der Webapp
$soll = ['BST' => 'BOARDING', 'PBT' => 'PUSHBACK', 'TXI' => 'TAXI_OUT', 'TOF' => 'TAKEOFF', 'ICL' => 'CLIMB',
    'ENR' => 'CRUISE', 'TEN' => 'APPROACH', 'FIN' => 'FINAL', 'LDG' => 'LANDING', 'LAN' => 'TAXI_IN',
    'ONB' => 'BLOCKS_ON', 'ARR' => 'ARRIVED', 'PSD' => null];
$alle = true;
foreach ($soll as $code => $phase) {
    $alle = $alle && N::phaseAusPhpvmsStatus($code) === $phase;
}
pruefe('phpVMS-Status → Phase', $alle);
$p = N::ausPhpvms(['pirep_id' => 'abc123', 'callsign' => 'GSG100', 'status' => 'ENR', 'lat' => 50, 'lon' => 8, 'altitude' => 35000, 'pilot_name' => 'Paul N.']);
// Beide gehen nur ueber oeffentlich() hinaus (LiveFlightsController::mitBeiwerk)
pruefe('phpVMS-Flug in derselben oeffentlichen Form', $p['id'] === 'phpvms/abc123' && $p['phase'] === 'CRUISE' && $p['pos']['alt_ft'] === 35000
    && array_keys(N::oeffentlich($p)) === array_keys(N::oeffentlich($f)), array_keys(N::oeffentlich($p)));
pruefe('oeffentlich: interne Pilotenzuordnung raus, Logo/PIREP-Link immer da', !array_key_exists('_pilot', N::oeffentlich($p))
    && array_key_exists('logo', N::oeffentlich($f)) && N::oeffentlich($f)['pirep_url'] === null);
$voll = N::ausPhpvms(['pirep_id' => 'abc124', 'callsign' => 'GSG101', 'status' => 'ENR', 'lat' => 50, 'lon' => 8, 'pilot_name' => 'Paul Peter Neumann']);
pruefe('phpVMS ohne name_private: voller Name wird gekürzt', $voll['pilot_name'] === 'Paul N.', $voll['pilot_name']);

// Quelle „Beide“: gleicher Pilot UND gleiches Rufzeichen (live mit Leerzeichen, phpVMS ohne)
$liveFlug = N::ausLive(array_merge($live, ['pilot_id' => '42', 'callsign' => 'ITY 4532', 'dep' => 'LIRF', 'arr' => 'EDDF']));
$pirep = N::ausPhpvms(['pirep_id' => 'p1', 'callsign' => 'ITY4532', 'status' => 'ENR', 'lat' => 48, 'lon' => 10,
    'user_id' => '7', 'user_pilot_id' => '42', 'registration' => 'EI-DTA', 'pirep_url' => 'https://va.test/pireps/p1', 'logo' => '/l/ity.png']);
$z = N::zusammenfuehren([$liveFlug], [$pirep]);
pruefe('Beide: ein Flug statt zwei, als zugeordnet gezaehlt', count($z['fluege']) === 1 && $z['zugeordnet'] === 1, $z['zugeordnet']);
pruefe('Beide: der AeroACARS-Flug bleibt (Phase/Position von live)', $z['fluege'][0]['id'] === 'GSG/42');
pruefe('Beide: PIREP-Link und Logo vom PIREP, eigene Registrierung bleibt', $z['fluege'][0]['pirep_url'] === 'https://va.test/pireps/p1'
    && $z['fluege'][0]['logo'] === '/l/ity.png' && $z['fluege'][0]['registration'] === 'JA861J', $z['fluege'][0]);
$ohneReg = N::ausLive(array_merge($live, ['pilot_id' => '42', 'callsign' => 'ITY 4532', 'last_position' => ['lat' => 48, 'lon' => 10]]));
pruefe('Beide: Registrierung vom PIREP, wenn live keine hat', N::zusammenfuehren([$ohneReg], [$pirep])['fluege'][0]['registration'] === 'EI-DTA');
$fremd = N::zusammenfuehren([N::ausLive(array_merge($live, ['pilot_id' => '43', 'callsign' => 'ITY4532']))], [$pirep]);
pruefe('Beide: gleiches Rufzeichen, anderer Pilot → zwei Fluege', count($fremd['fluege']) === 2 && $fremd['zugeordnet'] === 0);
$anderesCs = N::zusammenfuehren([N::ausLive(array_merge($live, ['pilot_id' => '42', 'callsign' => 'ITY4533']))], [$pirep]);
pruefe('Beide: gleicher Pilot, anderes Rufzeichen → zwei Fluege', count($anderesCs['fluege']) === 2);
pruefe('Beide: Pilot ueber users.id', N::zusammenfuehren([N::ausLive(array_merge($live, ['pilot_id' => '7', 'callsign' => 'ITY4532']))], [$pirep])['zugeordnet'] === 1);
$ohneCsLive = N::ausLive(array_merge($live, ['pilot_id' => '42', 'callsign' => '', 'dep' => 'LIRF', 'arr' => 'EDDF']));
$ohneCsPirep = N::ausPhpvms(['pirep_id' => 'p2', 'callsign' => '', 'status' => 'ENR', 'lat' => 48, 'lon' => 10, 'user_pilot_id' => '42', 'dep' => 'LIRF', 'arr' => 'EDDF']);
pruefe('Beide: ohne Rufzeichen reichen Pilot + Abflug + Ziel', N::zusammenfuehren([$ohneCsLive], [$ohneCsPirep])['zugeordnet'] === 1);
$andersZiel = N::ausPhpvms(['pirep_id' => 'p3', 'callsign' => '', 'status' => 'ENR', 'lat' => 48, 'lon' => 10, 'user_pilot_id' => '42', 'dep' => 'LIRF', 'arr' => 'EDDM']);
pruefe('Beide: ohne Rufzeichen, anderes Ziel → zwei Fluege', N::zusammenfuehren([$ohneCsLive], [$andersZiel])['zugeordnet'] === 0);
pruefe('Beide: ein PIREP wird nur einmal vergeben', N::zusammenfuehren([$liveFlug, $liveFlug], [$pirep])['zugeordnet'] === 1);
pruefe('Beide: ohne live nur die PIREPs', count(N::zusammenfuehren([], [$pirep, $ohneCsPirep])['fluege']) === 2);

// Logo nur fuer Kennungen, die es in airlines gibt; danach muss eine Ziffer folgen
$logos = ['DLH' => '/l/dlh.svg', 'BA' => '/l/ba.svg', 'BAW' => '/l/baw.svg'];
pruefe('Logo: DLH400', N::logoFuerRufzeichen('DLH400', $logos) === '/l/dlh.svg');
pruefe('Logo: Leerzeichen egal', N::logoFuerRufzeichen('dlh 400', $logos) === '/l/dlh.svg');
pruefe('Logo: laengster Treffer gewinnt (BAW vor BA)', N::logoFuerRufzeichen('BAW12', $logos) === '/l/baw.svg');
pruefe('Logo: BA12 ueber BA', N::logoFuerRufzeichen('BA12', $logos) === '/l/ba.svg');
pruefe('Logo: DLHX4 hat keine Ziffer nach der Kennung', N::logoFuerRufzeichen('DLHX4', $logos) === null);
pruefe('Logo: unbekannte Kennung', N::logoFuerRufzeichen('XYZ1', $logos) === null);
pruefe('Logo: leer/null', N::logoFuerRufzeichen(null, $logos) === null && N::logoFuerRufzeichen('', $logos) === null);
pruefe('Rufzeichen-Schluessel wie im Widget', N::rufzeichenSchluessel(' ity-4532 ') === 'ITY4532');

// Adresse von AeroACARS Live: der Schluessel darf nur an live.kant.ovh gehen (SSRF)
pruefe('Standardadresse', N::liveBasis('', false) === 'https://live.kant.ovh');
pruefe('live.kant.ovh mit Schraegstrich', N::liveBasis('https://live.kant.ovh/', false) === 'https://live.kant.ovh');
$verboten = ['https://evil.example', 'https://live.kant.ovh.evil.example', 'https://evil.example/live.kant.ovh',
    'http://live.kant.ovh', 'https://live.kant.ovh/api', 'https://user@live.kant.ovh', 'https://live.kant.ovh?x=1',
    'https://live.kant.ovh:8443', 'https://10.0.0.5', 'http://169.254.169.254', 'http://127.0.0.1:8792', 'javascript:alert(1)'];
$alleAb = true;
foreach ($verboten as $u) {
    if (N::liveBasis($u, false) !== null) {
        $alleAb = false;
        echo "   durchgelassen: $u\n";
    }
}
pruefe('fremde, interne und unsaubere Adressen abgelehnt (Produktion)', $alleAb);
pruefe('Pruefserver nur ausserhalb der Produktion', N::liveBasis('http://127.0.0.1:8792', true) === 'http://127.0.0.1:8792'
    && N::liveBasis('http://10.0.0.5:80', true) === null);

echo "\n$anzahl Pruefungen, $fehler fehlgeschlagen\n";
exit($fehler === 0 ? 0 : 1);
