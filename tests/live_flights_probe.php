<?php

// Featuretests fuer LiveMap/Http/Controllers/LiveFlightsController.php —
// durch den echten Router des lokalen phpVMS (~/Claude/phpvms-7), mit
// Http::fake() statt echtem live.kant.ovh:
//
//     php tests/live_flights_probe.php
//
// Endet mit Code 1, sobald eine Pruefung scheitert.
//
// # Was die Probe NICHT anfasst
//
// - Keine Datenbank, kein Modul-Schalter, keine Einstellung des lokalen
//   phpVMS: Einstellungen und Flugquelle sind Attrappen im Speicher
//   (SettingRepository / AcarsRepository), der Zwischenspeicher ist `array`.
//   Danach ist nichts zurueckzusetzen.
// - Den Modulcode laedt sie aus DIESEM Checkout (vorne angehaengter Lader),
//   nicht aus phpvms-7/modules/LiveMap (dort kann ein aelterer Stand liegen).
// - Die Routen aus LiveMap/Http/Routes/web.php haengt sie selbst unter
//   /livemap in den Router (wie der ServiceProvider, nur ohne Modulschalter).
// - Niemals Auth::logout() hier (Gehirn: DiscourseConnect-Falle).

error_reporting(E_ALL & ~E_DEPRECATED); // PHP 8.5 meldet Altlasten in vendor/ des lokalen phpVMS
define('LARAVEL_START', microtime(true));
chdir(getenv('PHPVMS_DIR') ?: (getenv('HOME').'/Claude/phpvms-7'));
require 'vendor/autoload.php';

$modulOrdner = dirname(__DIR__).'/LiveMap';
spl_autoload_register(static function (string $class) use ($modulOrdner): void {
    $prefix = 'Modules\\LiveMap\\';
    if (str_starts_with($class, $prefix)) {
        $pfad = $modulOrdner.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($pfad)) {
            require_once $pfad;
        }
    }
}, true, true);

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Exceptions\SettingNotFound;
use App\Repositories\AcarsRepository;
use App\Repositories\SettingRepository;
use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\LiveMap\Http\Controllers\LiveFlightsController;

$app->instance('request', Request::create('/', 'GET'));
$kernel->bootstrap();
$protokoll = sys_get_temp_dir().'/livemap-probe-'.getmypid().'.log';
register_shutdown_function(static fn () => @unlink($protokoll));
// Zwischenspeicher im Speicher, Protokoll in eine Wegwerfdatei: storage/ des lokalen phpVMS bleibt unberuehrt.
config(['cache.default' => 'array', 'logging.default' => 'livemap_probe', 'logging.channels.livemap_probe' => ['driver' => 'single', 'path' => $protokoll, 'level' => 'debug']]);
$app['env'] = 'dev';

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
        echo "FAIL $name".($ist !== null ? ' — ist: '.json_encode($ist, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : '')."\n";
    }
}

// ── Attrappen ────────────────────────────────────────────────────────────

/** Einstellungen im Speicher; fehlender Schluessel = SettingNotFound wie in phpVMS. */
$einstellungen = [];
$attrappeEinstellungen = (new ReflectionClass(new class() extends SettingRepository {
    public function __construct()
    {
    }

    public function retrieve($key)
    {
        global $einstellungen;
        if (!array_key_exists($key, $einstellungen)) {
            throw new SettingNotFound($key.' not found');
        }

        return $einstellungen[$key];
    }
}))->newInstanceWithoutConstructor();
$app->instance(SettingRepository::class, $attrappeEinstellungen);

/** Laufende PIREPs der phpVMS-Quelle. */
$pireps = [];
$pirepsKaputt = false;
$app->instance(AcarsRepository::class, new class() {
    public function getPositions($liveTime = 0)
    {
        global $pireps, $pirepsKaputt;
        if ($pirepsKaputt) {
            throw new RuntimeException('DB weg');
        }

        return collect($pireps);
    }

    public function forPirep($id, $type)
    {
        return collect([(object) ['lat' => 50.0, 'lon' => 8.0], (object) ['lat' => 50.5, 'lon' => 8.5]]);
    }
});

// Routen wie im ServiceProvider (Praefix livemap), nur ohne Modulschalter.
Route::group(['prefix' => 'livemap', 'namespace' => 'Modules\LiveMap\Http\Controllers'], function () use ($modulOrdner) {
    require $modulOrdner.'/Http/Routes/web.php';
});

const SCHLUESSEL = 'lm_GEHEIM-schluessel-0123456789abcdef';

/** Alle Antworttexte und Kopfzeilen der Probe — fuer die Schluessel-Pruefung am Ende. */
$alleAntworten = [];

function zurueck(): void
{
    global $einstellungen, $pireps, $pirepsKaputt;
    $einstellungen = [
        'acars.livemap_quelle'   => 'aeroacars',
        'acars.livemap_live_url' => 'https://live.kant.ovh',
        'acars.livemap_live_key' => SCHLUESSEL,
        'acars.live_time'        => 0,
    ];
    $pireps = [];
    $pirepsKaputt = false;
    Cache::flush();
    Carbon::setTestNow();
    live([]);
    $GLOBALS['app']['env'] = 'dev';
}

