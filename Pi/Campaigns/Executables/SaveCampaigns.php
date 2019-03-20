<?php

namespace CodePi\Campaigns\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Campaigns\DataSource\CampaignsDataSource as CampaignsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Campaigns\DataTransformers\CampaignsListTransformer as CampaignsTs;

class SaveCampaigns implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * 
     * @param CampaignsDs $objCampaignsDs
     * @param DataResponse $objDataResponse
     */
    function __construct(CampaignsDs $objCampaignsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objCampaignsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Add or Update the campaigns details
     * @param object $command
     * @return array
     */
    function execute($command) {

        $params = $command->dataToArray();
        $objResult = $this->dataSource->saveCampaigns($params);
        /**
         * Remove campaigns from events, if status is inactive
         */
        if($objResult->status != '1'){
            $this->dataSource->removeInactiveCampaignsEvent($objResult->id);
        }
        $params['id'] = $objResult->id;
        $objResult = $this->dataSource->getCampaignsList($params);
        $response = $this->objDataResponse->collectionFormat($objResult, new CampaignsTs(['id', 'name', 'description', 'status', 'start_date', 'end_date','assign_status', 'aprimo_campaign_id', 'campaigns_id']));
        return array_shift($response);
    }

}
