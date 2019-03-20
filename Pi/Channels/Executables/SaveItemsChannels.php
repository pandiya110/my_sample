<?php

namespace CodePi\Channels\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Channels\DataSource\ChannelsDataSource as ChannelsDs;
use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Utils\BroadcastResponse;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
class SaveItemsChannels implements iCommands {

    /**
     *
     * @var class
     */
    private $dataSource;

    /**
     *
     * @var class
     */
    private $objDataResponse;

    /**
     * 
     * @param ChannelsDs $objChannelsDS
     * @param DataResponse $objDataResponse
     */
    function __construct(ChannelsDs $objChannelsDS, DataResponse $objDataResponse) {
        $this->dataSource = $objChannelsDS;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * 
     * @param type $command
     * @return array
     */
//    function execute($command) {
//        $response = $this->dataSource->saveItemsChannelsAdtypes($command);        
//        if ($response['status'] == true) {
//
//            $data['items_id'] = isset($response['items_id']) && !empty($response['items_id']) ? array($response['items_id']) : array($command->items_id);
//            $data['event_id'] = PiLib::piEncrypt($command->events_id);
//            $data['parent_item_id'] = $command->parent_item_id;
//            $objCommand = new GetItemsList($data);
//            $cmdResponse = CommandFactory::getCommand($objCommand);
//            dd($cmdResponse);
//            $arrResponse = $cmdResponse['items'];
//            dd($arrResponse);
//        }
//        $arrResponse['status'] = $response['status'];
//	$arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//        if ($arrResponse) {
//
//            broadcast(new ItemActions($arrResponse, 'update'))->toOthers();
//        }
//
//        return $arrResponse;
//    }
    
    function execute($command) {

        $response = $this->dataSource->saveItemsChannelsAdtypes($command);        
        $postData = $command->dataToArray();
        $postData['result'] = $response;
        $postData['items_id'] = isset($response['items_id']) && !empty($response['items_id']) ? array($response['items_id']) : array($postData['items_id']);
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();        
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        if (!empty($postData['parent_item_id'])) {
            $objGroupDs = new GroupDs();
            $arrResult['itemCount'] = array('item' => $objGroupDs->getGroupedItemsCount($postData['parent_item_id']));
        }

        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('update');
        $objBroadCast->updateToBroadcast();

        return $arrResult;
    }

}
