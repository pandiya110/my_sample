<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\TableSequencesList;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\ImportExportLogsTransformer;

class TableSequences implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new TableSequencesList ();
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
        $result = $this->dataSource->tableSequencesData($data);
        //$response['data'] = $this->objCollectionFormat->collectionFormat($result, new ImportExportLogsTransformer());
        //$response['count'] = $result->total();
        return $result;
    }

}
