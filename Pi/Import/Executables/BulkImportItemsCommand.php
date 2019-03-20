<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use App\Events\ItemActions;
use CodePi\Base\Eloquent\ItemsReqVars;
use CodePi\Import\DataSource\BulkImportItemsDs as BulkImportDs;
use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
use CodePi\Items\Utils\ItemsGridDataResponse;

class BulkImportItemsCommand implements iCommands {

    private $dataSource;

    function __construct(BulkImportDs $objBulkImportDs) {
        $this->dataSource = $objBulkImportDs;
    }

    function execute($command) {


        $arrResponse = array();
       return $response = $this->dataSource->importBulkData($command);
        
//        $objCopyDs = new CopyDs();
//        $dataParent['items_id'] = $response['items_id'];
//        $dataParent['event_id'] = $command->events_id;
//        $returnResult['objResult'] = $objCopyDs->getItemListById($dataParent);
//        
//        $objGridResponse = new ItemsGridDataResponse();
//        $arrResponse = $objGridResponse->getGridResponse($returnResult, $command);
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//        
//        if ($arrResponse) {
//            broadcast(new ItemActions($arrResponse, 'import'))->toOthers();
//        }
//        return $arrResponse;
        
        
//        $data['items_id'] = $response['items_id'];
//        $data['event_id'] = PiLib::piEncrypt($command->events_id);
//        $objCommand = new GetItemsList($data);
//        $cmdResponse = CommandFactory::getCommand($objCommand);
//        $arrResponse = array_merge($response, $cmdResponse['items']);        
//        $arrResponse['status'] = true;
//        if($arrResponse){
//            broadcast(new ItemActions($arrResponse, 'import'))->toOthers();
//        }
//        return $arrResponse;
    }

}
