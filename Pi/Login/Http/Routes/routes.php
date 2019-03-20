<?php


Route::group(['middleware' => 'LoginCheck' ,'namespace' => 'Login\Http\Controllers'], function () {
 

    

});

Route::group(['namespace' => 'Login\Http\Controllers'], function () {
    //Route::any('/', 'LoginController@index');  
    Route::any('login', 'LoginController@index'); 
    //Route::any('login', ['uses' => 'LoginSSOController@getLoginSsoUrl']); 
    Route::post('getLogin', 'LoginController@getLogin');
    Route::any('logout', 'LoginController@logout');
    Route::any('getloginsso', ['uses' => 'LoginSSOController@getLoginSsoUrl']);
    Route::any('dologinsso', ['uses' => 'LoginSSOController@doLoginSso']);
    //Route::any('doLogoutSso', ['uses' => 'LoginSSOController@doLogoutSso']);
     Route::any('doLogoutSso', ['uses' => 'LoginController@logout']);
    Route::any('ssoAuth', ['uses' => 'LoginSSOController@doLoginSso']);
    Route::any('ssoLogout', ['uses' => 'LoginController@logout']);
    
});




