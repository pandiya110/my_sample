<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Eloquent\ItemsPriceZones;
use CodePi\Base\Eloquent\PriceZones;
use DB;
use App\Events\ItemsActivityLogs;
use CodePi\Items\DataSource\LinkedItemsDataSource as LinkedDs;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\ItemsActivityLog\Logs\ActivityLog;
class PriceZonesDataSource {

    private $unique_id;

    function __construct() {
        $this->unique_id = mt_rand() . time();
    }

    /**
     * 
     * @param array $params
     * @return array
     */
    function getCommonData($params) {

        $arrData['date_added'] = isset($params['date_added']) ? $params['date_added'] : date('Y-m-d H:i:s');
        $arrData['last_modified'] = isset($params['last_modified']) ? $params['last_modified'] : date('Y-m-d H:i:s');
        $arrData['gt_date_added'] = isset($params['gt_date_added']) ? $params['gt_date_added'] : gmdate('Y-m-d H:i:s');
        $arrData['gt_last_modified'] = isset($params['gt_last_modified']) ? $params['gt_last_modified'] : gmdate('Y-m-d H:i:s');
        $arrData['created_by'] = isset($params['created_by']) ? $params['created_by'] : 1;
        $arrData['last_modified_by'] = isset($params['last_modified_by']) ? $params['last_modified_by'] : 1;
        $arrData['ip_address'] = \Request::ip();
        return $arrData;
    }

    /**
     * Get the list of global price zones
     * @return type
     */
    function getMasterPriceZones() {
        $objPriceZone = new PriceZones();
        $dbResult = $objPriceZone->orderBy('short_trait_desc', 'asc')->get(['id', 'versions'])->toArray();
        $arrMasterVersions = [];
        foreach ($dbResult as $row) {
            $arrMasterVersions[$row['id']] = $row['versions'];
        }
        return $arrMasterVersions;
    }  
    /**
     * Get Price zone name given by id
     * @param int $priceId
     * @return string
     */
    function getPriceZoneNameById($priceId) {
        $objPriceZone = new PriceZones();
        $versionsName = $objPriceZone->where('id', $priceId)->first();
        return isset($versionsName->versions) ? $versionsName->versions : "";
    }

