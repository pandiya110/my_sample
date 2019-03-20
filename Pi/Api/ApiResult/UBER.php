<?php

namespace CodePi\Api\ApiResult;

use CodePi\Base\DataSource\DataSource;
use CodePi\Api\ApiResult\ApiInterface;
use CodePi\Api\DataSource\MasterItemsDataSource as MasterItmdDs;
use GuzzleHttp\Client;

class UBER implements ApiInterface {
    public $send_value;

    function __construct($key_value) {
        $this->send_value = $key_value;
    }
    
    function getConnections() {
        return new Client(['verify' => false]);
    }

    function getResult() {
        
        $url = config('smartforms.mcis_api_url') . '?value=' . $this->send_value;
        $response = $this->getConnections()->request('GET', $url, ['auth' => [config('smartforms.mcis_username'), config('smartforms.mcis_password')]]);
        $jsonResponse = $response->getBody()->getContents();
        $response = json_decode($jsonResponse, true);
        
        return $response;
    }   
}
