<?php

Route::group([], function () {
    Route::get('/', 'SettingsController@index');
    Route::post('/settings', 'SettingsController@update');
});
