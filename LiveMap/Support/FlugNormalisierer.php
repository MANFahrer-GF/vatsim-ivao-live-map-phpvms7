<?php

namespace Modules\LiveMap\Support;

/**
 * Bringt Flugdaten aus beiden Quellen in EINE Form, die das Widget liest.
 *
 * # Warum hier und nicht im Browser
 *
 * Die Quelle "AeroACARS Live" (live.kant.ovh) braucht einen Schluessel. Der
 * darf nie im Browser landen, also laeuft jeder Abruf ueber das Modul. Wenn
 * das Modul ohnehin dazwischen sitzt, legt es auch fest, WELCHE Felder die
 * oeffentliche Seite ueberhaupt sieht: nur die, die die Admin-Webapp auf
 * live im Flugdetail zeigt (`PilotDetail`, `FlightBoard`), nichts darueber
 * hinaus. Ein neues Feld auf live erscheint damit nicht von selbst auf der
 * GSG-Seite.
 *
 * # Die Form (je Flug)
 *
 *   id, va, pilot, callsign, pilot_name, aircraft_icao, registration, sim,
 *   acars, dep, arr, dep_gate, arr_gate, phase, shadow_segment, status,
 *   last_seen (ms), started_at (ms), pos{…} | null
 *
 * `phase` bleibt die ROHE Phase von live (UPPER_SNAKE). Die Anzeige-Phase
 * („Level" bei einer ATC-Hoehenbeschraenkung) rechnet das Widget wie die
 * Webapp (`phaseForDisplay`) aus `phase` + `shadow_segment`.
 *
 * Keine Laravel-Abhaengigkeit: die Klasse laeuft auch im Test ohne Framework.
 */
final class FlugNormalisierer
{
    /** ~5,5 km um 0/0 — dort liegt kein Flugplatz (wie `lmUsableLatLng` im Widget). */
    public const NULL_INSEL_GRAD = 0.05;

    /** Positionsfelder, die die Webapp im Flugdetail zeigt (`PilotDetail`, live webapp/src/tabs/LiveMap.tsx). */
    private const ZAHL_FELDER = [
        'alt_ft', 'ias_kt', 'gs_kt', 'vs_fpm', 'hdg_true', 'agl_ft', 'mach',
        'wind_dir_deg', 'wind_speed_kt', 'qnh_hpa', 'altimeter_hpa', 'oat_c',
        'fuel_total_kg', 'fuel_flow_kg_h', 'total_weight_kg',
        'gear_position', 'flaps_position', 'engines_running',
    ];
    private const SCHALTER_FELDER = [
        'on_ground', 'spoilers_armed', 'parking_brake', 'stall_warning', 'overspeed_warning',
        'ap_master', 'ap_hdg', 'ap_alt', 'ap_nav', 'ap_app',
    ];

    /**
     * phpVMS-Status (PirepStatus) → Phase der Webapp. Nur fuer die Quelle
     * "phpVMS": dort gibt es keine Phasen-Engine, nur den Status des PIREPs.
     */
    private const PHPVMS_STATUS_ZU_PHASE = [
        'INI' => 'PREFLIGHT', 'SCH' => 'PREFLIGHT', 'RDT' => 'PREFLIGHT',
        'BST' => 'BOARDING',
        'PBT' => 'PUSHBACK',
        'OFB' => 'TAXI_OUT', 'DIR' => 'TAXI_OUT', 'DIC' => 'TAXI_OUT', 'TXI' => 'TAXI_OUT',
        'GRT' => 'TAXI_IN',
        'TOF' => 'TAKEOFF',
        'ICL' => 'CLIMB', 'TKO' => 'CLIMB',
        'ENR' => 'CRUISE', 'DV' => 'CRUISE',
        'EMG' => 'DESCENT',
        'TEN' => 'APPROACH', 'APR' => 'APPROACH',
        'FIN' => 'FINAL',
        'LDG' => 'LANDING',
        'LAN' => 'TAXI_IN',
        'ONB' => 'BLOCKS_ON',
        'ARR' => 'ARRIVED',
    ];

