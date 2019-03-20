<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\DataSource\CopyItemsDataSource;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;

/**
 * Class CoptITems
 * Copy the items from draft to globla events
 */
class CopyItems implements iCommands {

    /**
     *
     * @var class,instance of ItemsDataSource
     * @access private
     */
    private $dataSource;

    /**
     * 
     * @param ItemDs $objItemDs     
     */
    function __construct(CopyItemsDataSource $objItemDs) {
        $this->dataSource = $objItemDs;
    }

    /**
     * Execution of Copy Items
     * 
     * @param object $command
     * @return array of copty Items
     */
//    function execute($command) {
//
//        $arrResponse = $this->dataSource->copyItems($command);
//        /**
//         * Get the updated items row
//         * Command Name : GetItemsList
//         */
//        if ($arrResponse['status'] == true) {
//
//            $data['items_id'] = isset($arrResponse['items_id']) ? $arrResponse['items_id'] : [];
//            $data['event_id'] = PiLib::piEncrypt($command->to_events_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = array_merge($arrResponse, $cmdResponse['items']);
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//                $arrResponse['itemCount'] = array('item' => $itemCount);
//            }
//
//            broadcast(new ItemActions($arrResponse, 'addrow'))->toOthers();
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {

        $arrResponse = $this->dataSource->copyItems($command);
        $postData = $command->dataToArray();        
        $postData['result'] = $arrResponse;
        $postData['items_id'] = isset($arrResponse['items_id']) ? $arrResponse['items_id'] : [];
        $postData['events_id'] = $postData['to_events_id'];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult = array_merge($arrResponse, $arrResult);
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('addrow');
        $objBroadCast->updateToBroadcast();

        return $arrResult;
    }

}
