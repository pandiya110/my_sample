<?php

namespace CodePi\Templates\DataSource;

use CodePi\Base\DataSource\DataSource;

use CodePi\Base\Eloquent\UsersTemplates;
use CodePi\Items\DataSource\ItemsDataSource;
use GuzzleHttp;
use CodePi\Base\Libraries\PiLib;
class UsersTemplatesDS {
    /**
     * Create and Edit the users templated views
     * @param type $params
     * @return type
     */
    function saveUsersTemplateView(array $params) {

        $customValues = [];
        $templateID = 0;
        $objUsersTemp = new UsersTemplates();
        $objUsersTemp->dbTransaction();
        $status = false;
        try {
            if (isset($params['columns']) && !empty($params['columns'])) {                
                foreach ($params['columns'] as $key => $columns) {                    
                    $customValues[$key] = $columns;                                    
                }
            }            
            if (!empty(array_filter($customValues))) {
                $params['columns'] = GuzzleHttp\json_encode($customValues);
                $this->changeActiveTemplateStatus($params['users_id']);
                $saveDetails = $objUsersTemp->saveRecord($params);
                $templateID = $saveDetails->id;
                $objUsersTemp->dbCommit();
                $status = true;
            }
        } catch (\Exception $ex) {
            $objUsersTemp->dbRollback();
        }
        return $templateID;
    }

    /**
     * One user will have only one template view, 
     * Before create new template , make it new template as a current template view,
     * Old template became isActive status will be 0
     * @param type $usersID
     * @return type
     */
    function changeActiveTemplateStatus($usersID = 0) {
        $objTemp = new UsersTemplates();
        $objTemp->dbTransaction();
        try {
            $countOfTemplates = $objTemp->where('users_id', $usersID)->count();
            if ($countOfTemplates > 0) {
                $objTemp->where('users_id', $usersID)
                        ->where('is_active', '1')
                        ->update(['is_active' => '0']);
            }
            $objTemp->dbCommit();
        } catch (Exception $ex) {
            $objTemp->dbRollback();
        }
    }

    /**
     * Delete the template
     * @param type $params
     */
    function deleteTemplates($params) {
        $objTemp = new UsersTemplates();
        $objTemp->dbTransaction();
        $status= false;
        try {
            $objTemp->where('id', $params['id'])
                    ->delete();
            $objTemp->dbCommit();
            $status = true;
        } catch (Exception $ex) {
            $objTemp->dbRollback();
        }
        
        return $status;
    }
    /**
     * Copy the template from one to others
     * @param type $params
     * @return boolean
     */
    function copyTemplates(array $params) {
        $data = [];
        $copyTemplateId = 0;
        $objTemp = new UsersTemplates();
        $objTemp->dbTransaction();
        $status = false;
        try {
            $dbResult = $objTemp->where('id', $params['id'])
                                ->get(['users_id', 'name', 'columns'])
                                ->toArray();            
            if (!empty($dbResult)) {
                foreach ($dbResult as $row) {
                    $data = $row;
                    $data['is_active'] = '0';                    
                    $data['name'] = $this->createCopyTemplateName('Copy of '.$row['name'], $params['users_id']);
                    $data['created_by'] = $params['users_id'];
                    $data['gt_date_added'] = gmdate('Y-m-d H:i:s');
                    $data['gt_last_modified'] = gmdate('Y-m-d H:i:s');
                }
                
                unset($params['id']);
                $data = array_merge($params, $data);                
                $result = $objTemp->saveRecord($data);
                $copyTemplateId = $result->id;
            }
            unset($data);
            $objTemp->dbCommit();
            $status = true;
        } catch (Exception $ex) {
            $objTemp->dbRollback();
        }
        
        return ['status' => $status, 'copyTemplateId' => $copyTemplateId];
    }
    /**
     * Assign dynamic name for while copy the template, template name should be same
     * @param string $str
     * @param int $intUsersId
     * @return string
     */
    function createCopyTemplateName($str, $intUsersId){
        $objTemp = new UsersTemplates();
        $result = $objTemp->whereRaw('SUBSTRING_INDEX(REPLACE(LOWER(name),\' \',\'\'), \'_\', 1) = ' . '\'' . str_replace(' ','',str_lower($str)) . '\'' . '')
                          ->where('users_id', $intUsersId)
                          ->count();
        if($result > 0){
            $strName = $str.'_'.$result;
        }else{
            $strName = $str;
        }
        
        return $strName;        
    }