/** @return array{0: int, 1: array, 2: string, 3: ?string} Code, JSON, Rohtext, Cache-Control */
function hole(string $pfad): array
{
    global $kernel, $alleAntworten;
    $antwort = $kernel->handle(Request::create($pfad, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => '203.0.113.9']));
    $text = (string) $antwort->getContent();
    $alleAntworten[] = $text."\n".$antwort->headers;

    return [$antwort->getStatusCode(), json_decode($text, true) ?? [], $text, $antwort->headers->get('Cache-Control')];
}

/**
 * Frische Http-Attrappe mit den Regeln der Pruefung. Was keine Regel trifft,
 * landet in der Auffangregel am Ende (Antwort 599) und steht in `anzahlAnLive()`:
 * so zaehlt auch ein Abruf, der nirgends erwartet war. Ein erneutes
 * Http::fake() allein haette die zuerst eingetragene Regel behalten.
 */
function live(array $regeln): void
{
    Http::swap(new Illuminate\Http\Client\Factory());
    Http::fake($regeln + ['*' => Http::response('', 599)]);
}

/** Abrufe, die Http::fake() aufgezeichnet hat. @return \Illuminate\Http\Client\Request[] */
function gesendet(): array
{
    $liste = [];
    foreach (Http::recorded() as [$anfrage]) {
        $liste[] = $anfrage;
    }

    return $liste;
}

function anzahlAnLive(): int
{
    return count(gesendet());
}

function adressen(): array
{
    return array_map(fn ($a) => $a->url(), gesendet());
}

// Die schlanke Form von live (`flugFuerLivemap` in recorder/src/livemapSchluessel.ts):
// nur die Felder, die das Modul liest — kein Passwort, kein payload_json.
function liveListe(): array
{
    return [[
        'va_prefix' => 'GSG', 'pilot_id' => '1003', 'display_name' => 'Kenji M.',
        'callsign' => 'JAL7', 'aircraft_icao' => '', 'dep' => 'PANC', 'arr' => 'RJAA',
        'dep_gate' => 'N5', 'arr_gate' => null, 'current_phase' => 'CLIMB', 'status' => 'online',
        'last_seen' => 1793325000000, 'started_at' => 1793314200000,
        'last_position' => [
            'lat' => 55.49, 'lon' => 170.13, 'alt_ft' => 39000, 'ias_kt' => 260, 'gs_kt' => 505, 'vs_fpm' => 0,
            'hdg_true' => 238, 'mach' => 0.85, 'aircraft_icao' => 'B789', 'aircraft_registration' => 'JA861J',
            'simulator' => 'XPLANE12', 'ap_master' => true,
        ],
    ], [
        // Kennung mit Schraegstrich: gehoert nicht in eine Adresse → fliegt raus.
        'va_prefix' => 'GSG', 'pilot_id' => '../boese', 'callsign' => 'X', 'status' => 'online',
        'last_position' => ['lat' => 1.0, 'lon' => 1.0],
    ]];
}

// ═════════════════════════════════════════════════════════════════════════
// 1. Quelle phpVMS
// ═════════════════════════════════════════════════════════════════════════
zurueck();
$einstellungen['acars.livemap_quelle'] = 'phpvms';
$pireps = [(object) [
    'id' => 'PIREP0000000001', 'flight_number' => '1234', 'airline' => (object) ['icao' => 'GSG', 'logo' => null],
    // phpVMS: name_private ist der gekuerzte Name ("Anna F."), name der volle.
    // id/pilot_id hat jeder echte Nutzer (Zuordnung fuer Quelle „Beide“).
    'user' => (object) ['id' => 7, 'pilot_id' => 1003, 'name' => 'Anna Maria Fischer', 'name_private' => 'Anna F.'],
    'aircraft' => (object) ['icao' => 'A320', 'registration' => 'D-AGSG'], 'source_name' => 'AeroACARS',
    'dpt_airport_id' => 'EDDF', 'arr_airport_id' => 'EDDM', 'status' => 'ENR',
    'position' => (object) ['lat' => 49.5, 'lon' => 9.5, 'altitude' => 35000, 'ias' => 280, 'gs' => 450, 'vs' => 0, 'heading' => 120],
    'updated_at' => Carbon::now(), 'created_at' => Carbon::now(),
]];
[$code, $j] = hole('/livemap/api/fluege');
pruefe('phpVMS: 200, ok, Quelle phpvms', $code === 200 && ($j['ok'] ?? null) === true && ($j['quelle'] ?? '') === 'phpvms', [$code, $j]);
pruefe('phpVMS: ein Flug, Kennung phpvms/<PIREP>, Rufzeichen GSG1234', count($j['fluege'] ?? []) === 1 && ($j['fluege'][0]['id'] ?? '') === 'phpvms/PIREP0000000001' && ($j['fluege'][0]['callsign'] ?? '') === 'GSG1234', $j['fluege'] ?? null);
pruefe('phpVMS: Phase ENR → CRUISE, Name gekuerzt, voller Name nicht in der Antwort', ($j['fluege'][0]['phase'] ?? '') === 'CRUISE' && ($j['fluege'][0]['pilot_name'] ?? '') === 'Anna F.' && !str_contains(json_encode($j), 'Fischer'), [$j['fluege'][0]['phase'] ?? null, $j['fluege'][0]['pilot_name'] ?? null]);
pruefe('phpVMS: kein Abruf an live', anzahlAnLive() === 0, anzahlAnLive());
[$code, $j] = hole('/livemap/api/fluege/phpvms/PIREP0000000001/spur');
pruefe('phpVMS-Spur: 200, zwei Punkte, kein Abruf an live', $code === 200 && count($j['punkte'] ?? []) === 2 && anzahlAnLive() === 0, [$code, $j, anzahlAnLive()]);
// Bei Quelle phpVMS wird fuer eine fremde VA nie bei live nachgefragt
[$code, $j] = hole('/livemap/api/fluege/GSG/1003/spur');
pruefe('Quelle phpVMS: Spur einer Live-Kennung → 404, kein Abruf', $code === 404 && anzahlAnLive() === 0, [$code, anzahlAnLive()]);
[$code, $j] = hole('/livemap/api/fluege/GSG/1003/route');
pruefe('Quelle phpVMS: Route → ok, leer, kein Abruf', $code === 200 && ($j['punkte'] ?? null) === [] && anzahlAnLive() === 0, [$code, $j, anzahlAnLive()]);

