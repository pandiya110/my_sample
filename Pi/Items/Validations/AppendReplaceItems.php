<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;

class AppendReplaceItems extends DataValidator {

    public $rules = [
        'id' => 'required',
        'events_id' => 'required',
        'item_key' => 'required|string',
        'item_value' => 'required',
    ];

}