    /**
     * Get the master item id
     * @param int $itemsId
     * @return int
     */
    function getMasterItemsIdByItemsId($itemsId) {
        $objItems = new Items();
        $masterItemsId = $objItems->where('id', $itemsId)->first();
        return isset($masterItemsId->master_items_id) && !empty($masterItemsId->master_items_id) ? $masterItemsId->master_items_id : 0;
    }
/****************** This Versions management in popup sections **************/
    /**
     * 
     * @param type $params
     * @return type
     */
    function getSystemVersionsCode($params) {
        $array = [];
        try {
            $intid = isset($params['id']) ? $params['id'] : 0;

            /**
             * Get AlreadyUsed versions
             */
            $arrUsedVers['used'] = [];
            /**
             * Get Assigned , Omit, Available versions
             */
            $arrAllVers = $this->getAvailableVersion($intid);
            $array = array_merge($arrAllVers, $arrUsedVers);

            unset($arrUsedVers, $arrAllVers);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return $array;
    }

//    function getSystemVersionsCode($params) {
//        $array = [];
//        try {
//            $intid = isset($params['id']) ? $params['id'] : 0;
//           
//            /**
//             * Get AlreadyUsed versions
//             */
//            $getUsedVers = $this->getUsedVersion($intid, $params['events_id'], '0');
//            $arrUsedVers['used'] = isset($getUsedVers['used']) && !empty($getUsedVers['used']) ? $getUsedVers['used'] : [];
//            $alreadyUsedIds = isset($getUsedVers['price_zone_id']) && !empty($getUsedVers['price_zone_id']) ? $getUsedVers['price_zone_id'] : [];
//            
//            /**
//             * Get Assigned , Omit, Available versions
//             */
//            $arrAllVers = $this->getAvailableVersion($intid,$alreadyUsedIds);              
//            $array = array_merge($arrAllVers, $arrUsedVers);
//            
//            unset($arrUsedVers, $alreadyUsedIds, $arrAllVers);
//        } catch (\Exception $ex) {
//            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
//            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
//        }
//        
//        return $array;
//    }

    /**
     * 
     * @param type $intid
     * @param type $alreadyUsedIds
     * @return type
     */
//    function getAvailableVersion($intid,$alreadyUsedIds) {
//        
//        $array['available'] = $array['unavailable'] = $array['omit_versions'] = [];
//        $objPriceZone = new PriceZones();           
//        $dbSelect = $objPriceZone->dbTable('pz')
//                                 ->leftJoin('items_price_zones as ipz', function($join) use ($intid) {
//                                    $join->on('pz.id', '=', 'ipz.price_zones_id')
//                                         ->where('ipz.items_id', $intid);
//                                 })
//                                 ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'ipz.items_id')
//                                 ->where(function ($query) use ($alreadyUsedIds) {
//                                     if(!empty($alreadyUsedIds)){
//                                         $query->whereNotIn('pz.id', $alreadyUsedIds);
//                                     }
//                                 })->orderBy('pz.short_trait_desc', 'asc')
//                                   ->get(['pz.id', 'pz.versions', 'ipz.items_id', 'ipz.is_omit', 'ie.was_price']);
//
//        if (!empty($dbSelect)) {
//            foreach ($dbSelect as $row) {
//                if (!empty($row->items_id) && $row->is_omit == '0') {                    
//                    $array['available'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
//                } else if (!empty($row->items_id) && $row->is_omit == '1') {
//                    $array['omit_versions'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
//                } else {
//                    $array['unavailable'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
//                }
//            }
//        }
//        
//        return $array;
//    }
    
    function getAvailableVersion($intid) {

        $array['available'] = $array['unavailable'] = $array['omit_versions'] = [];
        $objPriceZone = new PriceZones();
        $dbSelect = $objPriceZone->dbTable('pz')
                                 ->leftJoin('items_price_zones as ipz', function($join) use ($intid) {
                                    $join->on('pz.id', '=', 'ipz.price_zones_id')
                                    ->where('ipz.items_id', $intid);
                                 })
                                 ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'ipz.items_id')
                                 ->orderBy('pz.short_trait_desc', 'asc')
                                 ->get(['pz.id', 'pz.versions', 'ipz.items_id', 'ipz.is_omit', 'ie.was_price']);

        if (!empty($dbSelect)) {
            foreach ($dbSelect as $row) {
                if (!empty($row->items_id) && $row->is_omit == '0') {
                    $array['available'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
                } else if (!empty($row->items_id) && $row->is_omit == '1') {
                    $array['omit_versions'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
                } else {
                    $array['unavailable'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
                }
            }
        }

        return $array;
    }

    /**
     * Get Used price versions
     * @param type $intid
     * @param type $inteventid
     * @param type $intmasterid
     * @return type
     */
    function getUsedVersion($intid, $inteventid, $isOmit) {
        
        $array = [];
        $objPriceZone = new PriceZones();
        $intmasterid = $this->getMasterItemsIdByItemsId($intid);
        $dbSelect = $objPriceZone->dbTable('pz')
                                 ->join('items_price_zones as ipz', 'pz.id', '=', 'ipz.price_zones_id')
                                 ->join('items as i', 'i.id', '=', 'ipz.items_id')
                                 ->join('items_editable as ie', 'ie.items_id', '=', 'ipz.items_id')
                                 ->whereNotIn('ipz.items_id', [$intid])
                                 ->where('i.master_items_id', $intmasterid)
                                 ->where('i.events_id', $inteventid)
                                 ->where('ipz.is_omit', $isOmit)
                                 ->orderBy('pz.short_trait_desc', 'asc')
                                 ->get(['pz.id', 'pz.versions', 'ie.was_price', 'ipz.items_id']);
        if (!empty($dbSelect)) {
            foreach ($dbSelect as $row) {
                $array['used'][] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
                $array['price_zone_id'][] = $row->id;
            }        

            /**
             * Check Page & Adblock combinations conditions
             */
            $usedByItems = isset($array['used']) ? $array['used'] : [];
            //$versByPageAdblock = $this->getPageAndAdBlockVersions(array('id' => $intid, 'events_id' => $inteventid));
            //$finalArray = array_merge($usedByItems, $versByPageAdblock);

            /**
             * Get only unique array from both arrays
             */
            $array['used'] = array_map("unserialize", array_unique(array_map("serialize", $usedByItems)));
            unset($usedByItems);
        }

        return $array;
    }
    
    /**
     * 
     * @param type $intid
     * @param type $inteventid
     * @param type $isOmit
     * @return type
     */
//    function getUsedOmitVersion($intid, $inteventid, $isOmit) {
//
//        $array = [];
//        $objPriceZone = new PriceZones();
//        $intmasterid = $this->getMasterItemsIdByItemsId($intid);        
//        $dbSelect = $objPriceZone->dbTable('pz')
//                                 ->join('items_price_zones as ipz', 'pz.id', '=', 'ipz.price_zones_id')
//                                 ->join('items as i', 'i.id', '=', 'ipz.items_id')
//                                 ->where('i.master_items_id', $intmasterid)
//                                 ->where('i.events_id', $inteventid)
//                                 ->where(function ($query) use ($isOmit) {
//                                    if ($isOmit != '') {
//                                        $query->where('ipz.is_omit', $isOmit);
//                                    }
//                                 })
//                                 ->orderBy('pz.short_trait_desc', 'asc')
//                                 ->get(['pz.id', 'pz.versions', 'ipz.items_id']);
//        
//        if (!empty($dbSelect)) {
//            foreach ($dbSelect as $row) {
//                $array['versions'][$row->items_id][$row->id] = $row->versions;
//                $array['price_zone_id'][$row->id] = $row->id;
//            }
//        }
//
//        return $array;
//    }
    
    function getUsedOmitVersion($intid, $inteventid, $isOmit) {

        $array = [];
        $objPriceZone = new PriceZones();        
        $dbSelect = $objPriceZone->dbTable('pz')
                                 ->join('items_price_zones as ipz', 'pz.id', '=', 'ipz.price_zones_id')
                                 ->join('items as i', 'i.id', '=', 'ipz.items_id')
                                 ->where('items_id', $intid)
                                 ->where('i.events_id', $inteventid)
                                 ->where(function ($query) use ($isOmit) {
                                    if ($isOmit != '') {
                                        $query->where('ipz.is_omit', $isOmit);
                                    }
                                 })
                                 ->orderBy('pz.short_trait_desc', 'asc')
                                 ->get(['pz.id', 'pz.versions', 'ipz.items_id']);
        
        if (!empty($dbSelect)) {
            foreach ($dbSelect as $row) {
                $array['versions'][$row->items_id][$row->id] = $row->versions;
                $array['price_zone_id'][$row->id] = $row->id;
            }
        }

        return $array;
    }

    /***********************End************************/
    
    /**
     * Get price zone master table primary id by versions
     * @param type $versions
     * @return type
     */
    function getPriceZoneIdByVersions($versions) {

        $arrId = [];
        if (!empty($versions)) {
            $obj = new PriceZones();
            $dbResult = $obj->whereIn('versions', $versions)
                    ->get(['id'])
                    ->toArray();
            foreach ($dbResult as $row) {
                $arrId[] = $row['id'];
            }
        }

        return $arrId;
    }

    /**
     * get version by id
     * @param integer $ids
     * @return version name
     */
    function getVersionById($ids) {
        $version = [];
        $obj = new PriceZones();
        $dbResult = $obj->whereIn('id', $ids)->get(['versions'])->toArray();
        if(!empty($dbResult)){
            foreach ($dbResult as $row){
                $version[] = $row['versions'];
            }
        }
        return $version;
    }

    /**
     * 
     * @param Array $params ['tracking_id']      => The Tracking ID (required)
     *                      ['log']              => This flag for check activity log to be add o not
     *                      ['value']            => Input Price ID Array Values (required)
     *                      ['item_id']          => The Row ID (required)
     *                      ['events_id']        => The Events ID (required)
     *                      ['omited_versions']  => The Array values of Omiited Versions
     * @param $params array Associative array of parameters
     * @return Array
     * 
     */     
//    function saveManualVersions($params) {
//
//        $objItemPrcZone = new ItemsPriceZones();
//        $objItemPrcZone->dbTransaction();
//        $params['tracking_id'] = isset($params['tracking_id']) ? $params['tracking_id'] : $this->unique_id;
//        $params['log'] = isset($params['log']) ? $params['log'] : true; 
//        $arrNew = $versionsCode = $arrInPriceId = $arrAlreadyExists = $exitsPriceId = $otherItemId = [];
//        try {
//            
//            if ($params['type'] == 1) {
//                $arrInPriceId = isset($params['value']) ? $params['value'] : [];
//            } else {
//                $arrInPriceId = $this->getPriceZoneIdByVersions($params['versions']);
//            }
//            
//            /**
//             * Delete price id , if unchecked from price zone popup, which is already assigned and user wants to remove
//             */
//            
//             $affectedRow = $this->deletePriceIdNotExistsByItems($params['item_id'], $arrInPriceId, $isOmit = '0');
//            
//
//            /**
//             * Get already availabe version to current items
//             */
//            if (isset($params['value']) && !empty($params['value']) || isset($params['versions']) && !empty($params['versions'])) {
//                $availableVers = $this->getAvailableVersion($params['item_id'], array());               
//                if (isset($availableVers['available']) && !empty($availableVers['available'])) {
//                    foreach ($availableVers['available'] as $price_id) {
//                        $arrAlreadyExists[] = $price_id['id'];
//                    }
//                }               
//                unset($availableVers);
//                /**
//                 * Validate, if alreay used price version
//                 */
//                $usedVersions = $this->getUsedVersion($params['item_id'], $params['events_id'], '0');  
//                
//                if (isset($usedVersions['used']) && !empty($usedVersions['used'])) {
//                    foreach ($usedVersions['used'] as $row) {
//                        $unsetKey = array_search($row['id'], $arrInPriceId);
//
//                        if ($unsetKey !== false) {
//                            $exitsPriceId[$params['item_id']] = $arrInPriceId[$unsetKey];
//                            unset($arrInPriceId[$unsetKey]);
//                        }
//                    }
//                }
//                
//                unset($usedVersions);
//
//                /**
//                 * Find and replace, if omit versions, became price version, replace the matched versions with omit versions
//                 */
//                $otherItemId = $this->updateOmitToPriceVersion($params['item_id'], $params['events_id'], $arrInPriceId);
//                /**
//                 * Insert only new records
//                 */
//                $arrNew = array_diff($arrInPriceId, $arrAlreadyExists);                    
//                if (!empty($arrNew)) {
//                    $insertStatus = $this->insertPriceIds($params, $arrNew, $isOmit = '0');
//                }
//                unset($arrInPriceId, $arrAlreadyExists);
//
//                if (isset($params['versions'])) {
//                    unset($params['versions']);
//                }
//            }
//            /**
//             * Save if any omitted versions , to be selected from price versions popup
//             */
//            if (isset($params['omited_versions'])) {
//                $this->saveOmittedVersions($params);
//            }
//            /**
//             * Update selected price versions and omitted versions in items table
//             */
//            
//            $versionsCode = $this->updateVersions($params);
//            
//            $status = true;
//            $objItemPrcZone->dbCommit();
//        } catch (\Exception $ex) {
//            $status = false;
//            $objItemPrcZone->dbRollback();
//            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
//            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
//        }
//        $itemsID = array_merge([$params['item_id']], $otherItemId);
//        $updateRowID = array_map("unserialize", array_unique(array_map("serialize", $itemsID)));
//        unset($itemsID);        
//        $priceVersion = isset($versionsCode['priceVersion']) ? $versionsCode['priceVersion'] : '';
//        $omitVersion = isset($versionsCode['omitVersion']) ? $versionsCode['omitVersion'] : '';
//        $arrResponse = ['status' => $status, 'versions' => $priceVersion, 'omitVersions' => $omitVersion,
//                        'exists_price_id' => $exitsPriceId, 'items_id' => $updateRowID, 'new_price_id' => $arrNew
//                       ];
//        unset($arrNew, $versionsCode, $exitsPriceId, $otherItemId, $updateRowID);
//        return $arrResponse;
//    }
    
    function saveManualVersions($params) {

        $objItemPrcZone = new ItemsPriceZones();
        $objItemPrcZone->dbTransaction();
        $params['tracking_id'] = isset($params['tracking_id']) ? $params['tracking_id'] : $this->unique_id;
        $params['log'] = isset($params['log']) ? $params['log'] : true; 
        $arrNew = $versionsCode = $arrInPriceId = $arrAlreadyExists = $exitsPriceId = $otherItemId = [];
        try {
            
            if ($params['type'] == 1) {
                $arrInPriceId = isset($params['value']) ? $params['value'] : [];
            } else {
                $arrInPriceId = $this->getPriceZoneIdByVersions($params['versions']);
            }
            
            /**
             * Delete price id , if unchecked from price zone popup, which is already assigned and user wants to remove
             */
            
             $affectedRow = $this->deletePriceIdNotExistsByItems($params['item_id'], $arrInPriceId, $isOmit = '0');
            
            /**
             * Get already availabe version to current items
             */
            if (isset($params['value']) && !empty($params['value']) || isset($params['versions']) && !empty($params['versions'])) {
                $availableVers = $this->getAvailableVersion($params['item_id'], array());               
                if (isset($availableVers['available']) && !empty($availableVers['available'])) {
                    foreach ($availableVers['available'] as $price_id) {
                        $arrAlreadyExists[] = $price_id['id'];
                    }
                }               
                unset($availableVers);
                
                /**
                 * Find and replace, if omit versions, became price version, replace the matched versions with omit versions
                 */
                $otherItemId = $this->updateOmitToPriceVersion($params['item_id'], $params['events_id'], $arrInPriceId);
                /**
                 * Insert only new records
                 */
                $arrNew = array_diff($arrInPriceId, $arrAlreadyExists);                  
                if (!empty($arrNew)) {
                    $insertStatus = $this->insertPriceIds($params, $arrNew, $isOmit = '0');
                }
                unset($arrInPriceId, $arrAlreadyExists);

                if (isset($params['versions'])) {
                    unset($params['versions']);
                }
            }
            /**
             * Save if any omitted versions , to be selected from price versions popup
             */
            if (isset($params['omited_versions'])) {
                $this->saveOmittedVersions($params);
            }
            /**
             * Update selected price versions and omitted versions in items table
             */
            
            $versionsCode = $this->updateVersions($params);
            
            $status = true;
            $objItemPrcZone->dbCommit();
        } catch (\Exception $ex) {
            $status = false;
            $objItemPrcZone->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        $itemsID = array_merge([$params['item_id']], $otherItemId);
        $updateRowID = array_map("unserialize", array_unique(array_map("serialize", $itemsID)));
        unset($itemsID);        
        $priceVersion = isset($versionsCode['priceVersion']) ? $versionsCode['priceVersion'] : '';
        $omitVersion = isset($versionsCode['omitVersion']) ? $versionsCode['omitVersion'] : '';
        $arrResponse = ['status' => $status, 'versions' => $priceVersion, 'omitVersions' => $omitVersion,
                        'exists_price_id' => $exitsPriceId, 'items_id' => $updateRowID, 'new_price_id' => $arrNew
                       ];
        unset($arrNew, $versionsCode, $exitsPriceId, $otherItemId, $updateRowID);
        return $arrResponse;
    }

    /**
     * Delete price id, only unchecked values from price zone popup
     * @param type $intid
     * @param array $arrPriceID
     * @param type $isOmit
     */
    function deletePriceIdNotExistsByItems($intid, array $arrPriceID, $isOmit) {
        $objItemsPriceZone = new ItemsPriceZones();
        $objItemsPriceZone->dbTransaction();
        $deletedCount = 0;
        try {
            $deletedCount = $objItemsPriceZone->whereIn('items_id', [$intid])
                              ->where(function($query) use($arrPriceID) {
                                if (!empty($arrPriceID)) {
                                    $query->whereNotIn('price_zones_id', $arrPriceID);
                                }
                               })->where('is_omit', $isOmit)
                                 ->delete();
            $objItemsPriceZone->dbCommit();
        } catch (\Exception $ex) {
            $objItemsPriceZone->dbRollback();
        }
        return $deletedCount;
    }
    /**
     * Insert price id into items price zones table
     * @param type $params
     * @param array $arrData
     * @param type $isOmit
     * @throws DataValidationException
     */
    function insertPriceIds($params, array $arrData, $isOmit) {
        $objItemPriceZone = new ItemsPriceZones();
        $objItemPriceZone->dbTransaction();
        $data = [];
        try {
            if ($arrData) {
                $intMasterId = $this->getMasterItemsIdByItemsId($params['item_id']);
                $arrCommonData = $this->getCommonData($params);
                foreach ($arrData as $intPriceZoneId) {
                    $data[] = array_merge($arrCommonData, ['items_id' => $params['item_id'],
                                                           'events_id' => $params['events_id'],
                                                           'price_zones_id' => $intPriceZoneId,
                                                           'master_items_id' => $intMasterId,
                                                           'is_omit' => $isOmit
                                                           ]);
                }
                if (!empty($data)) {
                    $objItemPriceZone->insertMultiple($data);
                }
                unset($data);
            }
            unset($arrData);
            $objItemPriceZone->dbCommit();
        } catch (\Exception $ex) {
            $objItemPriceZone->dbRollback();
            throw new DataValidationException('Price versions are not inserted.', new MessageBag());
        }
    }
    /**
     * Update the selected price zone       
     * @param array $params
     * @return type
     * @throws DataValidationException
     */
    function updateVersions($params) {
        
        $priceVersion = $omitVersion = [];
        $objItems = new Items();
        $objItems->dbTransaction();        
        try {
            /**
             * Check source type, import, copy or manual
             */
            $source = isset($params['source']) ? $params['source'] : 'manual';
            $savedVers = $this->getAvailableVersion($params['item_id'], array());  
            
            if (isset($savedVers['available']) && !empty($savedVers['available'])) {
                foreach ($savedVers['available'] as $rowVers) {
                    $priceVersion[] = $rowVers['versions'];
                }
            } 
            
            if (isset($savedVers['omit_versions']) && !empty($savedVers['omit_versions'])) {
                foreach ($savedVers['omit_versions'] as $rowOmit) {                    
                    $omitVersion[] = $rowOmit['versions'];
                }
            }            
            unset($savedVers);
            
            if ($source == 'manual') {                
                $params['tracking_id'] = isset($params['tracking_id']) ? $params['tracking_id'] : $this->unique_id;
                $arrCommonData = $this->getCommonData($params);
                $arrItems = ['id' => $params['item_id'],
                    'last_modified' => $arrCommonData['last_modified'],
                    'last_modified_by' => $arrCommonData['last_modified_by'],
                    'ip_address' => $arrCommonData['ip_address'],
                    'events_id' => $params['events_id'],
                    'tracking_id' => $params['tracking_id'] . '-0'
                ];
                $items = $objItems->saveRecord($arrItems);
                unset($arrItems['id']);
                $objItemEdit = new ItemsEditable();
                $iePrimId = $objItemEdit->where('items_id', $items->id)->first();
                $arrItems['id'] = $iePrimId->id;
                $arrItems['versions'] = !empty($priceVersion) ? implode(", ", $priceVersion) : 'No Price Zone found.';
                $arrItems['mixed_column2'] = !empty($omitVersion) ? implode(", ", $omitVersion) : '';
                $objItemEdit->saveRecord($arrItems);
                unset($arrItems);
                /**
                 * Add Activity Logs
                 */
                if (!empty($params['log'])) {
                    $objLogs = new ActivityLog();
                    $logData = $objLogs->setActivityLog(array('events_id' => $params['events_id'], 'actions' => 'update',
                        'users_id' => $params['last_modified_by'],
                        'type' => '0',
                        'count' => count(array($params['item_id'])),
                        'descriptions' => 'New price versions updated',
                        'tracking_id' => str_replace('-0', '', $params['tracking_id'])));
                    $objLogs->updateActivityLog($logData);
                    unset($logData);
                }
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            throw new DataValidationException($exMsg, new MessageBag());
        }
        
        return ['priceVersion' => $priceVersion, 'omitVersion' => $omitVersion];
    }
    /**
     * This is method will handle , if any already omited versions, try to assign as a price versions 
     * Exists omited versions will be removed from items    
     * @param int $intid
     * @param int $inteventid
     * @param array $arrInPriceId
     * @return array
     * @throws DataValidationException
     */
    function updateOmitToPriceVersion($intid, $inteventid, array $arrInPriceId) {
        $objItemPrcZone = new ItemsPriceZones();
        $objItemPrcZone->dbTransaction();
        $removeIds = $otherItemsIds = [];
        try {
            $usedOmitVers = $this->getUsedOmitVersion($intid, $inteventid, '1');            
            if (isset($usedOmitVers['price_zone_id']) && !empty($usedOmitVers['price_zone_id'])) {
                $removeIds = array_intersect($usedOmitVers['price_zone_id'], $arrInPriceId);                                
                if (!empty($removeIds)) {
                    $otherItemsIds = isset($usedOmitVers['versions']) && !empty($usedOmitVers['versions']) ? array_keys($usedOmitVers['versions']) : [];
                    $objItemPrcZone->whereIn('price_zones_id', $removeIds)->where('items_id', $intid)->where('is_omit', '1')->delete();
                    if (!empty($otherItemsIds)) {
                        foreach ($usedOmitVers['versions'] as $item_id => $code) {
                            foreach ($code as $key => $value) {
                                $unset = array_search($key, $removeIds);
                                if($unset !== false){
                                    unset($code[$unset]);
                                }
                            }
                            asort($code);
                            $omitVer = !empty($code) ? implode(', ', $code) : '';
                            $objItemEdit = new ItemsEditable();
                            $objItemEdit->where('items_id', $item_id)->update(['mixed_column2' => $omitVer]);
                        }
                    }
                }
                unset($removeIds);
            }
            unset($usedOmitVers);
            $objItemPrcZone->dbCommit();
        } catch (\Exception $ex) {
            $objItemPrcZone->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            throw new DataValidationException($exMsg, new MessageBag());
        }
        return $otherItemsIds;
    }
    /**
     * 
     * @param type $params
     * @return type
     */
    function saveOmittedVersions($params) {
        $arrNew = $arrAlreadyExists = $arrInPriceId = [];                
        try {
            
            if ($params['type'] == 1) {
                $arrInPriceId = isset($params['omited_versions']) ? $params['omited_versions'] : [];
            } else {
                if (isset($params['omited_versions'])) {
                    $arrInPriceId = $this->getPriceZoneIdByVersions($params['omited_versions']);
                }
            }            
            
            /**
             * Delete price id , if unchecked from price zone popup, which is already assigned and user wants to remove
             */
            
            $affectedRow = $this->deletePriceIdNotExistsByItems($params['item_id'], $arrInPriceId, $isOmit = '1');
            
            if (isset($params['omited_versions']) && !empty($params['omited_versions'])) {
                
                $availableVers = $this->getAvailableVersion($params['item_id'], array());
                if (isset($availableVers['omit_versions']) && !empty($availableVers['omit_versions'])) {
                    foreach ($availableVers['omit_versions'] as $price_id) {
                        $arrAlreadyExists[] = $price_id['id'];
                    }
                }
                unset($availableVers); 
                
                $arrNew = array_diff($arrInPriceId, $arrAlreadyExists);                
                $insertStatus = $this->insertPriceIds($params, $arrNew, $isOmit = '1');
                unset($arrInPriceId);
            }
            unset($arrAlreadyExists);            
        } catch (\Exception $ex) {            
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $arrNew;
    }
    /**
     * If new versions available insert into price zones master table
     * @param array $versions
     */
    function saveVersionsInPriceZoneMaster($versions) {
        \DB::beginTransaction();
        try {
            $obj = new PriceZones();
            $arrNew = $arrExisting = $arrInsert = [];
            $arrInVersions = [];
            if (!empty($versions)) {
                $arrInVersions = explode(", ", $versions);
            }
            $masterVersion = $this->getMasterPriceZones();
            foreach ($masterVersion as $val) {
                $arrExisting[] = ($val);
            }

            $arrNew = array_diff($arrInVersions, $arrExisting);

            if (!empty($arrNew)) {
                foreach ($arrNew as $code) {
                    $shortCode = implode("-", $code);
                    $arrInsert[] = ['short_trait_desc' => isset($shortCode[0]) ? $shortCode[0] : "",
                        'trait_nbr' => isset($shortCode[1]) ? $shortCode[1] : "",
                        'versions' => $code
                    ];
                }
                if (!empty($arrInsert)) {
                    $obj->insertMultiple($arrInsert);
                }
            }
            unset($arrInsert);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }

    /**
     * Delete the versions given by items id 
     * @param type $itemsId
     */
    function deleteVersionsByItemsId($itemsId, $master_id = 0, $event_id = 0) {

        $itemsId = is_array($itemsId) && !empty($itemsId) ? $itemsId : [];
        $master_id = is_array($master_id) && !empty($master_id) ? array_filter($master_id) : [];
        if (!empty($itemsId) || (!empty($master_id) && !empty($event_id))) {

            \DB::beginTransaction();
            try {
                $objItmPrcZone = new ItemsPriceZones();
                $objItmPrcZone->where(function($query) use($itemsId) {
                                  if (!empty($itemsId)) {
                                     $query->whereIn('items_id', $itemsId);
                                  }
                              })->where(function($query) use($master_id) {
                                  if (!empty($master_id)) {
                                      $query->whereIn('master_items_id', $master_id);
                                  }
                              })->where(function($query) use($event_id) {
                                  if (!empty($event_id)) {
                                     $query->where('events_id', $event_id);
                                  }
                              })->delete();
                \DB::commit();
            } catch (\Exception $ex) {
                \DB::rollback();
            }
        }
    }
    /**
     * 
     * @param type $master_id
     * @param type $events_id
     * @param type $intPrcZoneId
     * @param type $intItemsId
     * @return type
     */
    function checkPriceZoneExists($master_id, $events_id, $intPrcZoneId, $intItemsId) {

        $existSVersions = [];
        $objItmPrcZone = new ItemsPriceZones();

        if (!empty($intPrcZoneId)) {
            $dbResult = $objItmPrcZone->where('master_items_id', $master_id)
                                      ->where('events_id', $events_id)
                                      ->whereIn('price_zones_id', $intPrcZoneId)
                                      ->get();

            foreach ($dbResult as $row) {
                $existSVersions[] = $row['price_zones_id'];
            }
        }

        return $existSVersions;
    }
    /**
     * 
     * @param type $input
     * @param type $intEventId
     * @param type $masterItemEvent
     * @param type $log
     * @param type $tracking_id
     * @return type
     * @throws DataValidationException
     */
//    function copyMultiplePriceZones($input, $intEventId, $masterItemEvent, $log = false, $tracking_id) {
//        try {
//            $alreadyExistsId = $add = $duplicateItemNbr = $masterItemEventVal = [];
//            if (isset($input['versions']) && !empty($input['versions'])) {
//                
//                $input['versions'] = preg_replace('!\s+!', ' ', $input['versions']); //Remove multiple white space within string                
//                $versions = explode(", ", $input['versions']);
//                /**
//                 * Get Pricezone id from copied text price zone
//                 */
//                $priceZoneId = $this->getPriceZoneIdByVersions($versions);
//                /**
//                 * Get Master Id, This is Reference for unique
//                 */
//                $intMasterId = $this->getMasterItemsIdByItemsId($input['id']);
//                $uniqueKeyMD5 = md5($intEventId . '_' . $intMasterId);
//                if (!in_array($uniqueKeyMD5, $masterItemEvent)) {
//
//                    $masterItemEventVal = $uniqueKeyMD5;
//                    if (!empty($priceZoneId)) {
//                        $add['item_id'] = $input['id'];
//                        $add['events_id'] = $intEventId;
//                        $add['value'] = $priceZoneId;
//                        $add['type'] = 1;
//                        $add['log'] = $log;
//                        $add['tracking_id'] = $tracking_id;
//                        //As per client request, removed version validation for page & adblock                             
//                        //$isDuplicate = $this->checkPageAdBlock($add);  
//                        //if (isset($isDuplicate['isNotExists']) && !empty($isDuplicate['isNotExists'])) {
//                            /**
//                             * Add Price Zone Id
//                             */
//                            $versionsCode = $this->saveManualVersions($add);
//                            /**
//                             * Find which is not copied price zone ids, from post price id
//                             */
//                            if (isset($versionsCode['new_price_id']) && !empty($versionsCode['new_price_id'])) {
//                                $newPriceIds = array_diff($priceZoneId, $versionsCode['new_price_id']);
//                                if (!empty($newPriceIds)) {
//                                    $alreadyExistsId[$add['item_id']] = $this->getVersionById($newPriceIds);
//                                }
//                            } else if (empty($versionsCode['new_price_id'])) {
//                                $alreadyExistsId[$add['item_id']] = $this->getVersionById($priceZoneId);
//                            }
////                        } else {                              
////                            /**
////                             * Find the duplicate price id, by using same page and ad_block conditions
////                             */
////                            $dupPriceVers = isset($isDuplicate['existsPriceVer']) ? $isDuplicate['existsPriceVer'] : [];                            
////                            $match_id = array_intersect($priceZoneId, $dupPriceVers);
////                            $alreadyExistsId[$add['item_id']] = $this->getVersionById($match_id);                            
////                        }
//                    }else{
//                        $alreadyExistsId[$input['id']] = 'Invalid Price Zone';
//                        throw new DataValidationException('Invalid Price Zone or price versions not exists', new MessageBag());
//                    }
//                } else {                             
//                    /**
//                     * Find the alreay used versions, if copied from same item number
//                     */
//                    $alreadyExistsId[$input['id']] = $this->getVersionById($priceZoneId);
//                }
//            } else {
//                if (isset($input['id']) && !empty($input['id'])) {
//                    /**
//                     * If copy and paste empty value, entire price versions will be clear, which is already assigned to items
//                     */
//                    $this->deleteVersionsByItemsId([$input['id']]);
//                    $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
//                }
//            }
//            unset($add);
//        } catch (\Exception $ex) {
//            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
//            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
//        }
//
//        return ['items_id' => $input['id'], 'duplicate' => $duplicateItemNbr, 'exists' => $alreadyExistsId, 'masterItemEvent' => $masterItemEventVal];
//    }
//    
    function copyMultiplePriceZones($input, $intEventId, $log = false, $tracking_id) {
        try {
            $alreadyExistsId = $add = $duplicateItemNbr = $masterItemEventVal = [];
            if (isset($input['versions']) && !empty($input['versions'])) {

                $input['versions'] = preg_replace('!\s+!', ' ', $input['versions']); //Remove multiple white space within string                
                $versions = explode(", ", $input['versions']);
                /**
                 * Get Pricezone id from copied text price zone
                 */
                $priceZoneId = $this->getPriceZoneIdByVersions($versions);                
                if (!empty($priceZoneId)) {
                    $add['item_id'] = $input['id'];
                    $add['events_id'] = $intEventId;
                    $add['value'] = $priceZoneId;
                    $add['type'] = 1;
                    $add['log'] = $log;
                    $add['tracking_id'] = $tracking_id;

                    /**
                     * Add Price Zone Id
                     */
                    $versionsCode = $this->saveManualVersions($add);
                    /**
                     * Find which is not copied price zone ids, from post price id
                     */
                    if (isset($versionsCode['new_price_id']) && !empty($versionsCode['new_price_id'])) {
                        $newPriceIds = array_diff($priceZoneId, $versionsCode['new_price_id']);
                        if (!empty($newPriceIds)) {
                            $alreadyExistsId[$add['item_id']] = $this->getVersionById($newPriceIds);
                        }
                    } else if (empty($versionsCode['new_price_id'])) {
                        $alreadyExistsId[$add['item_id']] = $this->getVersionById($priceZoneId);
                    }
                } else {
                    $alreadyExistsId[$input['id']] = 'Invalid Price Zone';
                    throw new DataValidationException('Invalid Price Zone or price versions not exists', new MessageBag());
                }
            } else {
                if (isset($input['id']) && !empty($input['id'])) {
                    /**
                     * If copy and paste empty value, entire price versions will be clear, which is already assigned to items
                     */
                    $this->deleteVersionsByItemsId([$input['id']]);
                    $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
                }
            }
            unset($add);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return ['items_id' => $input['id'], 'duplicate' => $duplicateItemNbr, 'exists' => $alreadyExistsId, 'masterItemEvent' => $masterItemEventVal];
    }

    /**
     * Copy Multiple omit versions
     * @param type $input
     * @param type $intEventId
     * @param type $masterItemEvent
     * @param type $log
     * @param type $tracking_id
     * @return type
     */
//    function copyMultipleOmitPriceZones($input, $intEventId, $masterItemEvent, $log = false, $tracking_id) {
//        
//        $add = $duplicateItemNbr = $alreadyExistsId = $masterItemEventVal = [];
//        try {
//            if (isset($input['mixed_column2']) && !empty($input['mixed_column2'])) {
//                
//                $input['mixed_column2'] = preg_replace('!\s+!', ' ', $input['mixed_column2']); //Remove multiple white space within string                
//                $omitVersions = explode(", ", $input['mixed_column2']);
//                
//                /**
//                 * Get Id from versions code
//                 */
//                $priceZoneId = $this->getPriceZoneIdByVersions($omitVersions);
//                /**
//                 * Get all used versions with same master id
//                 */
//                $versByMasterId = $this->getUsedOmitVersion($input['id'], $intEventId, '0');
//                $currVersions = isset($versByMasterId['price_zone_id']) ? $versByMasterId['price_zone_id'] : [];
//                
//                /**
//                 * Check if copied omit versions, already used as a price versions, 
//                 * Used versions will be removed and only avilable omited versions will be assigned
//                 */
//                $removeAlreadyUsed = array_diff($priceZoneId, $currVersions);                    
//                $intMasterId = $this->getMasterItemsIdByItemsId($input['id']);
//                $uniqueKeyMD5 = md5($intEventId . '_' . $intMasterId);
//                
//                if (!in_array($uniqueKeyMD5, $masterItemEvent)) {
//                    $masterItemEventVal = $uniqueKeyMD5;
//                    $add['item_id'] = $input['id'];
//                    $add['events_id'] = $intEventId;
//                    $add['type'] = 1;
//                    $add['log'] = $log;
//                    $add['tracking_id'] = $tracking_id;
//                    $add['omited_versions'] = $removeAlreadyUsed;
//                    $versionsCode = $this->saveOmittedVersions($add);                    
//                    if (!empty($versionsCode)) {
//                        $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
//                        $newPriceIds = array_diff($priceZoneId, $versionsCode);
//                        if (!empty($newPriceIds)) {
//                            $alreadyExistsId[$add['item_id']] = $this->getVersionById($newPriceIds);
//                        }
//                    }
//                } else {                    
//                    /**
//                     * Find the alreay used versions, if copied from same item number
//                     */
//                    $alreadyExistsId[$input['id']] = $this->getVersionById($priceZoneId);
//                }
//            } else {
//                if (isset($input['id']) && !empty($input['id'])) {
//                    /**
//                     * If copy and paste empty value, entire price versions will be clear, which is already assigned to items
//                     */
//                    $this->deleteVersionsByItemsId([$input['id']]);
//                    $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
//                }
//            }
//        } catch (\Exception $ex) {
//            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
//            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
//        }
//
//        return ['items_id' => $input['id'], 'duplicate' => $duplicateItemNbr, 'exists' => $alreadyExistsId, 'masterItemEvent' => $masterItemEventVal];
//    }
    
    function copyMultipleOmitPriceZones($input, $intEventId, $log = false, $tracking_id) {

        $add = $duplicateItemNbr = $alreadyExistsId = $masterItemEventVal = [];
        try {
            if (isset($input['mixed_column2']) && !empty($input['mixed_column2'])) {
                
                $input['mixed_column2'] = preg_replace('!\s+!', ' ', $input['mixed_column2']); //Remove multiple white space within string                
                $omitVersions = explode(", ", $input['mixed_column2']);

                /**
                 * Get Id from versions code
                 */
                $priceZoneId = $this->getPriceZoneIdByVersions($omitVersions);
                
                /**
                 * Get all used versions with same master id
                 */
                $versByMasterId = $this->getUsedOmitVersion($input['id'], $intEventId, '0');
                $currVersions = isset($versByMasterId['price_zone_id']) ? $versByMasterId['price_zone_id'] : [];

                /**
                 * Check if copied omit versions, already used as a price versions, 
                 * Used versions will be removed and only avilable omited versions will be assigned
                 */
                $removeAlreadyUsed = array_diff($priceZoneId, $currVersions);                
                $add['item_id'] = $input['id'];
                $add['events_id'] = $intEventId;
                $add['type'] = 1;
                $add['log'] = $log;
                $add['tracking_id'] = $tracking_id;
                $add['omited_versions'] = $removeAlreadyUsed;
                $versionsCode = $this->saveOmittedVersions($add);                
                if (!empty($versionsCode)) {
                    $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
                    $newPriceIds = array_diff($priceZoneId, $versionsCode);
                    if (!empty($newPriceIds)) {
                        $alreadyExistsId[$add['item_id']] = $this->getVersionById($newPriceIds);
                    }
                }else{
                    $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
                }
            } else {
                if (isset($input['id']) && !empty($input['id'])) {
                    /**
                     * If copy and paste empty value, entire price versions will be clear, which is already assigned to items
                     */
                    $this->deleteVersionsByItemsId([$input['id']]);
                    $this->updateVersions(['item_id' => $input['id'], 'tracking_id' => $tracking_id, 'events_id' => $intEventId]);
                }
            }
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return ['items_id' => $input['id'], 'duplicate' => $duplicateItemNbr, 'exists' => $alreadyExistsId, 'masterItemEvent' => $masterItemEventVal];
    }

    /**
     * Get the Omitted Versions against items
     * @param Int $intItemsId
     * @return array
     */
    function getOmittedVersions($intItemsId) {
        $arrOmitVersions = [];
        $objItemPrice = new ItemsPriceZones();
        $result = $objItemPrice->where('items_id', $intItemsId)
                ->where('is_omit', '1')
                ->get()
                ->toArray();
        if (!empty($result)) {
            foreach ($result as $row) {
                $arrOmitVersions[$row['price_zones_id']] = $this->getPriceZoneNameById($row['price_zones_id']);
            }
        }
        return $arrOmitVersions;
    }

    /**
     * check the condition for same page and adblock for other items with in the event
     * 
     * @param type $params
     * @return boolean true|false
     */
    function checkPageAdBlock($params) {

        $itemInfo = $this->getItemPageAndAddBlock($params);
        $params['page'] = isset($itemInfo[0]->page) && isset($itemInfo[0]->page) ? $itemInfo[0]->page : '';
        $params['ad_block'] = isset($itemInfo[0]->ad_block) && isset($itemInfo[0]->ad_block) ? $itemInfo[0]->ad_block : '';
        $isNotExists = false;
        $existsPriceVers = [];
        
        if (!empty($params['page']) && !empty($params['ad_block'])) {
            $objItems = new Items();
            $result = $objItems->dbTable('i')
                               ->select('ie.page', 'ie.ad_block', 'ipz.price_zones_id')
                               ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                               ->join('items_price_zones as ipz', 'ipz.items_id', '=', 'i.id')
                               ->where('i.id', '!=', $params['item_id'])
                               ->whereIn('ipz.price_zones_id', $params['value'])
                               ->where('ipz.is_omit', '0')
                               ->where('i.events_id', '=', $params['events_id'])
                               ->where(function ($query) use ($params) {
                                   if (isset($params['ad_block']) && trim($params['ad_block']) != '') {
                                       $query->where('ie.ad_block', $params['ad_block']);
                                   }
                               })->where(function ($query) use ($params) {
                                    if (isset($params['page']) && trim($params['page']) != '') {
                                        $query->where('ie.page', $params['page']);
                                    }
                                })->get();
       
            if (count($result) == 0) {
                $isNotExists = true;
            }
            
            if (!empty($result)) {
                foreach ($result as $row) {
                    $existsPriceVers[] = $row->price_zones_id;
                }
            }
        } else {
            $isNotExists = true;
        }

        return ['isNotExists' => $isNotExists, 'existsPriceVer' => $existsPriceVers];
    }

    /**
     * get the page and ad_block for the item
     * @param type $param
     * @return array
     */
    function getItemPageAndAddBlock($param) {
        $objItems = new Items();
        $result = $objItems->dbTable('i')
                           ->select('ie.page', 'ie.ad_block')
                           ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'i.id')
                           ->where('i.id', $param['item_id'])
                           ->where('i.events_id', $param['events_id'])
                           ->get();
        return $result;
    }
    /**
     * 
     * @param type $params
     * @return type
     */
    function getPageAndAdBlockVersions($params) {
        $arrId = [];
        $params['item_id'] = $params['id'];
        $itemInfo = $this->getItemPageAndAddBlock($params);
        $params['page'] = isset($itemInfo[0]->page) && isset($itemInfo[0]->page) ? $itemInfo[0]->page : '';
        $params['ad_block'] = isset($itemInfo[0]->ad_block) && isset($itemInfo[0]->ad_block) ? $itemInfo[0]->ad_block : '';
        $objItems = new Items();
        if (!empty($params['page']) && !empty($params['ad_block'])) {
            $result = $objItems->dbTable('i')
                               ->select('pz.id', 'pz.versions', 'ie.was_price')
                               ->leftJoin('items_price_zones as ipz', 'ipz.items_id', '=', 'i.id')
                               ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'i.id')
                               ->leftJoin('price_zones as pz', 'pz.id', '=', 'ipz.price_zones_id')
                               ->where('i.id', '!=', $params['item_id'])
                               ->where('ipz.is_omit', '0')
                               ->where('i.events_id', '=', $params['events_id'])
                               ->where('ie.ad_block', $params['ad_block'])
                               ->where('ie.page', $params['page'])
                               ->get();
            foreach ($result as $row) {
                $arrId[] = array('id' => $row->id, 'versions' => $row->versions, 'was_price' => !empty($row->was_price) ? $row->was_price : '--');
            }
        }
        return $arrId;
    }
    /**
     * Migrations script to update price versions & omit columns
     * @param type $type
     * @return int
     */
    function migrationsScriptUpdatePriceid() {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::beginTransaction();
        echo 'Start :: '.date('H:i:s');
        try {
            $objPriceVer = new PriceZones();
            $query = "SELECT i.id
                    FROM items AS i
                    INNER JOIN items_editable AS ie ON ie.items_id = i.id
                    WHERE i.items_type='0' AND i.is_no_record='0' AND ie.versions !='No Price Zone found.' AND ie.versions !='No Price Zone available.'
                    ORDER BY i.id ASC ";
            $dbObj = $objPriceVer->dbSelect($query);
            $i = 0;
            foreach ($dbObj as $row) {
                $sql = "SELECT GROUP_CONCAT(pz.versions ORDER BY pz.versions ASC SEPARATOR ', ') AS versions FROM 
                    items_price_zones AS ipz 
                    INNER JOIN price_zones AS pz ON pz.id = ipz.price_zones_id
                    WHERE ipz.items_id = " . $row->id . "
                    GROUP BY ipz.is_omit ";
                $dbRow = $objPriceVer->dbSelect($sql);
                $objItemsEdit = new ItemsEditable();
                $price_version = isset($dbRow[0]) && isset($dbRow[0]->versions) ? $dbRow[0]->versions : 'No Price Zone found.';
                $omit_version = isset($dbRow[1]) && isset($dbRow[1]->versions) ? $dbRow[1]->versions : '';                
                $objItemsEdit->where('items_id', $row->id)->update(['versions' => $price_version, 'mixed_column2' => $omit_version]);
            $i++;}
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            
        }
        echo 'Stop :: '.date('H:i:s');
        return $i;
    }
    /**
     * 
     * @param type $originalId
     * @param type $duplicateId
     * @param type $params
     */
    function duplicatePriceVersions($originalId, $duplicateId, $params) {
        $data = $arrItems = [];
        \DB::beginTransaction();
        try {
            $objItemPricVers = new ItemsPriceZones();
            $dbSelect = $objItemPricVers->whereIn('items_id', $originalId)->get()->toArray();
            if (!empty($dbSelect)) {
                foreach ($dbSelect as $row) {
                    $arrItems[$row['items_id']][] = $row;
                }
            }
            if (!empty($arrItems)) {
                foreach ($arrItems as $key => $values) {
                    foreach ($values as $k => $v) {
                        if (isset($duplicateId[$key])) {
                            $data[] = array_merge(array('master_items_id' => $v['master_items_id'], 'items_id' => $duplicateId[$key],
                                'events_id' => $v['events_id'], 'is_omit' => $v['is_omit'],
                                'price_zones_id' => $v['price_zones_id']), $this->getCommonData($params));
                        }
                    }
                }

                if (!empty($data)) {
                    $objItemPricVers->insertMultiple($data);
                }
            }
            unset($data, $arrItems);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }

}
