<?php

namespace CodePi\Items\Utils;

use CodePi\Api\DataSource\EmiApiDataSource;
use CodePi\Base\Eloquent\Settings;

class ItemsIQSRequest {

    public $searchValue;
    public $searchKey;

    function __construct($searchValue, $searchKey) {
        $this->searchValue = $searchValue;
        $this->searchKey = $searchKey;
    }

    /**
     * Call Iqs Api to pull the data from API
     * "stop_iqs_api" this flag is true , API won't call, if this flag is false API will trigger
     * @return boolean
     */
    function pullItemsFromIQSApi() {
        $stopIqsApiRequest = Settings::key('stop_iqs_api');        
        if ($stopIqsApiRequest == false) {
            $objEmiDs = new EmiApiDataSource();
            return $objEmiDs->getApiDataPull($this->searchValue, $this->searchKey);
        } else {
            return false;
        }
    }

}

?>