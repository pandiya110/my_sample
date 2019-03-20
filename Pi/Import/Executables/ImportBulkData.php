<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use App\Events\ItemActions;
use CodePi\Base\Eloquent\ItemsReqVars;
use CodePi\Import\DataSource\BulkImportItemsDs as BulkImportDs;

class ImportBulkData implements iCommands {

    private $dataSource;

    function __construct(BulkImportDs $objBulkImportDs) {
        $this->dataSource = $objBulkImportDs;
    }

    function execute($command) {
        
        $objItemsReqVars = new ItemsReqVars;
        $objItemsReqVars->dbTransaction();
        try {                       
            $command->users_id = $command->created_by;
            $command->new_ip_address = $command->ip_address;
            $data['post_var'] = json_encode($command->dataToArray());            
            $res = $objItemsReqVars->saveRecord($data);
            $objItemsReqVars->dbCommit();
            
            call_in_background('Items:bulkImport ' . $res->id);
        } catch (\Exception $ex) {
            $objItemsReqVars->dbRollback();
        }
        return true;
        
    }

}
