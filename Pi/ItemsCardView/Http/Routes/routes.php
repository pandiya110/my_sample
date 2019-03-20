<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'ItemsCardView\Http\Controllers'], function () {
    Route::any('getCardView', 'ItemsCardViewController@getCardView');
    Route::any('exportCardViewPdf', 'ItemsCardViewController@exportCardViewPdf');
    Route::any('downloadCardView', 'ItemsCardViewController@downloadCardView');
});


