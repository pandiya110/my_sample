<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\GroupedDataSource as GroupedDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataSource\CopyItemsDataSource as copyDs;
use CodePi\Items\Utils\ItemsGridDataResponse;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\DataSource\ItemsDataSource as ItemsDs;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\Utils\BroadcastResponse;
/**
 * Handle the execution of publish items
 */
class UnGroupItems implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of item datasource
     */
    function __construct(GroupedDs $objGroupedDs, DataResponse $objDataResponse) {
        $this->dataSource = $objGroupedDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * 
     * @param type $command
     * @return type
     */
//    function execute($command) {
//
//        $arrResponse = [];
//        $response = $this->dataSource->unGroupItems($command);
//        if (isset($response['items_id']) && !empty($response['items_id'])) {
//
//            $data['items_id'] = $response['items_id'];
//            $data['event_id'] = PiLib::piEncrypt($command->event_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = array_merge($response, $cmdResponse['items']);
//            if (!empty($command->parent_item_id)) {
//
//                $itemCount = $this->dataSource->getGroupedItemsCount($command->parent_item_id);
//                $arrResponse['itemCount'] = array('item' => $itemCount);
//            }
//        }
//        $arrResponse['deleted_items'] = $response['deleted_items'];
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->event_id);
//        $arrResponse['status'] = $response['status'];
//        if ($arrResponse) {
//
//            broadcast(new ItemActions($arrResponse, 'ungroupitem'))->toOthers();
//        }
//        return $arrResponse;
//    }
    
    function execute($command) {

        $response = $this->dataSource->unGroupItems($command);
        $postData = $command->dataToArray();
        $postData['result'] = $response;
        $postData['items_id'] = is_array($response['items_id']) ? $response['items_id'] : [$response['items_id']];
        $postData['events_id'] = $postData['event_id'];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        if (!empty($postData['parent_item_id'])) {
            $itemCount = $this->dataSource->getGroupedItemsCount($command->parent_item_id);
            $arrResult['itemCount'] = array('item' => $itemCount);
        }
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('ungroupitem');
        $objBroadCast->updateToBroadcast();
        return $arrResult;
    }

}
