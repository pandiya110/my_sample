<?php

Route::group(['namespace' => 'Api\Http\Controllers'], function () {

    Route::any('getMasterItems', 'ApiController@getMasterItems');
    Route::any('getApiResult', 'ApiController@getApiResult');
    Route::any('getAutoSearchVal', 'ApiController@getAutoSearchVal');
    Route::any('EmiApi', 'ApiController@EmiApi');
});




