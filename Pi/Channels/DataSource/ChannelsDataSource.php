<?php

namespace CodePi\Channels\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Channels;
use CodePi\Base\Eloquent\ChannelsAdTypes;
use CodePi\Base\Libraries\Upload\UploadType;
use URL;
use CodePi\Base\Eloquent\ChannelsEvents;
use CodePi\Base\Eloquent\ChannelsItems;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
#use App\Events\ItemsActivityLogs;
use CodePi\Base\Eloquent\RolesItemsHeaders;
use Auth;
use CodePi\Attachments\Commands\AddAttachment;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Eloquent\Resolutions;
use CodePi\Attachments\DataSource\AttachmentsDSource;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;

class ChannelsDataSource {
    /**
     * Add/Update channels
     * @param object $command
     * @return object
     */
    function saveChannels($command) {
        $params = $command->dataToArray();
        $objChannels = new Channels();
        $saveDetails = [];
        $objChannels->dbTransaction();
        
        try {
            $params['attachments_id'] = $this->addChannelLogoToAttachments($params);
            $saveDetails = $objChannels->saveRecord($params);
            $objChannels->dbCommit();
        } catch (\Exception $ex) {
            $objChannels->dbRollback();
            $exMsg = 'SaveDepartment->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        return $saveDetails;
    }

    /**
     * Add/Update/Delete Channels Ad Types
     * @param type $command
     * @return boolean
     */
    function saveChannelAdTypes($command) {
        $params = $command->dataToArray();
        $arrCreatedInfo = $command->getCreatedInfo();
        $arrAdTypesID = $arrNew = $arrAdTypes = $arrExisting = $arrUpdate = [];
        $objChannelAdTypes = new ChannelsAdTypes();
        $objChannelAdTypes->dbTransaction();
        try {
            $channelID = isset($params['channels_id']) ? $params['channels_id'] : 0;

            foreach ($params['ad_types'] as $row) {
                if ($row['id'] != '') {
                    $arrAdTypes[$row['id']] = ['name' => $row['name'], 'status' => ($row['status'] == true) ? '1' : '0'];
                }
            }
            $arrAdTypesID = array_keys($arrAdTypes);

            $objResult = $objChannelAdTypes->where('channels_id', $channelID)->select('id')->get();
            foreach ($objResult as $objRow) {
                $arrExisting[] = $objRow->id;
            }

            /**
             * Insert new Ad Types
             */
            foreach ($params['ad_types'] as $insert) {
                if ($insert['id'] == '' && $insert['name'] != '') {
                    $arrNew = array_merge(['channels_id' => $channelID, 'name' => $insert['name'], 'status' => $insert['status']], $arrCreatedInfo);
                    $objChannelAdTypes->saveRecord($arrNew);
                }
            }
            /**
             * Update Exists Ad Types
             */
            $arrUpdate = array_intersect($arrAdTypesID, $arrExisting);
            if (!empty($arrUpdate)) {
                foreach ($arrUpdate as $value) {
                    unset($arrCreatedInfo['created_by'], $arrCreatedInfo['date_added'], $arrCreatedInfo['gt_date_added']);
                    $objChannelAdTypes->where('channels_id', $channelID)->where('id', $value)->update(array_merge($arrAdTypes[$value], $arrCreatedInfo));
                }
            }
            $objChannelAdTypes->dbCommit();
            unset($arrAdTypesID, $arrAdTypes, $arrExisting, $arrNew, $arrUpdate);
        } catch (\Exception $ex) {
            $objChannelAdTypes->dbRollback();
            $exMsg = 'SaveDepartment->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        return true;
    }

    /**
     * Get Channles list
     * @param object $command
     * @return collection
     */
    function getChannelsList($params) {
        
        $totalCount = 0;
        $objChannels = new Channels();
        $objChannels = $objChannels->dbTable('c')
                                   ->leftJoin('channels_ad_types as cat', 'cat.channels_id', '=', 'c.id')
                                   ->leftJoin('attachments as a', 'a.id', '=', 'c.attachments_id')
                                   ->leftJoin('resolutions as r', 'r.id', '=', 'a.resolutions_id')
                                   ->select('c.id as channel_id', 'c.name as channel_name', 'cat.id as adtypes_id', 
                                            'cat.name as adtypes_name', 'c.description', 'c.status', 'c.channel_logo', 
                                            'cat.status as adtype_status', 'a.db_name', 'r.local_url')
                                   ->where(function($query)use($params) {
                                    if (isset($params['id']) && !empty($params['id'])) {
                                    $query->where('c.id', $params['id']);
                                    }
                                    })->where(function($query)use($params) {
                                    if (isset($params['search']) && trim($params['search']) != '') {
                                        $query->whereRaw("c.name like '%" . str_replace(" ", "", $params['search']) . "%' ");
                                    }
                                    })->where(function($query)use($params) {
                                    if (isset($params['status']) && trim($params['status']) != '') {
                                        $query->where('c.status', $params['status']);
                                    }
                                    });
                                    if (isset($params['sort']) && !empty($params['sort'])) {
                                        $objChannels->orderBy('c.name', $params['sort']);
                                    } else {
                                        $objChannels->orderBy('c.last_modified', 'desc');
                                    }
                                    if (isset($params['page']) && !empty($params['page'])) {
                                        $objChannels = $objChannels->paginate($params['perPage']);
                                        $totalCount = $objChannels->total();
                                    } else {
                                        $objChannels = $objChannels->get();
                                    }
                                    
                                    $objChannels->totalCount = $totalCount;
        return$objChannels;
    }
    
    /**
     * Get Channels Details view 
     * @param object $command
     * @return collection
     */
    function getChannelsDetails($command) {

        $params = $command->dataToArray();
        $objChannels = new Channels();
        $objChannels = $objChannels->dbTable('c')
                                   ->leftJoin('channels_ad_types as cat', 'cat.channels_id', '=', 'c.id')
                                   ->leftJoin('attachments as a', 'a.id', '=', 'c.attachments_id')
                                   ->leftJoin('resolutions as r', 'r.id', '=', 'a.resolutions_id')
                                   ->select('c.id as channel_id', 'c.name as channel_name', 'cat.id as adtypes_id', 
                                            'cat.name as adtypes_name', 'c.description', 'c.status', 'c.channel_logo', 
                                            'cat.status as adtype_status', 'c.attachments_id', 'a.db_name', 'r.local_url')
                                   ->where('c.id', $params['id'])->get();
        return$objChannels;
    }
    /**
     * 
     * @param collection $collection
     * @return array
     */
    function formatChannelsData($collection) {
        $arrChannlID = $arrResponse = $arrAdTypes = array();
        if (!empty($collection)) {
            foreach ($collection as $value) {
                $arrChannlID[$value->channel_id]        = $value->channel_id;
                $arrResponse[$value->channel_id]['id']  = isset($value->channel_id) ? $value->channel_id : 0;
                $arrResponse[$value->channel_id]['name'] = isset($value->channel_name) ? $value->channel_name : null;
                $arrResponse[$value->channel_id]['description'] = isset($value->description) ? $value->description : null;
                $arrResponse[$value->channel_id]['status'] = isset($value->status)  && ($value->status == '1' ) ? true : false;
                $arrResponse[$value->channel_id]['channel_logo'] = $this->setChannelLogo($value);
                if(isset($value->adtypes_id) && $value->adtypes_id != ''){
                    $arrAdTypes[$value->channel_id]['ad_types'][] = ['id' => isset($value->adtypes_id) ? $value->adtypes_id : 0, 
                                                                   'name' => isset($value->adtypes_name) ? $value->adtypes_name : null, 
                                                                   'status' => isset($value->adtype_status)&& ($value->adtype_status == '1' ) ? true : false
                                                                   ];
                }
            }
            foreach ($arrChannlID as $key){
                $arrResponse[$key]['ad_types'] =isset($arrAdTypes[$key]) && isset($arrAdTypes[$key]['ad_types']) ? ($arrAdTypes[$key]['ad_types']) : array();
            } 
            unset($arrChannlID, $arrAdTypes);
        }
        
        return array('channels' => array_values($arrResponse));
    }

    /**
     * Upload Channels logo
     * @return array
     */
    function uploadChannelLogo() {

        try {
            if (isset($_FILES['file']['tmp_name'])) {
                $upload = UploadType::Factory('Regular');
                $files = $_FILES['file'];
            } else {
                if (!empty($_SERVER['HTTP_X_FILE_NAME'])) {
                    $files = $_SERVER['HTTP_X_FILE_NAME'];
                } else {
                    $files = $_REQUEST['file'];
                }
                $upload = UploadType::Factory('Stream');
            }
            $upload->setFiles($files);
            $upload->setSize(5 * 1024 * 1024);
            $upload->setAllowedTypes(array('jpeg', 'jpg', 'PNG', 'png', 'GIF', 'gif', 'svg', 'SVG'));
            $upload->setContainer(storage_path('app/public') . '/Uploads/channel_logo/');
            $tmpfile = $upload->save();
            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
                $tmpfile['url'] = '/storage/app/public/Uploads/channel_logo/' . $tmpfile['image_name'];
            }
            return $tmpfile;
        } catch (\Exception $ex) {
            $exMsg = 'UploadLogo->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
    }

    /**
     * 
     * @param object $command
     * @return object
     */
    function saveEventsChannels($eventID = 0, $arrCreatedInfo) {
        $objEvtChannels = new ChannelsEvents();
        $saveDetails = [];
        $objEvtChannels->dbTransaction();
        try {
            $arrData = $this->prepareEventsChannelData($eventID, $arrCreatedInfo);
            $saveDetails = $objEvtChannels->insertMultiple($arrData);
            $objEvtChannels->dbCommit();
        } catch (\Exception $ex) {
            $objEvtChannels->dbRollback();
        }
        return $saveDetails;
    }

    /**
     * 
     * @param int $eventID
     * @param array $arrCreatedInfo
     * @return array
     */
    function prepareEventsChannelData($eventID, array $arrCreatedInfo) {
        $params['status'] = '1';
        $channels = $this->getChannelsList($params);
        $arrData = $arrChannelData = [];
        if (!empty($channels)) {
            foreach ($channels as $row) {
                $arrChannelData[$row->channel_id] = ['channels_id' => $row->channel_id, 'events_id' => $eventID];
            }
            if ($arrChannelData) {
                foreach ($arrChannelData as $value) {
                    $arrData[] = array_merge($value, $arrCreatedInfo);
                }
            }
        }
        return $arrData;
    }

    /**
     * Get Channels name as headers in Items grid
     * @param int $eventID
     * @return array
     */
    function getEventsChannels($eventID = 0) {
 
        $objEvents = new Events();        
        $dbResult = $objEvents->dbTable('e')
                              ->join('channels_events as ce', 'ce.events_id', '=', 'e.id')
                              ->join('channels as c', 'c.id', '=', 'ce.channels_id')
                              ->leftJoin('attachments as a', 'a.id', '=', 'c.attachments_id')
                              ->leftJoin('resolutions as r', 'r.id', '=', 'a.resolutions_id')
                              ->select('c.id as channel_id', 'c.name as channel_name', 'channel_logo', 'c.status', 
                                       'c.attachments_id', 'a.db_name')                              
                              ->where(function($query)use($eventID) {
                                    if (!empty($eventID)) {
                                        $query->where('e.id', $eventID);
                                    }
                              })->where('c.status', '1')
                                ->orderBy('c.name', 'asc')
                                ->get();                              
         return $this->prepareChannelsHeaders($dbResult);      

    }
    /**
     * This function will handle the assign channels as a Items headers based events 
     * @param type $data
     * @return type
     */
    function prepareChannelsHeaders($data) {
        $arrResponse = [];
        if (!empty($data)) {
            $objEvtChannels = new ChannelsEvents();
            $headers = $this->getChannelsColorCode();
            foreach ($data as $value) {
                $arrResponse[$value->channel_id]['column'] = $value->channel_name;
                $arrResponse[$value->channel_id]['channel_id'] = $value->channel_id;
                $arrResponse[$value->channel_id]['name'] = $value->channel_name;
                $arrResponse[$value->channel_id]['IsEdit'] = ($value->status == '1') ? true : false;
                $arrResponse[$value->channel_id]['type'] = 'channel';
                $arrResponse[$value->channel_id]['width'] = 200;
                $arrResponse[$value->channel_id]['IsMandatory'] = false;
                $arrResponse[$value->channel_id]['format'] = false;
                $arrResponse[$value->channel_id]['IsCopy'] = false;
                $arrResponse[$value->channel_id]['columnCount'] = false;
                $arrResponse[$value->channel_id]['color_code'] = isset($headers['color_code']) ? $headers['color_code'] : '#dadada';
                $arrResponse[$value->channel_id]['order_no'] = isset($headers['order_no']) ? $headers['order_no'] : 0;
                $arrResponse[$value->channel_id]['channel_logo'] = $this->setChannelLogo($value);
            }
        }
        return array_values($arrResponse);
    }

    /**
     * Get particular channels list of adtypes 
     * @param int $channel_id
     * @return array
     */
    function getChannelsAdtypes($params) {
        
        $channel_id = isset($params['channels_id']) ? $params['channels_id'] : 0;
        $arrAdTypes = [];
        $arrAdTypes['ad_types']['assigned'] = [];
        $arrAdTypes['ad_types']['all'] = [];
        
        /**
         * Get already selected Adtypes against Items
         */
        $objItemsAdtype = new ChannelsItems();
        $objResult = $objItemsAdtype->where('items_id', $params['items_id'])
                                    ->where('channels_id', $channel_id)
                                    ->get(['channels_adtypes_id'])
                                    ->toArray();
        if (!empty($objResult)) {
            foreach ($objResult as $row) {
                $arrAdTypes['ad_types']['assigned'][] = (int)$row['channels_adtypes_id'];
            }
        }
        /**
         * List of Adtypes
         */
        $objAdtypes = new ChannelsAdTypes();
        $dbResult = $objAdtypes->where('channels_id', $channel_id)
                               ->orderByRaw('cast(name as unsigned), cast(name as char)', 'asc')
                               ->get();
        if (!empty($dbResult)) {
            foreach ($dbResult as $rowValue) {
                $arrAdTypes['ad_types']['all'][] = ['id' => $rowValue->id, 'name' => $rowValue->name, 'status' => ($rowValue->status == '1') ? true : false];
            }
        }
        
        return $arrAdTypes;
    }

    /**
     * Get Item wise channels ad types by given $eventID
     * @param int $eventID
     * @return array
     */
    function getItemsChannelsAdtypes($eventID = 0) {
        
        $objEvtChannels = new ChannelsEvents();
        $dbResult = $objEvtChannels->dbTable('ce')
                        ->join('channels_items as ci', 'ci.channels_id', '=', 'ce.channels_id')
                        ->join('channels_ad_types as cat', 'cat.id', '=', 'ci.channels_adtypes_id')
                        ->join('channels as c', 'c.id', '=', 'ce.channels_id')
                        ->select('ci.items_id', 'c.id', 'c.name')
                        ->selectRaw(' group_concat(distinct cat.name order by cast(cat.name as unsigned), cast(cat.name as char) asc) as ad_types')
                        ->where(function($query)use($eventID) {
                            if (!empty($eventID)) {
                                $query->where('ce.events_id', $eventID);
                            }
                        })->groupBy('ci.items_id', 'ci.channels_id')->get();
                
        $arrData = [];
        if (!empty($dbResult)) {
            foreach ($dbResult as $row) {
                $arrData[$row->items_id][$row->name] = $row->ad_types;
            }
        }

        return $arrData;
    }

    /**
     * 
     * @param type $command
     * @return type
     */
    function saveItemsChannelsAdtypes($command) {

        $params = $command->dataToArray();
        $objChannelsItm = new ChannelsItems();
        $arrCreatedInfo = $command->getCreatedInfo();
        $arrAdtypesID = $arrAdTypes = $arrNew = $arrSaveData = $arrExisting = [];
        $objItems = new Items();
        $objChannelsItm->dbTransaction();
        try {
            foreach ($params['channels_adtypes_id'] as $row) {
                if ($row != '') {
                    $arrAdTypes[$row] = ['items_id' => $params['items_id'], 'channels_id' => $params['channels_id'], 'channels_adtypes_id' => $row];
                }
            }
            $arrAdTypesID = array_keys($arrAdTypes);
            /**
             * Delete if not exists
             */
            $objChannelsItm->where('channels_id', $params['channels_id'])->where('items_id', $params['items_id'])
                           ->where(function($query) use($arrAdTypesID) {
                            if (!empty($arrAdTypesID)) {
                                $query->whereNotIn('channels_adtypes_id', $arrAdTypesID);
                            }
                           })->delete();
            $objResult = $objChannelsItm->where('items_id', $params['items_id'])
                                        ->where('channels_id', $params['channels_id'])
                                        ->select('channels_adtypes_id')
                                        ->get();
            foreach ($objResult as $objRow) {
                $arrExisting[] = $objRow->channels_adtypes_id;
            }

            $arrNew = array_diff($arrAdTypesID, $arrExisting);
            $arrUpdate = array_intersect($arrAdTypesID, $arrExisting);

            /**
             * Insert new Ad Types
             */
            if (!empty($arrNew)) {
                foreach ($arrNew as $intAdtypesId) {
                    $arrSaveData[] = array_merge($arrAdTypes[$intAdtypesId], $arrCreatedInfo);
                }

                $objChannelsItm->insertMultiple($arrSaveData);
            }
            /**
             * Update Exists Ad Types
             */
            if (!empty($arrUpdate)) {
                foreach ($arrUpdate as $value) {
                    unset($arrCreatedInfo['created_by'], $arrCreatedInfo['date_added'], $arrCreatedInfo['gt_date_added']);
                    $prim_id = $objChannelsItm->where('channels_id', $params['channels_id'])
                                              ->where('items_id', $params['items_id'])
                                              ->where('channels_adtypes_id', $value)
                                              ->first();
                    $objChannelsItm->where('id', $prim_id->id)
                                   ->update(array_merge($arrAdTypes[$value], $arrCreatedInfo));
                }
            }
            unset($arrAdTypesID, $arrAdTypes, $arrExisting, $arrNew, $arrUpdate);
            $status = true;
            /**
             * Update in Items table, to tarck the recent changes
             */
            if ($status == true) {
                unset($arrCreatedInfo['created_by'], $arrCreatedInfo['date_added'], $arrCreatedInfo['gt_date_added']);                
                $objItems->saveRecord(array_merge(['id' => $params['items_id']], $arrCreatedInfo));
            }
            /**
             * Update activity logs             
            $eventID = $objItems->findRecord($params['items_id']);            
            $logsData = array_merge($arrCreatedInfo, ['events_id' => isset($eventID->events_id) ? $eventID->events_id : 0, 'actions' => 'update', 'tracking_id' => mt_rand() . time(), 
                                                      'users_id' => $arrCreatedInfo['last_modified_by'], 'descriptions' => count([$params['items_id']]).' Items Updated']);
            event(new ItemsActivityLogs($logsData));
             */
            $objChannelsItm->dbCommit();
        } catch (\Exception $ex) {
            $status = false;
            $objChannelsItm->dbRollback();
            $exMsg = 'SaveDepartment->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);                        
        }

        return ['items_id' => $params['items_id'], 'status' => $status];
    }
    /**
     * Copy Items Channels Adtypes
     * @param array $copyData
     * @param int $intEventId
     * @return boolean
     */
    function copyItemsChannelsAdtypes($copyData, $intEventId = 0) {

        $objItems = new Items();
        $objItemsAdtype = new ChannelsItems();
        $objItemsAdtype->dbTransaction();
        try {
            if (!empty($copyData)) {
                foreach ($copyData as $rowValue) {
                    $itemIds[] = $rowValue['id'];
                }
                $arrData = $objItemsAdtype->whereIn('items_id', $itemIds)->get()->toArray();
                $arrSaveData = [];
                if (!empty($arrData)) {
                    foreach ($arrData as $value) {
                        $prim_id = $objItems->where('copy_items_id', $value['items_id'])
                                            ->where('events_id', $intEventId)
                                            ->first();
                        $arrSaveData[] = ['items_id' => $prim_id->id, 'channels_id' => $value['channels_id'], 'channels_adtypes_id' => $value['channels_adtypes_id']];
                    }
                    $objItemsAdtype->insertMultiple($arrSaveData);
                }
            }
            $objItemsAdtype->dbCommit();
        } catch (\Exception $ex) {
            $objItemsAdtype->dbRollback();
            $exMsg = 'SaveDepartment->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        return true;
    }

    /**
     * if any new channels added, it has to map all the events
     * @param type $channels_id
     * @param type $arrCreatedInfo
     */
    function mapChannelsToAllEvents($channels_id, $arrCreatedInfo) {
        $objChannelsEvents = new ChannelsEvents();
        $objChannelsEvents->dbTransaction();
        try {
            if (!empty($channels_id)) {
                $saveData = [];
                $objEvents = new Events();
                $dbResult = $objEvents->get();
                foreach ($dbResult as $row) {
                    $saveData[] = array_merge(['channels_id' => $channels_id, 'events_id' => $row->id], $arrCreatedInfo);
                }
                if (!empty($saveData)) {
                    $objChannelsEvents->insertMultiple($saveData);
                }
            }
            $objChannelsEvents->dbCommit();
        } catch (\Exception $ex) {
            $objChannelsEvents->dbRollback();
        }
    }
    /**
     * Get the Channels Color code from role head
     * @return string
     */
    function getChannelsColorCode(){
        $roles_id = 0;
        if (\Auth::check()) {
            $roles_id = \Auth::user()->roles_id;
        }else{
            $roles_id = config('smartforms.adminRoleId');
        }
        $data = [];
        $objRoleHeader  = new RolesItemsHeaders();
        $objRoleHeader = $objRoleHeader->dbTable('rih')
                                       ->leftJoin('master_data_options as mdo', 'mdo.id', '=', 'rih.masters_color_id')
                                       ->select('mdo.name as color_code', 'headers_order_no as order_no')
                                       ->where('roles_id', $roles_id)
                                       ->where('items_headers_id', '999')
                                       ->get();
        foreach ($objRoleHeader as $obj){
            $data = ['color_code' => $obj->color_code, 'order_no' => $obj->order_no];
        }
        return $data;
    }
    /**
     * Delete the adtypes items wise
     * @param type $arrItemsId
     */
    function deleteChannelsItemsAdTypes($arrItemsId){
        $objChannelsAdType = new ChannelsItems();
        $objChannelsAdType->dbTransaction();
        try{
            if(is_array($arrItemsId) && !empty($arrItemsId)){
                $objChannelsAdType->whereIn('items_id', $arrItemsId)->delete();
            }
            $objChannelsAdType->dbCommit();
        } catch (Exception $ex) {
            $objChannelsAdType->dbRollback();
            $exMsg = 'SaveDepartment->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            
        }
    }
    
    /**
     * Add Uploaded channel logo to attachments table info
     * @param array $params
     * @return int
     */
    function addChannelLogoToAttachments($params) {
        $attachment_id = 0;
        if (isset($params['channel_logo']) && !empty($params['channel_logo'])) {
            $file_info = pathinfo($params['channel_logo']);
            $file = $file_info['basename'];
            if (!empty($file)) {
                $attachment = [];
                $attachment['status'] = 'local'; 
                $attachment['resolution_name'] = 'channel_logo';
                $attachment['db_name'] = $file;
                $attachment['original_name'] = $file;
                $attachment['local_to_cloud'] = 'false';
                $attachment['local_img_process'] = 'false';

                $objAttachment = new AddAttachment($attachment);
                $attachInfo = CommandFactory::getCommand($objAttachment);
                $attachment_id = $attachInfo->id;
            }
        }

        return $attachment_id;
    }
    
    /**
     * This function will handle the channel logo, if logo file not exists in storage path, it will return default logo
     * Uploaded image will check having attachment id or not, if yes , path will refer from resoultions table based on attachment id     
     * @param object $value
     * @return url
     */    
    function setChannelLogo($value) {

        $file = null;
        $channel_logo = URL::to(config('smartforms.channel_default_logo'));

        if (!empty($value->attachments_id)) {
            $file = $value->db_name;
        } else if (!empty($value->channel_logo)) {
            $file = $value->channel_logo;
        }

        if (!empty($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if (!empty($ext)) {
                $resolution = Resolutions::DeatailByName('channel_logo');
                $filename = pathinfo($file, PATHINFO_BASENAME);
                $storage_path = storage_path('app/public') . $resolution->local_url . "/" . $filename;
                if (file_exists($storage_path)) {
                    $channel_logo = url('storage/app/public') . $resolution->local_url . "/" . $filename;
                }
            }
        }

        return $channel_logo;
    }

}
