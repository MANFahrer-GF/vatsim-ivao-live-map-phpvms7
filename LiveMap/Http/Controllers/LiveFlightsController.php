<?php

namespace Modules\LiveMap\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Enums\AcarsType;
use App\Repositories\AcarsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\LiveMap\Support\FlugNormalisierer;
use Modules\LiveMap\Support\SharedCockpitQuelle;

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
            $fluege = $this->phpvmsFluege();
            if ($fluege === null) {
                return $this->antwort(['ok' => false, 'quelle' => 'phpvms', 'fehler' => 'phpvms', 'fluege' => []], 503);
            }

            return $this->mitBeiwerk(['ok' => true, 'quelle' => 'phpvms', 'fluege' => FlugNormalisierer::ohneSpurtraeger($fluege)]);
        }

        $live = $this->liveFluege();
        if ($quelle === 'aeroacars') {
            if (!$live['ok']) {
                return $this->antwort(['ok' => false, 'quelle' => 'aeroacars', 'fehler' => $live['fehler'], 'fluege' => []], 503);
            }

            return $this->mitBeiwerk(['ok' => true, 'quelle' => 'aeroacars', 'fluege' => $live['fluege']]);
        }

        // „Beide“: faellt eine Quelle aus, zeigt die Karte die andere und
        // nennt die Stoerung als Hinweis (statt ganz leer zu sein).
        $php = $this->phpvmsFluege();
        if (!$live['ok'] && $php === null) {
            return $this->antwort(['ok' => false, 'quelle' => 'beide', 'fehler' => $live['fehler'], 'fluege' => []], 503);
        }
        $zusammen = FlugNormalisierer::zusammenfuehren($live['ok'] ? $live['fluege'] : [], $php ?? []);
        $antwort = ['ok' => true, 'quelle' => 'beide', 'fluege' => $zusammen['fluege'], 'zugeordnet' => $zusammen['zugeordnet']];
        if (!$live['ok']) {
            $antwort['warnung'] = $live['fehler'];
        } elseif ($php === null) {
            $antwort['warnung'] = 'phpvms';
        }

        return $this->mitBeiwerk($antwort);
    }

    /** @return array{ok: bool, fehler?: string, fluege?: array} */
    private function liveFluege(): array
    {
        $ergebnis = $this->ausLive('/api/flights', 'livemap:live:fluege', self::PUFFER_LISTE_S);
        if (!$ergebnis['ok']) {
            return ['ok' => false, 'fehler' => $ergebnis['fehler']];
        }
        // Der Schluessel liefert alle VAs, die auf live fliegen. Mit gesetzter
        // VA-Kennung bleiben nur die eigenen (zusaetzlich zur Bindung auf live).
        $va = strtolower(trim((string) self::einstellung('acars.livemap_va_prefix', '')));
        $fluege = [];
        foreach (is_array($ergebnis['daten']) ? $ergebnis['daten'] : [] as $dto) {
            $f = FlugNormalisierer::ausLive($dto);
            if ($f !== null && ($va === '' || strtolower($f['va']) === $va)) {
                $fluege[] = $f;
            }
        }

        return ['ok' => true, 'fluege' => $fluege];
    }

    /**
     * Was die Karte zu jedem Flug noch braucht und nur phpVMS kennt: Logo
     * (fuer Fluege ohne PIREP ueber den Rufzeichen-Praefix), Flughafennamen
     * und -koordinaten (Distanz und Fortschritt). Interne Felder raus.
     */
    private function mitBeiwerk(array $antwort): JsonResponse
    {
        $logos = $this->airlineLogos();
        $icaos = [];
        foreach ($antwort['fluege'] as $i => $f) {
            if (empty($f['logo'])) {
                $f['logo'] = FlugNormalisierer::logoFuerRufzeichen($f['callsign'] ?? null, $logos);
            }
            $antwort['fluege'][$i] = FlugNormalisierer::oeffentlich($f);
            foreach (['dep', 'arr'] as $k) {
                if (!empty($f[$k])) {
                    $icaos[$f[$k]] = true;
                }
            }
        }
        $antwort['flughaefen'] = $this->flughaefen(array_keys($icaos));

        return $this->antwort($antwort);
    }

    /** @return array<string,string> ICAO → absolute Logo-Adresse */
    private function airlineLogos(): array
    {
        return Cache::remember('livemap:logos', now()->addMinutes(10), function () {
            try {
                return Airline::whereNotNull('logo')->where('logo', '!=', '')->get(['icao', 'logo'])
                    ->mapWithKeys(fn ($a) => [strtoupper((string) $a->icao) => self::absolut($a->logo)])
                    ->filter()->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /** @return array<string, array{name: ?string, lat: ?float, lon: ?float}> */
    private function flughaefen(array $icaos): array
    {
        sort($icaos);
        if (!$icaos) {
            return [];
        }

        // Je Flughafen ein Eintrag: die Menge bleibt durch die Zahl der Plaetze
        // begrenzt (ein Eintrag je Kombination wuchs mit jedem Flugwechsel).
        $schluessel = fn (string $icao) => 'livemap:flughafen:'.$icao;
        $treffer = [];
        $fehlen = [];
        foreach ($icaos as $icao) {
            $wert = Cache::get($schluessel($icao));
            if (is_array($wert)) {
                if ($wert !== []) {
                    $treffer[$icao] = $wert;
                }
            } else {
                $fehlen[] = $icao;
            }
        }
        if ($fehlen) {
            try {
                $neu = Airport::whereIn('id', $fehlen)->get(['id', 'name', 'lat', 'lon'])
                    ->mapWithKeys(fn ($a) => [strtoupper((string) $a->id) => [
                        'name' => $a->name !== null ? mb_substr((string) $a->name, 0, 60) : null,
                        'lat'  => is_numeric($a->lat) ? round((float) $a->lat, 5) : null,
                        'lon'  => is_numeric($a->lon) ? round((float) $a->lon, 5) : null,
                    ]])->all();
            } catch (\Throwable $e) {
                return $treffer;
            }
            foreach ($fehlen as $icao) {
                // Unbekannte ICAO als leerer Eintrag, damit sie nicht jede Abfrage neu trifft
                Cache::put($schluessel($icao), $neu[$icao] ?? [], now()->addMinutes(10));
                if (isset($neu[$icao])) {
                    $treffer[$icao] = $neu[$icao];
                }
            }
        }
        ksort($treffer);

        return $treffer;
    }

    private static function absolut(?string $logo): ?string
    {
        $logo = trim((string) $logo);
        if ($logo === '') {
            return null;
        }
        if (!str_starts_with($logo, 'http')) {
            $logo = url($logo);
        }

        return str_starts_with($logo, 'http://') ? 'https://'.substr($logo, 7) : $logo;
    }

    public function spur(string $va, string $pilot): JsonResponse
    {
        if (!FlugNormalisierer::kennungGueltig($va) || !FlugNormalisierer::kennungGueltig($pilot)) {
            return $this->antwort(['ok' => false, 'fehler' => 'ungueltig', 'punkte' => []], 404);
        }
        if ($va === 'phpvms') {
            return $this->antwort(['ok' => true, 'punkte' => $this->phpvmsSpur($pilot)]);
        }
        if ($this->quelle() === 'phpvms') {
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
        if ($va === 'phpvms' || $this->quelle() === 'phpvms') {
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

    /** 'aeroacars' | 'phpvms' | 'beide' */
    public static function quelle(): string
    {
        $wert = strtolower(trim((string) self::einstellung('acars.livemap_quelle', 'phpvms')));

        return in_array($wert, ['aeroacars', 'beide'], true) ? $wert : 'phpvms';
    }

    /**
     * Basisadresse von live — nur ein freigegebener Host (siehe
     * FlugNormalisierer::liveBasis). Der Schluessel geht damit nie an eine
     * fremde oder interne Adresse, auch wenn die Einstellung manipuliert ist.
     */
    public static function basis(): ?string
    {
        $roh = (string) self::einstellung('acars.livemap_live_url', self::STANDARD_BASIS);

        return FlugNormalisierer::liveBasis($roh, !app()->environment('production'));
    }

    /** @return array{ok: bool, fehler?: string, daten?: mixed} */
    private function ausLive(string $pfad, string $pufferSchluessel, int $pufferSekunden): array
    {
        $basis = self::basis();
        $schluessel = trim((string) self::einstellung('acars.livemap_live_key', ''));
        if ($basis === null) {
            return ['ok' => false, 'fehler' => 'adresse'];
        }
        if ($schluessel === '') {
            return ['ok' => false, 'fehler' => 'kein_schluessel'];
        }
        // Puffer gilt nur fuer genau dieses Ziel und diesen Schluessel: nach
        // einem Wechsel in der Verwaltung kommt sofort die neue Antwort.
        $pufferSchluessel .= ':'.substr(hash('sha256', $basis."\n".$schluessel), 0, 16);
        $gepuffert = Cache::get($pufferSchluessel);
        if (is_array($gepuffert) && array_key_exists('ok', $gepuffert)) {
            return $gepuffert;
        }

        try {
            $antwort = Http::timeout(self::ZEITLIMIT_S)
                ->withoutRedirecting()
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

    /** null = Abfrage gescheitert (nicht dasselbe wie "niemand fliegt"). */
    private function phpvmsFluege(): ?array
    {
        $gepuffert = Cache::get('livemap:phpvms:fluege');
        if (is_array($gepuffert)) {
            return $gepuffert;
        }
        $fluege = (function () {
            $fluege = [];
            try {
                $pireps = app(AcarsRepository::class)->getPositions(setting('acars.live_time', 0));
            } catch (\Throwable $e) {
                Log::warning('LiveMap: laufende PIREPs nicht lesbar: '.mb_substr($e->getMessage(), 0, 160));

                return null;
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
                    // Juengste von PIREP und letzter Position: phpVMS speichert den
                    // PIREP bei Positionsmeldungen ohne Aenderung nicht neu
                    // (AcarsController::acars_store, save() ohne dirty-Felder).
                    'updated_ms'    => max(
                        optional($p->updated_at)->getTimestampMs() ?? 0,
                        optional($pos->updated_at ?? $pos->created_at ?? null)->getTimestampMs() ?? 0
                    ) ?: null,
                    'created_ms'    => optional($p->created_at)->getTimestampMs(),
                    'logo'          => self::absolut(optional($p->airline)->logo),
                    'pirep_url'     => url('/pireps/'.$p->id),
                    'user_id'       => $nutzer ? (string) $nutzer->id : null,
                    'user_pilot_id' => $nutzer && $nutzer->pilot_id !== null ? (string) $nutzer->pilot_id : null,
                ]);
                if ($f !== null) {
                    $fluege[] = $f;
                }
            }

            return $this->mitCrews($fluege);
        })();
        if ($fluege !== null) {
            Cache::put('livemap:phpvms:fluege', $fluege, now()->addSeconds(self::PUFFER_LISTE_S));
        }

        return $fluege;
    }

    /**
     * Shared Cockpit (Weg B, B2): Crew-Kennzeichen am Gastgeber, Spurtraeger
     * markiert (FlugNormalisierer::crewsAnwenden). Ein Fehler im Modul
     * SharedCockpit darf die Karte nie kippen: dann die Liste wie ohne Crew.
     */
    private function mitCrews(array $fluege): array
    {
        try {
            $crews = app(SharedCockpitQuelle::class)->crews(array_column($fluege, 'pilot'));
        } catch (\Throwable $e) {
            Log::warning('LiveMap: Shared Cockpit nicht lesbar: '.mb_substr($e->getMessage(), 0, 160));

            return $fluege;
        }

        return FlugNormalisierer::crewsAnwenden($fluege, is_array($crews) ? $crews : []);
    }

    private function phpvmsSpur(string $pirepId): array
    {
        return Cache::remember('livemap:phpvms:spur:'.$pirepId, now()->addSeconds(self::PUFFER_SPUR_S), function () use ($pirepId) {
            try {
                $punkte = app(AcarsRepository::class)->forPirep($pirepId, AcarsType::FLIGHT_PATH);
            } catch (\Throwable $e) {
                Log::warning('LiveMap: PIREP-Spur nicht lesbar: '.mb_substr($e->getMessage(), 0, 160));

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
