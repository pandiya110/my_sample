<?php

namespace CodePi\Channels\Executables;

use CodePi\Channels\DataSource\ChannelsDataSource;

/**
 * Class :: GetChannelsList
 * Handle the list of channels active or Inactive
 */
class GetChannelsList {

    /**
     *
     * @var class 
     */
    private $dataSource;
    /**
     * 
     * @param ChannelsDataSource $objDepartmentsDs
     */
    public function __construct(ChannelsDataSource $objDepartmentsDs) {
        $this->dataSource = $objDepartmentsDs;
    }

    /**
     * 
     * @param object $command
     * @return array
     */
    public function execute($command) {

        $params = $command->dataToArray();
        $objResult = $this->dataSource->getChannelsList($params);
        $response = $this->dataSource->formatChannelsData($objResult);
        /**
         * Total count
         */
        $response['count'] = isset($response['channels']) ? count($response['channels']) : 0;
        /**
         * Pagination
         */
        if (isset($params['page']) && !empty($params['page'])) {
            $response['lastpage'] = $objResult->lastPage();
            $response['total'] = $objResult->totalCount;
        }

        return $response;
    }

}
