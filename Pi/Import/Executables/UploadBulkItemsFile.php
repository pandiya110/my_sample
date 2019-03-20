<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Import\DataSource\BulkImportItemsDs as BulkImportDs;

class UploadBulkItemsFile implements iCommands {

    private $dataSource;

    function __construct(BulkImportDs $objBulkImportDs) {
        $this->dataSource = $objBulkImportDs;
    }

    function execute($command) {

        $params = $command->dataToArray();
        $response = $this->dataSource->uploadBulkItemsFile($params);

        return $response;
    }

}
