<?php

namespace Modules\LiveMap\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Enums\AcarsType;
use App\Repositories\AcarsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\LiveMap\Support\FlugNormalisierer;

/**
 * Flugdaten fuer die Live-Karte — eine Adresse, zwei Quellen.
 *
 * # Quelle "aeroacars" (GSG, Pflichtenheft 3.0 P-11.11)
 *
 * Dieselbe Datenquelle wie die Admin-Webapp auf live.kant.ovh (F-020):
 *
 *     GET {basis}/api/flights                       Liste (flightRowToDto)
 *     GET {basis}/api/flights/{va}/{pilot}/positions geflogene Spur
 *     GET {basis}/api/flights/{va}/{pilot}/route     OFP-Wegpunkte
 *
 * mit `Authorization: Bearer <Karten-Schluessel>`. Der Schluessel liegt nur
 * hier auf dem Server (Einstellung `acars.livemap_live_key`), nie im Browser.
 *
 * ⚠ Stand 30.10.2026: Diese drei Routen nehmen auf live NUR die Admin-
 * Sitzung (`requireAuth`, Cookie) an, keinen Bearer. Bis live einen
 * Lese-Schluessel dafuer annimmt, antwortet live mit 401 und die Karte
 * zeigt „Schluessel abgelehnt". Das ist gewollt sichtbar, nicht still leer.
 *
 * # Quelle "phpvms" (Standard fuer fremde VAs)
 *
 * Laufende PIREPs aus der eigenen phpVMS-Datenbank — derselbe Bestand, den
 * frueher der phpVMS-Kern ueber `/api/acars` gezeigt hat.
 *
 * # Warum zwischengespeichert
 *
 * Jede Besucherin und jeder Besucher fragt alle paar Sekunden. Ohne Puffer
 * waere das je Besuch ein Abruf auf live; so ist es einer je Puffer-Fenster
 * fuer alle zusammen.
 */
class LiveFlightsController extends Controller
{
    public const STANDARD_BASIS = 'https://live.kant.ovh';

    private const PUFFER_LISTE_S = 8;
    private const PUFFER_SPUR_S = 15;
    private const PUFFER_ROUTE_S = 300;
    private const PUFFER_FEHLER_S = 8;
    private const ZEITLIMIT_S = 6;

    public function index(): JsonResponse
    {
        $quelle = $this->quelle();
        if ($quelle === 'phpvms') {
            return $this->antwort(['ok' => true, 'quelle' => 'phpvms', 'fluege' => $this->phpvmsFluege()]);
        }

        $ergebnis = $this->ausLive('/api/flights', 'livemap:live:fluege', self::PUFFER_LISTE_S);
        if (!$ergebnis['ok']) {
            return $this->antwort(['ok' => false, 'quelle' => 'aeroacars', 'fehler' => $ergebnis['fehler'], 'fluege' => []], 503);
        }

        $fluege = [];
        foreach (is_array($ergebnis['daten']) ? $ergebnis['daten'] : [] as $dto) {
            $f = FlugNormalisierer::ausLive($dto);
            if ($f !== null) {
                $fluege[] = $f;
            }
        }

        return $this->antwort(['ok' => true, 'quelle' => 'aeroacars', 'fluege' => $fluege]);
    }

    public function spur(string $va, string $pilot): JsonResponse
    {
        if (!FlugNormalisierer::kennungGueltig($va) || !FlugNormalisierer::kennungGueltig($pilot)) {
            return $this->antwort(['ok' => false, 'fehler' => 'ungueltig', 'punkte' => []], 404);
        }
        if ($va === 'phpvms') {
            return $this->antwort(['ok' => true, 'punkte' => $this->phpvmsSpur($pilot)]);
        }
        if ($this->quelle() !== 'aeroacars') {
            return $this->antwort(['ok' => false, 'fehler' => 'ungueltig', 'punkte' => []], 404);
        }

        $pfad = '/api/flights/'.rawurlencode($va).'/'.rawurlencode($pilot).'/positions';
        $ergebnis = $this->ausLive($pfad, 'livemap:live:spur:'.$va.':'.$pilot, self::PUFFER_SPUR_S);
        if (!$ergebnis['ok']) {
            return $this->antwort(['ok' => false, 'fehler' => $ergebnis['fehler'], 'punkte' => []], 503);
        }

        return $this->antwort(['ok' => true, 'punkte' => FlugNormalisierer::spur($ergebnis['daten'])]);
    }

    public function route(string $va, string $pilot): JsonResponse
    {
        if (!FlugNormalisierer::kennungGueltig($va) || !FlugNormalisierer::kennungGueltig($pilot)) {
            return $this->antwort(['ok' => false, 'fehler' => 'ungueltig', 'punkte' => []], 404);
        }
        if ($va === 'phpvms' || $this->quelle() !== 'aeroacars') {
            // phpVMS: die Route steht nur als Text im PIREP; ohne Navdaten keine Punkte.
            return $this->antwort(['ok' => true, 'punkte' => []]);
        }

        $pfad = '/api/flights/'.rawurlencode($va).'/'.rawurlencode($pilot).'/route';
        $ergebnis = $this->ausLive($pfad, 'livemap:live:route:'.$va.':'.$pilot, self::PUFFER_ROUTE_S);
        if (!$ergebnis['ok']) {
            return $this->antwort(['ok' => false, 'fehler' => $ergebnis['fehler'], 'punkte' => []], 503);
        }

        return $this->antwort(['ok' => true, 'punkte' => FlugNormalisierer::route($ergebnis['daten'])]);
    }

    /** 'aeroacars' | 'phpvms' */
    public static function quelle(): string
    {
        $wert = strtolower(trim((string) self::einstellung('acars.livemap_quelle', 'phpvms')));

        return $wert === 'aeroacars' ? 'aeroacars' : 'phpvms';
    }

