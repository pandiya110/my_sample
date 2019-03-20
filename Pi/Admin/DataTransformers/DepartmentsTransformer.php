<?php

namespace CodePi\Admin\DataTransformers;

use CodePi\Base\Eloquent\Departments;
use CodePi\Base\DataTransformers\PiTransformer;

class DepartmentsDataTransformers extends PiTransformer {

    /**
     * @param object $objectDepartments
     * @return array It will loop all records of departments table
     */
    function transform($objectDepartments) {

        return $this->filterData(['id' => $objectDepartments->id,
                                  'name' => $objectDepartments->name,
                                  'description' => $objectDepartments->description,
                                  'status' => ($objectDepartments->status == 1) ? true : false
                                 ]
                                );
    }

}
