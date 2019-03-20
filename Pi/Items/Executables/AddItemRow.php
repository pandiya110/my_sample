<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemsDs;
use CodePi\Base\DataTransformers\DataResponse;

use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\Utils\BroadcastResponse;


/**
 * Handle the execution of add new row in item grid
 */
class AddItemRow implements iCommands { 

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Items
     */
    function __construct(ItemsDs $objItemsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objItemsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return type int
     */
//    function execute($command) {
//
//        $result = $this->dataSource->addItemRow($command);
//        /**
//         * Get the updated items row
//         * Command Name : GetItemsList
//         */
//        $arrResponse['status'] = false;
//
//        if ($result['status'] == true) {
//            $data['items_id'] = [$result['items_id']];
//            $data['event_id'] = PiLib::piEncrypt($command->events_id);
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            $arrResponse = $cmdResponse['items'];
//            $arrResponse['status'] = true;
//            $arrResponse['deleted_items'] = [];
//        }
//        if ($arrResponse) {
//
//            broadcast(new ItemActions($arrResponse, 'addItemRow'))->toOthers();
//        }
//        return $arrResponse;
//    }

    function execute($command) {

        $result = $this->dataSource->addItemRow($command);
        $postData = $command->dataToArray();
        $postData['result'] = $result;
        $postData['items_id'] = [$result['items_id']];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('addItemRow');
        $objBroadCast->updateToBroadcast();

        return $arrResult;
    }

}
 
