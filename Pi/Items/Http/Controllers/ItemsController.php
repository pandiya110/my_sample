<?php

namespace CodePi\Items\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Redirector;
use Response;
use Session;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Items\Commands\GetDepartmentItems;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\Commands\AddItems;
use CodePi\Items\Commands\GetItemsHeaders;
use CodePi\Items\Commands\DeleteEventItem;
use CodePi\Items\Commands\ExcludeEventItem;
use CodePi\Items\Commands\EditEventItem;
use CodePi\Items\Commands\CheckImportStatus;
use CodePi\Items\Commands\AddItemRow;
use CodePi\Items\Commands\AddItemPublish;
use CodePi\Items\Commands\GetLinkedItemsList;
use CodePi\Items\Commands\MoveLinkedItems;
use CodePi\Items\Commands\AddSyncItems;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Items\Commands\ProcessItems;
use CodePi\Items\Commands\UnPublishItems;
use CodePi\Items\Commands\GetRandomUsers;
use CodePi\Items\Commands\AppendReplaceItems;
use App\Events\EventUsers;
use CodePi\Items\Commands\GetItemsPriceZones;
use CodePi\Items\Commands\CopyItems;
use CodePi\Items\Commands\GetHistoricalCrossData;
use CodePi\Items\Commands\EditMultipleItems;
use CodePi\Base\Eloquent\MasterDataOptions;
use CodePi\Items\Commands\UpdateHiglightColours;
use CodePi\Items\Commands\UpdateManualVersions;
use CodePi\Items\Commands\SaveCustomColumnWidth;
use CodePi\Items\DataSource\UsersColumnWidthDS;
use CodePi\Items\Commands\DuplicateItems;
use CodePi\Items\Commands\MoveItemsToLinkedItems;
use CodePi\Items\Commands\GroupedItemsList;
use CodePi\Items\Commands\ItemsGroupList;
use CodePi\Items\Commands\AddGroupItems;
use CodePi\Items\Commands\UnGroupItems;
use App\Events\EventsLiveUsers;
use CodePi\Base\Libraries\PiLib;
use App\Events\EventsLiveUsersEdit;
use Illuminate\Support\Facades\Artisan;

class ItemsController extends PiController {
    
    public function __construct() {
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
        header("Pragma: no-cache"); // HTTP 1.0.
        header("Expires: 0");
    }

    /**
     * Get Items count group by users vs departments
     * 
     * @param Request $request
     * @return Response
     */
    public function getItemsByDepartments(Request $request) {
        $data = $request->all();
        $command = new GetDepartmentItems($data);
        return $this->run($command, trans('Items::messages.S_DepartmentItems'), trans('Items::messages.E_DepartmentItems'));
    }

    /**
     * Get List of items given by Event
     * 
     * @param Request $request
     * @return Response
     */
    public function getItemsList(Request $request) {
        $data = $request->all();
        $command = new GetItemsList($data);
        
//        $objItemsDs = new ItemsDataSource();    
//        $intEventiD = PiLib::piDecrypt($data['event_id']);    
//        $arrUserList = $objItemsDs->getBroadcastLiveUsers($intEventiD);
//        
//        event(new EventsLiveUsers($arrUserList, $data['event_id']));
        //broadcast(new EventUsers($data['event_id']))->toOthers();
        
        return $this->run($command, trans('Items::messages.S_GetItems'), trans('Items::messages.E_GetItems'));
    }

    /**
     * Get items deafult headers
     * 
     * @param Request $request
     * @return Response
     */
    public function getItemsHeaders(Request $request) {
        $data = $request->all();
        $command = new GetItemsHeaders($data);
        return $this->run($command, trans('Items::messages.S_ItemsHeaders'), trans('Items::messages.S_ItemsHeaders'));
    }

    /**
     * Save the Items
     * 
     * @param Request $request
     * @return Response
     */
    public function addItems(Request $request) {
        $data = $request->all();
        $command = new AddItems($data);
        return $this->run($command, trans('Items::messages.S_GetEvents'), trans('Items::messages.E_GetEvents'));
    }

