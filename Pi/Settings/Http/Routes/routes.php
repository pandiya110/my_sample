<?php

Route::group(['namespace' => 'Settings\Http\Controllers'], function () {
    Route::post('save_settings', 'EmailController@settings');
    Route::post('emailControllerList', 'EmailController@emailControllerData');
    //Route::post('getEmailControllerData', 'EmailController@getEmailControllerData');
    Route::post('showEmailControllerMessage', 'EmailController@getEmailControllerMessage');
    Route::post('showEmailDetailsMessage', 'EmailController@getEmailDetailsMessage');
    Route::post('sendEmailControllerMail', 'EmailController@sendEmailControllerMail');
    Route::post('sentMailsList', 'EmailController@getEmailDetails');
    Route::post('getUserLogsList', 'EmailController@getUserLogsList');
    Route::post('importExportLogs', 'EmailController@importExportLogs');
    Route::post('errorLogs', 'EmailController@errorLogs');
    Route::any('systemErrors', 'EmailController@systemErrors');
    Route::any('updateSystemErrorStatus', 'EmailController@updateSystemErrorStatus');
    Route::any('tableSequences', 'EmailController@tableSequences');
    Route::any('updateSequences', 'EmailController@updateSequences');
    Route::any('listSchemas', 'EmailController@listSchemas');
    Route::any('emailTemplates', 'EmailController@emailTemplates');
    Route::any('listCrons', 'EmailController@listCrons');
    Route::any('getGeneralSettings', 'EmailController@getGeneralSettings');
    Route::any('saveGeneralSettings', 'EmailController@saveGeneralSettings');
    Route::any('cronsHandleManual', 'EmailController@cronsHandleManual');
});
Route::any('removeCache', 'EmailController@removeCache');
?>