// Standard (Einstellung fehlt ganz) und Muell → phpvms, nie still aeroacars
zurueck();
unset($einstellungen['acars.livemap_quelle']);
pruefe('Quelle: fehlende Einstellung → phpvms', LiveFlightsController::quelle() === 'phpvms');
$einstellungen['acars.livemap_quelle'] = '  AeroACARS ';
pruefe('Quelle: Grossschreibung/Leerzeichen → aeroacars', LiveFlightsController::quelle() === 'aeroacars');
$einstellungen['acars.livemap_quelle'] = 'irgendwas';
pruefe('Quelle: unbekannter Wert → phpvms', LiveFlightsController::quelle() === 'phpvms');

// phpVMS-Abfrage scheitert → 503 "phpvms", nicht "niemand fliegt"
zurueck();
$einstellungen['acars.livemap_quelle'] = 'phpvms';
$pirepsKaputt = true;
[$code, $j] = hole('/livemap/api/fluege');
pruefe('phpVMS-Abfrage scheitert: 503, ok=false, fehler=phpvms', $code === 503 && ($j['ok'] ?? null) === false && ($j['fehler'] ?? '') === 'phpvms' && ($j['fluege'] ?? null) === [], [$code, $j]);

// ═════════════════════════════════════════════════════════════════════════
// 1b. Quelle „Beide“ (5.1): AeroACARS + phpVMS, derselbe Flug einmal
// ═════════════════════════════════════════════════════════════════════════
// live `pilot_id` ist users.id (aeroacars-live provision.ts) — zugeordnet wird ueber user->id.
$beidePirep = fn (string $id, string $nr, int $userId, int $pilotNr = 1) => (object) [
    'id' => $id, 'flight_number' => $nr, 'airline' => (object) ['icao' => 'JAL', 'logo' => null],
    'user' => (object) ['id' => $userId, 'pilot_id' => $pilotNr, 'name' => 'Kenji Mori', 'name_private' => 'Kenji M.'],
    'aircraft' => (object) ['icao' => 'B789', 'registration' => 'JA861J'], 'source_name' => 'AeroACARS',
    'dpt_airport_id' => 'PANC', 'arr_airport_id' => 'RJAA', 'status' => 'ENR',
    'position' => (object) ['lat' => 55.0, 'lon' => 170.0, 'altitude' => 39000, 'ias' => 260, 'gs' => 505, 'vs' => 0, 'heading' => 238],
    'updated_at' => Carbon::now(), 'created_at' => Carbon::now(),
];
zurueck();
$einstellungen['acars.livemap_quelle'] = 'beide';
$pireps = [$beidePirep('PIREPJAL7', '7', 1003), $beidePirep('PIREPGSG9', '9', 2000)];
live(['live.kant.ovh/api/flights' => Http::response(liveListe(), 200)]);
[$code, $j, $roh] = hole('/livemap/api/fluege');
$ids = array_column($j['fluege'] ?? [], 'id');
pruefe('Beide: 200, ok, Quelle beide, ein Flug zugeordnet', $code === 200 && ($j['quelle'] ?? '') === 'beide' && ($j['zugeordnet'] ?? null) === 1, [$code, $j['zugeordnet'] ?? null]);
pruefe('Beide: JAL7 einmal (von live), der andere PIREP dazu', $ids === ['GSG/1003', 'phpvms/PIREPGSG9'], $ids);
pruefe('Beide: PIREP-Link am live-Flug', str_ends_with((string) ($j['fluege'][0]['pirep_url'] ?? ''), '/pireps/PIREPJAL7'), $j['fluege'][0]['pirep_url'] ?? null);
pruefe('Beide: interne Zuordnung (_pilot) verlaesst den Server nicht', !str_contains($roh, '_pilot'));
// Gegenprobe: Pilotnummer 1003 eines anderen Kontos (users.id 55) ist nicht derselbe Pilot
zurueck();
$einstellungen['acars.livemap_quelle'] = 'beide';
$pireps = [$beidePirep('PIREPJAL7', '7', 55, 1003)];
live(['live.kant.ovh/api/flights' => Http::response(liveListe(), 200)]);
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Beide: Pilotnummer eines fremden Kontos ordnet nicht zu', $code === 200 && ($j['zugeordnet'] ?? null) === 0 && count($j['fluege'] ?? []) === 2, [$code, $j['zugeordnet'] ?? null]);
pruefe('Beide: ohne Stoerung keine Warnung', !array_key_exists('warnung', $j));
// Flughaefen: je ICAO ein Puffer-Eintrag (auch unbekannte, als leerer Eintrag), keine Kombinations-Schluessel
pruefe('Flughaefen: je ICAO gepuffert', is_array($j['flughaefen'] ?? null) && Cache::has('livemap:flughafen:PANC') && Cache::has('livemap:flughafen:RJAA'), array_keys($j['flughaefen'] ?? []));

