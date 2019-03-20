<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Roles\Http\Controllers'], function () {

    Route::any('getRolesList', 'RolesController@getRolesList');
    Route::any('getRolesDetails', 'RolesController@getRolesDetails');
    Route::get('getColourCodeDropdown', 'RolesController@getColourCodeDropdown');
    Route::post('addRoles', 'RolesController@addRoles');
    Route::any('getRoleHeaders', 'RolesController@getRoleHeaders');
});




