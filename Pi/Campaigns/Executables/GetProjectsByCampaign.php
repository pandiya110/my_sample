<?php

namespace CodePi\Campaigns\Executables;

use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Campaigns\DataSource\CampaignsDataSource as CampaignsDs;
use CodePi\Campaigns\DataTransformers\ProjectsListTransformer as ProjectsTs;

class GetProjectsByCampaign {

    /**
     *
     * @var type 
     */
    private $dataSource;

    /**
     *
     * @var type 
     */
    private $objDataResponse;

    /**
     * 
     * @param CampaignsDs $objCampaignsDs
     * @param DataResponse $objDataResponse
     */
    public function __construct(CampaignsDs $objCampaignsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objCampaignsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Get the list of campaigns
     * @param object $command
     * @return array
     */
    public function execute($command) {

        $params = $command->dataToArray();
        $result = $this->dataSource->getProjectsByCampaignsID($params);
        $response['items'] = $this->objDataResponse->collectionFormat($result, new ProjectsTs(['id', 'project_name']));       
        return $response;
    }

}
