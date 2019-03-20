<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Channels\Http\Controllers'], function () {

    Route::post('addChannels', 'ChannelsController@addChannels');
    Route::post('getChannelsList', 'ChannelsController@getChannelsList');
    Route::any('getChannelDetails', 'ChannelsController@getChannelDetails');    
    Route::post('saveItemsChannels', 'ChannelsController@saveItemsChannels');
    Route::any('getChannelsAdtypes', 'ChannelsController@getChannelsAdtypes');
});
Route::group(['namespace' => 'Channels\Http\Controllers'], function () {
    Route::post('uploadChannelLogo', 'ChannelsController@uploadChannelLogo');
});

