<?php


Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Admin\Http\Controllers'], function () {

Route::post('addDepartments', 'AdminController@addDepartments');  
Route::any('getDepartments', 'AdminController@getDepartments');
Route::any('departments', 'AdminController@departments');

});




