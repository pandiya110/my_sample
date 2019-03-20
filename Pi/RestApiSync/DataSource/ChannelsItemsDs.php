<?php

namespace CodePi\RestApiSync\DataSource;

use CodePi\Base\DataSource\DataSource;
use DB;
use CodePi\Base\DataSource\Elastic;
use CodePi\RestApiSync\Utils\ImportElasticUtils;
use CodePi\Base\Commands\CommandFactory;
use CodePi\RestApiSync\DataSource\ItemsDataSource;
use CodePi\Base\Eloquent\ChannelsItems;

class ChannelsItemsDs {
    
    /**
     * Get Items Channels Adtypes History data
     * @param String $action
     * @params action > Insert & Delete
     * @return array
     */
    function getItemsChannelsSyncData($action) {
        $sql = "SELECT * FROM history AS h WHERE h.table_name = 'channels_items' AND is_es_sync = '1' AND h.action = '" . $action . "' ORDER BY id ASC";
        $result = DB::select($sql);
        $arrChannels = [];

        foreach ($result as $row) {
            $isValid = $this->jsonValidator((array) json_decode($row->total_history));
            if ($isValid) {
                $tableValues = (array) json_decode($row->total_history);
                $arrChannels[$row->items_id]['channels'][$tableValues['channels_adtypes_id'][0]] = $this->getChannelsInfo($tableValues['channels_id'][0], $tableValues['channels_adtypes_id'][0]);
            }
        }
        return $arrChannels;
    }

    /**
     * Get the channels adtypes
     * @param type $intItemsId
     * @return array
     */
    function getAssignedChannelsAdtypes($intItemsId) {
        $sql = "SELECT 
                c.name AS channel_name, c.id AS channel_id, cat.id AS ad_types_id, cat.name AS ad_types_name
                FROM channels_items AS ci
                LEFT JOIN channels AS c ON c.id = ci.channels_id
                LEFT JOIN channels_ad_types AS cat ON cat.id = ci.channels_adtypes_id
                WHERE ci.items_id = " . $intItemsId . " ORDER BY ci.id ASC";
        $objChannelsItems = new ChannelsItems();
        $result = $objChannelsItems->dbSelect($sql);
        $data = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $data[$intItemsId]['channels'][$row->ad_types_id] = ['channel_id' => $row->channel_id, 'channel_name' => $row->channel_name, 'ad_types_name' => $row->ad_types_name, 'ad_types_id' => $row->ad_types_id];
            }
        } else {
            $data[$intItemsId]['channels'] = array();
        }

        return $data;
    }

    /**
     * Chekc given string is valid JSON or not
     * @param type $data
     * @return boolean
     */
    function jsonValidator($data = NULL) {
        if (!empty($data)) {
            @json_decode($data);
            return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
    }

    /**
     * Get Channels by given id
     * @param type $intChannelsId 
     * @param type $intAdtypesId
     * @return type
     */
    function getChannelsInfo($intChannelsId = 0, $intAdtypesId) {
        $data = [];
        $sql = " SELECT c.id, c.name as channels_name, cat.id as ad_types_id, cat.name as ad_types_name "
                . "FROM channels AS c "
                . "LEFT JOIN channels_ad_types AS cat ON cat.channels_id = c.id "
                . "WHERE c.id = " . $intChannelsId . " AND cat.id = " . $intAdtypesId . " ";
        $result = DB::select($sql);
        if (!empty($result)) {
            foreach ($result as $row) {
                $data = ['channel_id' => $row->id, 'channel_name' => $row->channels_name, 'ad_types_name' => $row->ad_types_name, 'ad_types_id' => $row->ad_types_id];
            }
        }
        return $data;
    }
    /**
     * Sync Channels Data
     * @param type $command
     */   
    function syncChannelsData($command) {
        
        $params = $command->dataToArray();
        $newChannels = $this->getItemsChannelsSyncData('insert');       
        if(!empty($newChannels)){
            $this->updateChannelsToItems($newChannels, 'insert');
        }
        
        $deleteChannels = $this->getItemsChannelsSyncData('delete');
        if(!empty($deleteChannels)){
            $this->updateChannelsToItems($deleteChannels, 'delete');
        }
               
    }
    
    /**
     * Update channels to against items in elasticsearch
     * @param array $result
     * @param string $action
     * @return boolean
     */
    function updateChannelsToItems($result, $action) {
        $status = false;
        try {
            $itemData = [];
            foreach ($result as $l => $m) {
                $channelItems = $this->getAssignedChannelsAdtypes($l);
                foreach ($channelItems as $id => $items) {
                    $objUtils = new ImportElasticUtils();
                    $objUtils->setIndex('sm_items');
                    $objUtils->setType('items');
                    $isExists = $objUtils->isExistsInIndex($id);
                    if ($isExists > 0) {
                        $objUtils->setEmptyValueToProperties(['id' => $id, 'channels' => array()]);
                        $objUtils->setAction('update');
                        $itemData['body'][] = $objUtils->getIndexBody($id);
                        $itemData['body'][] = ['doc' => $items];
                        $success_id[] = $id;
                    } else {
                        $failure_id[] = $id;
                    }
                }
                
                $objElasic = new Elastic();
                $objElasic->bulk($itemData);
                unset($itemData);
            }

            if (!empty($success_id)) {
                $objItemsDs = new ItemsDataSource();
                $objItemsDs->updateIsEsSyncFlag($success_id, $action, 'channels_items', '0');
            }
            if (!empty($failure_id)) {
                $objItemsDs = new ItemsDataSource();
                $objItemsDs->updateIsEsSyncFlag($failure_id, $action, 'channels_items', '0');
            }
            $status = true;
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $status;
    }

   
}
