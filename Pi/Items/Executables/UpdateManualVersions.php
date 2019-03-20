<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
#use CodePi\Base\DataTransformers\DataResponse;
use \CodePi\Base\Libraries\PiLib;
#use CodePi\Base\Commands\CommandFactory;
#use CodePi\Items\Commands\GetItemsList;
#use \App\Events\ItemActions;
use CodePi\Items\DataSource\PriceZonesDataSource;
#use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
#use CodePi\Items\Utils\ItemsGridDataResponse;
#use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
#use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
use CodePi\Items\Utils\BroadcastResponse;

class UpdateManualVersions implements iCommands {

    private $dataSource;
    #private $objCopyDs;
    #Private $objGridResponse;
    #private $objItemDs;

    function __construct(PriceZonesDataSource $objPrcZoneDs/*, ItemDs $objItemDs, CopyDs $objCopyDs, ItemsGridDataResponse $objGridResponse*/) {
        $this->dataSource = $objPrcZoneDs;
        #$this->objCopyDs = $objCopyDs;
        #$this->objGridResponse = $objGridResponse;
        #$this->objItemDs = $objItemDs;
    }

//    function execute($command) {
//        $arrResponse = [];
//        $params = $command->dataToArray();
//        $result = $this->dataSource->saveManualVersions($params);
//        
//        if ($result['status'] == true) {
//
//            $params = array('items_id' => $result['items_id'], 'event_id' => $command->events_id);
//            $itemsList = $this->objCopyDs->getItemListById($params);
//            $users_id = (isset($command->users_id) && $command->users_id != 0) ? $command->users_id : $command->last_modified_by;
//            $permissions = $this->objItemDs->getAccessPermissions($users_id);
//            $arrResponse = $this->objGridResponse->getGridResponse(array('objResult' => $itemsList, 'permissions' => $permissions), array(), $command->events_id);
//            /**
//             * Event Info
//             */
//            $objGroupDs = new GroupDs();
//            $arrEventInfo = $objGroupDs->getEventsInformationsById($command->events_id);
//            $arrResponse['event_id'] = isset($arrEventInfo['event_id']) ? $arrEventInfo['event_id'] : '';
//            $arrResponse['event_status'] = isset($arrEventInfo['event_status']) ? $arrEventInfo['event_status'] : '';
//        }
//
//        $arrResponse['status'] = $result['status'];
//        $arrResponse['users_id'] = $command->created_by;
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//
//        if ($arrResponse) {
//            $checkPort = PiLib::isPortOpen(config('smartforms.socket_host'), config('smartforms.socket_id'));
//            if ($checkPort) {
//                broadcast(new ItemActions($arrResponse, 'update'))->toOthers();
//            }
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {
        
        $params = $command->dataToArray();
        $result = $this->dataSource->saveManualVersions($params);
        $params['result'] = $result;
        $params['items_id'] = isset($result['items_id']) ? $result['items_id'] : [];
        $objBroadCast = new BroadcastResponse($params);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($params['events_id']);        
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('update');
        $objBroadCast->updateToBroadcast();
        return $arrResult;
    }

}
