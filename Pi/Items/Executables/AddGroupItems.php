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
use CodePi\Items\Utils\BroadcastResponse;

/**
 * Handle the execution of publish items
 */
class AddGroupItems implements iCommands {

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
     * @param object $command
     * @return array of grouped items 
     */
//    function execute($command) {
//
//        $response = $this->dataSource->addGroupItems($command);
//        $arrResponse = [];
//        if (isset($response['items_id']) && !empty($response['items_id'])) {
//            $data['items_id'] = array($response['items_id']);
//            $data['event_id'] = $command->events_id;
//            $objCopyDs = new copyDs();
//            $return['objResult'] = $objCopyDs->getItemListById($data);
//            $objGridResponse = new ItemsGridDataResponse();
//            $arrResponse = $objGridResponse->getGridResponse($return, array(), $command->events_id);
//        }
//        $arrResponse['deleted_items'] = $response['deleted_items'];
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//        $arrResponse['status'] = $response['status'];
//        if($arrResponse){
//            broadcast(new ItemActions($arrResponse, 'groupitem'))->toOthers();
//        }
//        return $arrResponse;
//    }
    
    function execute($command) {

        $response = $this->dataSource->addGroupItems($command);        
        $postData = $command->dataToArray();
        $postData['result'] = $response;
        $postData['items_id'] = is_array($response['items_id']) ? $response['items_id'] : [$response['items_id']];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('groupitem');
        $objBroadCast->updateToBroadcast();
        return $arrResult;
    }

}
