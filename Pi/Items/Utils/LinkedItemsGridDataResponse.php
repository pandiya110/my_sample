<?php

namespace CodePi\Items\Utils;

use CodePi\Items\Utils\ItemsUtils;
use CodePi\Items\DataSource\ItemsDataSource;

class LinkedItemsGridDataResponse {

   /**
    * Format linked items data
    * @param collection $result
    * @return array
    */
    function getGridResponse($result) {
        $arrItems = [];
        $objItemsDs = new ItemsDataSource();
        foreach ($result as $val) {            
            $val = (object) $objItemsDs->filterStringDecode((array) $val);
            $val->is_excluded = ($val->is_excluded == '1') ? true : false;
            $val->items_import_source = ($val->items_import_source == '1') ? 'Import' : 'IQS';
            $val->cost = ItemsUtils::formatPriceValues($val->cost);
            $val->base_unit_retail = ItemsUtils::formatPriceValues($val->base_unit_retail);
            $arrItems[] = (array) $val;
        }
        $arrResponse = array('linkItemValues' => $arrItems);
        unset($arrItems);
        return $arrResponse;
    }

}

?>