    public static function nutzbar($lat, $lon): bool
    {
        if (!is_numeric($lat) || !is_numeric($lon)) {
            return false;
        }
        $lat = (float) $lat;
        $lon = (float) $lon;
        if (!is_finite($lat) || !is_finite($lon)) {
            return false;
        }
        if (abs($lat) > 90 || abs($lon) > 180) {
            return false;
        }
        // NUR wenn BEIDE nahe 0 sind. 0°N/10°E ist Gabun und gueltig.
        return !(abs($lat) < self::NULL_INSEL_GRAD && abs($lon) < self::NULL_INSEL_GRAD);
    }

    /** Der einzige Host, an den der Lese-Schluessel gehen darf. */
    public const LIVE_HOST = 'live.kant.ovh';

    /**
     * Pruefung der eingestellten Adresse von AeroACARS Live.
     *
     * Erlaubt ist nur https://live.kant.ovh (ohne Pfad, Nutzer, Abfrage).
     * Ausserhalb der Produktion zusaetzlich http://127.0.0.1 bzw. localhost
     * mit Port fuer einen Pruefserver. Alles andere → null: Der Schluessel
     * ginge sonst an eine beliebige — auch interne — Adresse (SSRF).
     */
    public static function liveBasis(string $roh, bool $lokalErlaubt): ?string
    {
        $roh = rtrim(trim($roh), '/');
        if ($roh === '') {
            $roh = 'https://'.self::LIVE_HOST;
        }
        $teile = parse_url($roh);
        if (!is_array($teile) || empty($teile['host']) || isset($teile['user']) || isset($teile['pass'])
            || isset($teile['query']) || isset($teile['fragment']) || (isset($teile['path']) && $teile['path'] !== '')) {
            return null;
        }
        $schema = strtolower((string) ($teile['scheme'] ?? ''));
        $host = strtolower((string) $teile['host']);
        $port = isset($teile['port']) ? ':'.(int) $teile['port'] : '';
        if ($schema === 'https' && $host === self::LIVE_HOST && ($port === '' || $port === ':443')) {
            return 'https://'.self::LIVE_HOST;
        }
        if ($lokalErlaubt && $schema === 'http' && in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return 'http://'.$host.$port;
        }

        return null;
    }

    /** Kennung, wie sie in der Adresse steht: nur Buchstaben, Ziffern, `_-.`, hoechstens 40 Zeichen. */
    public static function kennungGueltig(string $wert): bool
    {
        return preg_match('/^[A-Za-z0-9_.\-]{1,40}$/D', $wert) === 1;
    }