zurueck();
$einstellungen['acars.livemap_quelle'] = 'beide';
$pireps = [$beidePirep('PIREPJAL7', '7', 1003)];
live(['live.kant.ovh/api/flights' => Http::response('', 500)]);
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Beide, live gestoert: phpVMS-Flug + Warnung statt leer', $code === 200 && ($j['ok'] ?? null) === true
    && array_column($j['fluege'] ?? [], 'id') === ['phpvms/PIREPJAL7'] && !empty($j['warnung']) && $j['warnung'] !== 'phpvms', [$code, $j]);

zurueck();
$einstellungen['acars.livemap_quelle'] = 'beide';
$pirepsKaputt = true;
live(['live.kant.ovh/api/flights' => Http::response(liveListe(), 200)]);
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Beide, phpVMS gestoert: live-Flug + Warnung phpvms', $code === 200 && array_column($j['fluege'] ?? [], 'id') === ['GSG/1003'] && ($j['warnung'] ?? '') === 'phpvms', [$code, $j]);

zurueck();
$einstellungen['acars.livemap_quelle'] = 'beide';
$pirepsKaputt = true;
live(['live.kant.ovh/api/flights' => Http::response('', 500)]);
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Beide, beide gestoert: 503, ok=false', $code === 503 && ($j['ok'] ?? null) === false && ($j['fluege'] ?? null) === [], [$code, $j]);

// ═════════════════════════════════════════════════════════════════════════
// 2. Quelle AeroACARS Live, gueltiger Schluessel
// ═════════════════════════════════════════════════════════════════════════
zurueck();
live(['live.kant.ovh/api/flights' => Http::response(liveListe(), 200)]);
[$code, $j, , $cc] = hole('/livemap/api/fluege');
$f = $j['fluege'][0] ?? [];
pruefe('Live: 200, ok, Quelle aeroacars', $code === 200 && ($j['ok'] ?? null) === true && ($j['quelle'] ?? '') === 'aeroacars', [$code, $j]);
pruefe('Live: normalisiert — gueltiger Flug bleibt, "../boese" faellt weg', count($j['fluege'] ?? []) === 1 && ($f['id'] ?? '') === 'GSG/1003', $j['fluege'] ?? null);
pruefe('Live: Flugzeug aus der Position, Flughafen, Name, Phase', ($f['aircraft_icao'] ?? '') === 'B789' && ($f['dep'] ?? '') === 'PANC' && ($f['pilot_name'] ?? '') === 'Kenji M.' && ($f['phase'] ?? '') === 'CLIMB', $f);
pruefe('Live: Position mit Hoehe/Mach/AP', ($f['pos']['alt_ft'] ?? 0) === 39000 && ($f['pos']['mach'] ?? 0) === 0.85 && ($f['pos']['ap_master'] ?? null) === true, $f['pos'] ?? null);
$an = gesendet();
pruefe('Live: genau ein Abruf, GET https://live.kant.ovh/api/flights', count($an) === 1 && $an[0]->method() === 'GET' && $an[0]->url() === 'https://live.kant.ovh/api/flights', adressen());
pruefe('Live: Authorization Bearer <Schluessel>', ($an[0]->header('Authorization')[0] ?? '') === 'Bearer '.SCHLUESSEL, $an[0]->header('Authorization'));
pruefe('Live: Kopfzeile User-Agent GSG-LiveMap/5, Accept JSON', ($an[0]->header('User-Agent')[0] ?? '') === 'GSG-LiveMap/5' && str_contains($an[0]->header('Accept')[0] ?? '', 'application/json'));
pruefe('Antwort an den Browser: Cache-Control no-store', is_string($cc) && str_contains($cc, 'no-store'), $cc);

// Spur + Route (gleicher Schluessel, eigene Pfade)
zurueck();
live([
    'live.kant.ovh/api/flights/GSG/1003/positions' => Http::response([['lat' => 61.0, 'lon' => 179.5], ['lat' => 61.0, 'lon' => -179.5]], 200),
    'live.kant.ovh/api/flights/GSG/1003/route'     => Http::response(['waypoints' => [['name' => 'NIPPI', 'lat' => 50, 'lon' => 179.9]]], 200),
]);
[$code, $j] = hole('/livemap/api/fluege/GSG/1003/spur');
pruefe('Spur: 200, zwei Punkte', $code === 200 && ($j['punkte'] ?? null) == [[61.0, 179.5], [61.0, -179.5]], [$code, $j]);
[$code, $j] = hole('/livemap/api/fluege/GSG/1003/route');
pruefe('Route: 200, Wegpunkt NIPPI', $code === 200 && ($j['punkte'] ?? null) == [[50.0, 179.9, 'NIPPI']], [$code, $j]);
pruefe('Spur/Route gehen nur an die zwei freigegebenen Pfade', adressen() === ['https://live.kant.ovh/api/flights/GSG/1003/positions', 'https://live.kant.ovh/api/flights/GSG/1003/route'], adressen());
pruefe('Spur/Route tragen den Schluessel', gesendet()[0]->header('Authorization')[0] === 'Bearer '.SCHLUESSEL && gesendet()[1]->header('Authorization')[0] === 'Bearer '.SCHLUESSEL);

