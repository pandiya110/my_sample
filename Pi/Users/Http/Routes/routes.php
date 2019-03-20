<?php

Route::any('getActivationLink/{id}/{token}/{type}', 'Users\Http\Controllers\UserController@getActivationLink');
Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Users\Http\Controllers'], function () {

    Route::any('getUsersList', 'UserController@getUsersList');
    Route::any('showPermissions', 'UserController@showPermissions');
    Route::any('addPermissions', 'UserController@addPermissions');
    Route::any('getPermissions', 'UserController@getPermissions');
    Route::any('getGlobalData', 'UserController@getGlobalData');
    Route::post('addUser', 'UserController@addUser');
    Route::any('getUserDetails', 'UserController@getUserDetails');

    //Route::any('uploadLogo', 'UserController@uploadLogo'); 

//    Route::get('users', 'UserController@users');
//    Route::get('admin', 'UserController@users');
//    Route::get('sample', 'UserController@users');
//    Route::get('404', 'UserController@users');
//    Route::get('admin/departments', 'UserController@users');
//    Route::get('admin/channels', 'UserController@users');
//    Route::get('admin/roles', 'UserController@users');
//    Route::get('admin/campaigns', 'UserController@users');
//
//    Route::get('superadmin', 'UserController@users');
//    Route::get('superadmin/settings', 'UserController@users');
//    Route::get('superadmin/crons', 'UserController@users');
//    Route::get('events', 'UserController@users');
//    Route::get('listbuilder/events', 'UserController@users');
    Route::get('/', 'UserController@users');
//    Route::get('events/globalevents', 'UserController@users');
//    Route::get('events/draftevents', 'UserController@users');

    //Route::get('items/itemslist/{event_id}', 'UserController@users');
    //Route::get('items/linkeditems/{event_id}', 'UserController@users');
//    Route::get('devtest', 'UserController@users');
//    Route::any('superadmin/systemerrors', 'UserController@users');
//    Route::any('superadmin/emailcontroller', 'UserController@users');
//    Route::any('superadmin/sentemail', 'UserController@users');
//    Route::any('superadmin/userlogs', 'UserController@users');
//    Route::any('superadmin/errorlogs', 'UserController@users');
//    Route::any('superadmin/systemlogs', 'UserController@users');
//    Route::any('superadmin/emailtemplates', 'UserController@users');
//    Route::any('superadmin/generalsettings', 'UserController@users');

    Route::any('forgotPasswordLinkValidate', 'UserController@forgotPasswordLinkValidate');
    Route::any('resetPasswordLink', 'UserController@resetPasswordLink');
    Route::any('securePassword', 'UserController@securePassword');
   
    
});
Route::group(['namespace' => 'Users\Http\Controllers'], function () {
    Route::any('saveSSOUserPermissions', 'UserController@saveSSOUserPermissions');
    Route::any('uploadLogo', 'UserController@uploadLogo');
    Route::any('addTestUser', 'UserController@addTestUser');
    Route::any('resendActivationLink', 'UserController@resendActivationLink');
    Route::get('createPassword', 'UserController@createPassword');
    Route::post('forgotPassword', 'UserController@forgotPassword');
    Route::any('resetPassword', 'UserController@resetPassword');
});
//Route::group(['middleware' => ['EventItemsAccess', 'LoginCheck'], 'namespace' => 'Users\Http\Controllers'], function () {
//    Route::get('items/itemslist/{event_id}', 'UserController@users');
//    Route::get('items/linkeditems/{event_id}', 'UserController@users');
//});
