<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\LinkedItemsDataSource as LinkItemDs;
use CodePi\Base\DataTransformers\DataResponse;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;

/**
 * Handle the execution of linked items
 */
class MoveLinkedItems implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of linked items
     */
    function __construct(LinkItemDs $objLinkItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objLinkItemDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return boolean
     */
//    function execute($command) {
//        $arrResponse = $arrResponseParent = [];
//        $objResult = $this->dataSource->moveLinkedItems($command);
//        
//        /**
//         * Get Updated items row
//         */
//        if ($objResult == true) {
//            $data['items_id'] = $objResult['items_id'];
//            $data['event_id'] = PiLib::piEncrypt($command->event_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = $cmdResponse['items'];
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//                $arrResponse['itemCount'] = array('item' => $itemCount);
//            }            
//            /**
//             * Update parent items
//             */
//            $dataParent['items_id'] = isset($objResult['sameUpcItemsId']) && !empty($objResult['sameUpcItemsId']) ? $objResult['sameUpcItemsId'] : [$command->parent_id];
//            $dataParent['event_id'] = PiLib::piEncrypt($command->event_id);
//            $dataParent['parent_item_id'] = $command->parent_item_id;
//            $objCommandParent = new GetItemsList($dataParent);
//            $cmdResponseParent = CommandFactory::getCommand($objCommandParent);
//            $arrResponseParent = $cmdResponseParent['items'];
//            if (!empty($command->parent_item_id)) {
//                $objGroupDs = new GroupDs();
//                $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//                $arrResponseParent['itemCount'] = array('item' => $itemCount);
//            }
//        }
//        $arrResponse['status'] = $objResult['status'];
//        $arrResponseParent['status'] = $objResult['status'];
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->event_id);
//        $arrResponseParent['event_id'] = PiLib::piEncrypt($command->event_id);
//        unset($objResult['items_id']);
//        if ($objResult['status'] == false) {
//            $arrResponse['message'] = 'Could not connect to Api.Failed to move items';
//        }        
//        if ($arrResponse) {
//
//            broadcast(new ItemActions($arrResponse, 'addrow'))->toOthers();
//            broadcast(new ItemActions($arrResponseParent, 'update'));
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {

        $response = $this->dataSource->moveLinkedItems($command);
        if (isset($response['status']) && !empty($response['status'])) {
            $postData = $command->dataToArray();
            $postData['result'] = $response;
            $postData['items_id'] = is_array($response['items_id']) ? $response['items_id'] : [$response['items_id']];
            $postData['events_id'] = $postData['event_id'];
            $objBroadCast = new BroadcastResponse($postData);
            $arrResult = $objBroadCast->getRowData();
            $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
            if (!empty($postData['parent_item_id'])) {
                $objGroupDs = new GroupDs();
                $itemCount = $objGroupDs->getGroupedItemsCount($postData['parent_item_id']);
                $arrResult['itemCount'] = array('item' => $itemCount);
            }
            $objBroadCast->setData($arrResult);
            $objBroadCast->setAction('addrow');
            $objBroadCast->updateToBroadcast();
            /**
             * Update Same Upc Related Items Through Broadcasting
             */
            $parentData = $command->dataToArray();
            $parentData['result'] = $response;
            $parentData['items_id'] = isset($response['sameUpcItemsId']) && !empty($response['sameUpcItemsId']) ? $response['sameUpcItemsId'] : [$parentData['parent_id']];
            $parentData['events_id'] = $parentData['event_id'];
            $objBroadCast = new BroadcastResponse($parentData);
            $arrParent = $objBroadCast->getRowData();
            if (!empty($parentData['parent_item_id'])) {
                $objGroupDs = new GroupDs();
                $itemCount = $objGroupDs->getGroupedItemsCount($parentData['parent_item_id']);
                $arrParent['itemCount'] = array('item' => $itemCount);
            }
            $arrParent['event_id'] = isset($arrParent['event_id']) ? $arrParent['event_id'] : PiLib::piEncrypt($parentData['events_id']);
            $objBroadCast->setData($arrParent);
            $objBroadCast->setAction('update');
            $objBroadCast->updateToBroadcast();
            unset($arrParent, $postData, $parentData, $response);

            return $arrResult;
        } else {
            throw new DataValidationException('Unable to Move Items, Try again..!', new MessageBag());
        }
    }

}
