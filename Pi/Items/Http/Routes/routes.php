<?php

Route::group(['middleware' => 'LoginCheck', 'namespace' => 'Items\Http\Controllers'], function () {

    Route::any('getItemsByDepartments', 'ItemsController@getItemsByDepartments');
    Route::any('getItemsList', 'ItemsController@getItemsList');
    Route::any('getItemsHeaders', 'ItemsController@getItemsHeaders');
    Route::any('addItems', 'ItemsController@addItems');
    Route::any('deleteItems', 'ItemsController@deleteItems');
    Route::any('excludeItems', 'ItemsController@excludeItems');
    Route::any('editItems', 'ItemsController@editItems');
    Route::any('addItemRow', 'ItemsController@addItemRow');
    Route::any('processItems', 'ItemsController@processItems');
    Route::any('addPublishStatus', 'ItemsController@addPublishStatus');
    Route::any('getLinkedItems', 'ItemsController@getLinkedItems');
    Route::any('moveItems', 'ItemsController@moveItems');
    Route::any('unPublishItems', 'ItemsController@unPublishItems');
    Route::any('getRandomUsers', 'ItemsController@getRandomUsers');
    Route::post('appendReplaceItems', 'ItemsController@appendReplaceItems');
    Route::post('getItemsPriceZones', 'ItemsController@getItemsPriceZones');
    Route::post('copyItems', 'ItemsController@copyItems');
    Route::post('getHistCrsData', 'ItemsController@getHistCrsData');
    Route::post('editMultipleItems', 'ItemsController@editMultipleItems');
    Route::any('getAttributeColumnValues', 'ItemsController@getAttributeColumnValues');    
    Route::post('updateHiglightColours', 'ItemsController@updateHiglightColours');
    Route::post('updateManualVersions', 'ItemsController@updateManualVersions');
    Route::post('saveCustomColumnWidth', 'ItemsController@saveCustomColumnWidth');
    Route::post('duplicateItems', 'ItemsController@duplicateItems');
    Route::post('moveItemsToLinkedItems', 'ItemsController@moveItemsToLinkedItems');
    Route::any('getGroupedItemsList', 'ItemsController@getGroupedItemsList');
    Route::any('itemsGroupList', 'ItemsController@itemsGroupList');
    Route::any('addGroupItems', 'ItemsController@addGroupItems');
    Route::any('unGroupItems', 'ItemsController@unGroupItems');
    Route::post('addUsersToChannels', 'ItemsController@addUsersToChannels');
    Route::post('removeUsersFromChannels', 'ItemsController@removeUsersFromChannels');
    Route::post('addUserToEditChannels', 'ItemsController@addUserToEditChannels');
    Route::post('removeUserFromEditChannels', 'ItemsController@removeUserFromEditChannels');    
    Route::any('getVendorSupplyValue', 'ItemsController@getVendorSupplyValue');    
});

Route::group(['namespace' => 'Items\Http\Controllers'], function () {
    Route::get('checkExternalIpStatus', 'ItemsController@checkExternalIpStatus');
});

