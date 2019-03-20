<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Events\Commands\GetEventDetails;
use \CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use \App\Events\ItemActions;
use CodePi\Items\DataSource\CopyItemsDataSource;
use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;

class EditMultipleItems implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * 
     * @param ItemDs $objItemDs
     * @param DataResponse $objDataResponse
     */
    function __construct(ItemDs $objItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objItemDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Executions of multiple edit
     * @param object $command
     * @return array of updated items $arrResponse
     */
//    function execute($command) {
//        $arrResponse = [];
//        $arrResult = $this->dataSource->editMultipleItems($command);
//
//        /**
//         * Get updated rows
//         */
//        if ($arrResult['status'] == true) {
//            $data['items_id'] = $arrResult['items_id'];
//            $data['event_id'] = PiLib::piEncrypt($command->events_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = $cmdResponse['items'];
//
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//                $arrResponse['itemCount'] = array('item' => $itemCount);
//            }
//        }
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//        $arrResponse['status'] = $arrResult['status'];
//        if ($arrResponse) {
//            $checkPort = PiLib::isPortOpen(config('smartforms.socket_host'), config('smartforms.socket_id'));
//            if ($checkPort) {
//                $broadcastdata = $arrResponse;
//                $broadcastdata['current_user'] = \Auth::User()->id;
//                broadcast(new ItemActions($broadcastdata, 'multiEdit'))->toOthers();
//            }
//        }
//        $arrResponse['is_copied'] = true;
//        if (!empty($arrResult['exists'])) {
//            $arrResponse['un_copied'] = $arrResult['exists'];
//            $arrResponse['is_copied'] = false;
//        }
//        return $arrResponse;
//    }
    
    function execute($command) {

        $response = $this->dataSource->editMultipleItems($command);
        $postData = $command->dataToArray();
        $postData['result'] = $response;
        $postData['items_id'] = is_array($response['items_id']) ? $response['items_id'] : [$response['items_id']];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        if (!empty($postData['parent_item_id'])) {
            $objGroupDs = new GroupDs();
            $itemCount = $objGroupDs->getGroupedItemsCount($postData['parent_item_id']);
            $arrResult['itemCount'] = array('item' => $itemCount);
        }
        $arrResult['current_user'] = \Auth::User()->id;
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('multiEdit');
        $objBroadCast->updateToBroadcast();
        $arrResult['is_copied'] = true;
        if (!empty($response['exists'])) {
            $arrResult['un_copied'] = $response['exists'];
            $arrResult['is_copied'] = false;
        }
        unset($response, $arrResult['current_user']);
        return $arrResult;
    }

}
