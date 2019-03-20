<?php
Route::group(['namespace'=>'Export\Http\Controllers'],function () {
       Route::any('exportItems', 'ExportController@exportItems');
       Route::any('downloadFiles', 'ExportController@downloadFiles');
       Route::any('moveFileToSftp', 'ExportController@moveFileToSftp');
       Route::post('exportFlatFile', 'ExportController@exportFlatFile');
       Route::any('downloadZipFile', 'ExportController@downloadZipFile');
       
});