    /**
     * Get templated assigned columns list
     * Based on Userid and template id template columsn will be loaded
     * @param type $params
     * @return type
     */
    function getActiveTemplateColumns(array $params) {
        $usersId = isset($params['users_id']) ? $params['users_id'] : 0;
        $arrColumnProperties = $arrayItemHeaders = [];
        $arrData = [];
        if (!empty($usersId)) {
            
            $objTemp = new UsersTemplates();
            $dbResult = $objTemp->where('users_id', $usersId)
                                ->where('is_active', '1')
                                ->where(function ($query) use ($params) {
                                    if (isset($params['id']) && !empty(trim($params['id']))) {
                                        $query->where('id', $params['id']);
                                    }
                                })
                                ->get()
                                ->toArray();
           
            $isEmpty = 0;
            if (!empty($dbResult)) {
                foreach ($dbResult as $row) {
                    $columnDecode = (array) GuzzleHttp\json_decode($row['columns']);
                    $arrData = isset($columnDecode['originalColumns']) && !empty($columnDecode['originalColumns']) ? $columnDecode['originalColumns'] : [];
                    
                    if (!empty($arrData)) {
                        
                        $i = 1;
                        foreach ($arrData as $data) {
                            $userTemplateColumns[$data->column]['column'] = $data->column;
                            $userTemplateColumns[$data->column]['width'] = $data->width;
                            /**
                             * If order no is null while post, assign order number based on column key post order
                             */
                            $userTemplateColumns[$data->column]['order'] = !empty($data->order) ? $data->order : $i; 
                            $i++;
                        }

                        $isEmpty = 1;
                    }
                }
                
                unset($arrData);
                
                /**
                 * Merge the default headers vs users templated headers
                 */
                if (!empty($isEmpty)) {
                    $arrLinkHeaders = [];
                    $objItemDs = new ItemsDataSource();                    
                    $result = $objItemDs->getMappedItemHeaders($params);                    
                    $defaultColumnProperties = isset($result['itemHeaders']) ? $result['itemHeaders'] : [];
                    if ($params['linked_item_type'] == 2) {
                        $i = 0;                        
                        foreach ($defaultColumnProperties as $values) {
                            $arrLinkHeaders[$i] = $values;
                            $arrLinkHeaders[$i]['order_no'] = $i;
                            $i++;
                        }
                    }
                    $defaultColumnProperties = ($params['linked_item_type'] == 2) ? $arrLinkHeaders : $defaultColumnProperties;
                    
                    foreach ($defaultColumnProperties as $key => $values) {                       
                        if (isset($userTemplateColumns[$values['column']])) {
                            $arrayItemHeaders[$userTemplateColumns[$values['column']]['order']] = $values;
                            $arrayItemHeaders[$userTemplateColumns[$values['column']]['order']]['width'] = isset($userTemplateColumns[$values['column']]['width']) ? $userTemplateColumns[$values['column']]['width'] : $values['width'];
                            $arrayItemHeaders[$userTemplateColumns[$values['column']]['order']]['order_no'] = isset($userTemplateColumns[$values['column']]['order_no']) ? $userTemplateColumns[$values['column']]['order_no'] : $values['order_no'];
                        }
                    }
                    ksort($arrayItemHeaders);                    
                    $arrColumnProperties['itemHeaders'] = array_values($arrayItemHeaders);
                    $arrColumnProperties['currentTemplateId'] = $result['currentTemplateId'];
                    $arrColumnProperties['hiddenColumns'] = $result['hiddenColumns'];
                    $arrColumnProperties['itemHeadersWidth'] = $result['itemHeadersWidth'];

                    unset($userTemplateColumns, $arrayItemHeaders, $result);
                }
            }
        }
        
        return $arrColumnProperties;
    }

