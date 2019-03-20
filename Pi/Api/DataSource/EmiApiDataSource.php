<?php

namespace CodePi\Api\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Api\DataSource\DataSourceInterface\iItems;
use CodePi\Api\DataSource\MasterItemsDataSource as MasterItmdDs;
use GuzzleHttp\Client;
use CodePi\Base\Eloquent\MasterItems;
use CodePi\Base\Eloquent\ItemsHeaders;
 use DB;   

class EmiApiDataSource {
    
    /**
     * Save IQS response into master table
     * @param type $data
     * @return boolean
     */
     function insertIntoMasterData($data,$flag) {
        $objMasterItems = new MasterItems();        
        \DB::beginTransaction();
        try {

            foreach ($data as $row) {

                if (isset($row['parent_item']) && !empty($row['parent_item'])) {

                    $row['parent_item']['new'] = $row['parent_item']['new'] == 'true' ? 'Yes' : 'No';
                    if($flag=='1'){
                    $dbResult = $objMasterItems->where('itemsid', $row['parent_item']['itemsid'])
                            ->where('searched_item_nbr', $row['parent_item']['searched_item_nbr'])
                            ->where('is_primary', 2)->limit(1)->get();
                    $row['parent_item']['is_primary'] = 2;
                    }else{
                      $dbResult = $objMasterItems->where('itemsid', $row['parent_item']['itemsid'])
                               ->where('is_primary', 1)->limit(1)->get();  
                      $row['parent_item']['is_primary'] = 1;
                    }

                    if (count($dbResult) > 0) {

                        $objMasterItems->where('id', $dbResult[0]->id)->update($row['parent_item']);

                        if (isset($row['child_item']) && !empty($row['child_item'])) {
                            $this->insertLinkedItems($row['child_item'], $dbResult[0]->id);
                        }
                    } else {
                        $master_data = $objMasterItems->saveRecord($row['parent_item']);
                        if (isset($row['child_item']) && !empty($row['child_item'])) {
                            $this->insertLinkedItems($row['child_item'], $master_data->id);
                        }
                    }
                }
            }            
            \DB::commit();
        } catch (\Exception $ex) {
            echo $ex->getMessage();            
            \DB::rollback();
        }

        return true;
    }
    
