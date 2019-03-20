<?php

namespace CodePi\Channels\Executables;

use CodePi\Channels\DataSource\ChannelsDataSource;

/**
 * Class :: GetChannelDetails
 * Handle the particular channels details
 */
class GetChannelDetails {
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

        $objResult = $this->dataSource->getChannelsDetails($command);
        return $this->dataSource->formatChannelsData($objResult);
        
    }

}
