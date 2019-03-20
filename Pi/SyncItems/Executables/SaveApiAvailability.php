<?php

namespace CodePi\SyncItems\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\Eloquent\ItemsReqVars;
use DB;
use CodePi\Base\Commands\CommandFactory;
use CodePi\SyncItems\Commands\CheckAvailability;
use CodePi\Base\Libraries\PiLib;

class SaveApiAvailability implements iCommands {

    private $dataSource;

    function __construct(ItemsReqVars $objReqVar) {
        $this->dataSource = $objReqVar;
    }

    /**
     * 
     * @param object $command
     * @return array
     */
    function execute($command) {
        $response = [];
        $this->dataSource->dbTransaction();
        try {
            $command->users_id = $command->created_by;
            $data['post_var'] = json_encode($command->dataToArray());
            $primId = $this->dataSource->saveRecord($data);
            call_in_background('Iqs:Update ' . $primId->id);
            $this->dataSource->dbCommit();
        } catch (\Exception $ex) {
            $this->dataSource->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        /**
         * Check socket is running or not
         */
        $url = config('smartforms.socket_host');
        $port = config('smartforms.socket_id');
        $split = parse_url($url);
        $ip = preg_replace('/^www\./', '', $split['host']);        
        $isPortOpen = PiLib::isPortOpen($ip, $port);                
        $response['broadcastStatus'] = $isPortOpen;        
        return $response;
    }

}
