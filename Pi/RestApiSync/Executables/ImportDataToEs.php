<?php

namespace CodePi\RestApiSync\Executables;

use CodePi\Base\DataSource\Elastic;
use CodePi\RestApiSync\Utils\ImportType;

class ImportDataToEs {

    private $dataSource;
    /**
     * 
     * @param type $command
     * @return type
     */
    public function execute($command) {
        $importType = $command->importType;
        $this->dataSource = ImportType::Factory($importType);
        return $this->dataSource->importDataToEs($command);
    }

}