    /** Ein Eintrag aus `GET /api/flights` (live, `flightRowToDto`). */
    public static function ausLive($dto): ?array
    {
        if (!is_array($dto)) {
            return null;
        }
        $va = self::text($dto['va_prefix'] ?? null, 20);
        $pilot = self::text($dto['pilot_id'] ?? null, 40);
        if ($va === null || $pilot === null || !self::kennungGueltig($va) || !self::kennungGueltig($pilot)) {
            return null;
        }
        $lp = is_array($dto['last_position'] ?? null) ? $dto['last_position'] : null;

        // Flugzeug wie die Webapp (`pickAircraftLabel`): Spalte, sonst Position,
        // leere Zeichenketten zaehlen als fehlend.
        $icao = self::text($dto['aircraft_icao'] ?? null, 8) ?? self::text($lp['aircraft_icao'] ?? null, 8);

        return [
            'id'             => $va.'/'.$pilot,
            'va'             => $va,
            'pilot'          => $pilot,
            'callsign'       => self::text($dto['callsign'] ?? null, 20) ?? self::text($lp['callsign'] ?? null, 20),
            'pilot_name'     => self::kurzname(self::text($dto['display_name'] ?? null, 60)),
            'aircraft_icao'  => $icao !== null ? strtoupper($icao) : null,
            'registration'   => self::text($lp['aircraft_registration'] ?? null, 12),
            'sim'            => self::text($lp['simulator'] ?? null, 20),
            'acars'          => self::text($dto['acars'] ?? null, 20) ?? self::text($lp['acars'] ?? null, 20),
            'dep'            => self::icao($dto['dep'] ?? null),
            'arr'            => self::icao($dto['arr'] ?? null),
            'dep_gate'       => self::text($dto['dep_gate'] ?? null, 12),
            'arr_gate'       => self::text($dto['arr_gate'] ?? null, 12),
            'phase'          => self::phase($dto['current_phase'] ?? ($lp['phase'] ?? null)),
            'shadow_segment' => self::text($dto['shadow_segment'] ?? ($lp['shadow_segment'] ?? null), 20),
            'status'         => ($dto['status'] ?? null) === 'offline' ? 'offline' : 'online',
            'last_seen'      => self::ganzzahl($dto['last_seen'] ?? null),
            'started_at'     => self::ganzzahl($dto['started_at'] ?? null),
            'pos'            => self::position($lp),
        ];
    }

    /**
     * Ein laufender phpVMS-PIREP (Quelle "phpVMS", fuer VAs ohne AeroACARS Live).
     * Erwartet ein flaches Feld, das der Controller aus dem Pirep-Modell baut.
     */
    public static function ausPhpvms(array $p): ?array
    {
        $id = self::text($p['pirep_id'] ?? null, 40);
        if ($id === null || !self::kennungGueltig($id)) {
            return null;
        }
        $roh = [
            'lat'      => $p['lat'] ?? null,
            'lon'      => $p['lon'] ?? null,
            'alt_ft'   => $p['altitude'] ?? null,
            'ias_kt'   => $p['ias'] ?? null,
            'gs_kt'    => $p['gs'] ?? null,
            'vs_fpm'   => $p['vs'] ?? null,
            'hdg_true' => $p['heading'] ?? null,
            'fuel_total_kg' => $p['fuel_kg'] ?? null,
        ];

        return [
            'id'             => 'phpvms/'.$id,
            'va'             => 'phpvms',
            'pilot'          => $id,
            'callsign'       => self::text($p['callsign'] ?? null, 20),
            // Auch hier gekürzt: Ohne name_private reicht phpVMS den vollen Namen durch.
            'pilot_name'     => self::kurzname(self::text($p['pilot_name'] ?? null, 60)),
            'aircraft_icao'  => ($i = self::text($p['aircraft_icao'] ?? null, 8)) !== null ? strtoupper($i) : null,
            'registration'   => self::text($p['registration'] ?? null, 12),
            'sim'            => null,
            'acars'          => self::text($p['source_name'] ?? null, 20),
            'dep'            => self::icao($p['dep'] ?? null),
            'arr'            => self::icao($p['arr'] ?? null),
            'dep_gate'       => null,
            'arr_gate'       => null,
            'phase'          => self::phaseAusPhpvmsStatus((string) ($p['status'] ?? '')),
            'shadow_segment' => null,
            'status'         => 'online',
            'last_seen'      => self::ganzzahl($p['updated_ms'] ?? null),
            'started_at'     => self::ganzzahl($p['created_ms'] ?? null),
            'pos'            => self::position($roh),
        ];
    }

    /**
     * „Thomas Kant" → „Thomas K." — wie die Karte schon bisher Namen zeigt.
     * Die Webapp auf live ist nur fuer Admins; diese Karte ist oeffentlich,
     * deshalb verlaesst der volle Nachname den Server nicht.
     */
    public static function kurzname(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }
        $teile = preg_split('/\s+/u', trim($name)) ?: [];
        $teile = array_values(array_filter($teile, fn ($t) => $t !== ''));
        if (count($teile) === 0) {
            return null;
        }
        if (count($teile) === 1) {
            return $teile[0];
        }

