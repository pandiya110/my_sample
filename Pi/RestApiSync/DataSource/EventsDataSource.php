<?php

namespace CodePi\RestApiSync\DataSource;
use CodePi\Base\DataSource\DataSource;

#use CodePi\RestApiSync\DataSource\DataSourceInterface\iEvents;
use CodePi\RestApiSync\DataSource\DataSourceInterface\iImportElastic;
#use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\DataSource\Elastic;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\History;
use CodePi\Base\Eloquent\Campaigns;
#use URL;
use DB;
use CodePi\RestApiSync\Utils\ImportElasticUtils;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\RestApiSync\DataTransformers\EventsTransformer as EventsTs;
use CodePi\RestApiSync\DataSource\ItemsDataSource;
use CodePi\Base\Commands\CommandFactory;
#use CodePi\Base\Libraries\PiLog;
#use CodePi\ImportExportLog\Commands\ImportExportLog;


class EventsDataSource implements iImportElastic {
    
    private $actionType = array('insert', 'update', 'delete');
    
    /**
     * Get All events data to import into elasticsearch index
     * @param type $command
     * @return type
     */
    function getAllData($command) {

        $params = $command->dataToArray();
        $objEvents = new Events;
        $objResult = $objEvents->dbTable('e')
                               ->leftJoin('campaigns as c', 'c.id', '=', 'e.campaigns_id')
                               ->leftJoin('campaigns_projects as cp', 'cp.id', '=', 'e.campaigns_projects_id')
                               ->select('c.name AS campaigns_name', 'c.aprimo_campaign_id', 'e.id', 'e.name', 
                                        'e.statuses_id', 'e.start_date', 'e.end_date', 'e.last_modified', 
                                        'e.date_added', 'e.ip_address', 'e.is_draft', 'e.last_modified_by', 
                                        'e.campaigns_id', 'e.created_by', 'cp.title as aprimo_project_name', 
                                        'cp.aprimo_project_id', 'e.campaigns_projects_id')
                               ->where('is_draft', '0')                               
                               ->orderBy('e.id', 'asc')
                               ->get();

        return $objResult;
    }

