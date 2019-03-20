<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImportUtils
 *
 * @author enterpi
 */

namespace CodePi\RestApiSync\Utils;

use CodePi\Base\DataSource\Elastic;
use CodePi\Base\Eloquent\History;
use DB;
class ImportElasticUtils {
    
    public $index;
    public $type;
    public $table_name;
    public $action;


    /**
     * 
     * @param type $index
     */
    function setIndex($index) {
        $this->index = $index;
    }
    /**
     * 
     * @return type
     */
    function getIndex() {
        return $this->index;
    }
    /**
     * 
     * @param type $type
     */
    function setType($type) {
        $this->type = $type;
    }
    /**
     * 
     * @return type
     */
    function getType() {
        return $this->type;
    }
    
    function setTableName($table_name){
        $this->table_name = $table_name;
    }
    
    function getTableName(){
        return $this->table_name;
    }
    
    function setAction($action){
        $this->action = $action;
    }
    
    function getAction(){
        return $this->action;
    }


    /**
     * Clear the entire data in ElasticSearch
     * @param String $index = The name of index in ElasticSearch
     * @param String $type = The name of type in ElasticSearch
     * @return type     
     */
    function deleteAll() {
        $objElastic = new Elastic();
        $params['index'] = $this->getIndex();
        $params['type'] = $this->getType();
        $params['body'] = array('query' => array('range' => array('id' => array('gte' => 1))));
        return $objElastic->deleteByQuery($params);
    }
    /**
     * Insert new data into Elasticsearch
     * @param type $data
     */
    function insertRecord($data) {
        $objElastic = new Elastic();
        $itemData = [];
        if (!empty($data)) {
            foreach ($data as $row) {                
                $itemData['body'][] = ['index' => 
                                                [
                                                  '_index' => $this->getIndex(),
                                                  '_type' => $this->getType(),
                                                  '_id' => $row['id']
                                                ]
                                       ];
                $itemData['body'][] = $row;
            }            
            $objElastic->bulk($itemData);
        }
    }
    /**
     * 
     */
    function setSyncStatusInHistory(){
        DB::beginTransaction();
        try{
            $tablename = $this->getTableName();            
            $objUpdate = new History();
            $objUpdate->whereIn('table_name', $tablename)->update(['is_es_sync' => '0']);
            DB::commit();
        } catch (Exception $ex) {
            DB::rollback();
        }
    }
    
    /**
     * Check the the ID exists in ElasticSearch
     * @param String $index 
     * @param String $type
     * @param Int $intId
     * @return Count
     */
    function isExistsInIndex($intId) {
        $objE = new Elastic();
        $params['index'] = $this->getIndex();
        $params['type'] = $this->getType();
        $params['body'] = array('query' => array('match' => array('id' => $intId)));
        $result = $objE->search($params);
        $total = isset($result['hits']) && isset($result['hits']['total']) ? $result['hits']['total'] : 0;
        return $total;
    }
    /**
     * 
     * @param type $data
     * @return type
     */
    function getIndexBody($id) {
        $actionType = $this->getAction();
        return [$actionType => [
                '_index' => $this->getIndex(),
                '_type' => $this->getType(),
                '_id' => $id
            ]
        ];
    }
    /**
     * 
     * @param type $params
     */
    function setEmptyValueToProperties($params) {                
        $data['index'] = $this->getIndex();
        $data['type'] = $this->getType();
        $data['id'] = $params['id'];
        $data['body'] = ['doc' => $params];
        $objElastic = new Elastic();
        $objElastic->update($data);
    }

}
