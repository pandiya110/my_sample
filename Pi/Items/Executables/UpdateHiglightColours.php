<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;

class UpdateHiglightColours implements iCommands {
    /**
     *
     * @var class
     */
    private $dataSource;

    /**
     * 
     * @param ItemDs $objItemDs     
     */
    function __construct(ItemDs $objItemDs) {
        $this->dataSource = $objItemDs;
    }
    
    /**
     * 
     * @param type $command
     * @return array
     */
//    function execute($command) {
//
//        $params = $command->dataToArray();
//        $arrResponse = [];
//        $arrResult = $this->dataSource->updateColourCodesByItems($params);
//        
//        if ($arrResult['status'] == true) {
//            $data['items_id'] = $arrResult['items_id'];
//            $data['event_id'] = PiLib::piEncrypt($command->event_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = array_merge($arrResult, $cmdResponse['items']);
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//                $arrResponse['itemCount'] = array('item' => $itemCount);
//            }
//        }
//        
//        $arrResponse['status'] = $arrResult['status'];
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->event_id);
//        
//        if ($arrResponse) {
//            broadcast(new ItemActions($arrResponse, 'update'))->toOthers();
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {
        $params = $command->dataToArray();
        $arrResponse = $this->dataSource->updateColourCodesByItems($params);
        $params['result'] = $arrResponse;
        $params['items_id'] = isset($arrResponse['items_id']) ? $arrResponse['items_id'] : [];
        $objBroadCast = new BroadcastResponse($params);
        $arrResult = $objBroadCast->getRowData();
        $arrResult = array_merge($arrResponse, $arrResult);
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($params['events_id']);
        if (!empty($params['parent_item_id'])) {
            $objGroupDs = new GroupDs();
            $itemCount = $objGroupDs->getGroupedItemsCount($params['parent_item_id']);
            $arrResponse['itemCount'] = array('item' => $itemCount);
        }
        
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('update');
        $objBroadCast->updateToBroadcast();

        return $arrResult;
    }

}
