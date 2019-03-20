<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
#use CodePi\Base\DataTransformers\DataResponse;
#use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
#use CodePi\Items\Commands\GetItemsList;
#use CodePi\Base\Commands\CommandFactory;
#use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
#use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;
/**
 * Handle the execution of exclude event items
 */
class ExcludeEventItem implements iCommands {

    /**
     *
     * @var class, instance of ItemsDataSource
     * @access private
     */
    private $dataSource;
    //private $objCopyDs;
    //Private $objGridResponse;

    /**
     * @ignore It will create an object of Events
     */
    function __construct(ItemDs $objItemDs) {
        $this->dataSource = $objItemDs;
        //$this->objCopyDs = $objCopyDs;
        //$this->objGridResponse = $objGridResponse;
    }

    /**
     * Execution of exclude or activate the selected items
     * 
     * @param object $command
     * @return array
     */
//    function execute($command) {
//        $arrResponse = [];
//        $objResult = $this->dataSource->excludeEventItem($command);
//
//        /**
//         * Get the updated items row
//         */
//        if ($objResult == true) {
//            $data['items_id'] = $command->id;
//            $data['event_id'] = PiLib::piEncrypt($command->events_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            unset($data);
//            $arrResponse = $cmdResponse['items'];
//            if ($command->parent_item_id) {
//                $objGroupDs = new GroupDs();
//                $arrResponse['itemCount'] = array('item' => $objGroupDs->getGroupedItemsCount($command->parent_item_id));
//            }
//        }
//        $arrResponse['status'] = $objResult;
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//        if ($arrResponse) {
//
//            broadcast(new ItemActions($arrResponse, 'exclude'))->toOthers();
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {
        
        $result = $this->dataSource->excludeEventItem($command);
        
        $postData = $command->dataToArray();
        $postData['result'] = $result;
        $postData['items_id'] = $result['items_id'];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($command->events_id);
        if (!empty($postData['parent_item_id'])) {
            $objGroupDs = new GroupDs();
            $arrResult['itemCount'] = array('item' => $objGroupDs->getGroupedItemsCount($postData['parent_item_id']));
        }
        
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('exclude');
        $objBroadCast->updateToBroadcast();

        return $arrResult;
    }

}
