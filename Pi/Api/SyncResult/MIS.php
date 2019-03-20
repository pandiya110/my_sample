<?php

namespace CodePi\Api\SyncResult;

use CodePi\Base\DataSource\DataSource;
use CodePi\Api\DataSource\MasterItemsDataSource as MasterItmdDs;


class MIS extends AbstractSync{
   
    
   /**
     * 
     * @param array $response
     * @return array
     */
   function formatResult() {
        $response = $this->data;
        $value = $this->key_value;
        $arrResponse = [];

        $objMaster = new MasterItmdDs();
        if (isset($response['data']) && !empty($response['data'])) {
            $columns = $objMaster->getApiColumn('MIS');
            foreach ($columns as $col) {
                $arrResponse[$value][$col] = isset($response['data'][$col]) ? $response['data'][$col] : '';
            }
        }
        return $arrResponse;
    }

}
