<?php

Route::group(['middleware' => 'LoginCheck','namespace' => 'ReportView\Http\Controllers'], function () {
    Route::any('getItemsReportView', 'ItemsReportViewController@getItemsReportView');
    Route::any('getLinkedItemsReportView', 'ItemsReportViewController@getLinkedItemsReportView');
});
