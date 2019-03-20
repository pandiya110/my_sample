<?php

Route::group(['namespace' => 'Import\Http\Controllers'], function () {

    Route::any('importAddItems', 'ImportController@importAddItems');    
    Route::post('uploadItems', 'ImportController@uploadItems');
    Route::post('importMasterItems', 'ImportController@importMasterItems');
    Route::post('uploadBulkItemsFile', 'ImportController@uploadBulkItemsFile');
    Route::post('bulkImportItems', 'ImportController@bulkImportItems');    
});
