<?php

namespace CodePi\Channels\Executables;

use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\Base\DataTransformers\DataResponse;
use URL;

class GetChannelsAdtypes {

    /**
     *
     * @var class 
     */
    private $dataSource;

    /**
     *
     * @var class 
     */
    private $objDataResponse;

    /**
     * 
     * @param ChannelsDataSource $objDepartmentsDs
     * @param DataResponse $objDataResponse
     */
    public function __construct(ChannelsDataSource $objDepartmentsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objDepartmentsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * 
     * @param object $command
     * @return array
     */
    public function execute($command) {
        $response = [];
        $params = $command->dataToArray();
        $objResult = $this->dataSource->getChannelsAdtypes($params);
        if (!empty($objResult)) {
            $response = $objResult;
        }
        return $response;
    }

}
