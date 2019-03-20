<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;

class ExcludeEventItem extends DataValidator {

    public $rules = [
        'id' => 'required|array',
        'events_id' => 'required',
    ];

}
