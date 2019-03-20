<?php

Route::group(['namespace' => 'RestApiSync\Http\Controllers'], function () {

    Route::any('getEvents', 'EventsController@getEvents');
    Route::any('syncEvents', 'EventsController@syncEvents');
    Route::any('getItems', 'ItemsController@getItems');
    Route::any('syncItems', 'ItemsController@syncItems');
    Route::any('syncItemsChannels', 'ItemsController@syncItemsChannels');
    Route::any('docs', 'ItemsController@docs');
});
