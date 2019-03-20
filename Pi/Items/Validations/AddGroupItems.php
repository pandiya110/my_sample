<?php

namespace CodePi\Items\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Libraries\PiValidations;
use CodePi\Base\Eloquent\ItemsGroups;
use CodePi\Base\Eloquent\ItemsGroupsItems;
use CodePi\Items\DataSource\GroupedDataSource;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
class AddGroupItems extends DataValidator {

    public $rules = [
        'name' => 'required_if:items_groups_id,==,0|isDynamicRule',
    ];
     protected $messages = [
        'name.is_dynamic_rule' => 'Group name already exists.',
        'name.required_if:items_groups_id,==,0' => 'Group name is required' 
    ];

//    public function doValidation($data) {
//        if (isset($data['id']) && $data['id'] == '') {
//            $data['id'] = 0;
//        }
//        $rules = [
//            'name' => 'unique:items_groups,id.neq'
//        ];
//        $messages = ['name.unique' => 'Item name already exists.'];
//        $objPiValid = new PiValidations($data, $rules, $messages);
//        return $objPiValid->validation();
//    }
    
     /**
      * Custom Validations for Group Items creations
      * @param int $data
      * @return boolean
      * @throws DataValidationException
      */
    public function doValidation($data) {
        
        if (isset($data['items_groups_id']) && $data['items_groups_id'] == '') {
            $data['items_groups_id'] = 0;
        }
        
        $objGroups = new ItemsGroups();
        $responseOn = array();
        
        $count = $objGroups->whereRaw('lower(trim(name)) = '.trim(strtolower(\DB::connection()->getPdo()->quote($data['name']))).'')
                           ->where('id', '!=', $data['items_groups_id'])
                           ->where('events_id', $data['events_id'])
                           ->count();
        
        $objGroupedDs = new GroupedDataSource();
        $checkItemInGroup = $objGroupedDs->checkItemInGroup($data);
        $checkItemInGroupItems = $objGroupedDs->checkItemInGroupItems($data);
        $paranetExists = $objGroupedDs->checkParentItemsExistsAsChildItems($data);
        
        if ($checkItemInGroup == $data['items_id'] && empty($data['items_groups_id'])) {
            throw new DataValidationException('Selected parent items already assigned to another group', new MessageBag());
        } else if ($checkItemInGroupItems > 0) {
            throw new DataValidationException('Selected items already assigned to another group', new MessageBag());
        }else if($paranetExists > 0){
            throw new DataValidationException('Selected items already assigned to another group', new MessageBag());
        }

        if ($count > 0) {
            return false;
        } else {
            return true;
        }
    }

}
