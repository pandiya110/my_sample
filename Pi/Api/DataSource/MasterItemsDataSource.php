<?php

namespace CodePi\Api\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\Base\Eloquent\MasterItems;

class MasterItemsDataSource {
    
   
    /**
     * Find the Api column from Headers table by given Api name
     * 
     * @param string $apiName, column source type
     * @return array of api colums names
     * @access public
     */
    function getApiColumn($apiName){
        
        $arrColumn = [];
        $objHeaders = new ItemsHeaders();
        $dbResult = $objHeaders->where('column_source', $apiName)->orderBy('column_order', 'asc')->get();
        foreach ($dbResult as $value) {
            $arrColumn[] = $value->column_name;
        }
        return $arrColumn;
    }
    
    /**
     * Get the list of Master items data
     * 
     * @param object $command->search_key, item, plu, fineline, upc number any on these search keys
     * @param object $command->item_nbr (this can be multiple)
     * @return array of master items data
     * @access public
     */
    function getMasterItemsData($command){
        
        $arrMaster = [];
        $params = $command->dataToArray();
        
        $objMasterItms = new MasterItems();    
//        if($params['search_key']=='searched_item_nbr'){
//            //$item_nbr = implode("','", $params['item_nbr']);                        
//            $dbResult = $objMasterItms->whereRaw('searched_item_nbr in (' . '\'' . implode("','",  $params['item_nbr']) . '\'' . ') '
//                                                . 'or upc_nbr in (' . '\'' . implode("','",  $params['item_nbr']) . '\'' . ')')
//                                        ->get()
//                                        ->toArray();           
//        }else{
//            $dbResult = $objMasterItms->whereIn($params['search_key'], $params['item_nbr'])->get()->toArray(); 
//        }
        $is_searched_item_nbr = ($params['search_key'] == 'searched_item_nbr')?true:false;
        if ($is_searched_item_nbr) {
            $dbResult = $objMasterItms->whereIn($params['search_key'], $params['item_nbr'])->where('parent_id', '0')->where('itemsid', '!=', '')->orderBy('id', 'desc')->limit(1)->get()->toArray();
        } else {
            $dbResult = $objMasterItms->whereIn($params['search_key'], $params['item_nbr'])->where('parent_id', '0')->where('is_primary', 1)->where('itemsid', '!=', '')->orderBy('id', 'desc')->limit(1)->get()->toArray();
        }

        /**
         * Convert object data into array
         */
        foreach ($dbResult as $objValue){             
             //$objValue = $this->stringTrim((array)$objValue);                          
              $arrMaster[] = (array)$objValue;                        
        }
        
        return $arrMaster;
    }
    /**
     * Remove special characters
     * @param type $array
     * @return array
     */
    function stringTrim($array){
        $arrayValue = [];
        foreach ($array as $key => $value){
            if(!\CodePi\Base\Libraries\PiLib::isValidURL($value)){
                $arrayValue[$key] = preg_replace("/[^a-zA-Z0-9\-:]+/", "\/", html_entity_decode($value, ENT_QUOTES));//preg_replace('/\s+/', '', $value);
            }else{
                $arrayValue[$key] = $value;
            }
        }
        return $arrayValue;
    }
    
    /**
     * Get Auto suggest search value, based on search creteria 
     * 
     * @param type $commmand
     * @return type
     */
    function getAutoSugSearchVal($commmand) {
        $totalCount = 0;
        $params = $commmand->dataToArray();        
        $objMasterItms = new MasterItems();
        $arrResponse = [];
        
        if (isset($params['search_key']) && !empty($params['search_val'])) {            
            $objMasterItms = $objMasterItms->where(function($query)use($params) {
                                     $query->where($params['search_key'], 'like', '%' . $params['search_val'] . '%');
                             })->groupBy($params['search_key']);
                             if (isset($params['page']) && !empty($params['page'])) {
                                $objMasterItms = $objMasterItms->paginate($params['perPage']);
                                $totalCount = $objMasterItms->total();
                             } else {
                                $objMasterItms = $objMasterItms->get()->toArray();
                             } 
                             $objMasterItms->totalCount = $totalCount;
        }
        
        return $objMasterItms;
    }

}