    /**
     * Basisadresse von live. Nur https — ausser fuer einen Pruefserver auf
     * dieser Maschine (127.0.0.1/localhost), sonst ginge der Schluessel
     * unverschluesselt ueber die Leitung.
     */
    public static function basis(): ?string
    {
        $roh = rtrim(trim((string) self::einstellung('acars.livemap_live_url', self::STANDARD_BASIS)), '/');
        if ($roh === '') {
            $roh = self::STANDARD_BASIS;
        }
        $teile = parse_url($roh);
        if (!is_array($teile) || empty($teile['host']) || !empty($teile['user']) || !empty($teile['query'])) {
            return null;
        }
        $schema = strtolower((string) ($teile['scheme'] ?? ''));
        $host = strtolower((string) $teile['host']);
        $lokal = in_array($host, ['127.0.0.1', 'localhost'], true);
        if ($schema !== 'https' && !($schema === 'http' && $lokal)) {
            return null;
        }

        return $roh;
    }

    /** @return array{ok: bool, fehler?: string, daten?: mixed} */
    private function ausLive(string $pfad, string $pufferSchluessel, int $pufferSekunden): array
    {
        $gepuffert = Cache::get($pufferSchluessel);
        if (is_array($gepuffert) && array_key_exists('ok', $gepuffert)) {
            return $gepuffert;
        }

        $basis = self::basis();
        $schluessel = trim((string) self::einstellung('acars.livemap_live_key', ''));
        if ($basis === null) {
            return ['ok' => false, 'fehler' => 'adresse'];
        }
        if ($schluessel === '') {
            return ['ok' => false, 'fehler' => 'kein_schluessel'];
        }

        try {
            $antwort = Http::timeout(self::ZEITLIMIT_S)
                ->acceptJson()
                ->withToken($schluessel)
                ->withHeaders(['User-Agent' => 'GSG-LiveMap/5'])
                ->get($basis.$pfad);
        } catch (\Throwable $e) {
            Log::warning('LiveMap: live nicht erreichbar: '.mb_substr($e->getMessage(), 0, 160));
            $ergebnis = ['ok' => false, 'fehler' => 'nicht_erreichbar'];
            Cache::put($pufferSchluessel, $ergebnis, now()->addSeconds(self::PUFFER_FEHLER_S));

            return $ergebnis;
        }

        if ($antwort->status() === 401 || $antwort->status() === 403) {
            $ergebnis = ['ok' => false, 'fehler' => 'abgelehnt'];
        } elseif (!$antwort->successful()) {
            $ergebnis = ['ok' => false, 'fehler' => 'nicht_erreichbar'];
        } else {
            $daten = $antwort->json();
            $ergebnis = is_array($daten) ? ['ok' => true, 'daten' => $daten] : ['ok' => false, 'fehler' => 'ungueltig'];
        }

        Cache::put(
            $pufferSchluessel,
            $ergebnis,
            now()->addSeconds($ergebnis['ok'] ? $pufferSekunden : self::PUFFER_FEHLER_S)
        );

        return $ergebnis;
    }

    private function phpvmsFluege(): array
    {
        return Cache::remember('livemap:phpvms:fluege', now()->addSeconds(self::PUFFER_LISTE_S), function () {
            $fluege = [];
            try {
                $pireps = app(AcarsRepository::class)->getPositions(setting('acars.live_time', 0));
            } catch (\Throwable $e) {
                return [];
            }
            foreach ($pireps as $p) {
                $pos = $p->position;
                if ($pos === null) {
                    continue;
                }
                $nutzer = $p->user;
                $f = FlugNormalisierer::ausPhpvms([
                    'pirep_id'      => $p->id,
                    'callsign'      => optional($p->airline)->icao.$p->flight_number,
                    'pilot_name'    => $nutzer ? ($nutzer->name_private ?? $nutzer->name) : null,
                    'aircraft_icao' => optional($p->aircraft)->icao,
                    'registration'  => optional($p->aircraft)->registration,
                    'source_name'   => $p->source_name,
                    'dep'           => $p->dpt_airport_id,
                    'arr'           => $p->arr_airport_id,
                    'status'        => $p->status,
                    'lat'           => $pos->lat,
                    'lon'           => $pos->lon,
                    'altitude'      => $pos->altitude,
                    'ias'           => $pos->ias,
                    'gs'            => $pos->gs,
                    'vs'            => $pos->vs,
                    'heading'       => $pos->heading,
                    'updated_ms'    => optional($p->updated_at)->getTimestampMs(),
                    'created_ms'    => optional($p->created_at)->getTimestampMs(),
                ]);
                if ($f !== null) {
                    $fluege[] = $f;
                }
            }

            return $fluege;
        });
    }

    private function phpvmsSpur(string $pirepId): array
    {
        return Cache::remember('livemap:phpvms:spur:'.$pirepId, now()->addSeconds(self::PUFFER_SPUR_S), function () use ($pirepId) {
            try {
                $punkte = app(AcarsRepository::class)->forPirep($pirepId, AcarsType::FLIGHT_PATH);
            } catch (\Throwable $e) {
                return [];
            }

            return FlugNormalisierer::spur($punkte->map(fn ($a) => ['lat' => $a->lat, 'lon' => $a->lon])->all());
        });
    }

    private function antwort(array $daten, int $code = 200): JsonResponse
    {
        $daten['stand'] = (int) round(microtime(true) * 1000);

        return response()->json($daten, $code)->header('Cache-Control', 'no-store');
    }

    private static function einstellung(string $schluessel, $standard)
    {
        $wache = '__LIVEMAP_FEHLT__';
        $wert = setting($schluessel, $wache);

        return $wert === $wache ? $standard : $wert;
    }
}