    /**
     * Delete Items
     * 
     * @param Request $request
     * @return Response
     */
    public function deleteItems(Request $request) {
        $data = $request->all();
        $command = new DeleteEventItem($data);
        return $this->run($command, trans('Items::messages.S_DeleteItem'), trans('Items::messages.E_DeleteItem'));
    }

    /**
     * Exclude / Active the selected items
     * 
     * @param Request $request
     * @return Reponse
     */
    public function excludeItems(Request $request) {
        $data = $request->all();
        $command = new ExcludeEventItem($data);
        return $this->run($command, trans('Items::messages.S_ExcludeItem'), trans('Items::messages.E_ExcludeItem'));
    }

    /**
     * Edit the single row items
     * 
     * @param Request $request
     * @return Reponse
     */
    public function editItems(Request $request) {
        $data = $request->all();
        $command = new EditEventItem($data);
        return $this->run($command, trans('Items::messages.S_EditItem'), trans('Items::messages.E_EditItem'));
    }
   
    /**
     * Add new empty row in item grid
     * 
     * @param Request $request
     * @return Reponse
     */
    public function addItemRow(Request $request) {
        $data = $request->all();
        $command = new AddItemRow($data);
        return $this->run($command, trans('Items::messages.A_AddRow'), trans('Items::messages.E_AddRow'));
    }
   
    /**
     * Published the selected items
     * 
     * @param Request $request
     * @return Reponse
     */
    public function addPublishStatus(Request $request) {
        $data = $request->all();
        $command = new AddItemPublish($data);
        return $this->run($command, trans('Items::messages.S_PublishItem'), trans('Items::messages.E_PublishItem'));
    }

    /**
     * Get linked items
     * 
     * @param Request $request
     * @return Response
     */
    public function getLinkedItems(Request $request) {
        $data = $request->all();
        $command = new GetLinkedItemsList($data);
        return $this->run($command, trans('Items::messages.S_GetItems'), trans('Items::messages.E_GetItems'));
    }

    /**
     * Moved items from Linked to Normal items
     * 
     * @param Request $request
     * @return Reponse
     */
    public function moveItems(Request $request) {
        $data = $request->all();
        $command = new MoveLinkedItems($data);
        return $this->run($command, trans('Items::messages.S_MoveItem'), trans('Items::messages.E_MoveItem'));
    }

    /**
     * Unpublished the selected items
     * 
     * @param Request $request
     * @return Response
     */
    public function unPublishItems(Request $request) {
        $data = $request->all();
        $command = new UnPublishItems($data);
        return $this->run($command, trans('Items::messages.S_UnPublishItem'), trans('Items::messages.E_UnPublishItem'));
    }

    public function getRandomUsers(Request $request) {
        $data = $request->all();
        $command = new GetRandomUsers($data);
        return $this->run($command, trans('Items::messages.S_UnPublishItem'), trans('Items::messages.E_UnPublishItem'));
    }

