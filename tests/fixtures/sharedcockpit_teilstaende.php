<?php

// Teilstaende von SharedCockpit fuer den Wächter SharedCockpitQuelle::verfuegbar()
// (tests/live_flights_probe.php). Je Namensraum fehlt genau eine Voraussetzung,
// `Livestand` ist das heutige main (Crew und Spurtraeger, ohne CrewAuskunft,
// ohne laufenderFuehrender/gastgeberLebt). Crew::where zaehlt mit: Der Wächter
// muss vor der ersten Abfrage greifen.

namespace LiveMapProbe {
    final class Zaehler
    {
        public static int $where = 0;
    }
}

namespace LiveMapProbe\Livestand {
    final class Crew
    {
        public const ANGENOMMEN = 'angenommen';

        public static function where(string $feld, $wert)
        {
            return \LiveMapProbe\SC\Crew::where($feld, $wert);
        }
    }

    final class Spurtraeger
    {
    }
}

namespace LiveMapProbe\OhneAuskunft {
    final class Crew
    {
        public const ANGENOMMEN = 'angenommen';

        public static function where(string $feld, $wert)
        {
            return \LiveMapProbe\SC\Crew::where($feld, $wert);
        }
    }

    final class Spurtraeger
    {
        public static function laufenderFuehrender($crew)
        {
            return null;
        }

        public static function gastgeberLebt($crew): bool
        {
            return true;
        }
    }
}

namespace LiveMapProbe\OhneCrew {
    final class CrewAuskunft
    {
        public static function daten(array $c): array
        {
            return \LiveMapProbe\SC\CrewAuskunft::daten($c);
        }
    }

    final class Spurtraeger
    {
        public static function laufenderFuehrender($crew)
        {
            return null;
        }

        public static function gastgeberLebt($crew): bool
        {
            return true;
        }
    }
}

namespace LiveMapProbe\OhneFuehrender {
    final class Crew
    {
        public const ANGENOMMEN = 'angenommen';

        public static function where(string $feld, $wert)
        {
            return \LiveMapProbe\SC\Crew::where($feld, $wert);
        }
    }

    final class CrewAuskunft
    {
        public static function daten(array $c): array
        {
            return \LiveMapProbe\SC\CrewAuskunft::daten($c);
        }
    }

    final class Spurtraeger
    {
        public static function gastgeberLebt($crew): bool
        {
            return true;
        }
    }
}

namespace LiveMapProbe\OhneLebt {
    // Stand feat/weg-b vor der Serverzeit-Frische.
    final class Crew
    {
        public const ANGENOMMEN = 'angenommen';

        public static function where(string $feld, $wert)
        {
            return \LiveMapProbe\SC\Crew::where($feld, $wert);
        }
    }

    final class CrewAuskunft
    {
        public static function daten(array $c): array
        {
            return \LiveMapProbe\SC\CrewAuskunft::daten($c);
        }
    }

    final class Spurtraeger
    {
        public static function laufenderFuehrender($crew)
        {
            return null;
        }
    }
}
