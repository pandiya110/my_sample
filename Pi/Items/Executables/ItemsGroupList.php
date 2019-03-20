<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\GroupedDataSource as GroupedDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataTransformers\ItemsDataTransformers as ItemsTs;

/**
 * Handle the execution of publish items
 */
class ItemsGroupList implements iCommands {

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
     * @return array of items 
     */
    function execute($command) {
        $arrResponse = [];
        $result = $this->dataSource->getItemsGroupList($command);
        $arrResponse['groups'] =  $this->dataSource->getGroupsList($command);
        $arrResponse['items']= $this->objDataResponse->collectionFormat($result, new ItemsTs(['items_id', 'value']));
         if (!empty($command->page) && $command->is_export == false) {
                $arrResponse['count'] = $result->total();
                $arrResponse['lastpage'] = $result->lastPage();
        }
        return $arrResponse;
        
    }

}
