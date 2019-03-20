<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\DataSource\CopyItemsDataSource;
use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;
class DuplicateItems implements iCommands {

    /**
     *
     * @var class,instance of ItemsDataSource
     * @access private
     */
    private $dataSource;
    Private $objGridResponse;
    Private $objItemDs;

    /**
     * 
     * @param ItemDs $objItemDs     
     */
    function __construct(CopyItemsDataSource $objCopyDs, ItemsGridDataResponse $objGridResponse, ItemDs $objItemDs) {
        $this->dataSource = $objCopyDs;
        $this->objGridResponse = $objGridResponse;
        $this->objItemDs = $objItemDs;
    }

    /**
     * Execution of Duplicate Items
     * 
     * @param object $command
     * @return array of duplicate Items
     */
//    function execute($command) {
//
//        $arrResponse = $this->dataSource->duplicateItems($command);
//
//        /**
//         * Get the updated items row
//         * Command Name : GetItemsList
//         */
//        if ($arrResponse['status'] == true) {
//
//            $data['items_id'] = isset($arrResponse['items_id']) ? $arrResponse['items_id'] : [];
//            $data['event_id'] = PiLib::piEncrypt($command->events_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = array_merge($arrResponse, $cmdResponse['items']);
//            if (isset($arrResponse['isDuplicateFromGroup']) && $arrResponse['isDuplicateFromGroup'] == true) {
//                $arrResponse['itemValues'] = array();
//            }
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $arrResponse['itemCount'] = array('item' => $objGroupDs->getGroupedItemsCount($command->parent_item_id));
//            }
//
//            $broadcastdata = $arrResponse;
//            $broadcastdata['current_user'] = \Auth::User()->id;
//            broadcast(new ItemActions($broadcastdata, 'duplicateRow'))->toOthers();
//        }
//
//        return $arrResponse;
//    }

    
    function execute($command) {

        $arrResponse = $this->dataSource->duplicateItems($command);        
        $postData = $command->dataToArray();
        $postData['result'] = $arrResponse;
        $postData['items_id'] = $arrResponse['items_id'];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($command->events_id);
        $arrResult = array_merge($arrResponse, $arrResult);
        if (isset($arrResponse['items_id']) && !empty($arrResponse['items_id'])) {
            foreach ($arrResponse['items_id'] as $key => $value) {
                if (isset($arrResult['itemValues']) && !empty($arrResult['itemValues'])) {
                    if (in_array($value, array_column($arrResult['itemValues'], 'id'))) { // search value in the array
                        $index = array_search($value, array_column($arrResult['itemValues'], 'id'));
                        if (isset($arrResult['itemValues'][$index])) {
                            $arrResult['itemValues'][$index]['reference_items_id'] = (string)$key;
                        }
                    }
                }
            }
        }
        $arrResult['current_user'] = \Auth::User()->id;
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('duplicateRow');
        $objBroadCast->updateToBroadcast();
        unset($arrResult['current_user']);

        return $arrResult;
    }

}
