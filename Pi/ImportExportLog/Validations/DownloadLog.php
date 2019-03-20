<?php
namespace CodePi\Exports\Validations;

use CodePi\Base\Validations\DataValidator;

class DownloadLog extends DataValidator {

    protected $rules = [
        "id" => "required|integer"
    ];
}




