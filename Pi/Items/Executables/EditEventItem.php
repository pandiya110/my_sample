<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
#use CodePi\Base\DataTransformers\DataResponse;
#use CodePi\Events\Commands\GetEventDetails;
use \CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use \App\Events\ItemActions;
use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
use CodePi\Items\Utils\ItemsGridDataResponse;
use Illuminate\Support\Facades\Log;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;


/**
 * Handle the execution of delete items
 */
class EditEventItem implements iCommands {

    private $dataSource;    
    private $objCopyDs;    
    Private $objGridResponse;
    /**
     * 
     * @param ItemDs $objItemDs
     * @param DataResponse $objDataResponse
     */
    function __construct(ItemDs $objItemDs, CopyDs $objCopyDs, ItemsGridDataResponse $objGridResponse) {
        $this->dataSource = $objItemDs;        
        $this->objCopyDs = $objCopyDs;
        $this->objGridResponse = $objGridResponse;
    }

    /**
     * @param object $command
     * @return array of saved events $arrResponse
     */
//    function execute($command) {
//        $arrResponse = [];
//        $objResult = $this->dataSource->editEventItem($command);
//        /**
//         * Get the updated items row
//         */
//        if ($objResult['status'] == true) {
//
//            if (isset($command->item_key) && $command->item_key == 'grouped_item') {
//                $arrResponse['itemValues'] = [];
//            } else {
//                $data['items_id'] = $objResult['items_id'];
//                $data['event_id'] = PiLib::piEncrypt($command->event_id);
//                $data['parent_item_id'] = $command->parent_item_id;
//                $objCommand = new GetItemsList($data);
//                $cmdResponse = CommandFactory::getCommand($objCommand);
//                $response['status'] = true;
//                $arrResponse = array_merge($cmdResponse['items'], $response);
//            }
//
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//                $arrResponse['itemCount'] = array('item' => $itemCount);
//            }
//            if (isset($command->item_key) && $command->item_key == 'grouped_item') {
//                $arrResponse['itemValues'] = [];
//            }
//        }
//
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->event_id);
//        $arrResponse['deleted_items'] = $objResult['deleted_items'];
//        $arrResponse['status'] = $objResult['status'];
//        $arrResponse['is_updated'] = $objResult['is_updated'];
//        $arrResponse['users_id'] = $command->created_by;
//        
//        unset($objResult);
//        
//        if ($arrResponse) {
//            $broadcastdata = $arrResponse;
//            $broadcastdata['current_user'] = \Auth::User()->id;
//            broadcast(new ItemActions($broadcastdata, 'editCell'))->toOthers();
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {
        
        $editResponse = $this->dataSource->editEventItem($command);
        $postData = $command->dataToArray();        
        $postData['result'] = $editResponse;
        $postData['items_id'] = $editResponse['items_id'];
        $postData['events_id'] = $postData['event_id'];
        $objBroadCast = new BroadcastResponse($postData);
        
        if (isset($postData['item_key']) && $postData['item_key'] == 'grouped_item') {
            $arrResult['itemValues'] = [];
            $arrResult['event_id'] =  PiLib::piEncrypt($postData['event_id']);
            $arrResult = array_merge($editResponse, $arrResult);
        } else {
            $arrResult = $objBroadCast->getRowData();
            $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['event_id']);
            $arrResult = array_merge($editResponse, $arrResult);
        }
        unset($editResponse);
        if (!empty($postData['parent_item_id'])) {
            $objGroupDs = new GroupDs();
            $itemCount = $objGroupDs->getGroupedItemsCount($postData['parent_item_id']);
            $arrResult['itemCount'] = array('item' => $itemCount);
        }
        $arrResult['users_id'] = $postData['created_by'];
        $arrResult['current_user'] = \Auth::User()->id;
        unset($postData);
        
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('editCell');
        $objBroadCast->updateToBroadcast();
        unset($arrResult['current_user']);

        return $arrResult;
    }

}
