<?php

namespace CodePi\Api\SyncResult;

use CodePi\Base\Eloquent\MasterItems;
use CodePi\Api\DataSource\MasterItemsDataSource as MasterItmDs;

abstract class AbstractSync {

    protected $data;
    protected $key_value;

    function __construct($data, $key_value) {
        $this->data = $data;
        $this->key_value = $key_value;
    }

    abstract function formatResult();

    function syncResult($saveData = array()) {
        $status = 'false';
        
        $objMasterItm = new MasterItems();
        if (!empty($saveData)) {

            $dbValue = $objMasterItm->get()->toArray();
            $dbUpc = [];
            foreach ($dbValue as $upcValue) {
                $dbUpc[$upcValue['itemsid']] = $upcValue['itemsid'];
            }
            
            $inputUpc[$this->key_value] = $this->key_value;
            
            $insertData = array_diff($inputUpc, $dbUpc);
            $updateData = array_intersect_key($dbUpc,$inputUpc);
            
            if(!empty($insertData)){
                foreach ($insertData as $value){
                    $insert[] = $saveData[$value];
                }
            
                $objMasterItm->insertMultiple($insert);
            }
            
            if (!empty($updateData)) {

                $result = $objMasterItm->where('itemsid', $this->key_value)->get(['id'])->toArray();
                foreach ($result as $id) {
                    $update = $saveData[$this->key_value];
                    $update['id'] = $id['id'];
                    $objMasterItm->saveRecord($update);
                }
            }

            $status = 'true';
        }
        return $status;
    }

}