    /**
     * Save linked items into master items
     * @param array $data
     * @param int $parent_id
     * @return boolean
     */
    function insertLinkedItems($data, $parent_id) {
        \DB::beginTransaction();
        try {

            $objMasterItems = new MasterItems();
            $objMasterItems->where('parent_id', $parent_id)->delete();
            if (!empty($data)) {
                foreach ($data as $row) {
                    $row['parent_id'] = $parent_id;
                    $insertData[] = $row;
                }

                $objMasterItems->insertMultiple($insertData);
                \DB::commit();
            }
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return true;
    }

    /**
     * 
     * @param obj $command
     * @return boolean
     */
    function getApiData($command){
        $iqsData = [];
        $search_key_array = ['upc_nbr'=>'UPC','itemsid'=>'ITEMID','upc_nbr'=>'UPC','upc_nbr'=>'UPC','upc_nbr'=>'UPC', 
                             'searched_item_nbr' => 'GTIN'];
        //$type = $search_key_array['searched_item_nbr'];
        //$searchValue = ['555161340'];
        //print_r($searchValue);exit;
        $type = $search_key_array['searched_item_nbr'];
        //$searchValue = ['554850082'];  
        $searchValue = ['567065101'];   
        $searchValue = ['692705110048'];   
         $searchValue = ['552211234','552929099'];     
         $searchValue = ['552211234'];  
        $mcisColumns = $this->getMastersColumns();
        foreach($searchValue as $searchVal){ 
          /*Given  upc number more than 12 digit, consider a GTIN number*/  
         // if($type == 'UPC' && strlen($searchVal) >12){
              //$type = 'GTIN';
          //}     
           $searchVal_val = $searchVal;    
             $flag = 0;		
          if($type == 'GTIN'){            
                $searchVal = $this->getconsumableGtin($searchVal); 
				$flag = 1;				
               //$type = 'GTIN';                                            
          }else if($type=='UPC' ){
                $searchVal_new = ltrim($searchVal,'0');
                if(strlen($searchVal_new) >13){
                    $type = 'GTIN';
                    $flag = 2;
                    $searchVal = str_pad($searchVal_new,14,'0',STR_PAD_LEFT);                  
                } else if(strlen($searchVal_new) == 13){
                    $type = 'GTIN';
                     $flag = 2;
                    //$removeZero = ltrim($searchVal, '0');
                    $searchVal = $this->findCheckDigitNum($searchVal_new,13);
                
            }else if(strlen($searchVal_new) == 12){
                    $type = 'GTIN';
                     $flag = 2;
                    //$removeZero = ltrim($searchVal, '0');
                    $searchVal = $this->findCheckDigitNum($searchVal_new,13);
                
            }else if(strlen($searchVal_new) < 12){
                $type = 'UPC';			  
                $searchVal = $this->findCheckDigitNum($searchVal_new);
            }	
            }	  
            //echo $type;
            //echo $searchVal;exit;
          //print_r($searchVal); exit;   
           $ApiData = $this->getIQSApiData($type, $searchVal,$mcisColumns,$searchVal_val,$flag);           
           if (isset($ApiData['parent_item'])) {
                if (count($ApiData['parent_item']) > 0 && $ApiData['parent_item']['upc_nbr'] != '') {
                    if ($type == 'UPC' || $flag == 2) {
                        $ApiData['parent_item']['upc_nbr'] = $searchVal_val;
                    } else if ($type == 'GTIN' && $flag == 1) {
                        $ApiData['parent_item']['searched_item_nbr'] = $searchVal_val;
                    }
                    $iqsData[] = $ApiData;
                }
            }
        }
        //$this->insertIntoMasterData($iqsData);
        echo "<pre>";
        print_r($iqsData);exit;
        return true;
 
    }
    
    /**
     * Get Items data from IQS Api
     * @param strig $searchValue
     * @param string $search_key
     * @return boolean
     */
    function getApiDataPull($searchValue, $search_key) {
        $iqsData = [];
        $search_key_array = ['upc_nbr' => 'UPC', 'itemsid' => 'ITEMID', 'upc_nbr' => 'UPC', 'upc_nbr' => 'UPC', 'upc_nbr' => 'UPC', 'searched_item_nbr' => 'GTIN'];
        //$type = $search_key_array[$search_key];
        //print_r($searchValue);exit;        
        $mcisColumns = $this->getMastersColumns();
        foreach ($searchValue as $searchVal) {
            // if($type == 'UPC' && strlen($searchVal) >12){
            //$type = 'GTIN';
            //}   
            $type = $search_key_array[$search_key];
            $searchVal_val = $searchVal;
            $flag = 0;
            //     if($type == 'GTIN'){            
            //         $searchVal = $this->getconsumableGtin($searchVal);                
            //        $flag = 1;                                           
            //    }else if($type=='UPC' && strlen($searchVal) >12){
            //       $type = 'GTIN';
            // 	  $flag = 2;
            //       $searchVal = str_pad($searchVal,14,'0',STR_PAD_LEFT);
            //    }else if($type=='UPC' && strlen($searchVal) == 12){
            //       $type = 'UPC';
            // 	  $removeZero = ltrim($searchVal, '0');
            //       $searchVal = $this->findCheckDigitNum($removeZero);
            //    }else if($type=='UPC'&& strlen($searchVal) < 12){
            //       $type = 'UPC';			  
            //       $searchVal = $this->findCheckDigitNum($searchVal);
            //    }	

            if ($type == 'GTIN') {
                $searchVal = $this->getconsumableGtin($searchVal);
                $flag = 1;

                //$type = 'GTIN';                                            
            } else if ($type == 'UPC') {
                $searchVal_new = ltrim($searchVal, '0');
                if (strlen($searchVal_new) > 13) {
                    $type = 'GTIN';
                    $flag = 2;
                    $searchVal = str_pad($searchVal_new, 14, '0', STR_PAD_LEFT);
                } else if (strlen($searchVal_new) == 13) {
                    $type = 'GTIN';
                    $flag = 2;
                    //$removeZero = ltrim($searchVal, '0');
                    $searchVal = $this->findCheckDigitNum($searchVal_new, 13);
                } else if (strlen($searchVal_new) == 12) {
                    $type = 'GTIN';
                    $flag = 2;
                    //$removeZero = ltrim($searchVal, '0');
                    $searchVal = $this->findCheckDigitNum($searchVal_new, 13);
                } else if (strlen($searchVal_new) < 12) {
                    $type = 'UPC';
                    $searchVal = $this->findCheckDigitNum($searchVal_new, 11);
                }
            }

            $ApiData = $this->getIQSApiData($type, $searchVal, $mcisColumns, $searchVal_val, $flag);

            if (count($ApiData['parent_item']) > 0 && $ApiData['parent_item']['upc_nbr'] != '') {
                if ($type == 'UPC' || $flag == 2) {
                    $ApiData['parent_item']['upc_nbr'] = $searchVal_val;
                } else if ($type == 'GTIN' && $flag == 1) {
                    $ApiData['parent_item']['searched_item_nbr'] = $searchVal_val;
                }
                $iqsData[] = $ApiData;
            }
        }
        //echo $type;
        //echo $searchVal;exit;
        $this->insertIntoMasterData($iqsData, $flag);
        return true;
    }

    /**
     * Api call for IQS
     * @param string $type
     * @param string $searchValue
     * @param array $mcisColumns
     * @return array
     */
    function getIQSApiData($type, $searchValue, $mcisColumns,$searchVal_val,$flag) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            //CURLOPT_URL => "http://itemsetupquerysvc.prod.nxgensearch.catdev.prod.walmart.com/item-setup-query-service-app/services/products/v1/key/".$type."/".$searchValue."",
            //CURLOPT_URL => "http://itemsetupquerysvc.prod.nxgensearch.catdev.glb.prod.walmart.com/item-setup-query-service-app/services/uber/v1?id=" . $searchValue . "&type=" . $type . "&rt=PRODUCT&rt=PRICE&rt=SITI&rt=LOGISTICS",
            CURLOPT_URL => config('smartforms.iqsApiUrl')."id=" . $searchValue . "&type=" . $type . "&rt=PRODUCT&rt=PRICE&rt=SITI&rt=LOGISTICS",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
//            CURLOPT_HTTPHEADER => array(
//                "WM_CONSUMER.ID:74e6fd32-2ee4-4786-a986-4257d2c461c0",
//                "WM_SVC.NAME: item-setup-query-service-app",
//                "WM_SVC.VERSION: 1.0.0",
//                "WM_QOS.CORRELATION_ID:1"
//            ),
            CURLOPT_HTTPHEADER => config('smartforms.iqsCurlHeaders'),
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $data = json_decode($response, true);
			//echo '<pre>';print_r($data);exit;
            /* if($type == 'GTIN'){
              $isExists = $this->checkWalmartItemNumberKeyExists($data);
              if($isExists == true){
              $return = $this->getIQSApiDataFormat($data,$mcisColumns);
              }else{
              $return = [];
              }
              }else{ */
            //$return = $this->getIQSApiDataFormat($data,$mcisColumns);
            //$return['linked_items'] = $this->getLinkedItems($data,$mcisColumns);
            //}
           
            $return['parent_item'] = $this->getIQSApiDataFormat($data, $mcisColumns,$searchVal_val,$flag);
            $searchVal_val_api_mtr = $return['parent_item']['searched_item_nbr']; 
            $upc_nbr = $return['parent_item']['upc_nbr'];
            $return['child_item'] = $this->getLinkedItems($data, $mcisColumns,$searchVal_val_api_mtr, $upc_nbr);
            // echo "<pre>";
            //print_r($return);exit;
            return $return;
        }
    }
    
    /**
     * Format the API response 
     * @param array $apiResponse
     * @param array $mcisColumns
     * @return array
     */
    function getIQSApiDataFormat(array $apiResponse, array $mcisColumns,$searchVal_val,$flag) {

        $masterData = [];
        $arrData = [];
        $itemImgUrl = isset($apiResponse['payload']) &&
                isset($apiResponse['payload']['product']) &&
                isset($apiResponse['payload']['product']['assets']) &&
                isset($apiResponse['payload']['product']['assets']['values']) &&
                isset($apiResponse['payload']['product']['assets']['values'][0]) &&
                isset($apiResponse['payload']['product']['assets']['values'][0]['properties']) &&
                isset($apiResponse['payload']['product']['assets']['values'][0]['properties']['assetUrl']) ?
                $apiResponse['payload']['product']['assets']['values'][0]['properties']['assetUrl'] : "";

        $dotComprice = isset($apiResponse['payload']) && isset($apiResponse['payload']['offers']) &&
                isset($apiResponse['payload']['offers'][0]) && isset($apiResponse['payload']['offers'][0]) &&
                isset($apiResponse['payload']['offers'][0]['pricing']) && isset($apiResponse['payload']['offers'][0]['pricing'][0]) &&
                isset($apiResponse['payload']['offers'][0]['pricing'][0]['storefrontPricingList']) &&
                isset($apiResponse['payload']['offers'][0]['pricing'][0]['storefrontPricingList'][0]) &&
                isset($apiResponse['payload']['offers'][0]['pricing'][0]['storefrontPricingList'][0]['currentPrice']) &&
                isset($apiResponse['payload']['offers'][0]['pricing'][0]['storefrontPricingList'][0]['currentPrice']['currentValue']) &&
                isset($apiResponse['payload']['offers'][0]['pricing'][0]['storefrontPricingList'][0]['currentPrice']['currentValue']['currencyAmount']) ?
                $apiResponse['payload']['offers'][0]['pricing'][0]['storefrontPricingList'][0]['currentPrice']['currentValue']['currencyAmount'] : '';
        
        $offerID = isset($apiResponse['payload']) && isset($apiResponse['payload']['offers']) &&
                isset($apiResponse['payload']['offers'][0]) && isset($apiResponse['payload']['offers'][0]['limo']) &&
                isset($apiResponse['payload']['offers'][0]['limo']['logisticsOffer']) && isset($apiResponse['payload']['offers'][0]['limo']['logisticsOffer']['logisticsOfferId']) &&
                isset($apiResponse['payload']['offers'][0]['limo']['logisticsOffer']['logisticsOfferId']['offerId']) ?
                $apiResponse['payload']['offers'][0]['limo']['logisticsOffer']['logisticsOfferId']['offerId'] : '';
        
        $wupcNbr = isset($apiResponse['payload']) && isset($apiResponse['payload']['offers']) &&
                isset($apiResponse['payload']['offers'][0]) && isset($apiResponse['payload']['offers'][0]['limo']) &&
                isset($apiResponse['payload']['offers'][0]['limo']['logisticsOffer']) && isset($apiResponse['payload']['offers'][0]['limo']['logisticsOffer']['logisticsOfferId']) &&
                isset($apiResponse['payload']['offers'][0]['limo']['logisticsOffer']['logisticsOfferId']['wupc']) ?
                $apiResponse['payload']['offers'][0]['limo']['logisticsOffer']['logisticsOfferId']['wupc'] : '';

        $finalData = isset($apiResponse['payload']) && isset($apiResponse['payload']['product']) ? $apiResponse['payload']['product']['product_attributes'] : [];

        if ($finalData) {
            $arrData['offers_id'] = $offerID;
            $arrData['dotcom_price'] = $dotComprice;
            $arrData['signing_description'] = $this->formatArrayValue($finalData, 'product_name');
            $arrData['upc_nbr'] = ($this->formatArrayValue($finalData, 'wupc')) ? $this->formatArrayValue($finalData, 'wupc') : $wupcNbr;
            //$arrData['sbu'] = $this->formatArrayValue($finalData, 'ironbank_category');
            $arrData['acctg_dept_nbr'] = $this->formatArrayValue($finalData, 'walmart_department_number');
            $arrData['dept_description'] = $this->formatArrayValue($finalData, 'ironbank_category');
            //$arrData['dept_description'] = $this->formatArrayValue($finalData, 'karf_primary_department_title');
            $arrData['new'] = $this->formatArrayValue($finalData, 'new');
            $arrData['brand_name'] = $this->formatArrayValue($finalData, 'brand');
            $arrData['made_in_america'] = $this->formatArrayValue($finalData, 'country_of_origin_components');
            $arrData['category_description'] = $this->formatArrayValue($finalData, 'product_pt_family');
            $arrData['items_status_code'] = $this->formatArrayValue($finalData, 'display_status');
            //$arrData['searched_item_nbr'] = $this->formatArrayValue($finalData, 'walmart_item_number');
            $arrData['marketing_description'] = $this->formatArrayValue($finalData, 'karf_picker_description');
            //$arrData['advertised_item_description'] = $this->formatArrayValue($finalData, 'product_long_description');
            $arrData['size'] = $this->formatArrayValue($finalData, 'size');
            $arrData['gtin_nbr'] = (isset($apiResponse['payload']) && isset($apiResponse['payload']['gtins'])) ? str_pad(trim($apiResponse['payload']['gtins'][0]), 14, '0', STR_PAD_LEFT) : '';
            $arrData['landing_url'] = $this->formatArrayValue($finalData, 'product_url_text');
            if ($arrData['landing_url'] != '') {
                $arrData['landing_url'] = 'https://www.walmart.com' . $arrData['landing_url'];
            }
            $arrData['itemsid'] = $this->formatArrayValue($finalData, 'item_id');
            $arrData['item_image_url'] = $this->getProductItemUrl($apiResponse);
            $arrData['dotcom_thumbnail'] = $this->getProductItemUrl($apiResponse);
            $arrData['dotcom_description'] = $this->formatArrayValue($finalData, 'product_name');
            
            $arrData['grocery_url'] = $this->formatArrayValue($finalData, 'storepickable');
            if($arrData['grocery_url'] == true && !empty($arrData['itemsid'])){
                $arrData['grocery_url'] = 'https://www.grocery.walmart.com/product/'.$arrData['itemsid'];
            }
            $arrData['landing_comment'] = isset($apiResponse['payload']) && isset($apiResponse['payload']['productPublishStatus']) ? $apiResponse['payload']['productPublishStatus'] : '';
        }

        $supplyTradeItems = $this->getSupplyTradeItemsData($apiResponse,$searchVal_val,$flag);
        $arrData = array_merge($arrData, $supplyTradeItems);
        $masterData = array_merge($mcisColumns, $arrData);
        return $masterData;
    }
    
    /**
     * 
     * @param array $apiData
     * @param string $key
     * @return array
     */
    function formatArrayValue($apiData, $key) {

        $arrResponse = '';
        if (isset($apiData[$key]) && isset($apiData[$key]['values'])) {

            if ($key == 'karf_primary_department_title' || $key == 'product_pt_family') {
                $arrResponse = isset($apiData[$key]['values'][0]) && isset($apiData[$key]['values'][0]['value']) ?
                        $apiData[$key]['values'][0]['value'] : "";
            }else {            
                $arrResponse = isset($apiData[$key]['values'][0]) && isset($apiData[$key]['values'][0]['source_value']) ?
                        $apiData[$key]['values'][0]['source_value'] : "";
            }
        }
        return $arrResponse;
    }
    
    /**
     * Get only api columns from master table
     * @return array
     */
    function getMastersColumns() {
        $objHeaders = new ItemsHeaders();
        //$dbResult = $objHeaders->where('status', '1')->orderBy('column_order', 'asc')->get(['column_name'])->toArray();
        $sql = "select column_name from information_schema.columns where table_name ='master_items'";
        $dbResult = $objHeaders->dbSelect($sql);
        $arrayData = [];

        foreach ($dbResult as $value) {

            $arrayData[$value->column_name] = '';
        }
        unset($arrayData['id']);
        return $arrayData;
    }
    
    /**
     * Get item image URL from IQS response
     * @param array $apiResponse
     * @return string
     */
    function getProductItemUrl($apiResponse) {
        $itemImgUrl = '';
        $finalData = isset($apiResponse['payload']) && isset($apiResponse['payload']['product']) && isset($apiResponse['payload']['product']['assets']) ? $apiResponse['payload']['product']['assets'] : [];
        $apiData = (isset($finalData['values'])) ? $finalData['values'] : [];

        if (!empty($apiData)) {
            foreach ($apiData as $k => $data) {
                foreach ($data as $k2 => $val) {
                    if ($k2 == 'properties') {
                        if (isset($val['assetType']) && $val['assetType'] == 'PRIMARY') {
                            $itemImgUrl = isset($val['assetUrl']) ? $val['assetUrl'] : '';
                        }
                    }
                }
            }
        }
        return $itemImgUrl;
    }
    
    /**
     * Get supply items data from uber/v1 api
     * @param array $apiResponse
     * @return array
     */
    function getSupplyTradeItemsData($apiResponse, $searchVal_val, $flag) {
        $arrData = [];
        $plu_val = '';
        $apiData = (isset($apiResponse['payload']) && isset($apiResponse['payload']['supplyTradeItems'])) ? $apiResponse['payload']['supplyTradeItems'] : [];
        if (!empty($apiData)) {
            foreach ($apiData as $data) {

                foreach ($data['payloadJson'] as $k => $value) {

                    if ($k == 'attributes') {
                        if ($plu_val == '') {
                            $plu_val = isset($value['pluNbr']) ? ($value['pluNbr']) : '';
                        }
                        if ($flag == '1') {
                            $searchVal_val_api = isset($value['itemNbr']) ? ($value['itemNbr']) : '';
                            if ($searchVal_val_api == $searchVal_val) {
                                $arrData['supplier_nbr'] = isset($value['supplierNbr']) ? ($value['supplierNbr']) : '';
                                //$arrData['plu_nbr'] = isset($value['pluNbr']) ? ($value['pluNbr']) : '';
                                $arrData['cost'] = isset($value['unitCostAmt']) ? ($value['unitCostAmt']) : '';
                                $arrData['base_unit_retail'] = isset($value['baseRetailAmt']) ? ($value['baseRetailAmt']) : '';
                                $arrData['fineline_number'] = isset($value['finelineNbr']) ? ($value['finelineNbr']) : '';
                                $arrData['buyer_user_id'] = isset($value['buyerUserId']) ? ($value['buyerUserId']) : '';
                                $arrData['searched_item_nbr'] = isset($value['itemNbr']) ? ($value['itemNbr']) : '';
                                $arrData['item_file_description'] = isset($value['supplyItemPrimaryDescription']) ? ($value['supplyItemPrimaryDescription']) : '';
                            }
                        } else if (isset($value['isPrimaryVendorInd']) && $value['isPrimaryVendorInd'] == '1') {
                            $arrData['supplier_nbr'] = isset($value['supplierNbr']) ? ($value['supplierNbr']) : '';
                            //$arrData['plu_nbr'] = isset($value['pluNbr']) ? ($value['pluNbr']) : '';
                            $arrData['cost'] = isset($value['unitCostAmt']) ? ($value['unitCostAmt']) : '';
                            $arrData['base_unit_retail'] = isset($value['baseRetailAmt']) ? ($value['baseRetailAmt']) : '';
                            $arrData['fineline_number'] = isset($value['finelineNbr']) ? ($value['finelineNbr']) : '';
                            $arrData['buyer_user_id'] = isset($value['buyerUserId']) ? ($value['buyerUserId']) : '';
                            $arrData['searched_item_nbr'] = isset($value['itemNbr']) ? ($value['itemNbr']) : '';
                            $arrData['item_file_description'] = isset($value['supplyItemPrimaryDescription']) ? ($value['supplyItemPrimaryDescription']) : '';
                            $arrData['season_year'] = isset($value['seasonYearNbr']) ? ($value['seasonYearNbr']) : '';
                        }
                    }
                }
            }
        }
        $arrData['plu_nbr'] = $plu_val;
        return $arrData;
    }

    function getProductItemsData($apiData, $key) {
        $arrData = [];
        if (!empty($apiData)) {
            if (isset($apiData[$key])) {
                foreach ($apiData[$key] as $value) {
                    if (isset($value['values'])) {
                        foreach ($value['values'] as $val) {
                            $arrData = isset($val['source_value']) ? $val['source_value'] : '';
                        }
                    }
                }
            }
        }
        return $arrData;
    }
    
    /**
     * Get Gtin number from supply_item api
     * @param string $searchValue
     * @return array
     */
    function getconsumableGtin($searchValue) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            //CURLOPT_URL => 'http://itemsetupquerysvc.prod.nxgensearch.catdev.glb.prod.walmart.com/item-setup-query-service-app/services/supply-item/v2/' . $searchValue . '?bu=WM&country=US',
            CURLOPT_URL => config('smartforms.iqsGTINApiUrl'). $searchValue . '?bu=WM&country=US',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
