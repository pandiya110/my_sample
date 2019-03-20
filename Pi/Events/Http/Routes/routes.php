<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Events\Http\Controllers'], function () {

    Route::post('addEvents', 'EventsController@addEvents');
    Route::any('getEventsList', 'EventsController@getEventsList');
    Route::any('getGlobal', 'EventsController@getGlobal');
    Route::any('getEventDetails', 'EventsController@getEventDetails');
    Route::any('getEventsDropDown', 'EventsController@getEventsDropDown');
    Route::any('getUsersDropDown', 'EventsController@getUsersDropDown');
});




