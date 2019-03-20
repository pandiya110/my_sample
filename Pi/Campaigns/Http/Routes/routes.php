<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Campaigns\Http\Controllers'], function () {


    Route::any('getCampaignsList', 'CampaignsController@getCampaignsList');
    Route::post('saveCampaigns', 'CampaignsController@saveCampaigns');
    Route::any('getCampaignsDropdown', 'CampaignsController@getCampaignsDropdown');
    Route::any('getProjects', 'CampaignsController@getProjects');
});




