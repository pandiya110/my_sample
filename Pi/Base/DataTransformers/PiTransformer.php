<?php

namespace CodePi\Base\DataTransformers;

use League\Fractal\TransformerAbstract;
use CodePi\Base\Libraries\PiLib;
class PiTransformer extends TransformerAbstract {
    
    private $filters = array();
    public $encrypt_id;
    public $defaultColumns = array();
    public $encryptColoumns = array('id');
    public function __construct($filters = [],$encrypt_id=true) {
        $this->filters = $filters;
        $this->encrypt_id=$encrypt_id;
    }

    public function filterData(array $arrResult) {
        if (!empty($this->filters)) {
            return array_intersect_key($arrResult, array_flip($this->filters));
        }
        return $arrResult;
    }
    
    function checkColumnExists($object,$key){
        return isset($object->$key)? $object->$key:'';
    }
    
    function mapColumns($objResult){
        $arrResult =[];
        $dbColumns = empty($this->filters) ? $this->defaultColumns : $this->filters;
        foreach ($dbColumns as $column) {
            if (in_array($column, $this->encryptColoumns)) {
                $arrResult[$column] = empty($this->checkColumnExists($objResult, $column)) ? 0 : ($this->encrypt_id) ? PiLib::piEncrypt($objResult->$column) : $objResult->$column;
                continue;
            }
            $arrResult[$column] = $this->checkColumnExists($objResult, $column);
        }
        return $arrResult;
    }

}
