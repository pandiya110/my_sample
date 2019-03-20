<?php

Route::group(['namespace' => 'SyncItems\Http\Controllers'], function () {

    Route::post('saveSyncItems', 'SyncController@saveSyncItems');
    Route::post('saveApiAvailability', 'SyncController@saveApiAvailability');
    Route::post('reSyncItems', 'SyncController@reSyncItems');
    Route::post('checkAvailability', 'SyncController@checkAvailability');
});




