<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Import\DataSource\ImportItemsDataSource;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs; 
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use App\Events\ItemActions;
use App\Events\IqsProgress;
use CodePi\Base\Eloquent\ItemsReqVars;
use CodePi\Import\Utils\FileReadValidations;

/**
 * Handle the execution of import events items
 */
class ImportItems implements iCommands {
    /**
     *
     * @var class, instance of ItemsDataSource
     */
    private $dataSource;
    private $ImportItemsDataSource;

    /**
     * 
     * @param ItemDs $objItemDs
     * @param ImportItemsDataSource $objImportItemsDataSource
     * @param DataResponse $objDataResponse
     */
    function __construct(ItemDs $objItemDs, ImportItemsDataSource $objImportItemsDataSource, DataResponse $objDataResponse) {
        $this->dataSource = $objItemDs;
        $this->ImportItemsDataSource = $objImportItemsDataSource;
    }

    /**
     * Execution of add item through import
     * 
     * @param object $command
     * @return array of imported items
     */
    function execute($command) {
        //$response = $this->addData($command);

        $objItemsReqVars = new ItemsReqVars;
        if (!empty($command->filename)) {
            $storage_path = storage_path('app/public') . '/Uploads/item_imports/'.$command->filename;            
            $objFileReadValidations = new FileReadValidations();
            $objFileReadValidations->setFiles($storage_path);
            $fileResponse = $objFileReadValidations->validate();
           
            if (isset($fileResponse['inputValues']) && !empty($fileResponse['inputValues'])) {
                $command->items = $fileResponse['inputValues'];
                if(file_exists($storage_path)){
                    unlink($storage_path);
                }
            }
            unset($command->filename);            
        }
        
        $command->users_id = $command->created_by;
        $command->new_ip_address = $command->ip_address;
        $data['post_var'] = json_encode($command->dataToArray());
 
        $objItemsReqVars->dbTransaction();
        $res = $objItemsReqVars->saveRecord($data);
        $objItemsReqVars->dbCommit();
        call_in_background('Iqs:import ' . $res->id);
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

    function addData($command){
         $params = $command->dataToArray();
        $response = [];
        try {
            if (!empty($command->filename)) {
                $response = $this->ImportItemsDataSource->importItems($command);
            } else {
                if (isset($params['items']) && !empty($params['items'])) {
                    $response = $this->dataSource->saveItems($command);
                }
            }
           // return ['status' => true];

            if (isset($response['items_id']) && !empty($response['items_id'])) {

                $data['items_id'] = array_filter($response['items_id']);
                $data['event_id'] = PiLib::piEncrypt($command->event_id);
                $objCommand = new GetItemsList($data);
                $cmdResponse = CommandFactory::getCommand($objCommand);
                $response['status'] = true;
                $response = array_merge($cmdResponse['items'], $response);
                //unset($response['items_id']);
            } else {
                $response['event_id'] = $command->event_id;
                $response['status'] = false;
                /**
                 * Need to add  more descriptive error messages
                 */
                $response['message'] = isset($params['items']) && empty($params['items']) ? 'Please enter valid search numbers' : 'Failure to add items. Please try agian..!';
            }
            /*
              $objItems=['users_id'=>1,'events_id'=>$response['event_id'],'progress'=>10,'count'=>20,'total'=>200,'message'=>'5 Completed, 15 Ignored','status'=>false];
              event(new IqsProgress($objItems));

             */
            if ($response) {
                broadcast(new ItemActions($response, 'addrow'))->toOthers();
            }
            return $response;
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

}
