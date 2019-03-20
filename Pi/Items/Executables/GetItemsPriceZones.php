<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs; 
use CodePi\Items\DataSource\PriceZonesDataSource;


class GetItemsPriceZones implements iCommands { 
    /**
     * @access private
     * @var class, instance of ItemsDataSource class
     */
    private $dataSource;
    

    /**
     * Constructor
     * 
     * @param class ItemDs $objItemDs     
     */
    function __construct(PriceZonesDataSource $objItemDs) {
        $this->dataSource = $objItemDs;
        
    }

    /**
     * Execution of Append or Replace the items
     * 
     * @param object $command
     * @return array 
     */
    function execute($command) {
        $arrResponse = [];
        $params = $command->dataToArray();
        //$response = $this->dataSource->getSystemVersionsCode($params);
        $response = $this->dataSource->getSystemVersionsCode($params);
        return $response;
    }

}
