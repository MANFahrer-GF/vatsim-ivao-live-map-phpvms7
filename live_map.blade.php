{{-- LiveMap 5.1 — Theme-Datei. Kopieren nach
     resources/views/layouts/<Theme>/widgets/live_map.blade.php
     Alles Weitere (Aussehen, Skripte, eigenes Leaflet) liefert das Modul,
     ein Update betrifft nur noch modules/LiveMap. Die alten Dateien
     live_map_scripts.blade.php und live_map_styles.blade.php im Theme-Ordner
     werden nicht mehr benutzt und koennen geloescht werden. --}}
@include('livemap::widget')