// ═════════════════════════════════════════════════════════════════════════
// 3. live lehnt den Schluessel ab
// ═════════════════════════════════════════════════════════════════════════
foreach ([401, 403] as $st) {
    zurueck();
    live(['live.kant.ovh/*' => Http::response(['error' => 'unauthorized', 'echo' => SCHLUESSEL], $st)]);
    [$code, $j, $roh] = hole('/livemap/api/fluege');
    pruefe("Live $st: 503, ok=false, fehler=abgelehnt, keine Fluege", $code === 503 && ($j['ok'] ?? null) === false && ($j['fehler'] ?? '') === 'abgelehnt' && ($j['fluege'] ?? null) === [], [$code, $j]);
    pruefe("Live $st: Antworttext von live wird nicht durchgereicht", !str_contains($roh, 'unauthorized'), $roh);
}
// Der Client behandelt "abgelehnt" nicht als kurze Stoerung (leert die Karte): tests/widget.test.mjs.

// ═════════════════════════════════════════════════════════════════════════
// 4. Kein Schluessel
// ═════════════════════════════════════════════════════════════════════════
foreach (['leer' => '', 'nur Leerzeichen' => "  \t ", 'fehlt' => null] as $name => $wert) {
    zurueck();
    if ($wert === null) {
        unset($einstellungen['acars.livemap_live_key']);
    } else {
        $einstellungen['acars.livemap_live_key'] = $wert;
    }
    live(['*' => Http::response(liveListe(), 200)]);
    [$code, $j] = hole('/livemap/api/fluege');
    pruefe("Kein Schluessel ($name): 503, fehler=kein_schluessel", $code === 503 && ($j['fehler'] ?? '') === 'kein_schluessel' && ($j['fluege'] ?? null) === [], [$code, $j]);
    pruefe("Kein Schluessel ($name): live wird gar nicht gefragt", anzahlAnLive() === 0, anzahlAnLive());
}
zurueck();
unset($einstellungen['acars.livemap_live_key']);
live(['*' => Http::response([], 200)]);
hole('/livemap/api/fluege/GSG/1003/spur');
hole('/livemap/api/fluege/GSG/1003/route');
pruefe('Kein Schluessel: auch Spur und Route fragen live nicht', anzahlAnLive() === 0);

// ═════════════════════════════════════════════════════════════════════════
// 5. Host ≠ live.kant.ovh wird nie angefragt
// ═════════════════════════════════════════════════════════════════════════
$fremde = [
    'http://live.kant.ovh'               => 'falsches Schema (http)',
    'https://live.kant.ovh.evil.example' => 'Host nur als Anfang',
    'https://evil.example'               => 'fremder Host',
    'https://user:pw@live.kant.ovh'      => 'Zugangsdaten in der Adresse',
    'https://live.kant.ovh@evil.example' => 'Host nach dem @',
    'https://live.kant.ovh/pfad'         => 'Pfad',
    'https://live.kant.ovh?x=1'          => 'Abfrage',
    'https://live.kant.ovh#x'            => 'Fragment',
    'https://live.kant.ovh:8443'         => 'anderer Port',
    'https://169.254.169.254'            => 'Metadaten-Adresse',
    'https://localhost'                  => 'localhost per https',
    'http://127.0.0.1:8080'              => 'lokal, aber Produktion',
    'ftp://live.kant.ovh'                => 'anderes Schema',
    '//live.kant.ovh'                    => 'ohne Schema',
];
foreach ($fremde as $adresse => $grund) {
    zurueck();
    $app['env'] = 'production'; // lokale Pruefserver sind nur ausserhalb der Produktion erlaubt
    $einstellungen['acars.livemap_live_url'] = $adresse;
    live(['*' => Http::response(liveListe(), 200)]);
    [$code, $j, $roh] = hole('/livemap/api/fluege');
    $ok = $code === 503 && ($j['fehler'] ?? '') === 'adresse' && anzahlAnLive() === 0 && !str_contains($roh, SCHLUESSEL);
    pruefe("Adresse $adresse ($grund): 503 adresse, kein Abruf", $ok, [$code, $j['fehler'] ?? null, anzahlAnLive()]);
}
// Spur/Route mit manipulierter Adresse ebenso
zurueck();
$einstellungen['acars.livemap_live_url'] = 'https://evil.example';
live(['*' => Http::response([], 200)]);
hole('/livemap/api/fluege/GSG/1003/spur');
hole('/livemap/api/fluege/GSG/1003/route');
pruefe('Fremde Adresse: auch Spur und Route fragen niemanden', anzahlAnLive() === 0, anzahlAnLive());

// Gegenprobe: die erlaubten Formen werden gefragt
zurueck();
foreach (['https://live.kant.ovh', 'https://live.kant.ovh/', 'HTTPS://LIVE.KANT.OVH', 'https://live.kant.ovh:443', '  https://live.kant.ovh  '] as $adresse) {
    Cache::flush();
    live(['*' => Http::response(liveListe(), 200)]);
    $einstellungen['acars.livemap_live_url'] = $adresse;
    [$code] = hole('/livemap/api/fluege');
    $u = gesendet()[0] ?? null;
    pruefe("Adresse '".trim($adresse)."' ist erlaubt und geht an https://live.kant.ovh/api/flights", $code === 200 && $u !== null && $u->url() === 'https://live.kant.ovh/api/flights', [$code, $u ? $u->url() : null]);
}
zurueck();
$einstellungen['acars.livemap_live_url'] = '';
live(['*' => Http::response(liveListe(), 200)]);
hole('/livemap/api/fluege');
pruefe('Leere Adresse → Standard live.kant.ovh', adressen() === ['https://live.kant.ovh/api/flights'], adressen());
// Pruefserver nur ausserhalb der Produktion
zurueck();
$einstellungen['acars.livemap_live_url'] = 'http://127.0.0.1:9';
live(['127.0.0.1:9/*' => Http::response(liveListe(), 200)]);
[$code] = hole('/livemap/api/fluege');
pruefe('Pruefserver http://127.0.0.1:9 ausserhalb der Produktion erlaubt', $code === 200 && adressen() === ['http://127.0.0.1:9/api/flights'], [$code, adressen()]);

