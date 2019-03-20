<?php

Route::group([ 'namespace' => 'Templates\Http\Controllers'], function () {

    Route::post('saveUsersTemplates', 'TemplatesController@saveUsersTemplates');
    Route::post('copyTemplates', 'TemplatesController@copyTemplates');
    Route::post('deleteTemplates', 'TemplatesController@deleteTemplates');
    Route::any('getTemplatesList', 'TemplatesController@getTemplatesList');
    Route::any('assignDefaultTemplate', 'TemplatesController@assignDefaultTemplate');
});


