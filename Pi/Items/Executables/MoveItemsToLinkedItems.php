<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\LinkedItemsDataSource as LinkItemDs;
use CodePi\Base\DataTransformers\DataResponse;
#use App\Events\ItemActions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Utils\BroadcastResponse;
#use CodePi\Items\Commands\GetItemsList;
#use CodePi\Base\Commands\CommandFactory;
#use CodePi\Items\DataSource\GroupedDataSource as GroupDs;
#use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;

/**
 * Handle the execution of linked items
 */
class MoveItemsToLinkedItems implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of linked items
     */
    function __construct(LinkItemDs $objLinkItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objLinkItemDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return boolean
     */
   /* function execute($command) {
        //$checkisMovable = $this->dataSource->checkIsMovable($command);
        $params = $command->dataToArray();
        $objGroupDs = new GroupDs();
        $arrItemsId = $objGroupDs->moveGroupedItems($params); 
        
       //if($checkisMovable == true){
       foreach ($arrItemsId as $id) {
           $UpcDetails = $this->dataSource->getUpcDetails($id);               
           $param['searched_item_nbr'] = $UpcDetails[0]->searched_item_nbr;           
           $param['item_file_description'] = $UpcDetails[0]->item_file_description;
           $param['cost'] = $UpcDetails[0]->cost;
           $param['base_unit_retail'] = $UpcDetails[0]->base_unit_retail;
           $param['supplier_nbr'] = $UpcDetails[0]->supplier_nbr;
           $param['events_id'] = $UpcDetails[0]->events_id;
           $param['upc_nbr'] = $UpcDetails[0]->upc_nbr;
           $param['items_import_source'] = $UpcDetails[0]->items_import_source;
           $param['signing_description'] = ($param['items_import_source'] == '1') ? $UpcDetails[0]->signing_description : '';
           $param['date_added'] = $command->date_added;
           $param['gt_date_added'] = $command->gt_date_added;
           $param['created_by'] = $command->created_by;
           $param['last_modified_by'] = $command->last_modified_by;
           $param['last_modified'] = $command->last_modified;
           $param['ip_address'] = $command->ip_address;
           $param['gt_last_modified'] = $command->gt_last_modified;
           $remainingItems = $this->dataSource->getRemainingItems($param,$id);
           $this->dataSource->deleteItemRecord($id);
           $this->dataSource->deleteEditableRecord($id);
           $this->dataSource->deleteNonEditableRecord($id);
           $this->dataSource->deleteItemFromGroup($id);
           $insertLinkedItemsRecord = $this->dataSource->insertLinkedItemsRecord($param);
           $itemsId[] = $insertLinkedItemsRecord;
           if(!empty($remainingItems)){
               foreach($remainingItems as $item){
                     $itemsLeft[] = $item->id;
               }
           }
       }
       $arrResponse['status'] = false;
       if ($insertLinkedItemsRecord) {
           $data['items_id'] = $itemsId;
           $data['event_id'] = PiLib::piEncrypt($command->event_id);
           $objCommand = new GetItemsList($data);
           $cmdResponse = CommandFactory::getCommand($objCommand);
           $arrResponse = $cmdResponse['items'];
           $arrResponse['status'] = true;
           $arrResponse['deleted_items'] = $command->id;
           if(!empty($itemsLeft)){
           $dataNew['items_id'] = $itemsLeft;
           $dataNew['event_id'] = PiLib::piEncrypt($command->event_id);
           $objNewCommand = new GetItemsList($dataNew);
           $cmdNewResponse = CommandFactory::getCommand($objNewCommand);
           $arrResponse['itemValues'] = $cmdNewResponse['items']['itemValues'];
           }
       }
       if ($arrResponse) {

           broadcast(new ItemActions($arrResponse, 'addrow'))->toOthers();
       }
//       }else{
//           $arrResponse['message'] = 'The selected items cannot be moved. Please check and try again.';
//           $arrResponse['status'] = $checkisMovable;
//       }
       return $arrResponse;
    }
    */
     
   function execute($command) {

        $postData = $command->dataToArray();
        $response = $this->dataSource->insertLinkedItemsRecord($postData);          
        $postData['result'] = $response;
        $postData['items_id'] = is_array($response['items_id']) ? $response['items_id'] : [$response['items_id']];
        $objBroadCast = new BroadcastResponse($postData);
        $arrResult = $objBroadCast->getRowData();
        $arrResult['event_id'] = isset($arrResult['event_id']) ? $arrResult['event_id'] : PiLib::piEncrypt($postData['events_id']);
        $objBroadCast->setData($arrResult);
        $objBroadCast->setAction('remove');
        $objBroadCast->updateToBroadcast();
        return $arrResult;
    }

}