// Weiterleitung: nicht folgen (der Schluessel ginge sonst mit), kein zweiter Abruf
zurueck();
live(['live.kant.ovh/*' => Http::response('', 302, ['Location' => 'https://evil.example/api/flights']), '*' => Http::response(liveListe(), 200)]);
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Weiterleitung: nicht_erreichbar, nur der eine Abruf an live', $code === 503 && ($j['fehler'] ?? '') === 'nicht_erreichbar' && adressen() === ['https://live.kant.ovh/api/flights'], [$code, $j['fehler'] ?? null, adressen()]);

// ═════════════════════════════════════════════════════════════════════════
// 6. Pfad-Ausbruch
// ═════════════════════════════════════════════════════════════════════════
$boese = [
    '/livemap/api/fluege/GSG/..%2Fadmin%2Fx/spur',
    '/livemap/api/fluege/GSG/..%2F..%2Fapi%2Fadmin/route',
    '/livemap/api/fluege/..%2Fx/1003/spur',
    '/livemap/api/fluege/GSG/%2e%2e%2fx/spur',
    '/livemap/api/fluege/GSG/1003%2F..%2Fadmin/spur',
    '/livemap/api/fluege/GSG/../x/spur',
    '/livemap/api/fluege/GSG/1003%00/spur',
    '/livemap/api/fluege/GSG/1003%3Fx%3D1/route',
    '/livemap/api/fluege/GSG/'.str_repeat('a', 41).'/spur',
    '/livemap/api/fluege/GSG/a%20b/spur',
];
foreach ($boese as $pfad) {
    zurueck();
    live(['*' => Http::response([], 200)]);
    [$code] = hole($pfad);
    pruefe("Pfad $pfad → 404 und kein Abruf an live", $code === 404 && anzahlAnLive() === 0, [$code, anzahlAnLive()]);
}
// Die Sperre im Controller selbst (falls die Route je lockerer wird): direkter Aufruf
zurueck();
live(['*' => Http::response([], 200)]);
$c = new LiveFlightsController();
foreach (['../x', 'a/b', "a\n", '', str_repeat('a', 41), '%2e%2e'] as $kennung) {
    $s = $c->spur('GSG', $kennung);
    $r = $c->route($kennung, '1003');
    pruefe('Controller: Kennung '.json_encode($kennung).' → 404 (Spur und Route)', $s->getStatusCode() === 404 && $r->getStatusCode() === 404, [$s->getStatusCode(), $r->getStatusCode()]);
}
pruefe('Controller: bei verbotener Kennung kein Abruf', anzahlAnLive() === 0, anzahlAnLive());
// Gegenprobe: eine gueltige Kennung mit allen erlaubten Sonderzeichen geht durch
zurueck();
live(['*' => Http::response([], 200)]);
$c->spur('V-A.1', 'p_2');
pruefe('Gueltige Kennung V-A.1/p_2 geht unveraendert in den Pfad', adressen() === ['https://live.kant.ovh/api/flights/V-A.1/p_2/positions'], adressen());

// ═════════════════════════════════════════════════════════════════════════
// 7. Zwischenspeicher 8 s haengt an Adresse + Schluessel
// ═════════════════════════════════════════════════════════════════════════
zurueck();
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));
live(['*' => Http::response(liveListe(), 200)]);
hole('/livemap/api/fluege');
hole('/livemap/api/fluege');
pruefe('Puffer: zwei Besuche, ein Abruf', anzahlAnLive() === 1, anzahlAnLive());
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:07'));
hole('/livemap/api/fluege');
pruefe('Puffer: nach 7 s noch gepuffert', anzahlAnLive() === 1, anzahlAnLive());
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:09'));
hole('/livemap/api/fluege');
pruefe('Puffer: nach 9 s neuer Abruf', anzahlAnLive() === 2, anzahlAnLive());

// Schluessel gewechselt → sofort frisch, auch innerhalb der 8 s
zurueck();
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));
live(['*' => Http::response(liveListe(), 200)]);
hole('/livemap/api/fluege');
$einstellungen['acars.livemap_live_key'] = 'lm_NEUER-schluessel';
hole('/livemap/api/fluege');
$an = gesendet();
pruefe('Puffer: Schluesselwechsel → sofort neuer Abruf mit dem neuen Schluessel', count($an) === 2 && ($an[1]->header('Authorization')[0] ?? '') === 'Bearer lm_NEUER-schluessel', array_map(fn ($a) => $a->header('Authorization')[0] ?? null, $an));
hole('/livemap/api/fluege');
pruefe('Puffer: neuer Schluessel wird danach selbst gepuffert', anzahlAnLive() === 2, anzahlAnLive());
$einstellungen['acars.livemap_live_key'] = SCHLUESSEL;
hole('/livemap/api/fluege');
pruefe('Puffer: der alte Schluessel hat seinen eigenen Eintrag', anzahlAnLive() === 2, anzahlAnLive());

