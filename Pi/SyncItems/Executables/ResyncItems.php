<?php

namespace CodePi\SyncItems\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\SyncItems\DataSource\SyncDataSource;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;

class ResyncItems implements iCommands {

    private $dataSource;

    /**
     * 
     * @param SyncDataSource $objSyncDs
     */
    function __construct(SyncDataSource $objSyncDs) {
        $this->dataSource = $objSyncDs;
    }

    /**
     * 
     * @param object $command
     * @return array
     */
    function execute($command) {

        $result = $this->dataSource->reSyncItems($command);
        if ($result['status'] == true) {
            $data['items_id'] = $command->item_id;
            $data['event_id'] = PiLib::piEncrypt($command->event_id);
            $objCommand = new GetItemsList($data);
            $cmdResponse = CommandFactory::getCommand($objCommand);
            $arrResponse = array_merge($result,$cmdResponse['items']);
        }
        $arrResponse['status'] = $result['status'];
        $arrResponse['event_id'] = PiLib::piEncrypt($command->event_id);
        $arrResponse['message'] = $result['message'];
        if ($arrResponse) {

            broadcast(new ItemActions($arrResponse, 'update'))->toOthers();
        }        
        return $arrResponse;
    }

}
