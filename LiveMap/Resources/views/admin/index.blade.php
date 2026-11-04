@extends('livemap::layouts.admin')

@section('title', 'Live Map Settings')

@section('content')
  @php
    $lmS = function (string $key, $default = null) use ($settings) {
      return array_key_exists($key, $settings) ? $settings[$key] : $default;
    };
    $lmAn = function (string $key, bool $default) use ($lmS) {
      return filter_var($lmS($key, $default), FILTER_VALIDATE_BOOLEAN);
    };
    $tafelLage = (string) $lmS('acars.livemap_flights_panel', 'bottom');
    if (!in_array($tafelLage, ['top', 'bottom', 'off'], true)) $tafelLage = 'bottom';
    $lmQuelle = old('flight_source', $lmS('acars.livemap_quelle', 'phpvms'));
    $lmModus = old('theme_mode', $lmS('acars.livemap_theme_mode', 'auto'));
    $currentBasemap = $lmS('acars.livemap_default_basemap', 'positron');
    $currentLayer = $lmS('acars.livemap_weather_default_layer', 'combo');
    $lmQuellenName = ['phpvms' => 'phpVMS', 'aeroacars' => 'AeroACARS Live', 'beide' => 'AeroACARS Live + phpVMS'][$lmQuelle] ?? 'phpVMS';
    $lmLiveKey = trim((string) $lmS('acars.livemap_live_key', '')) !== '';
    // Ampel je Statuszeile: ok | warn | bad | off
    $lmWetterAmpel = ['success' => 'ok', 'warning' => 'warn', 'danger' => 'bad', 'info' => 'off'][(string) $weatherProxyStatus['badgeClass']] ?? 'off';
    $lmCartoAmpel = ['success' => 'ok', 'warning' => 'warn', 'danger' => 'bad', 'info' => 'off'][(string) $cartoStatus['badgeClass']] ?? 'off';
  @endphp

  <style>
    /* LiveMap 5.1 settings — flat panels with a hairline, one accent, scoped to .lm-admin */
    .lm-admin { --lma-linie: rgba(16, 28, 46, .10); --lma-text-2: #5d6b7c; --lma-flaeche-2: #f6f8fb; --lma-akzent: #2f6fe4; color: #101c2e; }
    .lm-admin .lma-kopf { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 6px 16px; margin: 0 0 16px; }
    .lm-admin .lma-kopf h4 { margin: 0; font-size: 22px; }
    .lm-admin .lma-kopf p { margin: 0; color: var(--lma-text-2); font-size: 13px; }
    .lm-admin .lma-status { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin: 0 0 22px; }
    .lm-admin .lma-kachel { border: 1px solid var(--lma-linie); border-radius: 10px; padding: 10px 12px; background: #fff; font-size: 12.5px; line-height: 1.45; }
    .lm-admin .lma-kachel b { display: block; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--lma-text-2); font-weight: 700; margin-bottom: 3px; }
    .lm-admin .lma-kachel span { display: inline-flex; align-items: center; gap: 7px; font-weight: 600; font-size: 13px; }
    .lm-admin .lma-kachel small { display: block; color: var(--lma-text-2); margin-top: 2px; }
    .lm-admin .lma-punkt { width: 8px; height: 8px; border-radius: 50%; flex: none; background: #98a2b3; }
    .lm-admin .lma-punkt.ok { background: #16a34a; }
    .lm-admin .lma-punkt.warn { background: #e8a317; }
    .lm-admin .lma-punkt.bad { background: #dc2626; }
    .lm-admin .lma-sek { border: 1px solid var(--lma-linie); border-radius: 12px; background: #fff; margin: 0 0 18px; overflow: hidden; }
    .lm-admin .lma-sek > header { padding: 14px 18px 12px; border-bottom: 1px solid var(--lma-linie); background: var(--lma-flaeche-2); }
    .lm-admin .lma-sek > header h5 { margin: 0; font-size: 15px; font-weight: 700; text-transform: none; letter-spacing: 0; }
    .lm-admin .lma-sek > header p { margin: 4px 0 0; color: var(--lma-text-2); font-size: 12.5px; line-height: 1.5; }
    .lm-admin .lma-zeile { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 6fr); gap: 6px 28px; padding: 14px 18px; border-top: 1px solid var(--lma-linie); align-items: start; }
    .lm-admin .lma-zeile:first-of-type { border-top: 0; }
    .lm-admin .lma-zeile[hidden] { display: none; }
    .lm-admin .lma-zeile > div:first-child label { display: block; margin: 0 0 2px; font-weight: 600; font-size: 13.5px; text-transform: none; letter-spacing: 0; color: inherit; }
    .lm-admin .lma-hilfe { margin: 0; color: var(--lma-text-2); font-size: 12.5px; line-height: 1.5; }
    .lm-admin .lma-hilfe + .lma-hilfe { margin-top: 6px; }
    .lm-admin .lma-hilfe code, .lm-admin .lma-kachel code { font-size: 11.5px; }
    .lm-admin .lma-schalter { display: flex; align-items: flex-start; gap: 10px; margin: 0; font-weight: 600; font-size: 13.5px; text-transform: none; letter-spacing: 0; cursor: pointer; }
    .lm-admin .lma-schalter input { margin: 3px 0 0; flex: none; width: 16px; height: 16px; accent-color: var(--lma-akzent); }
    .lm-admin .lma-paar { display: flex; gap: 8px; align-items: center; max-width: 340px; }
    .lm-admin .lma-paar span { color: var(--lma-text-2); font-size: 12px; }
    .lm-admin .lma-farbe { display: flex; gap: 10px; align-items: center; }
    .lm-admin .lma-farbe input[type=color] { width: 56px; height: 34px; padding: 2px; border: 1px solid var(--lma-linie); border-radius: 8px; background: #fff; cursor: pointer; }
    .lm-admin code { color: #344054; background: var(--lma-flaeche-2); border: 1px solid var(--lma-linie); border-radius: 5px; padding: 1px 5px; }
    .lm-admin .lma-fehler { margin: 6px 0 0; color: #b42318; font-size: 12.5px; }
    .lm-admin .lma-nebenbei { margin-top: 8px; font-size: 12.5px; font-weight: 400; }
    .lm-admin .lma-nebenbei input { width: 14px; height: 14px; margin: 0 6px 0 0; vertical-align: -2px; }
    .lm-admin details { margin-top: 8px; font-size: 12.5px; color: var(--lma-text-2); }
    .lm-admin details summary { cursor: pointer; color: var(--lma-akzent); font-weight: 600; }
    .lm-admin details pre { margin: 8px 0; padding: 10px 12px; font-size: 12px; border-radius: 8px; background: var(--lma-flaeche-2); border: 1px solid var(--lma-linie); }
    .lm-admin .lma-hinweis { border-radius: 10px; padding: 10px 12px; margin: 0 0 16px; font-size: 13px; line-height: 1.5; }
    .lm-admin .lma-hinweis.ok { background: #ecfdf3; border: 1px solid #abefc6; color: #05603a; }
    .lm-admin .lma-hinweis.bad { background: #fef3f2; border: 1px solid #fecdca; color: #912018; }
    .lm-admin .lma-hinweis.warn { background: #fffaeb; border: 1px solid #fedf89; color: #93370d; }
    .lm-admin .lma-hinweis ul { margin: 0; padding-left: 18px; }
    .lm-admin .lma-fuss { position: sticky; bottom: 0; z-index: 2; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 18px; margin-top: 4px; border: 1px solid var(--lma-linie); border-radius: 12px; background: rgba(255,255,255,.96); box-shadow: 0 -6px 18px -12px rgba(16,24,40,.35); }
    .lm-admin .lma-fuss p { margin: 0; font-size: 12px; color: var(--lma-text-2); }
    @media (max-width: 760px) {
      .lm-admin .lma-zeile { grid-template-columns: minmax(0, 1fr); }
    }
  </style>

  <div class="card border-blue-bottom">
    <div class="content lm-admin">
      <div class="lma-kopf">
        <h4 class="title">Live Map Settings</h4>
        <p>Changes apply the next time the map page is loaded.</p>
      </div>

      @if (session('status'))
        <div class="lma-hinweis ok" role="status">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="lma-hinweis bad" role="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Overview: what the map currently runs on --}}
      <div class="lma-status">
        <div class="lma-kachel">
          <b>Flights</b>
          <span><i class="lma-punkt {{ ($lmQuelle !== 'phpvms' && !$lmLiveKey) ? 'bad' : 'ok' }}"></i>{{ $lmQuellenName }}</span>
          <small>{{ ($lmQuelle !== 'phpvms' && !$lmLiveKey) ? 'AeroACARS Live read key missing.' : 'Flight data source.' }}</small>
        </div>
        <div class="lma-kachel">
          <b>Basemap</b>
          <span><i class="lma-punkt {{ $cartoStatus['hasApiKey'] ? $lmCartoAmpel : 'off' }}"></i>{{ $cartoStatus['hasApiKey'] ? $cartoStatus['title'] : 'OpenStreetMap (no CARTO key)' }}</span>
          <small>{{ $cartoStatus['hasApiKey'] ? 'CARTO light and dark styles available.' : 'Works as is. Add a CARTO key for the light and dark styles.' }}</small>
        </div>
        <div class="lma-kachel">
          <b>Weather</b>
          <span><i class="lma-punkt {{ $lmWetterAmpel }}"></i>{{ $weatherProxyStatus['title'] }}</span>
          <small>{{ $weatherProxyStatus['message'] }}</small>
        </div>
        <div class="lma-kachel">
          <b>phpVMS ACARS live time</b>
          <span><i class="lma-punkt {{ $acarsLiveTimeStatus['isSafe'] ? 'ok' : 'warn' }}"></i>{{ $acarsLiveTimeStatus['value'] }} {{ $acarsLiveTimeStatus['value'] === 1 ? 'hour' : 'hours' }}</span>
          <small>
            @if($acarsLiveTimeStatus['isSafe'])
              Fine. Set in Admin → Settings → ACARS.
            @else
              Set it to 1 or higher in Admin → Settings → ACARS. phpVMS also uses it to clean up stuck PIREPs.
            @endif
          </small>
        </div>
      </div>

      <form method="POST" action="{{ url('/admin/livemap/settings') }}">
        @csrf

        {{-- ── Flight data ── --}}
        <section class="lma-sek">
          <header>
            <h5>Flight data</h5>
            <p>Where the flights on the map come from.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label for="flight_source">Source</label>
              <p class="lma-hilfe"><strong>phpVMS</strong> shows the running PIREPs of this site. This is the right choice for every VA.</p>
              <p class="lma-hilfe"><strong>AeroACARS Live</strong> and <strong>Both</strong> are for <strong>German Sky Group only</strong>: they read the live server of German Sky Group's own ACARS client and need a read key that only German Sky Group has. Other VAs cannot use them.</p>
            </div>
            <div>
              <select id="flight_source" name="flight_source" class="form-control">
                <option value="phpvms" {{ $lmQuelle === 'phpvms' ? 'selected' : '' }}>phpVMS (running PIREPs)</option>
                <option value="aeroacars" {{ $lmQuelle === 'aeroacars' ? 'selected' : '' }}>AeroACARS Live — German Sky Group only</option>
                <option value="beide" {{ $lmQuelle === 'beide' ? 'selected' : '' }}>Both: AeroACARS Live + phpVMS — German Sky Group only</option>
              </select>
            </div>
          </div>
          <div class="lma-zeile" data-lma-live>
            <div>
              <label for="live_key">AeroACARS Live read key</label>
              <p class="lma-hilfe">Issued by German Sky Group. Stays on this server and is never sent to the browser.</p>
              <p class="lma-hilfe"><strong>Both</strong> matches AeroACARS flights to their PIREP by callsign and pilot and adds flights only phpVMS knows.</p>
            </div>
            <div>
              <input type="password" id="live_key" name="live_key" class="form-control" autocomplete="new-password" value=""
                     placeholder="{{ $lmLiveKey ? 'Stored — leave empty to keep it' : 'Not set' }}">
              @error('live_key')<p class="lma-fehler">{{ $message }}</p>@enderror
              @if($lmLiveKey)
                <label class="lma-schalter lma-nebenbei"><input type="checkbox" name="live_key_clear" value="1"> Remove the stored key on save</label>
              @endif
            </div>
          </div>
          <div class="lma-zeile" data-lma-live>
            <div>
              <label for="va_prefix">VA prefix</label>
              <p class="lma-hilfe">Only show AeroACARS Live flights of this VA, e.g. <code>gsg</code>. Empty shows every VA the key allows.</p>
            </div>
            <div>
              <input type="text" id="va_prefix" name="va_prefix" class="form-control" autocomplete="off" maxlength="20"
                     value="{{ old('va_prefix', $lmS('acars.livemap_va_prefix', '')) }}" placeholder="e.g. gsg">
              @error('va_prefix')<p class="lma-fehler">{{ $message }}</p>@enderror
            </div>
          </div>
          <div class="lma-zeile" data-lma-live>
            <div>
              <label for="live_url">AeroACARS Live address</label>
              <p class="lma-hilfe">Leave as is. Only the official server is accepted, because the read key is sent there.</p>
            </div>
            <div>
              <input type="text" id="live_url" name="live_url" class="form-control" autocomplete="off"
                     value="{{ old('live_url', $lmS('acars.livemap_live_url', \Modules\LiveMap\Http\Controllers\LiveFlightsController::STANDARD_BASIS)) }}">
              @error('live_url')<p class="lma-fehler">{{ $message }}</p>@enderror
            </div>
          </div>
        </section>

        {{-- ── Flights list ── --}}
        <section class="lma-sek">
          <header>
            <h5>Flights list</h5>
            <p>The list of active and planned flights next to the map.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label for="flights_panel">Position</label>
              <p class="lma-hilfe"><strong>Top</strong>: a collapsible panel over the map. <strong>Bottom</strong>: a table below the map, always open, sortable by column. <strong>Off</strong>: no list, the aircraft stay on the map.</p>
              <p class="lma-hilfe">Upgrading from 4.x: replaces “Layout mode” (Modern = Top, Old style = Off).</p>
            </div>
            <div>
              <select id="flights_panel" name="flights_panel" class="form-control">
                <option value="top" {{ $tafelLage === 'top' ? 'selected' : '' }}>Top — collapsible panel over the map</option>
                <option value="bottom" {{ $tafelLage === 'bottom' ? 'selected' : '' }}>Bottom — table below the map (recommended)</option>
                <option value="off" {{ $tafelLage === 'off' ? 'selected' : '' }}>Off — no flights list</option>
              </select>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="default_follow_flight" value="1" {{ $lmAn('acars.livemap_default_follow_flight', true) ? 'checked' : '' }}> Follow the selected flight</label>
            </div>
            <div><p class="lma-hilfe">When a visitor clicks a flight, the map keeps it centred. This sets the start state of the Follow button; visitors can switch it off.</p></div>
          </div>
        </section>

        {{-- ── Map ── --}}
        <section class="lma-sek">
          <header>
            <h5>Map</h5>
            <p>Look and size of the map itself.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label for="theme_mode">Light / dark</label>
              <p class="lma-hilfe"><strong>Follow the theme</strong> switches to dark when your site theme is dark (e.g. the dark mode of Seven or SPTheme).</p>
            </div>
            <div>
              <select id="theme_mode" name="theme_mode" class="form-control">
                <option value="auto" {{ $lmModus === 'auto' ? 'selected' : '' }}>Follow the theme</option>
                <option value="light" {{ $lmModus === 'light' ? 'selected' : '' }}>Always light</option>
                <option value="dark" {{ $lmModus === 'dark' ? 'selected' : '' }}>Always dark</option>
              </select>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="min_height">Height</label>
              <p class="lma-hilfe">The map fills the browser window below the page header, but never less than the minimum or more than the maximum.</p>
            </div>
            <div>
              <div class="lma-paar">
                <input type="number" id="min_height" name="min_height" class="form-control" min="320" max="2000" value="{{ old('min_height', $lmS('acars.livemap_min_height', 420)) }}" aria-label="Minimum height in pixels">
                <span>to</span>
                <input type="number" id="max_height" name="max_height" class="form-control" min="320" max="3000" value="{{ old('max_height', $lmS('acars.livemap_max_height', 1000)) }}" aria-label="Maximum height in pixels">
                <span>px</span>
              </div>
              @error('min_height')<p class="lma-fehler">{{ $message }}</p>@enderror
              @error('max_height')<p class="lma-fehler">{{ $message }}</p>@enderror
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="show_labels" value="1" {{ $lmAn('acars.livemap_show_labels', true) ? 'checked' : '' }}> Callsigns next to the aircraft</label>
            </div>
            <div><p class="lma-hilfe">Shown from zoom level 6. The selected flight always shows its callsign.</p></div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="ui_primary_color">Accent colour</label>
              <p class="lma-hilfe">The map's own colour in light mode: active toolbar buttons and switches, the selected tab, the selected flight and the light tint of hovered and selected rows. Pick your site's main colour, e.g. green for a green site. Text on it turns white or dark automatically.</p>
            </div>
            <div class="lma-farbe">
              <input id="ui_primary_color" name="ui_primary_color" type="color" value="{{ $lmS('acars.livemap_color_flights_header_start', '#1A2A4A') }}">
              <code>{{ strtoupper($lmS('acars.livemap_color_flights_header_start', '#1A2A4A')) }}</code>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="ui_accent_dark_color">Accent colour (dark mode)</label>
              <p class="lma-hilfe">The same in dark mode. Use a lighter shade of your colour so it stands out on the dark panels.</p>
            </div>
            <div class="lma-farbe">
              <input id="ui_accent_dark_color" name="ui_accent_dark_color" type="color" value="{{ $lmS('acars.livemap_color_accent_dark', '#7CB8F0') }}">
              <code>{{ strtoupper($lmS('acars.livemap_color_accent_dark', '#7CB8F0')) }}</code>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="color_box_background">Panel background</label>
              <p class="lma-hilfe">Background of the flights list, the flight details and the toolbar panels. Light mode only; keep it light so the text stays readable.</p>
            </div>
            <div class="lma-farbe">
              <input id="color_box_background" name="color_box_background" type="color" value="{{ $lmS('acars.livemap_color_box_background', '#FFFFFF') }}">
              <code>{{ strtoupper($lmS('acars.livemap_color_box_background', '#FFFFFF')) }}</code>
            </div>
          </div>
        </section>

        {{-- ── Basemap ── --}}
        <section class="lma-sek">
          <header>
            <h5>Basemap</h5>
            <p>The map tiles. OpenStreetMap and Satellite work without a key; the CARTO light and dark styles need a free CARTO key.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label for="default_basemap">Default style</label>
              <p class="lma-hilfe">The style visitors see first. Without a CARTO key a CARTO style falls back to OpenStreetMap, and the CARTO styles are not offered.</p>
            </div>
            <div>
              <select id="default_basemap" name="default_basemap" class="form-control">
                @foreach($basemapOptions as $value => $label)
                  <option value="{{ $value }}" {{ $currentBasemap === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="show_basemap_switcher" value="1" {{ $lmAn('acars.livemap_show_basemap_switcher', true) ? 'checked' : '' }}> Let visitors change the style</label>
            </div>
            <div><p class="lma-hilfe">Adds the map style button to the toolbar.</p></div>
          </div>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="enable_satellite" value="1" {{ $lmAn('acars.livemap_enable_satellite', true) ? 'checked' : '' }}> Offer satellite imagery</label>
            </div>
            <div><p class="lma-hilfe">Adds Esri World Imagery to the styles.</p></div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="carto_api_key">CARTO API key</label>
              <p class="lma-hilfe">Free up to 5 million tiles per month: <a href="https://carto.com/basemaps/apikey/" target="_blank" rel="noreferrer">carto.com/basemaps/apikey</a>. Without a key CARTO prints “API KEY REQUIRED” on every tile.</p>
              <p class="lma-hilfe">Status: <strong>{{ $cartoStatus['hasApiKey'] ? 'set' : 'not set' }}</strong>@if($cartoStatus['hasApiKey']) ({{ ($cartoStatus['quelle'] ?? '') === 'env' ? 'from .env' : 'from this page' }}), accepted by CARTO: <strong>{{ $cartoStatus['accepted'] === true ? 'yes' : ($cartoStatus['accepted'] === false ? 'no' : 'unknown') }}</strong>@endif.</p>
              @if($cartoStatus['hasApiKey'] && $cartoStatus['accepted'] !== true)
                <p class="lma-hilfe">{{ $cartoStatus['message'] }}</p>
              @endif
            </div>
            <div>
              <input id="carto_api_key" name="carto_api_key" type="password" class="form-control"
                     value="{{ old('carto_api_key', '') }}" autocomplete="new-password"
                     placeholder="{{ $cartoStatus['hasApiKey'] ? 'Stored — leave empty to keep it' : 'Paste your CARTO key (cb1_…)' }}">
              @error('carto_api_key')<p class="lma-fehler">{{ $message }}</p>@enderror
              @if($cartoStatus['hasApiKey'])
                <label class="lma-schalter lma-nebenbei"><input type="checkbox" name="carto_api_key_clear" value="1" {{ old('carto_api_key_clear') ? 'checked' : '' }}> Remove the stored key on save</label>
              @endif
              @if(!empty($cartoStatus['envUeberstimmt']))
                <p class="lma-hilfe" style="margin-top:8px"><code>CARTO_API_KEY</code> is set in <code>.env</code>, but the key was cleared here, and this page wins. Remove the <code>.env</code> line too, or enter a key again.</p>
              @endif
              <details>
                <summary>Set the key in .env instead</summary>
                <pre>CARTO_API_KEY=cb1_...</pre>
                Then run <code>php artisan config:clear</code> (or Admin → Maintenance → Clear Caches). A key entered on this page always wins over <code>.env</code>.
              </details>
              <p class="lma-hilfe" style="margin-top:8px">A new key is checked on save. Keep the CARTO and OpenStreetMap credits visible; that is a condition of the free tier.</p>
            </div>
          </div>
        </section>

        {{-- ── Weather ── --}}
        <section class="lma-sek">
          <header>
            <h5>Weather</h5>
            <p>Optional weather layers from OpenWeatherMap. Without an API key the map simply has no weather.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="show_weather_box" value="1" {{ $lmAn('acars.livemap_show_weather_box', true) ? 'checked' : '' }}> Show the weather button</label>
            </div>
            <div><p class="lma-hilfe">The weather panel in the toolbar. Off only removes the button: the layer at start (below) still shows. Without an OpenWeatherMap key there is no weather, so switch the button off.</p></div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="owm_api_key">OpenWeatherMap API key</label>
              <p class="lma-hilfe">Free at openweathermap.org. A brand-new key can take up to two hours before OpenWeatherMap accepts it; it is saved anyway.</p>
            </div>
            <div>
              <input id="owm_api_key" name="owm_api_key" type="password" class="form-control"
                     value="{{ old('owm_api_key', '') }}" autocomplete="new-password"
                     placeholder="{{ $weatherProxyStatus['hasApiKey'] ? 'Stored — leave empty to keep it' : 'Paste your OpenWeatherMap key' }}">
              @error('owm_api_key')<p class="lma-fehler">{{ $message }}</p>@enderror
              @if($weatherProxyStatus['hasApiKey'])
                <label class="lma-schalter lma-nebenbei"><input type="checkbox" name="owm_api_key_clear" value="1" {{ old('owm_api_key_clear') ? 'checked' : '' }}> Remove the stored key on save</label>
              @endif
              @if(!empty($weatherProxyStatus['lastErrorCode']) || $weatherProxyStatus['fallbackActive'])
                <details>
                  <summary>Last OpenWeatherMap error: {{ $weatherProxyStatus['errorInfo']['code'] ?? 'none' }}</summary>
                  <p class="lma-hilfe" style="margin-top:6px">{{ $weatherProxyStatus['errorInfo']['meaning'] }} {{ $weatherProxyStatus['errorInfo']['action'] }}</p>
                  @if(!empty($weatherProxyStatus['errorInfo']['reason']))
                    <p class="lma-hilfe">Detail: {{ $weatherProxyStatus['errorInfo']['reason'] }}</p>
                  @endif
                  @if(!empty($weatherProxyStatus['lastErrorAt']))
                    <p class="lma-hilfe">At {{ $weatherProxyStatus['lastErrorAt'] }}@if(!empty($weatherProxyStatus['lastSuccessAt'])) — last good tile {{ $weatherProxyStatus['lastSuccessAt'] }}@endif.</p>
                  @endif
                </details>
              @endif
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="weather_proxy_enabled" value="1" {{ $lmAn('acars.livemap_weather_proxy_enabled', true) ? 'checked' : '' }}> Load weather through this server (recommended)</label>
            </div>
            <div><p class="lma-hilfe">Keeps the key on the server. When off, the key is sent to every visitor's browser.</p></div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="weather_default_layer">Layer at start</label>
              <p class="lma-hilfe">Visitors can change it in the weather panel.</p>
            </div>
            <div>
              <select id="weather_default_layer" name="weather_default_layer" class="form-control">
                @foreach($layerOptions as $value => $label)
                  <option value="{{ $value }}" {{ $currentLayer === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label for="weather_default_opacity">Opacity at start</label>
              <p class="lma-hilfe">From 0.2 (faint) to 1 (solid).</p>
            </div>
            <div>
              <input id="weather_default_opacity" name="weather_default_opacity" type="number" min="0.2" max="1" step="0.05"
                     class="form-control" style="max-width:140px" value="{{ $lmS('acars.livemap_weather_default_opacity', 1) }}">
            </div>
          </div>
        </section>

        {{-- ── VATSIM / IVAO ── --}}
        <section class="lma-sek">
          <header>
            <h5>VATSIM and IVAO</h5>
            <p>Online network traffic from the public VATSIM and IVAO data. These options set what is switched on when the map opens; visitors can toggle each one.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="show_network_box" value="1" {{ $lmAn('acars.livemap_show_network_box', true) ? 'checked' : '' }}> Show the network button</label>
            </div>
            <div><p class="lma-hilfe">The network panel in the toolbar. Off only removes the button: whatever is switched on below still shows, and visitors cannot change it. To show no network traffic, untick VATSIM and IVAO as well.</p></div>
          </div>
          <div class="lma-zeile">
            <div><label>Networks at start</label></div>
            <div>
              <label class="lma-schalter"><input type="checkbox" name="default_network_vatsim" value="1" {{ $lmAn('acars.livemap_default_network_vatsim', true) ? 'checked' : '' }}> VATSIM</label>
              <label class="lma-schalter" style="margin-top:8px"><input type="checkbox" name="default_network_ivao" value="1" {{ $lmAn('acars.livemap_default_network_ivao', true) ? 'checked' : '' }}> IVAO</label>
            </div>
          </div>
          <div class="lma-zeile">
            <div>
              <label>Shown at start</label>
              <p class="lma-hilfe">Network pilots can crowd the map; controllers and sectors are lighter.</p>
            </div>
            <div>
              <label class="lma-schalter"><input type="checkbox" name="default_show_pilots" value="1" {{ $lmAn('acars.livemap_default_show_pilots', false) ? 'checked' : '' }}> Pilots</label>
              <label class="lma-schalter" style="margin-top:8px"><input type="checkbox" name="default_show_controllers" value="1" {{ $lmAn('acars.livemap_default_show_controllers', true) ? 'checked' : '' }}> Controllers</label>
              <label class="lma-schalter" style="margin-top:8px"><input type="checkbox" name="default_show_sectors" value="1" {{ $lmAn('acars.livemap_default_show_sectors', false) ? 'checked' : '' }}> FIR / UIR sectors</label>
            </div>
          </div>
        </section>

        {{-- ── Phones ── --}}
        <section class="lma-sek">
          <header>
            <h5>Phones and narrow screens</h5>
            <p>On narrow screens the flights list, the flight details and the toolbar panels share one column, so only one is open at a time.</p>
          </header>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="mobile_show_flights_button" value="1" {{ $lmAn('acars.livemap_mobile_show_flights_button', true) ? 'checked' : '' }}> Show the flights list</label>
            </div>
            <div><p class="lma-hilfe">Off hides the flights list on narrow screens; the aircraft stay on the map.</p></div>
          </div>
          <div class="lma-zeile">
            <div>
              <label class="lma-schalter"><input type="checkbox" name="mobile_flights_open" value="1" {{ $lmAn('acars.livemap_mobile_flights_open', false) ? 'checked' : '' }}> Open the flights list at start</label>
            </div>
            <div><p class="lma-hilfe">Position “Top” only. At the bottom the list is always open.</p></div>
          </div>
          <div class="lma-zeile">
            <div><label>Open a panel at start</label></div>
            <div>
              <label class="lma-schalter"><input type="checkbox" name="mobile_network_open" value="1" {{ $lmAn('acars.livemap_mobile_network_open', false) ? 'checked' : '' }}> Network</label>
              <label class="lma-schalter" style="margin-top:8px"><input type="checkbox" name="mobile_weather_open" value="1" {{ $lmAn('acars.livemap_mobile_weather_open', false) ? 'checked' : '' }}> Weather</label>
              <p class="lma-hilfe" style="margin-top:8px">Only one panel opens: Network wins over Weather, and neither opens when the flights list opens at start.</p>
            </div>
          </div>
        </section>

        <div class="lma-fuss">
          <button type="submit" class="btn btn-primary">Save settings</button>
          <p>Crafted with &hearts; in Germany by Thomas Kant · <a href="https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2" target="_blank" rel="noopener noreferrer">Support via PayPal</a></p>
        </div>
      </form>
    </div>
  </div>

  <script>
    // AeroACARS Live fields only matter when that source is selected.
    (function () {
      var wahl = document.getElementById('flight_source');
      if (!wahl) return;
      var zeige = function () {
        var live = wahl.value !== 'phpvms';
        document.querySelectorAll('[data-lma-live]').forEach(function (z) { z.hidden = !live; });
      };
      wahl.addEventListener('change', zeige);
      zeige();
    })();
  </script>
@endsection
