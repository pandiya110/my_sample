<?php

Route::group(['namespace' => 'Aprimo\Http\Controllers'], function () {
    Route::any('updateAprimoAuthDetails', 'AprimoController@updateAprimoAuthDetails');
    Route::any('getAprimoActivities', 'AprimoController@getAprimoActivities');
    Route::any('getAprimoProjects', 'AprimoController@getAprimoProjects');
});
