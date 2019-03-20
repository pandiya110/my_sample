<?php

namespace CodePi\RestApiSync\Executables;

use CodePi\RestApiSync\DataSource\ItemsDataSource as ItemsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\DataSource\Elastic;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Items\DataSource\ItemsDataSource as appIemsDs;
use CodePi\Export\DataSource\ExportItemsSftpDs as appExportDs;
/**
 * Handle the execution of get department list
 */ 
class GetItems  { 

    private $dataSource;
    private $appIemsDs;
    private $appExportDs;

    /**
     * @ignore It will create an object of Departments
     */
    public function __construct(ItemsDs $objItemsDs, appIemsDs $appIemsDs, appExportDs $appExportDs) {
        $this->dataSource = $objItemsDs;
        $this->appIemsDs = $appIemsDs;
        $this->appExportDs = $appExportDs;
    }

    /**
     * Execution of Get the list of departments
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $result = $response = [];

        /**
         * Clear the data in elastic search
         */
        $this->dataSource->clearDataInEsearch('sm_items', 'items');
        $toatlCount = $this->dataSource->getItemsTotalCount();
        $channels = $this->dataSource->getItemsChannels();
        $objE = [];
        echo 'Start :: ' . date('Ymd H:i:s');
        for ($i = 0; $i <= $toatlCount; $i = $i + 1000) {
            
            if ($i)
                $command->offset = $i + 1;
            else
                $command->offset = $i;

            $objResult = $this->dataSource->getItems($command);

            if (!empty($objResult)) {
                foreach ($objResult as $row) {
                    $isEmpty = ItemsUtils::isRowEmpty($row);
                    if ($isEmpty == false) {
                        $row->is_excluded = !empty($row->is_excluded) ? true : false;
                        $row->is_no_record = !empty($row->is_no_record) ? true : false;
                        $row->item_sync_status = !empty($row->item_sync_status) ? true : false;
                        $row->publish_status = !empty($row->publish_status) ? true : false;
                        $row->is_excluded = !empty($row->is_excluded) ? true : false;
                        $row->items_type = !empty($row->items_type) ? true : false;
                        $row->items_import_source = ($row->items_import_source == '1') ? 'Import' : 'IQS';
                        $row->date_added = $row->date_added;
                        $row->last_modified = $row->last_modified;
                        $row->channels = isset($channels[$row->id]) ? $channels[$row->id] : [];
                        $row->attributes = $this->appIemsDs->getAttributesSelectedValues($row->attributes);
                        //$row->no_of_linked_item = $this->dataSource->getLinkedItemsCount($row->events_id, $row->upc_nbr);
                        $row->versions = $this->dataSource->getSelectedVersions($row->id);
                        $row->mixed_column2 = $this->dataSource->getOmitVersionsByItemId($row->id);
                        $objE['body'][] = [
                            'index' => [
                                '_index' => 'sm_items',
                                '_type' => 'items',
                                '_id' => $row->id
                            ]
                        ];
                        $objE['body'][] = $row;
                    }
                }

                $objElasic = new Elastic;
                $objElasic->bulk($objE);
                unset($objE);
            }
        }
        echo 'stop :: ' . date('Ymd H:i:s');
        return $response;
    }

    public function executeOld($command) {
        ini_set('memory_limit', '250M');
        $result = $response = [];
        $objElasic = new Elastic;
        /**
         * Clear the data in elastic search
         */
        $this->dataSource->clearDataInEsearch('sm_items', 'items');
        $toatlCount = $this->dataSource->getItemsTotalCount();
        $channels = $this->dataSource->getItemsChannels();
        for ($i = 0; $i <= $toatlCount; $i = $i + 500) {
            if ($i)
                $command->offset = $i + 1;
            else
                $command->offset = $i;

            $objResult = $this->dataSource->getItems($command);  
            
            if (!empty($objResult)) {
                foreach ($objResult as $row) {
                    $isEmpty = ItemsUtils::isRowEmpty($row);
                    if($isEmpty == false){
                        $row->is_excluded = !empty($row->is_excluded) ? true : false;
                        $row->is_no_record = !empty($row->is_no_record) ? true : false;
                        $row->item_sync_status = !empty($row->item_sync_status) ? true : false;
                        $row->publish_status = !empty($row->publish_status) ? true : false;
                        $row->is_excluded = !empty($row->is_excluded) ? true : false;
                        $row->items_type = !empty($row->items_type) ? true : false;
                        $row->date_added = $row->date_added;
                        $row->last_modified =$row->last_modified;
                        $row->channels = isset($channels[$row->id]) ? $channels[$row->id] : [];
                        $row->attributes = $this->appIemsDs->getAttributesSelectedValues($row->attributes);
                        $row->no_of_linked_item = $this->dataSource->getLinkedItemsCount($row->events_id, $row->upc_nbr);                                                
                        $objE = [];
                        $objE['index'] = 'sm_items';
                        $objE['type'] = 'items';
                        $objE['id'] = $row->id;
                        $objE['body'] = $row;
                        $objElasic->insert($objE);
                    }
                }
            }
        }
        return $response;
    }
}
