<?php

Route::group([], function () {
    Route::get('/weather-tile/{layer}/{z}/{x}/{y}.png', 'WeatherProxyController@tile')
        ->where([
            'layer' => '[a-z_]+',
            'z'     => '[0-9]+',
            'x'     => '[0-9]+',
            'y'     => '[0-9]+',
        ]);
});
