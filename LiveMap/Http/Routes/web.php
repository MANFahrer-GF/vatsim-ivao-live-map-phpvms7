<?php

Route::group([], function () {
    Route::get('/weather-tile/{layer}/{z}/{x}/{y}.png', 'WeatherProxyController@tile')
        ->where([
            'layer' => '[a-z_]+',
            'z'     => '[0-9]+',
            'x'     => '[0-9]+',
            'y'     => '[0-9]+',
        ]);

    // Flugdaten fuer die Karte (Quelle laut Einstellung, siehe LiveFlightsController).
    Route::get('/api/fluege', 'LiveFlightsController@index')
        ->middleware('throttle:120,1');
    Route::get('/api/fluege/{va}/{pilot}/spur', 'LiveFlightsController@spur')
        ->where(['va' => '[A-Za-z0-9_.\-]{1,40}', 'pilot' => '[A-Za-z0-9_.\-]{1,40}'])
        ->middleware('throttle:120,1');
    Route::get('/api/fluege/{va}/{pilot}/route', 'LiveFlightsController@route')
        ->where(['va' => '[A-Za-z0-9_.\-]{1,40}', 'pilot' => '[A-Za-z0-9_.\-]{1,40}'])
        ->middleware('throttle:120,1');
});
