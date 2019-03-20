<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\ImportExportLogsList;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\ImportExportLogsTransformer;

class ImportExportLogs implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new ImportExportLogsList ();
        $this->objCollectionFormat = new DataResponse ();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $response = array();
        $data = $command->dataToArray();
        $result = $this->dataSource->importExportLogsData($data);
        $response['data'] = $this->objCollectionFormat->collectionFormat($result, new ImportExportLogsTransformer());
        $response['count'] = $result->total();
        return $response;
    }

}
