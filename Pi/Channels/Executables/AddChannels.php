<?php

namespace CodePi\Channels\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Channels\DataSource\ChannelsDataSource as ChannelsDs;
use CodePi\Channels\Commands\GetChannelDetails;
use CodePi\Base\Commands\CommandFactory;

/**
 * Handle the execution of Channels creation
 */
class AddChannels implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Channels 
     */
    function __construct(ChannelsDs $objChannelsDS, DataResponse $objDataResponse) {
        $this->dataSource = $objChannelsDS;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Executions of Save channels and Adtypes
     * @param object $command
     * @return array
     */
    function execute($command) {

        $params = $command->dataToArray();
        $objResult = $this->dataSource->saveChannels($command);

        if ($objResult->id) {
            if (isset($params['ad_types']) && !empty($params['ad_types'])) {
                $command->channels_id = $objResult->id;
                $this->dataSource->saveChannelAdTypes($command);
            }
        }
        /**
         * if we add any new channels, it has to map to the all events
         */
        if (isset($params['id']) && empty($params['id'])) {
            $arrCreatedInfo = $command->getCreatedInfo();
            $this->dataSource->mapChannelsToAllEvents($objResult->id, $arrCreatedInfo);
        }
        /**
         * Get updated channels details
         */
        $objCommand = new GetChannelDetails(['id' => $objResult->id]);
        $response = CommandFactory::getCommand($objCommand);

        return $response;
    }

}