        return $teile[0].' '.mb_strtoupper(mb_substr($teile[count($teile) - 1], 0, 1)).'.';
    }

    public static function phaseAusPhpvmsStatus(string $code): ?string
    {
        $code = strtoupper(trim($code));

        return self::PHPVMS_STATUS_ZU_PHASE[$code] ?? null;
    }

    /** `GET /api/flights/:va/:pilot/positions` → [[lat, lon], …], unbrauchbare Punkte raus. */
    public static function spur($zeilen): array
    {
        if (!is_array($zeilen)) {
            return [];
        }
        $aus = [];
        foreach ($zeilen as $z) {
            if (!is_array($z)) {
                continue;
            }
            $lat = $z['lat'] ?? null;
            $lon = $z['lon'] ?? ($z['lng'] ?? null);
            if (!self::nutzbar($lat, $lon)) {
                continue;
            }
            $aus[] = [round((float) $lat, 5), round((float) $lon, 5)];
        }

        return $aus;
    }

    /** `GET /api/flights/:va/:pilot/route` → [[lat, lon, ident], …]. */
    public static function route($antwort): array
    {
        $punkte = is_array($antwort) && is_array($antwort['waypoints'] ?? null) ? $antwort['waypoints'] : [];
        $aus = [];
        foreach ($punkte as $w) {
            if (!is_array($w)) {
                continue;
            }
            $lat = $w['lat'] ?? ($w['latitude'] ?? null);
            $lon = $w['lon'] ?? ($w['lng'] ?? ($w['longitude'] ?? null));
            if (!self::nutzbar($lat, $lon)) {
                continue;
            }
            $ident = self::text($w['ident'] ?? ($w['name'] ?? ($w['id'] ?? null)), 12);
            $aus[] = [round((float) $lat, 5), round((float) $lon, 5), $ident];
        }

        return $aus;
    }

    private static function position($lp): ?array
    {
        if (!is_array($lp) || !self::nutzbar($lp['lat'] ?? null, $lp['lon'] ?? null)) {
            return null;
        }
        $pos = ['lat' => round((float) $lp['lat'], 5), 'lon' => round((float) $lp['lon'], 5)];
        foreach (self::ZAHL_FELDER as $f) {
            $v = $lp[$f] ?? null;
            $pos[$f] = is_numeric($v) && is_finite((float) $v) ? $v + 0 : null;
        }
        foreach (self::SCHALTER_FELDER as $f) {
            $v = $lp[$f] ?? null;
            $pos[$f] = is_bool($v) ? $v : ($v === 1 || $v === 0 ? (bool) $v : null);
        }

        return $pos;
    }

    private static function phase($wert): ?string
    {
        $t = self::text($wert, 24);
        if ($t === null) {
            return null;
        }
        $t = strtoupper($t);

        return preg_match('/^[A-Z_]+$/', $t) === 1 ? $t : null;
    }

    private static function icao($wert): ?string
    {
        $t = self::text($wert, 8);
        if ($t === null) {
            return null;
        }
        $t = strtoupper($t);

        return preg_match('/^[A-Z0-9]{2,8}$/', $t) === 1 ? $t : null;
    }

    private static function text($wert, int $max): ?string
    {
        if (!is_string($wert) && !is_int($wert)) {
            return null;
        }
        $t = trim((string) $wert);
        if ($t === '') {
            return null;
        }
        // Steuerzeichen raus; die Ausgabe landet als Text im Browser.
        $t = preg_replace('/[\x00-\x1F\x7F]/u', '', $t) ?? '';

        return $t === '' ? null : mb_substr($t, 0, $max);
    }

    private static function ganzzahl($wert): ?int
    {
        return is_numeric($wert) ? (int) $wert : null;
    }
}
