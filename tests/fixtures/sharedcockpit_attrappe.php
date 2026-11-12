<?php

// Attrappe der drei SharedCockpit-Klassen (Zweig feat/weg-b), die
// LiveMap/Support/SharedCockpitQuelle liest — fuer tests/live_flights_probe.php.
// Eigener Namensraum: das echte Modul im lokalen phpVMS bleibt unberuehrt.
// Crew filtert wie der Query-Builder (where/whereIn/get) ueber `$alle`.

namespace LiveMapProbe\SC;

require_once __DIR__.'/sharedcockpit_teilstaende.php';

final class Crew
{
    public const ANGENOMMEN = 'angenommen';

    /** @var array<int, array> */
    public static array $alle = [];
    public static bool $wirft = false;

    private array $bedingungen = [];

    public static function where(string $feld, $wert): self
    {
        \LiveMapProbe\Zaehler::$where++;
        if (self::$wirft) {
            throw new \RuntimeException('Tabelle sc_crews fehlt');
        }
        $q = new self();
        $q->bedingungen[] = [$feld, [$wert]];

        return $q;
    }

    public function whereIn(string $feld, array $werte): self
    {
        $this->bedingungen[] = [$feld, array_values($werte)];

        return $this;
    }

    public function get(): array
    {
        return array_values(array_filter(self::$alle, function (array $c) {
            foreach ($this->bedingungen as [$feld, $werte]) {
                if (!in_array($c[$feld] ?? null, $werte, true)) {
                    return false;
                }
            }

            return true;
        }));
    }
}

final class CrewAuskunft
{
    /** Form wie Modules\SharedCockpit\Support\CrewAuskunft::daten (Vertrag B1). */
    public static function daten(array $c): array
    {
        return [
            'crew_id'             => $c['id'],
            'status'              => $c['status'],
            'rolle_gastgeber'     => $c['rolle_gastgeber'],
            'gastgeber'           => ['user_id' => 1, 'pilot_id' => 'GSG0001', 'name' => 'Thomas K'],
            'partner'             => ['user_id' => 7, 'pilot_id' => 'GSG0007', 'name' => 'Peter M'],
            'fuehrender_pirep_id' => $c['fuehrender_pirep_id'],
            'spur_pirep_id'       => $c['spur_pirep_id'],
            'partner_pirep_id'    => null,
            'simulator'           => 'MSFS 2024',
        ];
    }
}

final class Spurtraeger
{
    /** Ergebnis von gastgeberLebt (Serverzeit-Frische, Schalter fuer die Probe). */
    public static bool $lebt = true;

    public static function laufenderFuehrender($crew)
    {
        return null;
    }

    public static function gastgeberLebt($crew): bool
    {
        return self::$lebt;
    }
}