    /**
     * 
     * @param type $array
     * @param type $key
     * @param type $sort_flags
     * @return type
     */
    function msort($array, $key, $sort_flags = SORT_REGULAR) {
        if (is_array($array) && count($array) > 0) {
            if (!empty($key)) {
                $mapping = array();
                foreach ($array as $k => $v) {
                    $sort_key = '';
                    if (!is_array($key)) {
                        $sort_key = $v[$key];
                    } else {                        
                        foreach ($key as $key_key) {
                            $sort_key .= $v[$key_key];
                        }
                        $sort_flags = SORT_STRING;
                    }
                    $mapping[$k] = $sort_key;
                }
                asort($mapping, $sort_flags);
                $sorted = array();
                foreach ($mapping as $k => $v) {
                    $sorted[] = $array[$k];
                }
                return $sorted;
            }
        }
        return $array;
    }
    /**
     * Template dropdown list
     * @param type $intUsersId
     * @return type
     */
    function getTemplateListByUserId($intUserId = 0) {
        $arrTemp = [];
        $objTemp = new UsersTemplates();
        $dbResult = $objTemp->where('users_id', $intUserId)
                            ->orderBy('last_modified', 'desc')
                            ->get();
        return $dbResult;
        
//        if (!empty($dbResult)) {
//            foreach ($dbResult as $row) {
//                $arrTemp[] = ['id' => $row['id'], 
//                              'name' => $row['name'], 
//                              'is_active' => !empty($row['is_active']) ? true : false
//                             ];
//            }
//        }        
//        return $arrTemp;
    }
    /**
     * 
     * @param int $intTempId
     * @return type
     */
    function getTemplateDetails(int $intTempId = 0){
        $objTemp = new UsersTemplates();
        $dbResult = $objTemp->where('id', $intTempId)->get()->toArray();        
        $arrTemplateDetails = [];
        if(!empty($dbResult)){
            foreach ($dbResult as $row){
                $arrTemplateDetails['id'] = $row['id'];
                $arrTemplateDetails['name'] = $row['name'];
                $arrTemplateDetails['is_active'] = $row['is_active'];
                $jsonColumns = \GuzzleHttp\json_decode($row['columns']);
                if(!empty($jsonColumns)){
                    foreach ($jsonColumns as $key => $columns){
                        $arrTemplateDetails['columns'][$key] = (array)$columns;
                    }
                }
            }
        }
        
        return $arrTemplateDetails;
    }
    /**
     * 
     * @param int $intUsersId
     * @return array
     */
    function getHiddenColumns(int $intUsersId = 0){
        $objTemp = new UsersTemplates();
        $dbResult = $objTemp->where('users_id', $intUsersId)
                            ->where('is_active', '1')                             
                            ->get()                            
                            ->toArray();
        
        $arrData = [];
        $columnDecode = [];
        if(!empty($dbResult)){
            foreach($dbResult as $row){
                $columnDecode = json_decode($row['columns']);
                $arrData = isset($columnDecode->hiddenColumns) && !empty($columnDecode->hiddenColumns) ? $columnDecode->hiddenColumns : [];
            }
        }        
        return $arrData;
    }
    /**
     * Get Active templateId
     * @param int $intUserId
     * @return type
     */
    function getActiveTemplateIdByUserId(int $intUsersId = 0) {
        $objTemp = new UsersTemplates();
        $dbResult = $objTemp->where('users_id', $intUsersId)
                            ->where('is_active', '1')
                            ->first();
        $templateId = 0;
        if (!empty($dbResult)) {
            $templateId = $dbResult->id;
        }
        return $templateId;
    }
    /**
     * 
     * @param string $params
     */
    function assignDefaultTemplate($params) {

        $objTemp = new UsersTemplates();
        $objTemp->dbTransaction();
        $templateID = 0;
        $status = false;
        try {
            $this->changeActiveTemplateStatus($params['users_id']);
            if ($params['is_default'] != true) {                
                $params['is_active'] = '1';
                $resutl = $objTemp->saveRecord($params);
                $templateID = $resutl->id;                
            }
            $objTemp->dbCommit();
            $status = true;
        } catch (\Exception $ex) {
            $objTemp->dbRollback();
            $status = false;
        }
        return $status;
    }

}
