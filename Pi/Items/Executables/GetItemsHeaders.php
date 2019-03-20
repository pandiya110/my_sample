<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemsDs;
use CodePi\Templates\DataSource\UsersTemplatesDS;
use CodePi\Base\DataTransformers\DataResponse;

class GetItemsHeaders implements iCommands {

    private $dataSource;
    private $objDataResponse;
    private $UsersTemplatesDS;

    /**
     * @ignore It will create an object of items headers
     */
    public function __construct(ItemsDs $objItemsDs, DataResponse $objDataResponse, UsersTemplatesDS $objUserTempDs) {
        $this->dataSource = $objItemsDs;
        $this->UsersTemplatesDS = $objUserTempDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Get items headers list and properties of columns
     * If users assigned any custom templates, custom template headers will be assigned to grid (getActiveTemplateColumns)
     * If not ,default role level headers will be assigned to grid headers (getMappedItemHeaders)
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        $params = $command->dataToArray();
        $tempColumns = [];
        
        if ($params['linked_item_type'] != 2) {
            $tempColumns = $this->UsersTemplatesDS->getActiveTemplateColumns($params);
        }

        if (!empty($tempColumns) && $params['report_view'] == false) {
            
            $objResult = $tempColumns;
        } else {
            
            $objResult = $this->dataSource->getMappedItemHeaders($params);
            /**
             * This is only for Reporting view
             * @param ['report_view'] should be true when we required report view, deafult false;
             */
            if($params['report_view'] == true){
                $array[] = array(
                    "id" => 1111,
                    "column" => "event_name",
                    "color_code" => "#dadada",                  
                    "name" => "Event Name",
                    "IsEdit" => false,
                    "type" => "text",
                    "width" => 200,
                    "order_no" => 0,
                  
                );
                $objResult['itemHeaders'] = array_merge($array, $objResult['itemHeaders']);
            }
        }
        $response = [];
        $response['items'] = $objResult;
        return $response;
    }

}
