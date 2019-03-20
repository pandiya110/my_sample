<?php

namespace CodePi\Api\ApiResult;

use CodePi\Base\DataSource\DataSource;
#use CodePi\Api\DataSource\DataSourceInterface\iItems;
use CodePi\Api\ApiResult\ApiInterface;
use CodePi\Api\DataSource\MasterItemsDataSource as MasterItmdDs;
use GuzzleHttp\Client;

class MIS implements ApiInterface{
    
    public $send_value;

    function __construct($key_value) {
        $this->send_value = $key_value;
    }

    /**
     * 
     * @return Client
     */
    function getConnections() {
        return new Client(['verify' => false]);
    }
    /**
     * 
     * @param object $command
     * @return array
     */
    function getResult() {
        
        $url = config('smartforms.mcis_api_url') . '?value=' . $this->send_value;
        $response = $this->getConnections()->request('GET', $url, ['auth' => [config('smartforms.mcis_username'), config('smartforms.mcis_password')]]);
        $jsonResponse = $response->getBody()->getContents();
        $response = json_decode($jsonResponse, true);
                
        return $response;
        
                  
/*          $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => config('smartforms.mcis_api_url'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                                        "accept: application/vnd.com.walmart.canonical.masterItem.SupplyItem-8+json",
                                        "itemnumbers: ".$this->send_value."",
                                        "WMT-API-KEY: ".config('smartforms.mcisApiKey').""
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['status' => false, 'message' => "cURL Error #:" . $err];            
        } else {
            return ['status' => true, 'apiData' => $response];
        }*/
    }

}