    /**
     * Get data from history table to sync
     * @param type $params
     * @return collection
     */
    function getSyncData($params){
        
        $objHistory = new History;
        $objHistory = $objHistory->where('is_es_sync', '1')
                                 ->where('table_name', $params['table_name'])
                                 ->where('action', $params['action'])
                                 ->orderBy('id', 'asc')    
                                 ->get();
        return $objHistory;
        
    }
    /**
     * Format the query result
     * @param array $data
     * @return array
     */
    function prepareSynData($data) {
        $arrSynData = $arrData = [];
        $failure_id = [];
        try {

            if (!empty($data)) {

                foreach ($data as $row) {
                    $tableValues = (array) json_decode($row->total_history);
                    $updateFields = array_filter(explode(",", str_replace('"', '', str_replace(']', '', str_replace('[', '', $row->changed_fields)))));

                    foreach ($tableValues as $key => $value) {
                        $arrData[$key] = $value[1];
                        if ($key == 'statuses_id') {
                            $arrData['status'] = $this->StatusArray($value[1]);
                        }
                    }
                    if ($row->action == 'update') {
                        if (!empty($updateFields)) {
                            foreach ($updateFields as $fields) {
                                if (isset($arrData[$fields])) {
                                    if (isset($arrData['is_draft']) && $arrData['is_draft'] != '1') {
                                        $arrSynData[$row->events_id][$fields] = $arrData[$fields];
                                        $arrSynData[$row->events_id]['id'] = $row->events_id;
                                        if ($fields == 'statuses_id') {
                                            $arrSynData[$row->events_id]['status'] = $this->StatusArray($arrSynData[$row->events_id][$fields]);
                                        }
                                        if (isset($fields) && $fields == 'campaigns_id') {
                                            $campaignsInfo = $this->getCampaignsDetails($row->events_id);
                                            $arrSynData[$row->events_id]['aprimo_campaign_name'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->name : '';
                                            $arrSynData[$row->events_id]['aprimo_campaign_id'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->aprimo_campaign_id : '';
                                            $arrSynData[$row->events_id]['aprimo_project_name'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->title : '';
                                            $arrSynData[$row->events_id]['aprimo_project_id'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->campaigns_projects_id : '';
                                        }
                                    } else {
                                        $failure_id[] = $row->events_id;
                                    }
                                }
                            }
                            if (!empty($failure_id)) {
                                $objItemsDs = new ItemsDataSource();
                                $objItemsDs->updateIsEsSyncFlag($failure_id, $action = 'update', 'events',  '2');
                            }
                        }
                    } else if ($row->action == 'insert') {
                        $arrSynData[$row->events_id] = $arrData;
                        $arrSynData[$row->events_id]['id'] = $row->events_id;
                        $campaignsInfo = $this->getCampaignsDetails($row->events_id);
                        $arrSynData[$row->events_id]['aprimo_campaign_name'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->name : '';
                        $arrSynData[$row->events_id]['aprimo_campaign_id'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->aprimo_campaign_id : '';
                        $arrSynData[$row->events_id]['aprimo_project_name'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->title : '';
                        $arrSynData[$row->events_id]['aprimo_project_id'] = isset($campaignsInfo[0]) && !empty($campaignsInfo[0]) ? $campaignsInfo[0]->campaigns_projects_id : '';
                    }
                }
            }
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
        }

        return array_values($arrSynData);
    }

    /**
     * 
     * @param type $intCampaignsId
     * @return type
     */
//    function getCampaignsDetails($intCampaignsId = 0){
//        $objCamp = new Campaigns;
//        return $objCamp->where('id', $intCampaignsId)->get();
//        
//    }
    
    function getCampaignsDetails($intEventId = 0) {
        $objCamp = new Events();
        $objCamp = $objCamp->dbTable('e')
                           ->join('campaigns as c', 'c.id', '=', 'e.campaigns_id')
                           ->leftJoin('campaigns_projects as cp', 'cp.id', '=', 'e.campaigns_projects_id')
                           ->where('e.id', $intEventId)->get();
        return $objCamp;
    }

    /**
     * 
     * @param type $intID
     * @param type $action
     * @return boolean
     */
//    function updateIsEsSyncFlag($intID = 0, $action, $isEsSyncStatus) {
//        DB::beginTransaction();
//        try {
//            $objHistory = new History;
//            $objHistory->whereIn('events_id', $intID)
//                       ->where('table_name', 'events')
//                       ->where('action', $action)
//                       ->update(['is_es_sync' => $isEsSyncStatus]);
//            DB::commit();
//        } catch (\Exception $ex) {
//            DB::rollback();            
//        }
//
//        return true;
//    }

    function StatusArray($intID){
        $array =  [1 => 'NEW', 2 => 'ACTIVE', 3 => 'PUBLISHED', 4 => 'DRAFT', 5 => 'ARCHIVED'];
        $status = isset($array[$intID]) ? $array[$intID] : '';
        return $status;
    }
    
    /**
     * Import data from mysql db to elasticsearch
     * @param type $command
     * @return type
     */
    function importDataToEs($command) {
        $status = false;
        $exMsg = '';
        try {
            $getData = $this->getAllData($command);
            $objDataResponse = new DataResponse();
            $result = $objDataResponse->collectionFormat($getData, new EventsTs([]));            
            unset($getData);
            $objUtils = new ImportElasticUtils();
            $objUtils->setIndex('sm_events');
            $objUtils->setType('events');
            $objUtils->setTableName(array('events'));
            $objUtils->setSyncStatusInHistory();
            $objUtils->deleteAll();
            $objUtils->insertRecord($result);
            $status = true;
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            //return CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return array('status' => $status, 'msg' => $exMsg);
    }
    
    /**
     * Sync data from history to elasticsearch
     * There is cron will run every minute to sync data from db to elastcisearch
     * @param type $command
     * @return boolean
     */
    function syncDataToElastic($command) {
        $status = false;
        $insert = $update = $delete = 0;
        try {
            $params = $command->dataToArray();
            $action = $this->actionType;

            $insert = $update = $delete = 0;
            foreach ($action as $value) {

                $params['action'] = $value;
                $data = $this->getSyncData($params);
                $result = $this->prepareSynData($data);
                
                if ($value == 'insert') {
                    $insert = $this->insertBulkRecord($result);
                } else if ($value == 'update') {                    
                    $update = $this->updateBulkRecord($result);
                } else if ($value == 'delete') {
                    $delete = $this->deleteBulkRecord($result);
                }
            }
            $status = true;
        } catch (\Exception $ex) {
            $exMsg = 'SyncEventsDataToElasticSearch => ' . $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();            
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $status;
    }

    /**
     * Insert bulk data to elasticsearch
     * @param type $data
     * @return type
     */
    function insertBulkRecord($data) {
        $itemData = $success_id = $failure_id = [];
        $syncCount = 0;
        try {
            if (!empty($data)) {
                foreach ($data as $row) {
                    if ($row['is_draft'] == '0') {
                        $objUtils = new ImportElasticUtils();
                        $objUtils->setIndex('sm_events');
                        $objUtils->setType('events');
                        $objUtils->setAction('index');
                        $itemData['body'][] = $objUtils->getIndexBody($row['id']);
                        $itemData['body'][] = $row;
                        $success_id[] = $row['id'];
                    } else {
                        $failure_id[] = $row['id'];
                    }
                }

                if (!empty($itemData)) {
                    $objElastic = new Elastic();
                    $objElastic->bulk($itemData);
                    unset($itemData);
                }
                if (!empty($success_id)) {
                    $objItemsDs = new ItemsDataSource();
                    $objItemsDs->updateIsEsSyncFlag($success_id, $action = 'insert', 'events', '0');
                }
                if (!empty($failure_id)) {
                    $objItemsDs = new ItemsDataSource();
                    $objItemsDs->updateIsEsSyncFlag($success_id, $action = 'insert', 'events', '2');
                }
                $syncCount = count($data);
            }
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $syncCount;
    }
    /**
     * Bulk update to elasticsearch
     * @param type $data
     * @return type
     */
    function updateBulkRecord($data) {
        $itemData = $success_id = $failure_id = [];
        $syncCount = 0;
        try {
            if (!empty($data)) {
                foreach ($data as $row) {

                    $objUtils = new ImportElasticUtils();
                    $objUtils->setIndex('sm_events');
                    $objUtils->setType('events');
                    $isExists = $objUtils->isExistsInIndex($row['id']);
                    
                    if ($isExists) {
                        $objUtils->setAction('update');
                        $itemData['body'][] = $objUtils->getIndexBody($row['id']);
                        $itemData['body'][] = ['doc' => $row];
                        $success_id[] = $row['id'];
                    } else {
                        $failure_id[] = $row['id'];
                    }
                }

                if (!empty($itemData)) {
                    $objElastic = new Elastic();
                    $objElastic->bulk($itemData);
                    unset($itemData);
                }
                if (!empty($success_id)) {                    
                    $objItemsDs = new ItemsDataSource();
                    $objItemsDs->updateIsEsSyncFlag($success_id, $action = 'update', 'events',  '0');
                }
                if (!empty($failure_id)) {
                    $objItemsDs = new ItemsDataSource();
                    $objItemsDs->updateIsEsSyncFlag($success_id, $action = 'update', 'events', '2');
                }
                $syncCount = count($data);
            }
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $syncCount;
    }
    /**
     * Bulk delete in elasticsearch
     * @param type $data
     * @return type
     */
    function deleteBulkRecord($data) {

        $itemData = $success_id = $failure_id = [];
        $syncCount= 0;
        try {
            if (!empty($data)) {
                foreach ($data as $row) {

                    $objUtils = new ImportElasticUtils();
                    $objUtils->setIndex('sm_events');
                    $objUtils->setType('events');
                    $isExists = $objUtils->isExistsInIndex($row['id']);

                    if ($isExists) {
                        $objUtils->setAction('delete');
                        $itemData['body'][] = $objUtils->getIndexBody($row['id']);
                        $success_id[] = $row['id'];
                    } else {
                        $failure_id[] = $row['id'];
                    }
                }

                if (!empty($itemData)) {
                    $objElastic = new Elastic();
                    $objElastic->bulk($itemData);
                    unset($itemData);
                }

                if (!empty($success_id)) {
                    $objItemsDs = new ItemsDataSource();
                    $objItemsDs->updateIsEsSyncFlag($success_id, $action = 'delete', 'events', '0');
                }
                if (!empty($failure_id)) {
                    $objItemsDs = new ItemsDataSource();
                    $objItemsDs->updateIsEsSyncFlag($success_id, $action = 'delete', 'events', '2');
                }
                $syncCount = count($data);
            }
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $syncCount;
    }

}
