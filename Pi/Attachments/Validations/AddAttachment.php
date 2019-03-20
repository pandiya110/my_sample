<?php

namespace CodePi\Attachments\Validations;

use CodePi\Base\Validations\DataValidator;


/**
 * @ignore It will handle validations for Add Attachments
 */
class AddAttachment extends DataValidator {
    

    protected $rules = [
        //"db_name" => "required|string|max:255",
        //"resolution_name" => "required|string"
    ];
    protected $messages = [
        'db_name.required'=>'Filename is required to save'
    ];
    
}
