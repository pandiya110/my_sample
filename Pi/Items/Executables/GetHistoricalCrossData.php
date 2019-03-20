<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Items\DataTransformers\GetHistCrsTransformers as HisCrsTs;

class GetHistoricalCrossData  implements iCommands{ 
    /**
     *
     * @var obj instance of ItemsDataSource 
     */
    private $dataSource;
    /**
     *
     * @var obj instance of DataResponse
     */
    private $objDataResponse;

    /**
     * 
     * @param ItemsDataSource $objItemsDataSource
     * @param DataResponse $objDataResponse
     */
    public function __construct(ItemsDataSource $objItemsDataSource, DataResponse $objDataResponse) {
        $this->dataSource = $objItemsDataSource;
        $this->objDataResponse = $objDataResponse;
    }
    
    /**
     * Get HistoricalCrossData of selected items number
     * @param object $command
     * @return array
     */
    public function execute($command) {
        $arrResponse = [];
        if (!empty($command->item_nbr)) {
            $objResult = $this->dataSource->getHistoricalCrossData($command);
            $arrResponse['items']['list'] = $this->objDataResponse->collectionFormat($objResult, new HisCrsTs(['events_id', 'event_name', 'start_date', 'end_date']));
            $arrResponse['items']['item_nbr'] = $command->item_nbr;
            $arrResponse['items']['used_count'] = count($objResult);
            
            if (!empty($command->page) && !empty($objResult)) {
                $arrResponse['count'] = $objResult->total();
                $arrResponse['lastpage'] = $objResult->lastPage();
            } else {
                $arrResponse['count'] = count($objResult);
            }
        }
        return $arrResponse;
    }

}
