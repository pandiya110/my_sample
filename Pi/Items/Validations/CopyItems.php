<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;

class CopyItems extends DataValidator {

    public $rules = [
        'items_id' => 'required|array',
        'from_events_id' => 'required',
        'to_events_id' => 'required',
    ];

}
