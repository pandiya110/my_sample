<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\GroupedDataSource as GroupedDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\ItemsDataSource as ItemsDs;

class GroupedItemsList implements iCommands {

    private $dataSource;
    private $objCopyDs;
    Private $objGridResponse;

    /**
     * 
     * @param GroupedDs $objGroupedDs
     * @param CopyDs $objCopyDs
     * @param ItemsGridDataResponse $objGridResponse
     */
    function __construct(GroupedDs $objGroupedDs, CopyDs $objCopyDs, ItemsGridDataResponse $objGridResponse) {
        $this->dataSource = $objGroupedDs;
        $this->objCopyDs = $objCopyDs;
        $this->objGridResponse = $objGridResponse;
    }

    /**
     * 
     * @param object $command
     * @return Array
     */
    function execute($command) {
        $arrResponse = [];
        try {

            $groupedItems = $this->dataSource->getGroupedItems($command);
            if (isset($groupedItems['items_id']) && !empty($groupedItems['items_id'])) {
                $data = ['items_id' => $groupedItems['items_id'],
                    'event_id' => PiLib::piEncrypt($command->event_id),
                    'parent_item_id' => $command->parent_item_id,
                    'search' => isset($command->search) ? $command->search : '',
                    'sort' => $command->sort,
                    'column' => $command->order,
                    'multi_sort' => isset($command->multi_sort) ? $command->multi_sort : '',
                    'is_excluded' => $command->is_excluded,
                    'item_sync_status' => $command->item_sync_status,
                ];

                $objCommand = new GetItemsList($data);
                $arrResponse = CommandFactory::getCommand($objCommand);
                $arrResponse['items']['groupName'] = isset($groupedItems['name']) ? $groupedItems['name'] : '';

                if (!empty($command->parent_item_id)) {
                    $itemCount = $this->dataSource->getGroupedItemsCount($command->parent_item_id);
                    $arrResponse['items']['itemCount'] = array('item' => $itemCount);
                }
                unset($data);
            } else {
                $arrResponse['items']['itemCount'] = 0;
                $arrResponse['items']['itemValues'] = [];
                $arrResponse['status'] = true;
                $arrResponse['count'] = 0;
            }
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            return $exMsg;
        }

        return $arrResponse;
    }

}
