<?php

namespace CodePi\Channels\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Channels\DataSource\ChannelsDataSource;

class UploadChannelLogo implements iCommands {

    private $dataSource;

    function __construct(ChannelsDataSource $objChannels) {
        $this->dataSource = $objChannels;
    }

    function execute($command) {
        $data = $command->dataToArray();
        $response = $this->dataSource->uploadChannelLogo($data);
        return $response;
    }

}
