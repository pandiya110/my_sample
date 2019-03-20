<?php

namespace CodePi\RestApiSync\Executables;

use CodePi\Base\DataSource\Elastic;
use CodePi\RestApiSync\DataSource\ChannelsItemsDs;

/**
 * Class : SyncItemsChannels
 * Constructor : ItemsDataSource , DataResponse
 * Method : execute
 */
class SyncItemsChannels {

    /**
     *
     * @var Object 
     */
    private $dataSource;
    /**
     * 
     * @param ChannelsItemsDs $objItemsDs
     */
    public function __construct(ChannelsItemsDs $objItemsDs) {
        $this->dataSource = $objItemsDs;
    }

    /**
     * Execution of Sync the ItemsChannelsAdtypes Data into ElasticSearch
     * @param Object $command
     * @return boolean
     */
    public function execute($command) {
        return $this->dataSource->syncChannelsData($command);
    }

}
