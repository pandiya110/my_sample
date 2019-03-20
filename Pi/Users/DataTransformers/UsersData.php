<?php

namespace CodePi\Users\DataTransformers;

#use League\Fractal\TransformerAbstract;
#use CodePi\Users\Eloquant\Users;

use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;

#use CodePi\Base\Libraries\PiLib;
use URL,
    DB;

class UsersData extends PiTransformer {

    public $defaultColumns = ['id', 'users_id', 'department', 'firstname', 'lastname', 'name', 'email', 'profile_image_url', 'users_divisions', 'system_permissions', 'permissions', 'global_system_permissions', 'profile_name', 'roles_id', 'global_system_roles', 'is_register', 'department_name'];
    public $encryptColoumns = [];

    /**
     * @param object $objectUsers
     * @return array It will loop all records of Users table
     */
    function transform($objectUsers) {

        if (!empty($objectUsers)) {
            $objectUsers->users_id = isset($objectUsers->id) ? $objectUsers->id : '';
            $objectUsers->name = PiLib::mbConvertEncoding($objectUsers->firstname . ' ' . $objectUsers->lastname);
            $objectUsers->profile_name = PiLib::mbConvertEncoding(substr($objectUsers->firstname, 0, 1) . '' . substr($objectUsers->lastname, 0, 1));
            $ext = pathinfo($objectUsers->profile_image_url, PATHINFO_EXTENSION);

            if (empty($ext)) {
                $objectUsers->image = null; //url('/').'resources/assets/images/default-user.png';//Need to add Config
                $objectUsers->user_image = null;
            } else {
                $fileInfo = pathinfo($objectUsers->profile_image_url);
                $objectUsers->image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_small.' . $fileInfo['extension']);
                $objectUsers->user_image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_medium.' . $fileInfo['extension']);
            }
            $objectUsers->status = (isset($objectUsers->status) && $objectUsers->status == '1') ? true : false;
            $objectUsers->is_register = (isset($objectUsers->is_register) && $objectUsers->is_register == '1') ? true : false;

            $objectUsers->department = $objectUsers->departments_id;
            $objectUsers->department_name = $objectUsers->department_name;
            $objectUsers->roles_id = isset($objectUsers->roles_id) ? $objectUsers->roles_id : '';
            $arrResult = $this->mapColumns($objectUsers);
            return $this->filterData($arrResult);
        } else {
            return [];
        }
    }

}
