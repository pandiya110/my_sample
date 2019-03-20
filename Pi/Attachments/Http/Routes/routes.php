<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Attachments\Http\Controllers'], function () {

    Route::any('uploadAttachments', 'AttachmentsController@uploadAttachments');
});
