<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailTemplatesDataSource;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\ImportExportLogsTransformer;

class EmailTemplates implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new EmailTemplatesDataSource ();
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
        // echo "<pre>";print_r($data);exit;
        $result = $this->dataSource->emailTemplatesData($data);
        //$response['data'] = $this->objCollectionFormat->collectionFormat($result, new ImportExportLogsTransformer());
        //$response['count'] = $result->total();
        return $result;
    }

}
