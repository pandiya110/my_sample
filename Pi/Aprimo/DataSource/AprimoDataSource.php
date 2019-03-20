<?php

namespace CodePi\Aprimo\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Eloquent\Settings;
use CodePi\Base\Eloquent\Campaigns;
use CodePi\Base\Eloquent\AprimoAuthDetails;
use CodePi\Base\Libraries\PiLib;
use URL;
use CodePi\ImportExportLog\Commands\ErrorLog;
use CodePi\Base\Eloquent\LogProcess;
use CodePi\Campaigns\Commands\SaveCampaigns;
use CodePi\Base\Eloquent\CampaignsProjects;
use DB;
use CodePi\Base\Libraries\DefaultIniSettings;

class AprimoDataSource {

    /**
     * Update Aprimo Auth info
     * @return type
     */
    function updateAprimoAuthDetails() {

        try {
            $getDataSync = $this->getDataSyncProcess();
            if ($getDataSync == false) {
                $authDetails = $this->getAprimoAuthDetails();
                $access_token_details = $this->getAprimoAccessToken($authDetails);
                if ($access_token_details['httpCode'] == '200') {
                    $data = array('accessToken' => $access_token_details['response'], 'last_modified' => PiLib::piDate('Y-m-d H:i:s'));
                } elseif ($access_token_details['httpCode'] == '401') {
                    $nativeTokenDetails = $this->getAprimoNativeToken($authDetails);
                    $data = array('refreshToken' => $nativeTokenDetails['response']->refreshToken,
                        'accessToken' => $nativeTokenDetails['response']->accessToken,
                        'last_modified' => PiLib::piDate('Y-m-d H:i:s')
                    );
                }
                $data['id'] = $authDetails->id;
                $objAuthDetails = new AprimoAuthDetails();
                $updateAuth = $objAuthDetails->saveRecord($data);
            }
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            return CommandFactory::getCommand(new ErrorLog(array('message' => $exMsg)), TRUE);
        }
    }

    /**
     * get aprimo auth details 
     * @param $command
     * @return array
     */
    function getAprimoAuthDetails() {
        $objAuthDetails = new AprimoAuthDetails();
        return $objAuthDetails->first();
    }

    function getDataSyncProcess() {
        $getDataSyncProcess = Settings::key('stop_aprimo_process');
        return $getDataSyncProcess;
    }

    /**
     * get aprimo access token details. 
     * @param $authDetails
     * @return array
     */
    function getAprimoAccessToken($authDetails) {
        $post = array('refreshToken' => $authDetails->refreshToken);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $authDetails->aprimo_url . "/api/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($post),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $authDetails->base64_string,
                "Cache-Control: no-cache",
                "client-id: " . $authDetails->client_id,
                "content-type: application/Json"
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $data['response'] = json_decode($response);
        $data['httpCode'] = $httpCode;

        return $data;
    }

