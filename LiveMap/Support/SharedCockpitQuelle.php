<?php

namespace Modules\LiveMap\Support;

/**
 * Crews aus dem Modul SharedCockpit (Weg B, B2) — nur lesend, nur wenn da.
 *
 * LiveMap wird oeffentlich verteilt und laeuft ohne SharedCockpit, auch mit
 * dessen heutigem Livestand (main, ohne `CrewAuskunft`). Darum keine harte
 * Abhaengigkeit: Klassen nur per Name, und nur wenn das Modul aktiv ist und
 * `CrewAuskunft`, `Crew`, `Spurtraeger::laufenderFuehrender` und
 * `Spurtraeger::gastgeberLebt` (Zweig feat/weg-b) existieren. Sonst keine
 * Crews → Karte wie ohne Shared Cockpit.
 * Fehler faengt der Aufrufer (LiveFlightsController) ab.
 *
 * Abfrage wie SharedCockpit `Http/Middleware/KernKarte`; der fuehrende Bericht
 * kommt aus `CrewAuskunft::daten` (`fuehrender_pirep_id` oder im Flug
 * `Spurtraeger::laufenderFuehrender`), die Frische aus
 * `Spurtraeger::gastgeberLebt` (Heartbeat beider Sitze in Serverzeit; nicht
 * `pireps.updated_at`, das traegt die PC-Uhr des Clients). Die Regel selbst wendet
 * `FlugNormalisierer::crewsAnwenden` an.
 *
 * Ueber den Container ersetzbar (`app(SharedCockpitQuelle::class)`).
 */
class SharedCockpitQuelle
{
    /**
     * @param string[] $pirepIds laufende Berichte der Karte (mit Position)
     *
     * @return array<int, array{status: mixed, spur_pirep_id: ?string, fuehrender_pirep_id: ?string, rolle_gastgeber: mixed, partner_name: mixed, gastgeber_user_id: mixed, gastgeber_lebt: bool}>
     */
    public function crews(array $pirepIds): array
    {
        if ($pirepIds === [] || !$this->verfuegbar()) {
            return [];
        }
        $crew = $this->klasse('Crew');
        $auskunft = $this->klasse('CrewAuskunft');
        $spurtraeger = $this->klasse('Spurtraeger');
        $aus = [];
        foreach ($crew::where('status', $crew::ANGENOMMEN)->whereIn('spur_pirep_id', array_values($pirepIds))->get() as $c) {
            $d = $auskunft::daten($c);
            $aus[] = [
                'status'              => $d['status'] ?? null,
                'spur_pirep_id'       => $d['spur_pirep_id'] ?? null,
                'fuehrender_pirep_id' => $d['fuehrender_pirep_id'] ?? null,
                'rolle_gastgeber'     => $d['rolle_gastgeber'] ?? null,
                'partner_name'        => $d['partner']['name'] ?? null,
                // Nur intern (FlugNormalisierer::crewsAnwenden), nie in der Antwort.
                'gastgeber_user_id'   => $d['gastgeber']['user_id'] ?? null,
                'gastgeber_lebt'      => $spurtraeger::gastgeberLebt($c) === true,
            ];
        }

        return $aus;
    }

    protected function verfuegbar(): bool
    {
        return class_exists($this->klasse('CrewAuskunft')) && class_exists($this->klasse('Crew'))
            && method_exists($this->klasse('Spurtraeger'), 'laufenderFuehrender')
            && method_exists($this->klasse('Spurtraeger'), 'gastgeberLebt') && $this->modulAktiv();
    }

    protected function modulAktiv(): bool
    {
        return function_exists('check_module') && check_module('SharedCockpit');
    }

    protected function klasse(string $name): string
    {
        return 'Modules\\SharedCockpit\\Support\\'.$name;
    }
}