// Adresse gewechselt (nur Pruefserver) → sofort frisch
zurueck();
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));
live(['*' => Http::response(liveListe(), 200)]);
hole('/livemap/api/fluege');
$einstellungen['acars.livemap_live_url'] = 'http://127.0.0.1:9';
hole('/livemap/api/fluege');
$einstellungen['acars.livemap_live_url'] = 'http://localhost:9';
hole('/livemap/api/fluege');
pruefe('Puffer: Adresswechsel → jedes Mal ein eigener Abruf', adressen() === ['https://live.kant.ovh/api/flights', 'http://127.0.0.1:9/api/flights', 'http://localhost:9/api/flights'], adressen());

// Fehler werden kurz gepuffert (8 s), nicht pro Besuch neu gerufen
zurueck();
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));
live(['*' => Http::response('', 401)]);
hole('/livemap/api/fluege');
hole('/livemap/api/fluege');
pruefe('Puffer: abgelehnt wird 8 s gemerkt (ein Abruf)', anzahlAnLive() === 1, anzahlAnLive());
$einstellungen['acars.livemap_live_key'] = 'lm_zweiter-versuch';
hole('/livemap/api/fluege');
pruefe('Puffer: nach Ablehnung und Schluesselwechsel sofort neuer Versuch', anzahlAnLive() === 2, anzahlAnLive());
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:09'));
hole('/livemap/api/fluege');
pruefe('Puffer: Ablehnung wird nach 9 s neu geprueft', anzahlAnLive() === 3, anzahlAnLive());

// Spur: eigener Puffer je Flug und Schluessel
zurueck();
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));
live(['*' => Http::response([['lat' => 1, 'lon' => 2]], 200)]);
hole('/livemap/api/fluege/GSG/1003/spur');
hole('/livemap/api/fluege/GSG/1003/spur');
hole('/livemap/api/fluege/GSG/1004/spur');
pruefe('Puffer Spur: je Flug ein Eintrag', anzahlAnLive() === 2, anzahlAnLive());
$einstellungen['acars.livemap_live_key'] = 'lm_anderer';
hole('/livemap/api/fluege/GSG/1003/spur');
pruefe('Puffer Spur: Schluesselwechsel → neuer Abruf', anzahlAnLive() === 3, anzahlAnLive());

// ═════════════════════════════════════════════════════════════════════════
// 8. Stoerung: der Server gibt keinen alten Stand aus (503, ok=false, keine
//    Fluege); den letzten guten Stand haelt der Client und markiert ihn
//    "veraltet" — Regel in tests/widget.test.mjs.
// ═════════════════════════════════════════════════════════════════════════
zurueck();
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));
live(['*' => Http::response(liveListe(), 200)]);
[$code, $gut] = hole('/livemap/api/fluege');
pruefe('Vor der Stoerung: guter Stand mit einem Flug', $code === 200 && count($gut['fluege'] ?? []) === 1);
live(['*' => Http::response('boom '.SCHLUESSEL, 500)]);
Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:10'));
[$code, $j, $roh] = hole('/livemap/api/fluege');
pruefe('Stoerung (HTTP 500): 503, ok=false, fehler=nicht_erreichbar, keine Fluege', $code === 503 && ($j['ok'] ?? null) === false && ($j['fehler'] ?? '') === 'nicht_erreichbar' && ($j['fluege'] ?? null) === [], [$code, $j]);
pruefe('Stoerung: Fehlertext von live wird nicht durchgereicht', !str_contains($roh, 'boom'), $roh);
live(['*' => function ($anfrage) {
    return new GuzzleHttp\Promise\RejectedPromise(new ConnectException('cURL error 28: Operation timed out '.SCHLUESSEL, $anfrage->toPsrRequest()));
}]);
Cache::flush();
[$code, $j, $roh] = hole('/livemap/api/fluege');
pruefe('Stoerung (Zeitueberschreitung): 503, fehler=nicht_erreichbar', $code === 503 && ($j['fehler'] ?? '') === 'nicht_erreichbar' && ($j['fluege'] ?? null) === [], [$code, $j]);
pruefe('Stoerung: Ausnahmetext (mit Schluessel) bleibt serverseitig', !str_contains($roh, 'cURL') && !str_contains($roh, SCHLUESSEL), $roh);
zurueck();
live(['*' => Http::response('<html>Wartung</html>', 200)]);
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Antwort ohne JSON: 503, fehler=ungueltig', $code === 503 && ($j['fehler'] ?? '') === 'ungueltig', [$code, $j]);
live(['*' => Http::response([], 200)]);
Cache::flush();
[$code, $j] = hole('/livemap/api/fluege');
pruefe('Leere Liste von live ist kein Fehler: 200, ok, keine Fluege', $code === 200 && ($j['ok'] ?? null) === true && ($j['fluege'] ?? null) === [], [$code, $j]);

// ═════════════════════════════════════════════════════════════════════════
// 9. Schluessel nie in HTML/JSON
// ═════════════════════════════════════════════════════════════════════════
$geleakt = [];
foreach ($alleAntworten as $i => $text) {
    foreach ([SCHLUESSEL, 'lm_NEUER', 'lm_zweiter', 'lm_anderer', 'Bearer'] as $nadel) {
        if (str_contains($text, $nadel)) {
            $geleakt[] = [$i, $nadel];
        }
    }
}
pruefe('Schluessel in keiner der '.count($alleAntworten).' Antworten (Text und Kopfzeilen)', $geleakt === [], $geleakt);

