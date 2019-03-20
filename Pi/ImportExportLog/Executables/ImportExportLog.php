<?php

namespace CodePi\ImportExportLog\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\ImportExportLog\DataSource\ImportExportLogDataSource;

//use CodePi\Base\Libraries\MyEncrypt;
class ImportExportLog implements iCommands {

    private $dataSource;
    private $objUserTransformer;

    function __construct() {
        $this->dataSource = new ImportExportLogDataSource ();
        $this->objDataResponse = new DataResponse ();
    }

    function execute($command) {        
       return $this->dataSource->saveImportExportLog($command);
    }

}
