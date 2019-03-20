<?php

namespace CodePi\Items\DataTransformers;

use CodePi\Base\Eloquent\Events;
use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;
class ItemsDataTransformers extends PiTransformer {


    /**
     * @param object $objEvents
     * @return array It will loop all records of events table
     */
    function transform($objItems) {              
      
        $arrResult = $this->mapColumns($objItems);      
        return $this->filterData($arrResult);
                
    }

}
