<?php

namespace CodePi\SyncItems\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\Eloquent\ItemsReqVars;

class SaveSyncItems implements iCommands {

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
        $command->users_id = $command->created_by;
        $data['post_var'] = json_encode($command->dataToArray());
        $this->dataSource->dbTransaction();
        $primId = $this->dataSource->saveRecord($data);
        $this->dataSource->dbCommit();
        call_in_background('Items:Resync ' . $primId->id);
        $response = [];
        return $response;
    }

}
