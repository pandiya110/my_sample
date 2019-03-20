<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
#use CodePi\Users\DataSource\CreateUser as CreateUserDs;
use CodePi\Users\Mailer\UsersMailer;
use CodePi\Base\DataTransformers\DataResponse;
#use CodePi\Users\DataSource\UsersDataSource as UsersListDt;
#use CodePi\Users\DataTranslators\CollectionFormat;
#use CodePi\Users\DataTranslators\UsersTransformer;
use CodePi\Base\Exceptions\EmailException;
use CodePi\Users\DataTransformers\UsersData AS UserDataTs;
use CodePi\Users\Cache\UsersCache;
use CodePi\Users\Commands\AddPermissions;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Users\DataSource\UsersData as UserDs;

/**
 * Handle the Execution of Save the users informations
 */
class CreateUser implements iCommands {

    private $dataSource;
    private $objDataResponse;
    private $objMailer;
    
    
    /**
     * @ignore It will create an object of Users
     */
    public function __construct(UserDs $objUsersDataDs, DataResponse $objDataResponse, UsersMailer $objMailer) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
        $this->objMailer = $objMailer;
    }

    /**
     * excution of create and update the users informations
     * @param type $command
     * @return type  $response
     */
    function execute($command) {

        $arrResponse = [];
        $params = $command->dataToArray();
        if (empty($params['id'])) {
            $params['activate_exp_time'] = date('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . '+1 days'));
        }

        $objUser = $this->dataSource->saveUser($params);

        /*
         * add & update users system permissions 
         */
        $permissionData = ['users_id' => $objUser->id, 'permissions' => $params['permissions']];
        $objCmd = new AddPermissions($permissionData);
        $savePermissions = CommandFactory::getCommand($objCmd, true);

        if (empty($objUser->color_code)) {
            $this->dataSource->setUsersColorCode($objUser->id);
        }

        $isReg = (isset($objUser->is_register) && !empty($objUser->is_register)) ? $objUser->is_register : '0';
        if (empty($params['id']) && $isReg == '0') {
            $userMail = $this->objMailer->registrationEmail($objUser);
        }

        /*
         * Return saved users info
         */
        $command->id = $objUser->id;
        $objResult = $this->dataSource->getUsersData($command);
        $arrResponse = $this->objDataResponse->collectionFormat($objResult, new UserDataTs(['id', 'name', 'image', 'email', 'firstname', 'lastname', 'department', 'status', 'profile_name', 'user_image', 'roles_id']));
        
        return array_shift($arrResponse);
    }

}