    /**
     * get aprimo refresh token and access token details. 
     * @param $authDetails
     * @return array
     */
    function getAprimoNativeToken($authDetails) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $authDetails->aprimo_url . "/api/oauth/create-native-token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $authDetails->base64_string,
                "Cache-Control: no-cache",
                "client-id: " . $authDetails->client_id,
                "content-type: application/Json",
                "Content-Length : 0"
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        curl_close($curl);
        $data['response'] = json_decode($response);
        $data['httpCode'] = $httpCode;
        //echo "<pre>";print_r($response);exit;
        return $data;
    }

    /**
     * get curl response based on accesstoken 
     * @param $command
     * @return array
     */
    function getCurlResponseAccessToken($authDetails, $url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "X-Access-Token: " . $authDetails->accessToken
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * get curl response based on refreshtoken 
     * @param $command
     * @return array
     */
    function getCurlResponseNativeToken($authDetails, $url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $authDetails->base64_string,
                "Cache-Control: no-cache",
                "content-type: application/Json",
                "X-Access-Token: " . $authDetails->accessToken
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($curl);
        $err = curl_error($curl);
        curl_close($curl);
        //echo "<pre>";print_r($response);exit;
        return $response;
    }

    /**
     * get curl response based on accesstoken 
     * @param $command
     * @return array
     */
    function postCurlResponseAccessToken($authDetails, $url, $postData) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData),
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "X-Access-Token: " . $authDetails->accessToken,
            //"Content-Length : 0"
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * get aprimo Acitivity details. 
     * @param $data
     * @return array
     */
    function getAprimoActivities($data) {
        set_time_limit(0);
        $status = false;
        $aprimoPullStatus = $this->getDataSyncProcess();
        if ($aprimoPullStatus == false) {
            $array = array('or' => array('0' => array('contains' => array('fieldName' => 'name', 'fieldValue' => 'FY18')), 
                                         '1' => array('contains' => array('fieldName' => 'name', 'fieldValue' => 'FY19')), 
                                         '2' => array('contains' => array('fieldName' => 'name', 'fieldValue' => 'FY20')),
                                         '3' => array('contains' => array('fieldName' => 'name', 'fieldValue' => 'FY21'))));

            /**
             * Update the AccessToken and RefreshToken
             */
            $this->updateAprimoAuthDetails();
            $campaignData = $response = [];
            $authDetails = $this->getAprimoAuthDetails();

            if (isset($data['id']) && $data['id'] != '') {
                $url = $authDetails->aprimo_url . "/api/activities/" . $data['id'];
            } else {
                //$url = $authDetails->aprimo_url . "/api/activities/search";
                $url = $authDetails->aprimo_url . "/api/activities/";
            }
            /**
             * Call aprimo api to pull the campaigns
             */
            $postData = json_encode($array);
            //$response_total = $this->postCurlResponseAccessToken($authDetails, $url, $postData);
            $response_total = $this->getCurlResponseAccessToken($authDetails, $url);

            $response_total = json_decode($response_total, true);
            $total = isset($response_total) ? $response_total['_total'] : 0;
            for ($i = 0; $i < $total; $i = $i + 500) {
                $url_limit = $url . '?limit=500&sortField=activityId&sortAscending=true&offset=' . $i;
                //$response = $this->postCurlResponseAccessToken($authDetails, $url_limit, $postData);
                $response = $this->getCurlResponseAccessToken($authDetails, $url);                
                $response = json_decode($response, true);
                if (count($response) > 0) {
                    $embedded = (isset($response['_embedded']) && $response['_embedded']['Activity']) ? $response['_embedded']['Activity'] : [];
                    foreach ($embedded as $row) {
                        if (isset($row['extendedAttributes'])) {
                            $outOfMarketDate = $this->getOutofMarketDate($row['extendedAttributes']);
                        }
                        $objCamp = new Campaigns();
                        $getCampaignInfo = $objCamp->where('aprimo_campaign_id', $row['activityId'])->first();
                        if (count($getCampaignInfo) > 0) {
                            $id = $getCampaignInfo->id;
                            $date_added = $getCampaignInfo->date_added;
                        } else {
                            $id = "";
                            $date_added = date('Y-m-d H:i:s');
                        }
                        DB::beginTransaction();
                        try {
                            $campaignData = array('id' => $id,
                                'aprimo_campaign_id' => $row['activityId'],
                                'name' => $row['name'],
                                'description' => isset($row['description']) ? $row['description'] : "",
                                'aprimo_campaign_type_id' => $row['activityTypeId'],
                                'date_added' => $date_added,
                                'last_modified' => date('Y-m-d H:i:s'),
                                'start_from' => PiLib::piDate($row['beginDate'], 'Y-m-d'),
                                'end_date' => PiLib::piDate($row['endDate'], 'Y-m-d'),
                                'out_of_market_date' => !empty($outOfMarketDate) ? PiLib::piDate($outOfMarketDate, 'Y-m-d') : '',
                            );

                            $saveData = $objCamp->saveRecord($campaignData);
                            DB::commit();
                            $status = true;
                        } catch (\Exception $ex) {
                            DB::rollback();
                            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
                            return CommandFactory::getCommand(new ErrorLog(array('message' => $exMsg)), TRUE);
                        }
                    }
                }
            }
        }
        return $status;
    }

    /**
     * Get Out of Market Date by Extt Atrribute id
     * @param type $arrData
     * @return date
     */
    function getOutofMarketDate($arrData) {
        $outOfMarketDate = '';
        foreach ($arrData as $attribute) {
            if (isset($attribute['eaId'])) {
                if ($attribute['eaId'] == 24803) {
                    $outOfMarketDate = isset($attribute['eaValue']) ? $attribute['eaValue'] : '';
                }
            }
        }
        return $outOfMarketDate;
    }

    /**
     * Get Aprimo Projects by specific CampaignsID
     * @return string
     */
    function getAprimoProjects($data) {
        DefaultIniSettings::apply();
        $exMsg = '';
        $aprimoPullStatus = $this->getDataSyncProcess();

        if ($aprimoPullStatus == false) {
            $this->updateAprimoAuthDetails();
            $authDetails = $this->getAprimoAuthDetails();
            $totalCount = $this->getCampaignsTotal();
            for ($i = 0; $i < $totalCount; $i = $i + 250) {
                $arrActivityID = $this->getCampaignsFromDB($i, 250);
                foreach ($arrActivityID as $key => $intActId) {
                    $url = $authDetails->aprimo_url . "/api/activities/" . $intActId . "/projects";
                    $aprimoResponse = $this->getCurlResponseAccessToken($authDetails, $url);
                    $aprimoResponse = json_decode($aprimoResponse, true);
                    if (isset($aprimoResponse['_total']) && $aprimoResponse['_total'] > 0) {
                        $arrProjects = isset($aprimoResponse['_embedded']) && isset($aprimoResponse['_embedded']['Project']) ? $aprimoResponse['_embedded']['Project'] : [];
                        DB::beginTransaction();
                        try {
                            if (!empty($arrProjects)) {
                                $insertData = [];
                                foreach ($arrProjects as $projectData) {

                                    $primID = $this->isExistsProjectId($projectData['projectId'], $projectData['activityId']);
                                    if (empty($primID)) {
                                        $insertData[] = [
                                            'campaigns_id' => $key,
                                            'aprimo_project_id' => isset($projectData['projectId']) ? $projectData['projectId'] : 0,
                                            'activity_id' => isset($projectData['activityId']) ? $projectData['activityId'] : 0,
                                            'title' => isset($projectData['title']) ? PiLib::filterString($projectData['title']) : NULL,
                                            'begin_date' => isset($projectData['beginDate']) ? PiLib::piDate($projectData['beginDate']) : NULL,
                                            'work_flow_id' => isset($projectData['workflowId']) ? $projectData['workflowId'] : 0,
                                            'project_type_id' => isset($projectData['projectTypeId']) ? $projectData['projectTypeId'] : 0,
                                            'project_status' => isset($projectData['projectStatus']) ? $projectData['projectStatus'] : 0,
                                            'project_manager' => isset($projectData['projectManager']) ? $projectData['projectManager'] : 0,
                                            'time_zone_id' => isset($projectData['timeZoneId']) ? $projectData['timeZoneId'] : 0
                                        ];
                                    } else {
                                        if (!empty($primID)) {
                                            $sqlUpdate = 'UPDATE campaigns_projects SET '
                                                    . 'aprimo_project_id = "' . isset($projectData['projectId']) ? $projectData['projectId'] : 0 . '", '
                                                    . 'activity_id = "' . isset($projectData['activityId']) ? $projectData['activityId'] : 0 . '", '
                                                    . 'title  = "' . isset($projectData['title']) ? PiLib::filterString($projectData['title']) : NULL . '", '
                                                    . 'begin_date = "' . isset($projectData['beginDate']) ? PiLib::filterString($projectData['beginDate']) : NULL . '", '
                                                    . 'work_flow_id = ' . isset($projectData['workflowId']) ? $projectData['workflowId'] : 0 . ' , '
                                                    . 'project_type_id = ' . isset($projectData['projectTypeId']) ? $projectData['projectTypeId'] : 0 . ', '
                                                    . 'project_status = ' . isset($projectData['projectStatus']) ? $projectData['projectStatus'] : 0 . ','
                                                    . 'project_manager = ' . isset($projectData['projectManager']) ? $projectData['projectManager'] : 0 . ','
                                                    . 'time_zone_id = ' . isset($projectData['timeZoneId']) ? $projectData['timeZoneId'] : 0 . ' '
                                                    . 'WHERE id = ' . $primID . '; ';
                                        }
                                    }
                                }

                                if (!empty($insertData)) {
                                    $objCampProject = new CampaignsProjects();
                                    $objCampProject->insertMultiple($insertData);
                                }

                                if (!empty($sqlUpdate)) {
                                    $objCampProject = new CampaignsProjects();
                                    $objCampProject->dbUnprepared($sqlUpdate);
                                }
                                DB::commit();
                            }
                        } catch (\Exception $ex) {
                            DB::rollback();
                            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
                            CommandFactory::getCommand(new ErrorLog(array('message' => $exMsg)), TRUE);
                        }
                    }
                }
                sleep(10);
            }

            $exMsg = 'Successfully projected imported';
        } else {
            $exMsg = 'Aprimo API Setting is off';
        }

        return $exMsg;
    }

    /**
     * Get list of all campaignsid(aprimo_activity_id)
     * @return Array
     */
    function getCampaignsFromDB($offset, $limit = 250) {
        $arrActivityID = [];
        $objCamp = new Campaigns();
        $dbResult = $objCamp->orderBy('id', 'asc')->offset($offset)->limit($limit)->get();
        foreach ($dbResult as $row) {
            $arrActivityID[$row->id] = $row->aprimo_campaign_id;
        }
        return $arrActivityID;
    }

    /**
     * 
     * @return type
     */
    function getCampaignsTotal() {
        $objCamp = new Campaigns();
        return $objCamp->count();
    }

    /**
     * Check $intProjectId & $intActId combinations already exists or not
     * @param Integer $intProjectId
     * @param Integer $intActId
     * @return Integer
     */
    function isExistsProjectId($intProjectId, $intActId) {
        $id = 0;
        $objCampProject = new CampaignsProjects();
        $dbData = $objCampProject->where('aprimo_project_id', $intProjectId)->where('activity_id', $intActId)->first();
        if (!empty($dbData)) {
            $id = $dbData->id;
        }
        return $id;
    }

}
