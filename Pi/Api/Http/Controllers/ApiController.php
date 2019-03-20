<?php

namespace CodePi\Api\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Redirector;
use Response;
use Session;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Api\Commands\GetMasterItems;
use CodePi\Api\ApiResult\ApiFactory;
use CodePi\Api\SyncResult\SyncFactory;
use CodePi\Api\Commands\GetAutoSearchVal;
use CodePi\Api\Commands\EmiApi;

class ApiController extends PiController {

    /**
     * Get Master Items Data
     * 
     * @param Request $request
     * @return Response
     */
    public function getMasterItems(Request $request){
        $data = $request->all();
        $keyType = isset($data['type']) ? $data['type'] : '';        
        switch ($keyType) {
            case 1 :                
                $data['search_key'] = 'searched_item_nbr';
                break;
            case 2 :                
                 $data['search_key'] = 'upc_nbr';
                break;
            case 3 :                
                 $data['search_key'] = 'fineline_number';
                break;
            case 4 :
                 $data['search_key'] = 'plu_nbr';
                break;
             case 5 :                
                $data['search_key'] = 'itemsid';
            break;
            default:                
                 $data['search_key'] = 'searched_item_nbr';
        }

        $command = new GetMasterItems($data);
        return $this->run($command, trans('Api::messages.S_MasterItem'), trans('Api::messages.E_MasterItem'));
    }
            
    /**
     * 
     * @param Request $request
     * @return Response
     */
    public function getApiResult(Request $request) {
        
        $type = $request->get('type');
        $key_value = $request->get('value');
        
        $objApiClass = ApiFactory::getApiName($type, $key_value);
        $apiResult = $objApiClass->getResult();
        
        $objSyncClass = SyncFactory::getSyncApiName($type, $apiResult, $key_value);
        $syncData = $objSyncClass->formatResult($apiResult);
        
        return $objSyncClass->syncResult($syncData);
    }
    /**
     * Get Auto search Value
     * @param Request $request
     * @return Response
     */
    public function getAutoSearchVal(Request $request) {
        $data = $request->all();
        $keyType = isset($data['type']) ? $data['type'] : '';
        
        switch ($keyType) {
            case 1 :
                $data['search_key'] = 'searched_item_nbr';
                break;
            case 2 :
                $data['search_key'] = 'upc_nbr';
                break;
            case 3 :
                $data['search_key'] = 'fineline_number';
                break;
            case 4 :
                $data['search_key'] = 'plu_nbr';
                break;
            case 5 :
                $data['search_key'] = 'itemsid';
                break;
            default:
                $data['search_key'] = 'searched_item_nbr';
        }

        $command = new GetAutoSearchVal($data);
        return $this->run($command, trans('Api::messages.S_AutoSearch'), trans('Api::messages.E_AutoSearch'));
    }
    /**
     * 
     * @param Request $request
     * @return Response
     */
    public function EmiApi(Request $request) {
        $data = $request->all();
        $command = new EmiApi($data);
        return $this->run($command, trans('Api::messages.S_AutoSearch'), trans('Api::messages.E_AutoSearch'));
    }

}
