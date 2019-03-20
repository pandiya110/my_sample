<?php

namespace CodePi\Events\DataTransformers;

use CodePi\Base\Eloquent\Events;
use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;
class UsersDataTransformers extends PiTransformer {

    public $defaultColumns = ['id','firstname','lastname'];
    public $encryptColoumns = [];

    /**
     * @param object $objUsers
     * @return array It will loop all records of users table
     */
    function transform($objUsers) {
        $arrResult = $this->mapColumns($objUsers);
        
        $arrResult = [
//            'id' => $this->checkColumnExists($objUsers, 'id') == '' ? 0 : ($this->encrypt_id) ? PiLib::piEncrypt($objUsers->id) : $objUsers->id,
            'id' => $this->checkColumnExists($objUsers, 'id'),
            'firstname' => $this->checkColumnExists($objUsers, 'firstname'),
            'lastname' => $this->checkColumnExists($objUsers, 'lastname'),
        ];

        return $this->filterData($arrResult);
    }

}
