@extends('livemap::layouts.admin')

@section('title', 'Live Map Settings')

@section('content')
  @php
    $lmS = function (string $key, $default = null) use ($settings) {
      return array_key_exists($key, $settings) ? $settings[$key] : $default;
    };
    $oldStyleChecked = (bool) $lmS('acars.livemap_old_style', false);
    $layoutMode = $oldStyleChecked ? 'old_style' : 'modern';
  @endphp
  <div class="card border-blue-bottom">
    <div class="content">
      <div class="header">
        <h4 class="title">Live Map Settings</h4>
      </div>

      @if (session('status'))
        <div class="alert alert-success">
          {{ session('status') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul style="margin:0; padding-left:18px">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="alert alert-{{ $weatherProxyStatus['badgeClass'] }}" style="margin-bottom:14px;">
        <strong>Weather Proxy Status:</strong>
        {{ $weatherProxyStatus['title'] }}<br>
        <span style="font-size:12px">{{ $weatherProxyStatus['message'] }}</span>
        <div style="margin-top:8px; font-size:12px;">
          <span>Proxy: <strong>{{ $weatherProxyStatus['proxyEnabled'] ? 'ON' : 'OFF' }}</strong></span>
          <span style="margin-left:10px;">API Key: <strong>{{ $weatherProxyStatus['hasApiKey'] ? 'SET' : 'MISSING' }}</strong></span>
          <span style="margin-left:10px;">Fallback: <strong>{{ $weatherProxyStatus['fallbackActive'] ? 'ACTIVE' : 'NO' }}</strong></span>
        </div>
        <div style="margin-top:10px; font-size:12px; border-top:1px solid rgba(0,0,0,0.08); padding-top:8px;">
          <div>
            <strong>Last OWM Error Code:</strong>
            {{ $weatherProxyStatus['errorInfo']['code'] ?? 'none' }}
            @if(!empty($weatherProxyStatus['lastErrorAt']))
              <span style="color:#777;">(at {{ $weatherProxyStatus['lastErrorAt'] }})</span>
            @endif
          </div>
          <div style="margin-top:4px;">
            <strong>Meaning:</strong> {{ $weatherProxyStatus['errorInfo']['meaning'] }}
          </div>
          <div style="margin-top:2px;">
            <strong>What to do:</strong> {{ $weatherProxyStatus['errorInfo']['action'] }}
          </div>
          @if(!empty($weatherProxyStatus['errorInfo']['reason']))
            <div style="margin-top:2px; color:#666;">
              <strong>Technical detail:</strong> {{ $weatherProxyStatus['errorInfo']['reason'] }}
            </div>
          @endif
          @if(!empty($weatherProxyStatus['lastSuccessAt']))
            <div style="margin-top:2px; color:#666;">
              <strong>Last successful tile:</strong> {{ $weatherProxyStatus['lastSuccessAt'] }}
            </div>
          @endif
        </div>
        <div style="margin-top:8px; font-size:12px; color:#555;">
          Note: The error code shows the last OpenWeatherMap response. "Fallback Active" means temporary blank tiles are served to avoid 502 browser error spam.
        </div>
      </div>

      @if(!$acarsLiveTimeStatus['isSafe'])
        <div class="alert alert-warning" style="margin-bottom:14px;">
          <strong>phpVMS Core Note:</strong>
          ACARS <strong>Live Time</strong> is currently <strong>{{ $acarsLiveTimeStatus['value'] }}</strong>.
          Keep it at <strong>1 or higher</strong> in <em>Admin - ACARS</em>.
          Do not use <code>0</code> on production systems, because this value is also used by core stale/stuck PIREP cleanup routines.
        </div>
      @else
        <p class="help-block" style="margin-top:-4px; margin-bottom:14px;">
          phpVMS Core: ACARS <strong>Live Time</strong> detected as <strong>{{ $acarsLiveTimeStatus['value'] }}</strong> (safe).
        </p>
      @endif

      <form method="POST" action="{{ url('/admin/livemap/settings') }}">
        @csrf

        <h5>Layout</h5>
        <div class="form-group">
          <label for="layout_mode">Layout mode</label>
          <select id="layout_mode" name="layout_mode" class="form-control">
            <option value="modern" {{ $layoutMode === 'modern' ? 'selected' : '' }}>
              Modern (show top flights panel)
            </option>
            <option value="old_style" {{ $layoutMode === 'old_style' ? 'selected' : '' }}>
              Old Style (hide top flights table overlay)
            </option>
          </select>
          <p class="help-block" style="margin-top:6px">
            Choose one mode to avoid conflicting layout settings.
          </p>
        </div>
        <div class="form-group">
          <label for="default_basemap">Default basemap</label>
          <select id="default_basemap" name="default_basemap" class="form-control">
            @php($currentBasemap = $lmS('acars.livemap_default_basemap', 'positron'))
            @foreach($basemapOptions as $value => $label)
              <option value="{{ $value }}" {{ $currentBasemap === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          <p class="help-block" style="margin-top:6px">
            Select which map style is used by default when users open the live map.
          </p>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="show_basemap_switcher" value="1" {{ $lmS('acars.livemap_show_basemap_switcher', true) ? 'checked' : '' }}>
            Show basemap switcher on map
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="enable_satellite" value="1" {{ $lmS('acars.livemap_enable_satellite', true) ? 'checked' : '' }}>
            Enable satellite map option
          </label>
          <p class="help-block" style="margin-top:6px">
            Satellite uses Esri World Imagery tiles.
          </p>
        </div>

        <hr>
        <h5>Weather</h5>
        <div class="form-group">
          <label>
            <input type="checkbox" name="show_weather_box" value="1" {{ $lmS('acars.livemap_show_weather_box', true) ? 'checked' : '' }}>
            Show weather box
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="weather_proxy_enabled" value="1" {{ $lmS('acars.livemap_weather_proxy_enabled', true) ? 'checked' : '' }}>
            Enable server-side weather proxy (recommended)
          </label>
          <p class="help-block" style="margin-top:6px">
            When enabled, OpenWeatherMap key stays server-side and is not exposed in browser DevTools.
          </p>
        </div>
        <div class="form-group">
          <label for="owm_api_key">OpenWeatherMap API Key</label>
          <input
            id="owm_api_key"
            name="owm_api_key"
            type="password"
            class="form-control"
            value="{{ $lmS('acars.livemap_owm_api_key', '') }}"
            autocomplete="off"
            placeholder="Paste OWM key">
          <p class="help-block" style="margin-top:6px">
            The key is validated on Save against OpenWeatherMap. Invalid keys are rejected.
          </p>
        </div>
        <div class="form-group">
          <label for="weather_default_layer">Default weather layer</label>
          <select id="weather_default_layer" name="weather_default_layer" class="form-control">
            @php($currentLayer = $lmS('acars.livemap_weather_default_layer', 'combo'))
            @foreach($layerOptions as $value => $label)
              <option value="{{ $value }}" {{ $currentLayer === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="weather_default_opacity">Default weather opacity (0.2 - 1.0)</label>
          <input
            id="weather_default_opacity"
            name="weather_default_opacity"
            type="number"
            min="0.2"
            max="1"
            step="0.05"
            class="form-control"
            value="{{ $lmS('acars.livemap_weather_default_opacity', 1) }}">
        </div>

        <hr>
        <h5>Network</h5>
        <div class="form-group">
          <label>
            <input type="checkbox" name="show_network_box" value="1" {{ $lmS('acars.livemap_show_network_box', true) ? 'checked' : '' }}>
            Show network box
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="default_network_vatsim" value="1" {{ $lmS('acars.livemap_default_network_vatsim', true) ? 'checked' : '' }}>
            VATSIM enabled by default
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="default_network_ivao" value="1" {{ $lmS('acars.livemap_default_network_ivao', true) ? 'checked' : '' }}>
            IVAO enabled by default
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="default_show_pilots" value="1" {{ $lmS('acars.livemap_default_show_pilots', false) ? 'checked' : '' }}>
            Show pilots by default
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="default_show_controllers" value="1" {{ $lmS('acars.livemap_default_show_controllers', true) ? 'checked' : '' }}>
            Show controllers by default
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="default_show_sectors" value="1" {{ $lmS('acars.livemap_default_show_sectors', false) ? 'checked' : '' }}>
            Show FIR/UIR sectors by default
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="default_follow_flight" value="1" {{ $lmS('acars.livemap_default_follow_flight', true) ? 'checked' : '' }}>
            Follow flight by default
          </label>
        </div>

        <hr>
        <h5>Mobile</h5>
        <div class="form-group">
          <label>
            <input type="checkbox" name="mobile_show_flights_button" value="1" {{ $lmS('acars.livemap_mobile_show_flights_button', true) ? 'checked' : '' }}>
            Show mobile Flights button
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="mobile_flights_open" value="1" {{ $lmS('acars.livemap_mobile_flights_open', false) ? 'checked' : '' }}>
            Open Flights panel by default on mobile
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="mobile_weather_open" value="1" {{ $lmS('acars.livemap_mobile_weather_open', false) ? 'checked' : '' }}>
            Open Weather panel by default on mobile
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="mobile_network_open" value="1" {{ $lmS('acars.livemap_mobile_network_open', false) ? 'checked' : '' }}>
            Open Network panel by default on mobile
          </label>
        </div>

        <hr>
        <h5>Colors</h5>
        <p class="help-block" style="margin-top:-4px">
          Reduced to the essentials: only 3 color settings for the whole map UI.
        </p>
        <p class="help-block" style="margin-top:-2px">
          These colors only change the UI design (panels/buttons), never flight or network data.
        </p>
        <div class="form-group">
          <label for="ui_primary_color">Primary UI Color</label>
          <input
            id="ui_primary_color"
            name="ui_primary_color"
            type="color"
            class="form-control"
            value="{{ $lmS('acars.livemap_color_flights_header_start', '#1A2A4A') }}">
          <p class="help-block" style="margin-top:6px">
            Used for weather/network headers, flights header start, and mobile flights button (inactive).
          </p>
        </div>
        <div class="form-group">
          <label for="ui_accent_color">Accent UI Color</label>
          <input
            id="ui_accent_color"
            name="ui_accent_color"
            type="color"
            class="form-control"
            value="{{ $lmS('acars.livemap_color_flights_header_end', '#243B6A') }}">
          <p class="help-block" style="margin-top:6px">
            Used for flights header end and mobile flights button (active/open).
          </p>
        </div>
        <div class="form-group">
          <label for="color_box_background">Box Background Color</label>
          <input
            id="color_box_background"
            name="color_box_background"
            type="color"
            class="form-control"
            value="{{ $lmS('acars.livemap_color_box_background', '#FFFFFF') }}">
          <p class="help-block" style="margin-top:6px">
            Body background color of flights/weather/network boxes.
          </p>
        </div>
        <div class="alert alert-info" style="font-size:12px; margin-top:8px; margin-bottom:14px;">
          <strong>Quick map:</strong><br>
          Primary = most headers + mobile flights button (inactive).<br>
          Accent = flights gradient end + mobile flights button (active).<br>
          Box Background = content area of flights/weather/network boxes.
        </div>

        <hr>
        <button type="submit" class="btn btn-primary">Save Settings</button>
        <p style="margin-top:14px; font-size:12px; color:#666;">
          Crafted with &hearts; in Germany by Thomas Kant -
          <a href="https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2" target="_blank" rel="noopener noreferrer">Support via PayPal</a>
        </p>
      </form>

    </div>
  </div>
@endsection
