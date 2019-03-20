<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\LinkedItemsDataSource as LinkedItemDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Events\Commands\GetEventDetails;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Events\DataSource\EventsDataSource;

class GetLinkedItemsList implements iCommands {

    private $dataSource;
    private $objDataResponse;
    private $objItemDataSource;

    /**
     * 
     * @param LinkedItemDs $objLinkItemsDs
     * @param DataResponse $objDataResponse
     * @param ItemsDataSource $objItemDs
     */
    public function __construct(LinkedItemDs $objLinkItemsDs, DataResponse $objDataResponse, ItemsDataSource $objItemDs) {
        $this->dataSource = $objLinkItemsDs;
        $this->objDataResponse = $objDataResponse;
        $this->objItemDataSource = $objItemDs;
    }

    /**
     * Execution of get linked items list
     * 
     * @param object $command
     * @return array $arrResponse
     */
    public function execute($command) {

        $arrResponse = $arrItems = $objResult = $arrEvents = [];
        $itemCount = 0;
        if ($command->parent_id != 0) {
            $objResult = $this->dataSource->getLinkedItemListByParent($command);
        } else {
            $objResult = $this->dataSource->getLinkedItemList($command);
        }

        if (!empty($objResult)) {
            $itemCount = count($objResult);
            foreach ($objResult as $val) {
                $val = (object) $this->objItemDataSource->filterStringDecode((array) $val);
                $val->is_excluded = ($val->is_excluded == '1') ? true : false;
                $val->items_import_source = ($val->items_import_source == '1') ? 'Import' : 'IQS';
                $val->cost = ItemsUtils::formatPriceValues($val->cost);
                $val->base_unit_retail = ItemsUtils::formatPriceValues($val->base_unit_retail);
                
                $arrItems[] = (array) $val;
            }
        }
        /**
         * Add Events informations
         */
        $objEventDs = new EventsDataSource();
        $arrEvents = $objEventDs->getEventAdditionalInfoByPermissions($command);
        unset($arrEvents['itemCount']);
        $arrResponse['items'] = array_merge($arrEvents, ['itemValues' => $arrItems, 'itemCount' => $itemCount]);
        $arrResponse['count'] = $itemCount;
        /**
         * Set Paginations
         */
        if (!empty($command->page) && empty($command->parent_id) && $command->is_export == false) {
            $arrResponse['count'] = $objResult->total();
            $arrResponse['lastpage'] = $objResult->lastPage();
        }
        unset($arrEvents, $arrItems, $objResult);
        return $arrResponse;
    }

    /**
     * 
     * @param type $value
     * @return string
     */
    function formatPriceValues($value) {
        $formatValue = '';
        if (!empty($value)) {
            $formatValue = preg_replace('/[\$,~]/', '', $value);
            if (!empty($formatValue)) {
                $formatValue = floatval($formatValue);
            }
        }
        return $formatValue;
    }

}