//            CURLOPT_HTTPHEADER => array(
//                "WM_CONSUMER.ID:74e6fd32-2ee4-4786-a986-4257d2c461c0",
//                "WM_SVC.NAME: item-setup-query-service-app",
//                "WM_SVC.VERSION: 1.0.0",
//                "WM_QOS.CORRELATION_ID:822b075e-110d-4ea1-b8b7-747553068f06",
//                "WM_SVC.ENV:prod",
//                "Accept:application/json"
//            ),
            CURLOPT_HTTPHEADER => config('smartforms.iqsGTINCurlHeaders'),
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $apiResponse = json_decode($response, true);
            $apiData = isset($apiResponse['payload']) && isset($apiResponse['payload']['supplyItem']) && isset($apiResponse['payload']['supplyItem']['attributes']) ? $apiResponse['payload']['supplyItem']['attributes'] : [];
            $consumableGtin = isset($apiData['consumableGtin']) ? $apiData['consumableGtin'] : "";
            return $consumableGtin;
        }
    }

    function checkWalmartItemNumberKeyExists($apiResponse) {

        if (!empty($apiResponse)) {
            $finalData = isset($apiResponse['payload']) && isset($apiResponse['payload']['product']) ? $apiResponse['payload']['product']['product_attributes'] : [];
            $value = $this->formatArrayValue($finalData, 'walmart_item_number');
            if (!empty($value)) {
                return true;
            } else {
                return false;
            }
        }
    }

    function findCheckDigitNum($upcNumber,$limit=11) {
        $length = strlen($upcNumber);
        $formatUPC = $upcNumber;
        if ($length <= $limit) {
            /**
             * Add leading zero numbers
             */
             $upcNumber = str_pad($upcNumber, $limit, '0', STR_PAD_LEFT);
            $checkDigit = $this->generateUpcCheckdigit($upcNumber,$limit);
           
            $formatUPC = $upcNumber . $checkDigit;
        }
       
        return $formatUPC;
    }
    
    /**
     * Generate check digit number
     * @param string $upcNumber
     * @return string
     */
    function generateUpcCheckdigit($upcNumber,$limit) {
        $oddNumber = 0;
        $evenNumber = 0;
        for ($i = 0; $i < $limit; $i++) {
            if ((($i + 1) % 2) == 0) {
                /* Sum even digits */
                $evenNumber += $upcNumber[$i];
            } else {
                /* Sum odd digits */
                $oddNumber += $upcNumber[$i];
            }
        }
        $sum = (3 * $oddNumber) + $evenNumber;
        /* Get the remainder MOD 10 */
        $checkDigit = $sum % 10;
        /* If the result is not zero, subtract the result from ten. */
        return ($checkDigit > 0) ? 10 - $checkDigit : $checkDigit;
        
    }
    
    /**
     * Get linked items from uper/vq api service
     * @param array $apiResponse
     * @param array $mcisColumns
     * @return array
     */
    function getLinkedItems($apiResponse, $mcisColumns,$searchVal_val_api_mtr, $upc_nbr) {
        $linkedArray = $finalArray = [];
        $apiData = (isset($apiResponse['payload']) && isset($apiResponse['payload']['supplyTradeItems'])) ? $apiResponse['payload']['supplyTradeItems'] : [];
        if (!empty($apiData)) {
            foreach ($apiData as $value) {
                if (isset($value['identifierType']) && $value['identifierType'] == 'SUPPLY_ITEM') {
                    foreach ($value as $val) {
                        if (isset($val['attributes'])) {
                            $searched_item_nbr = isset($val['attributes']['itemNbr']) ? $val['attributes']['itemNbr'] : "";
                            if($searchVal_val_api_mtr!=$searched_item_nbr){
                            $linkedArray[] = ['supplier_nbr' => isset($val['attributes']['supplierNbr']) ? $val['attributes']['supplierNbr'] : "",
                                'base_unit_retail' => isset($val['attributes']['baseRetailAmt']) ? $val['attributes']['baseRetailAmt'] : "",
                                'item_file_description' => isset($val['attributes']['supplyItemPrimaryDescription']) ? $val['attributes']['supplyItemPrimaryDescription'] : "",
                                'cost' => isset($val['attributes']['unitCostAmt']) ? $val['attributes']['unitCostAmt'] : "",
                                'plu_nbr' => isset($val['attributes']['pluNbr']) ? $val['attributes']['pluNbr'] : "",
                                'searched_item_nbr' => $searched_item_nbr,
                                'upc_nbr' => $upc_nbr
                            ];
                            }
                        }
                    }
                }
            }
            if (!empty($linkedArray)) {
                foreach ($linkedArray as $value) {
                    $finalArray[] = array_merge($mcisColumns, $value);
                }
            }
        }
        return $finalArray;
    }

}