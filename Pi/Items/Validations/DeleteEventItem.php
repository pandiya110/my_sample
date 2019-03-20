<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;

class DeleteEventItem extends DataValidator {

    public $rules = [
        'id' => 'required|array',
        'event_id' => 'required',
    ];

}
