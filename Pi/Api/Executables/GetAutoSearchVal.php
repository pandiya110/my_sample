<?php

namespace CodePi\Api\Executables;

use CodePi\Api\DataSource\MasterItemsDataSource as MasterItemsDs;

class GetAutoSearchVal {

    /**
     * @access private
     * @var class, this is instance of MasterItemsDs class
     */
    private $dataSource;

    /**
     * Constructor
     * @param MasterItemsDs $objMasterDs     
     */
    public function __construct(MasterItemsDs $objMasterDs) {

        $this->dataSource = $objMasterDs;
    }

    /**
     * Execution of get auto search value 
     * 
     * @param obj $command
     * @return array
     */
    public function execute($command) {
        $arrResponse = $return = [];
        $objResult = $this->dataSource->getAutoSugSearchVal($command);

        if (!empty($objResult)) {
            foreach ($objResult as $row) {
                $return[] = $row[$command->search_key];
            }
        }
        $arrResponse['items'] = $return;
        if (!empty($command->page)) {
            $arrResponse['lastpage'] = $objResult->lastPage();
            $arrResponse['total'] = $objResult->totalCount;
        }
        return $arrResponse;
    }

}
