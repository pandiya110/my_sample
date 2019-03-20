<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
use CodePi\Base\DataTransformers\DataResponse;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;

/**
 * Handle the execution of  publish items
 */
class AddItemPublish implements iCommands {

    /**
     *
     * @var class, instance of ItemsDataSource
     * @access private
     */
    private $dataSource;
    private $objCopyDs;
    Private $objGridResponse;

    /**
     * 
     * @param ItemDs $objItemDs
     */
    function __construct(ItemDs $objItemDs, CopyDs $objCopyDs, ItemsGridDataResponse $objGridResponse) {
        $this->dataSource = $objItemDs;
        $this->objCopyDs = $objCopyDs;
        $this->objGridResponse = $objGridResponse;
    }

    /**
     * Execution of Publish items
     * 
     * @param object $command
     * @return array of published items 
     */
//    function execute($command) {
//
//
//        $arrResponse = [];
//        $arrResult = $this->dataSource->addItemPublishStatus($command);
//
//        $data['items_id'] = $command->item_id;
//        $data['event_id'] = PiLib::piEncrypt($command->event_id);
//        $data['parent_item_id'] = $command->parent_item_id;
//        $objCommand = new GetItemsList($data);
//        $cmdResponse = CommandFactory::getCommand($objCommand);
//        $arrResponse = array_merge($arrResult, $cmdResponse['items']);
//        if (!empty($command->parent_item_id)) {
//            $objGroupDs = new GroupDs();
//            $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
//            $arrResponse['itemCount'] = array('item' => $itemCount);
//        }
//        if ($arrResponse) {
//
//            broadcast(new ItemActions($arrResponse, 'publish'))->toOthers();
//        }
//
//        return $arrResponse;
//    }

    function execute($command) {

        $response = $this->dataSource->addItemPublishStatus($command);
        $postData = $command->dataToArray();
        $postData['result'] = $response;
        $postData['items_id'] = is_array($postData['item_id']) ? $postData['item_id'] : [$postData['item_id']];
        $postData['events_id'] = $postData['event_id'];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        if (isset($response['fields']) && !empty($response['fields'])) {
            foreach ($response['fields'] as $key => $value) {
                if (isset($arrResult['itemValues']) && !empty($arrResult['itemValues'])) {
                    if (in_array($value['id'], array_column($arrResult['itemValues'], 'id'))) { // search value in the array
                        $index = array_search($value['id'], array_column($arrResult['itemValues'], 'id'));
                        if (isset($arrResult['itemValues'][$index])) {
                            $arrResult['itemValues'][$index]['publish_required_column'] = $value['column'];
                        }
                    }
                }
            }
        }
        unset($response['fields']);
        $arrResult = array_merge($response, $arrResult);
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        if (!empty($postData['parent_item_id'])) {
            $objGroupDs = new GroupDs();
            $itemCount = $objGroupDs->getGroupedItemsCount($postData['parent_item_id']);
            $arrResult['itemCount'] = array('item' => $itemCount);
        }
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('publish');
        $objBroadCast->updateToBroadcast();

        return $arrResult;
    }

}
