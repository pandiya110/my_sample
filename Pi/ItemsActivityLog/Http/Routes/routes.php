<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'ItemsActivityLog\Http\Controllers'], function () {

    Route::post('getActivityLogs', 'ActivityLogController@getActivityLogs');
    Route::any('getActivityLogsDetails', 'ActivityLogController@getActivityLogsDetails');
});


