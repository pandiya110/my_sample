<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataSource\UsersData AS UsersDataDs;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;
use CodePi\Users\Cache\UsersCache;

/**
 * Handle the execution of List Users
 */
class UsersData implements iCommands {

    private $dataSource;
    private $objDataResponse;
    /**
     * @ignore It will create an object of UsersDataDs
     */
    public function __construct(UsersDataDs $objUsersDataDs, DataResponse $objDataResponse) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Excutions of get the users list
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        $arrResponse=[];
        /*$parentKey='users_data';
        UsersCache::deleteCache('users_data');
        $objparams = UsersCache::paramsKeySet($parentKey,$command);
        if($response = UsersCache::keyHasGet($parentKey,$objparams)){
        }else{*/
        
            $objResult = $this->dataSource->getUsersData($command);
           
            $arrResponse['items'] = $this->objDataResponse->collectionFormat($objResult, new UsersDataTs(['id','name', 'image', 'email', 'firstname','color_code', 'lastname', 'department', 'status', 'profile_name','user_image', 'roles_id', 'is_register', 'department_name']));
            
            if(!empty($command->page)){
                $arrResponse['count'] = $objResult->total();
                $arrResponse['lastpage'] = $objResult->lastPage();
            }            
            
             /*  $cacheresult = $response;
               $cacheresult['fromCache'] = 'Yes';
               UsersCache::put($parentKey,$objparams,$cacheresult);
        }*/   
        return $arrResponse;
    }
}
