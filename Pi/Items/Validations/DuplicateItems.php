<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;

class DuplicateItems extends DataValidator {

    public $rules = [
        'item_id' => 'required|array',
        'events_id' => 'required',
    ];

}
