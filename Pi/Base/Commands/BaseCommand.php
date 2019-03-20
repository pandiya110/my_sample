<?php

namespace CodePi\Base\Commands;

#use Auth;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Libraries\PiAuth;
class BaseCommand {

    private $isAdd;
    public $isAuto;
    public $id;
    public $post;

    function __construct($isAdd = False) {
        $this->isAdd = $isAdd;
        if (empty($this->id)) {
            $this->date_added = PiLib::piDate();
            $this->gt_date_added = gmdate("Y-m-d H:i:s");
           
            if(!property_exists($this, 'created_by'))
                $this->created_by =  PiAuth::getLoggedUserId();
        }
        //$this->instances_id = 1; //config()->get('poet.appinstanceid');
        if(!property_exists($this, 'last_modified_by'))
            $this->last_modified_by = PiAuth::getLoggedUserId(true);

        $this->last_modified = PiLib::piDate();
        $this->ip_address = \Request::getClientIp();
        $this->gt_last_modified = gmdate("Y-m-d H:i:s");
    }

    function dataToArray() {

        if ($this->isAuto) {
            $data = array();
            $dataInfo = is_array($this->post) ? $this->post : (array)$this->post;

            if (count($dataInfo)) {
                $data = $this->prepareDataByPost($dataInfo);
            } else {
                $dataArray = $this->getObjectToVars();
                $data = $dataArray;
            }
            if (empty($this->id)) {
                $data = array_merge($data, array('id' => $this->id,
                    'date_added' => $this->date_added,
                    'gt_date_added' => $this->gt_date_added,
                    'created_by' => $this->created_by
                ));
            } else {
                unset($data['date_added'], $data['gt_date_added'], $data['created_by']);
            }
            $data = array_merge($data, array(//'instances_id' => $this->instances_id,
                'last_modified' => $this->last_modified,
                'last_modified_by' => $this->last_modified_by,
                'ip_address' => $this->ip_address,
                'gt_last_modified' => $this->gt_last_modified
            ));

            //dd($data);
            return $data;
        } else {
            $dataArray = $this->getObjectToVars();
            return $dataArray;
        }
    }

    function postAliases() {
        return [];
    }

    private function getObjectToVars() {
        $dataArray = get_object_vars($this);
        unset($dataArray ['isAdd'], $dataArray ['post'], $dataArray ['isAuto']);
        return $dataArray;
    }

    private function prepareDataByPost($dataInfo) {
        $postAliases = $this->postAliases();
        
        $data = array();
        foreach ($dataInfo as $key => $value) {
            if (property_exists($this, $key) && $this->$key != '')
                $data [$key] = $this->$key;
            elseif (isset($postAliases[$key]) && $this->{$postAliases[$key]} != '')
                $data [$postAliases[$key]] = $this->{$postAliases[$key]};
            elseif (isset($postAliases[$key]) && $this->{$postAliases[$key]} == '')
                $data [$postAliases[$key]] = $value;
            else
                $data [$key] = $value;
        }
        $dataArray = $this->getObjectToVars();
        foreach ($dataArray as $key => $value) {
            $data[$key] = PiLib::piIsset($data, $key, $value);
        }
        
        return $data;
    }

    /**
     * Get the Created Info
     * @return array 
     */
    function getCreatedInfo($intCreatedBy = 1, $intLastModifiedBy = 1) {
        return ['created_by' => $this->created_by,
            'last_modified_by' => $this->last_modified_by,
            'date_added' => PiLib::piDate(),
            'last_modified' => PiLib::piDate(),
            'gt_date_added' => gmdate("Y-m-d H:i:s"),
            'gt_last_modified' => gmdate("Y-m-d H:i:s"),
            'ip_address' => \Request::getClientIp()];
    }

}
