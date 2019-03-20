<?php

namespace CodePi\Users\Commands;


use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class ValidateAccountToken extends BaseCommand {

    public $id;
    public $token;
    public $client;
    
    public function __construct($data) {
       
        parent::__construct(empty($data['id'])); 
        
        $this->client = PiLib::piIsset($data, 'client', '');
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->token = PiLib::piIsset($data, 'token', '');
    }

}