    /**
     * Append/Replace , If user clicks on Append-API driven fields will be replaced , users edited fields will be left as they are
     * 
     * @param Request $request
     * @return Response
     */
    public function appendReplaceItems(Request $request) {
        $data = $request->all();
        $command = new AppendReplaceItems($data);
        return $this->run($command, trans('Items::messages.S_AppendReplace'), trans('Items::messages.E_AppendReplace'));
    }
    /**
     * Get Used and available versions
     * @param Request $request
     * @return Response
     */
    public function getItemsPriceZones(Request $request) {
        $data = $request->all();
        $command = new GetItemsPriceZones($data);
        return $this->run($command, trans('Items::messages.S_PriceZone'), trans('Items::messages.E_PriceZone'));
    }
    /**
     * Copy items from draft events to global events
     * @param Request $request
     * @return Response
     */
    public function copyItems(Request $request) {
        $data = $request->all();
        $command = new CopyItems($data);
        return $this->run($command, trans('Items::messages.S_CopyItem'), trans('Items::messages.E_CopyItem'));
    }
    /**
     * 
     * @param Request $request
     * @return Response
     */
    public function getHistCrsData(Request $request) {
        $data = $request->all();
        $command = new GetHistoricalCrossData($data);
        return $this->run($command, trans('Items::messages.S_HisCrossData'), trans('Items::messages.E_HisCrossData'));
    }
    /**
     * Edit multible items data
     * @param Request $request
     * @return Response
     */
    public function editMultipleItems(Request $request) {
        $data = $request->all();
        $command = new EditMultipleItems($data);
        return $this->run($command, trans('Items::messages.S_MultiEdit'), trans('Items::messages.E_MultiEdit'));
    }
    /**
     * getAttributeColumnValues (New, Exclusive, USDA)
     * @param Request $request
     * @return Response
     */
    public function getAttributeColumnValues(Request $request){       
        $data = $request->all();
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->getAttributeColumnValues($data);        
        $code = ($result) ? trans('Items::messages.S_AttrValue') : trans('Items::messages.E_AttrValue');
        $status = ($result) ? true : false;
        $response = new DataSourceResponse($result, $code, $status);
        return Response::json($response->formatMessage());    
    }
    
    /**
     * Update the higlighting cell colurs
     * @param Request $request
     * @return Response
     */
    public function updateHiglightColours(Request $request) {
        $data = $request->all();
        $command = new UpdateHiglightColours($data);
        return $this->run($command, trans('Items::messages.S_HiglightColour'), trans('Items::messages.E_HiglightColour'));
    }
    /**
     * add/remove the versions manualy 
     * @param Request $request
     * @return Response
     */
    public function updateManualVersions(Request $request) {
        $data = $request->all();
        $command = new UpdateManualVersions($data);
        return $this->run($command, trans('Items::messages.S_UpdateVersion'), trans('Items::messages.E_UpdateVersion'));
    }
    /**
     * Save the Users custom columns width
     * @param Request $request
     * @return Response
     */
    public function saveCustomColumnWidth(Request $request){
        $data = $request->all();
        $command = new SaveCustomColumnWidth($data);
        return $this->run($command, trans('Items::messages.S_ColumnWidth'), trans('Items::messages.E_ColumnWidth'));
    }
    /**
     * Duplicate the items from events
     * @param Request $request
     * @return Response
     */
     public function duplicateItems(Request $request){
        $data = $request->all();
        $command = new DuplicateItems($data);
        return $this->run($command, trans('Items::messages.S_ColumnWidth'), trans('Items::messages.E_ColumnWidth'));
    }
    /**
     * Move items to linked items
     * @param Request $request
     * @return Response
     */
     public function moveItemsToLinkedItems(Request $request){
        $data = $request->all();
        $command = new MoveItemsToLinkedItems($data);
        return $this->run($command, trans('Items::messages.S_MoveItem'), trans('Items::messages.E_MoveItem'));
    }

    /**
     * Get Grouped items list
     * @param id $parent_item_id parent item
     * @param id $event_id event id
     * @return Response
     */
     public function getGroupedItemsList(Request $request){
        $data = $request->all();
        $command = new GroupedItemsList($data);
        return $this->run($command, trans('Items::messages.S_GetItems'), trans('Items::messages.E_GetItems'));
    }
 
