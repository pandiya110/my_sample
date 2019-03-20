<?php

namespace CodePi\ItemsCardView\Validations;

use CodePi\Base\Validations\DataValidator;

class GetItemsCardView extends DataValidator {

    public $rules = [
        'event_id' => 'required',
    ];

}
