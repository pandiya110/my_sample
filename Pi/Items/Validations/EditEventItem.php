<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;

class EditEventItem extends DataValidator {

    public $rules = [
        'item_id' => 'required',
        'event_id' => 'required',
    ];

}