    /**
     * Get UnGrouped items list
     * @param id $event_id event id
     * @param array $item_id array of items which can be group
     * @return Response
     */
     public function itemsGroupList(Request $request){
        $data = $request->all();
        $command = new ItemsGroupList($data);
        return $this->run($command, trans('Items::messages.S_GetItems'), trans('Items::messages.E_GetItems'));
    }
    /**
     * Add items to a group
     * @param string $name group name
     * @param id $event_id event id
     * @param id $item_id parent item id
     * @param array $items array of items to be grouped
     * @return Response
     */
     public function addGroupItems(Request $request){
        $data = $request->all();
        $command = new AddGroupItems($data);
        return $this->run($command, trans('Items::messages.S_AddGroup'), trans('Items::messages.E_AddGroup'));
    }
    /**
     * Remove items from a group
     * @param id $event_id event id
     * @return Response
     */
     public function unGroupItems(Request $request){
        $data = $request->all();
        $command = new UnGroupItems($data);
        return $this->run($command, trans('Items::messages.S_UnGroup'), trans('Items::messages.E_UnGroup'));
    }
 
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function addUsersToChannels(Request $request){
        $data = $request->all();
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->addUsersToChannels($data);
        /**
         * Send user data to Events
         */
        $intEventID = PiLib::piDecrypt($data['event_id']);
        $arrUsersList = $objItemsDs->getBroadcastLiveUsers($intEventID);
        event(new EventsLiveUsers($arrUsersList, $data['event_id']));
        
        $code = ($result) ? trans('Items::messages.S_AddUserChannel') : trans('Items::messages.E_AddUserChannel');
        $status = ($result) ? true : false;
        $response = new DataSourceResponse($result, $code, $status);
        return Response::json($response->formatMessage());    
        
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function removeUsersFromChannels(Request $request){
        $data = $request->all();
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->removeUsersFromChannels($data);
        /**
         * Send user data to Events
         */
        $intEventID = PiLib::piDecrypt($data['event_id']);
        $arrUsersList = $objItemsDs->getBroadcastLiveUsers($intEventID);
        event(new EventsLiveUsers($arrUsersList, $data['event_id']));
        
        $code = ($result) ? trans('Items::messages.S_RemoveUserChannel') : trans('Items::messages.E_RemoveUserChannel');
        $status = ($result) ? true : false;
        $response = new DataSourceResponse($result, $code, $status);
        return Response::json($response->formatMessage());    
        
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function addUserToEditChannels(Request $request){
        $data = $request->all();
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->addUserToEditChannels($data);
        
        $intEventID = PiLib::piDecrypt($data['event_id']);
        $arrUsersList = $objItemsDs->getUsersLiveEditInfo($intEventID);
        event(new EventsLiveUsersEdit($arrUsersList, $data['event_id']));
        
        $code = ($result) ? trans('Items::messages.S_AddUserChannel') : trans('Items::messages.E_AddUserChannel');
        $status = ($result) ? true : false;
        $response = new DataSourceResponse($result, $code, $status);
        return Response::json($response->formatMessage());    
        
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function removeUserFromEditChannels(Request $request) {
        $data = $request->all();
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->removeUserFromEditChannels($data);

        $intEventID = PiLib::piDecrypt($data['event_id']);
        $arrUsersList = $objItemsDs->getUsersLiveEditInfo($intEventID);
        event(new EventsLiveUsersEdit($arrUsersList, $data['event_id']));

        $code = ($result) ? trans('Items::messages.S_RemoveUserChannel') : trans('Items::messages.E_RemoveUserChannel');
        $status = ($result) ? true : false;
        $response = new DataSourceResponse($result, $code, $status);
        return Response::json($response->formatMessage());
    }
    /**
     * 
     * @param Request $request
     * @return Response
     */
    function checkExternalIpStatus(Request $request) {
        Artisan::call('serverRunStatus');
        $result = array('Result' => Artisan::output());
        $response = new DataSourceResponse($result, 'success', true);
        return Response::json($response->formatMessage());
    }
    /**
     * 
     * @param Request $request
     * @return Response
     */
    public function getVendorSupplyValue(Request $request){       
        $data = $request->all();
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->getVendorSupplyValue($data);        
        $code = ($result) ? trans('Items::messages.S_AttrValue') : trans('Items::messages.E_AttrValue');
        $status = ($result) ? true : false;
        $response = new DataSourceResponse($result, $code, $status);
        return Response::json($response->formatMessage());    
    }

}
