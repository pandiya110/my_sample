<?php

namespace CodePi\Items\Utils;

use CodePi\Items\DataSource\ItemsDataSource;

class ItemsUtils {
    /**
     * Add Http
     * @param string $url
     * @return string
     */
    static  function addhttp($url) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url) && $url != '' && $url != 'UNPUBLISHED') {
            $url = "http://" . $url;
        }
        return $url;
    }

    /**
     * 
     * @param type $permissions
     * @param type $value
     * @param type $isArchived
     * @return boolean
     */
    static function is_row_edit($permissions, $value, $isArchived = false) {

        $return = false;
        $isPermissions = isset($permissions['items_access']) ? $permissions['items_access'] : '0';
        switch ($isPermissions) {
            case '6':
                $return = true;
                break;
            case '2':
                $return = true;
                break;
            case '3':
                if ($value->created_by == $value->last_modified_by) {
                    $return = true;
                }
                break;
            case '4':
                if ($value->created_by == $value->last_modified_by) {
                    $return = true;
                }
                break;
            case '5':
                if ($permissions['departments_id'] == $value->departments_id) {
                    $return = true;
                }
                break;
            case '1':
                $return = true;
                break;
            default :
                $return = true;
                break;
        }
        if ($return == true && $value->publish_status == '1') {
            if (isset($permissions['edit_publish_state']) && $permissions['edit_publish_state'] == '1') {
                $return = true;
            } else {
                $return = false;
            }
        }
        if(!empty($isArchived)){
            $return = false;
        }

        return $return;
    }

    /**
     * Set deafult values for Yes/No columns
     * @param string $column
     * @param string $value
     * @return string
     */
    static function setDefaultNoValuesCol($column, $value) {

        $defaultValue = '--';
        $columnArray = ['new', 'rollback', 'exclusive', 'special_value', 'made_in_america', 'day_ship', 'usda_organic', 'co_op', 'local_sources'];

        if (in_array($column, $columnArray)) {

            $defaultValue = (strlen(trim($value)) > 0 && trim(strtoupper($value)) != 'NO') ? $value : '--';
        }
        
        return $defaultValue;
    }

    /**
     * 
     * @param type $value
     * @return string
     */
    static function formatPriceValues($value) {
        $formatValue = 0;
        if (!empty($value)) {
            $formatValue = preg_replace('/[\$,~]/', '', $value);
            
            if (!empty($formatValue)) {
                $formatValue = floatval($formatValue);                
            }
        }        
        return $formatValue;
    }

    /**
     * 
     * @param type $val
     * @return boolean
     */
    static function getAdRetailSoldOutStaus($val) {
        $status = false;
        $adRetails = self::formatPriceValues($val->advertised_retail);
        $adRetails = preg_replace("/[^0-9]{1,4}/", '', $adRetails);
        $cost = self::formatPriceValues($val->cost);
        if (!empty($adRetails)) {
            $soldAmt = $adRetails - $cost;
            if ($soldAmt < 0) {
                $status = true;
            }
        }
        return $status;
    }

    /**
     * Get selected cell colour codes
     * @param type $values
     * @return array
     */
    static function getColorCodeValues($values) {
        $colours = [];
        if (!empty($values)) {
            $obj = new ItemsDataSource();
            $result = $obj->getMasterDataOptions(5);
            $jsonDecode = \GuzzleHttp\json_decode($values);
            foreach ($jsonDecode as $key => $val) {
                $colours[$key] = $result[$val];
            }
        }
        return $colours;
    }
    
     /**
     * Set Was price column edit or non edit as per price rules
     * 
     * @param string $priceID
     * @return boolean
     */
     static function setWaspriceEditorNonEdit($priceID) {

        switch ($priceID) {
            case 'EDLP' :
                $return = ['is_wasprice_edit' => false, 'is_adretail_edit' => true, 'is_adretail_req' => true, 'is_wasprice_req' => false, 'is_saveamount_edit' => false];
                break;
            case 'ROLLBACK' :
                $return = ['is_wasprice_edit' => true, 'is_adretail_edit' => true, 'is_adretail_req' => true, 'is_wasprice_req' => true, 'is_saveamount_edit' => true];
                break;
            case 'SPECIAL BUY' :
                $return = ['is_wasprice_edit' => false, 'is_adretail_edit' => true, 'is_adretail_req' => true, 'is_wasprice_req' => false, 'is_saveamount_edit' => false];
                break;
            case 'BONUS' :
                $return = ['is_wasprice_edit' => false, 'is_adretail_edit' => true, 'is_adretail_req' => true, 'is_wasprice_req' => false, 'is_saveamount_edit' => true];
                break;
            default :
                $return = ['is_wasprice_edit' => true, 'is_adretail_edit' => true, 'is_adretail_req' => false, 'is_wasprice_req' => false, 'is_saveamount_edit' => true];
                break;
        }

        return $return;
    }
    
    /**
     * Check the row is Empty or not
     * @param type $val
     * @return boolean
     */
     static function isRowEmpty($val){
        
        if($val->searched_item_nbr != '' || $val->itemsid != '' || $val->upc_nbr != '' || $val->fineline_number !='' || $val->plu_nbr != '' && $val->is_no_record != true){
            $isEmptyRow = false;
        }else{
            $isEmptyRow = true;
        }
        return $isEmptyRow;
    }
    /**
     * 
     * @param type $value
     * @return string
     */
    static function formatAdRetaliValue($value) {
        $formatValue = $value;
        if (!empty($value)) {
            $findNumber = preg_replace("/[^.0-9]{1,4}/", '', $value);
            
            if (!empty($findNumber)) {
                $formatValue = preg_replace('/[\$,~]/', '', $value);               
                if (strlen($formatValue) == strlen($findNumber)) {
                    if (!empty($formatValue)) {
                        $formatValue = "$" . number_format($formatValue, 2);
                    }
                }
            }
        }
        
        return $formatValue;
    }
    /**
     * 
     * @param type $intStatusId
     * @param type $itemCount
     * @param type $unPublishedCount
     * @param type $stringStatus
     * @return type
     */
    static function setEventStatus($intStatusId, $itemCount, $unPublishedCount, $stringStatus) {
        $nonStatus = [4, 5];

        if (!in_array($intStatusId, $nonStatus)) {
            if ($itemCount > 0 && $unPublishedCount == 0) {
                $status = 'PUBLISHED';
            } else if ($itemCount > 0 && $unPublishedCount > 0) {
                $status = 'ACTIVE';
            } else if ($itemCount == 0) {
                $status = 'NEW';
            }
        } else {
            $status = $stringStatus;
        }
        
        return $status;
    }

}

?>