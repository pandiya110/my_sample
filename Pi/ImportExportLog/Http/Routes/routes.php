<?php
Route::group(['middleware' => 'LoginCheck','namespace'=>'ImportExportLog\Http\Controllers'],function () {
        
//        Route::any('import/', 'ImportController@index');
        Route::any('listLogs','ImportExportLogController@listLogs');
        Route::any('systemLogs','ImportExportLogController@systemLogs');
    
});
