<?php

namespace CodePi\Channels\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Eloquent\Channels;
use CodePi\Base\Eloquent\ChannelsAdTypes;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;

class AddChannels extends DataValidator {

    protected $rules = [
        "name" => "required|min:2|max:255|isDynamicRule",
        "description" => "min:2|max:255",
    ];
    protected $messages = [
        'name.is_dynamic_rule' => 'Channel name already exists.'
    ];

    function doValidation($data) {
        if (isset($data['id']) && $data['id'] == '') {
            $data['id'] = 0;
        }

        $objChannels = new Channels();
        $responseOn = array();
        $count = $objChannels->where('name', trim($data['name']))->where('id', '!=', $data['id'])->count();
        $existsAdTypes = $this->isExistsAdTypes($data);
        
        if (!empty($existsAdTypes) > 0) {
            $message =[];
            $i = 1;
            foreach ($existsAdTypes as $value) {
                $message[] = $i . '. ' . $value . ' Ad Type already exists';
                $i++;
            }
            throw new DataValidationException(implode(", ", $message), new MessageBag());
        }

        if ($count > 0) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * Check ad types already exists or not 
     * @param array $data
     * @return array
     */
    function isExistsAdTypes($data) {
        if (isset($data['id']) && $data['id'] == '') {
            $data['id'] = 0;
        }
        $array = [];
        $objChannelsAdType = new ChannelsAdTypes();
        foreach ($data['ad_types'] as $row) {

            if (isset($row['id']) && $row['id'] == '') {
                $row['id'] = 0;
            }
            
            $dbResult = $objChannelsAdType->whereRaw('lower(trim(name))'.'= '.'\''.strtolower(trim($row['name'])).'\'')
                            ->where('channels_id', $data['id'])
                            ->where('id', '!=', $row['id'])->get();
            
            foreach ($dbResult as $val) {
                $array[$val->id] = $val->name;
            }
        }
        return $array;
    }

}
