<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs; 
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Base\Commands\CommandFactory;
use App\Events\ItemActions;
use CodePi\Items\DataSource\AppendReplaceItemsDS as AppendReplaceDs;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;

class AppendReplaceItems implements iCommands { 
    /**
     * @access private
     * @var class, instance of ItemsDataSource class
     */
    private $dataSource;
    private $objGroupDs;
    

    /**
     * Constructor
     * 
     * @param class ItemDs $objItemDs     
     */
    function __construct(AppendReplaceDs $objItemDs, GroupDs $objGroupDs) {
        $this->dataSource = $objItemDs;
        $this->objGroupDs = $objGroupDs;
        
    }

    /**
     * Execution of Append or Replace the items
     * 
     * @param object $command
     * @return array 
     */
    function execute($command) {
        $arrResponse = [];
        /**
         * Get validations for Append/Replace
         */
        $isExists = $this->dataSource->isExistsItemsByEvents($command);

        if (empty($isExists)) {
            $objResult = $this->dataSource->appendReplaceItems($command);

            $params = array('old_item_id' => $objResult['deleted_id'],
                            'new_item_id' => $objResult['items_id'],
                            'events_id' => $command->events_id,
                            'parent_item_id' => isset($command->parent_item_id) ? $command->parent_item_id : 0);
            $this->objGroupDs->saveAppendReplaceGroupItems($params);
            /**
             * Get updated items value
             */
            $data['items_id'] = $objResult['items_id'];
            $data['event_id'] = PiLib::piEncrypt($command->events_id);
            $data['parent_item_id'] = $command->parent_item_id;
            $objCommand = new GetItemsList($data);
            $arrItems = CommandFactory::getCommand($objCommand);
            unset($data);
            $arrResponse = $arrItems['items'];
            if (!empty($command->parent_item_id)) {
                $itemCount = $this->objGroupDs->getGroupedItemsCount($command->parent_item_id);
                $arrResponse['itemCount'] = array('item' => $itemCount);
            }

            $arrResponse['deleted_items'] = $objResult['deleted_id'];
            $arrResponse['status'] = true;
            if ($arrResponse) {
                if (empty($command->parent_item_id)) {
                    $objBroadCast = new BroadcastResponse(array());
                    $arrResponse['itemPage'] = $objBroadCast->pageGroupingArray($arrResponse['itemValues']);
                    broadcast(new ItemActions($arrResponse, 'addrow'))->toOthers();
                }
            }
            return $arrResponse;
        } else {
            return $isExists;
        }
    }

}