// Die Verwaltungsseite zeigt den gespeicherten Schluessel nie wieder an
$stub = sys_get_temp_dir().'/livemap-probe-'.getmypid();
@mkdir($stub.'/layouts', 0777, true);
file_put_contents($stub.'/layouts/admin.blade.php', "@yield('content')\n");
$ansichtenOrdner = config('view.compiled');
$ansichtenVorher = glob($ansichtenOrdner.'/*.php') ?: [];
$viewFehler = null;
$html = null;
// Statusfelder, die mit dem Schluessel nichts zu tun haben: jede Abfrage liefert wieder so ein leeres Ding.
$leer = new class() extends ArrayObject {
    public function offsetExists(mixed $k): bool
    {
        return true;
    }

    public function offsetGet(mixed $k): mixed
    {
        return $this;
    }

    public function __toString(): string
    {
        return '';
    }
};
try {
    app('view')->addNamespace('livemap', [$stub, $modulOrdner.'/Resources/views']);
    $html = view('livemap::admin.index', [
        'settings' => ['acars.livemap_live_key' => SCHLUESSEL, 'acars.livemap_quelle' => 'aeroacars', 'acars.livemap_live_url' => 'https://live.kant.ovh'],
        'layerOptions' => [], 'basemapOptions' => [], 'weatherProxyStatus' => $leer, 'cartoStatus' => $leer, 'acarsLiveTimeStatus' => $leer, 'errors' => new Illuminate\Support\ViewErrorBag(),
    ])->render();
} catch (Throwable $e) {
    $viewFehler = get_class($e).': '.mb_substr($e->getMessage(), 0, 200);
}
if ($html === null) {
    pruefe('Verwaltungsseite lässt sich rendern', false, $viewFehler);
} else {
    pruefe('Verwaltungsseite gerendert, Schluessel gespeichert', strlen($html) > 500 && str_contains($html, 'live_key'), strlen($html));
    pruefe('Verwaltungsseite: gespeicherter Schluessel steht nirgends im HTML', !str_contains($html, SCHLUESSEL));
    pruefe('Verwaltungsseite: Schluesselfeld ist ein Passwortfeld ohne Wert', preg_match('/<input type="password" id="live_key"[^>]*value=""/s', $html) === 1);
}
// uebersetzte Ansichten wieder entfernen: storage/ des lokalen phpVMS bleibt, wie es war
foreach (array_diff(glob($ansichtenOrdner.'/*.php') ?: [], $ansichtenVorher) as $neu) {
    @unlink($neu);
}
array_map('unlink', glob($stub.'/layouts/*') ?: []);
@rmdir($stub.'/layouts');
@rmdir($stub);
// Das Karten-Widget (Theme-Datei + alle Ansichten im Modul) darf den Schluessel gar nicht kennen
$widgetDateien = array_merge([dirname(__DIR__).'/live_map.blade.php', dirname(__DIR__).'/LiveMap/Resources/views/widget.blade.php'],
    glob(dirname(__DIR__).'/LiveMap/Resources/views/partials/*.blade.php'));
pruefe('Karten-Widget: alle Ansichten gefunden (Theme-Datei, widget, 4 Teile)', count(array_filter($widgetDateien, 'is_file')) === 6, $widgetDateien);
$widgetQuellen = '';
foreach ($widgetDateien as $d) {
    $widgetQuellen .= file_get_contents($d);
}
pruefe('Karten-Widget: Quelltext kennt weder live_key noch livemap_live_key noch Bearer', !preg_match('/live_key|livemap_live_key|Bearer/i', $widgetQuellen));

// 5.1 Tafel-Lage: Update von 4.7.x ohne neue Einstellung → aus den Altschluesseln
$lage = [\Modules\LiveMap\Http\Controllers\Admin\SettingsController::class, 'tafelLageAusAlt'];
pruefe('Tafel-Lage alt: Modern (old_style aus, Tafel an) → top', $lage(false, true) === 'top');
pruefe('Tafel-Lage alt: Old Style (old_style an) → off', $lage('1', '0') === 'off');
pruefe('Tafel-Lage alt: Tafel-Schalter aus → off', $lage('0', '0') === 'off');
pruefe('Tafel-Lage alt: nur old_style aus gesetzt → top', $lage(false, null) === 'top');
pruefe('Tafel-Lage: frische Installation (beide Altschluessel fehlen) → bottom', $lage(null, null) === 'bottom');
pruefe('Tafel-Lage: leere Altschluessel wie fehlend → bottom', $lage('', '') === 'bottom');
pruefe('Tafel-Lage alt: nur Tafel-Schalter an gesetzt → top', $lage(null, '1') === 'top');
pruefe('Tafel unten: Teil tafel.blade.php in markup oben UND unten eingebunden',
    substr_count(file_get_contents(dirname(__DIR__).'/LiveMap/Resources/views/partials/markup.blade.php'), "@include('livemap::partials.tafel')") === 2);

// ─────────────────────────────────────────────────────────────────────────
Carbon::setTestNow();
echo "\n$anzahl Pruefungen, $fehler Fehler\n";
exit($fehler > 0 ? 1 : 0);
