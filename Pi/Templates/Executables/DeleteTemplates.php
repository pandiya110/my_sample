<?php

namespace CodePi\Templates\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Templates\DataSource\UsersTemplatesDS;
use CodePi\Templates\DataTransformers\TemplateListTransformer;
use CodePi\Base\DataTransformers\DataResponse;

class DeleteTemplates implements iCommands {

    /**
     *
     * @var class
     */
    private $dataSource;
    private $dataResponse;

    function __construct(UsersTemplatesDS $objUsersTemplatesDS, DataResponse $objDataResponse) {

        $this->dataSource = $objUsersTemplatesDS;
        $this->dataResponse = $objDataResponse;
    }

    /**
     * 
     * @param obj $command
     * @return array
     */
    function execute($command) {
        $response = [];
        $params = $command->dataToArray();
        $result = $this->dataSource->deleteTemplates($params);
        if (!empty($result)) {
            $objResult = $this->dataSource->getTemplateListByUserId($params['users_id']);
            $response['items'] = $this->dataResponse->collectionFormat($objResult, new TemplateListTransformer(['id', 'name', 'is_active']));
            $response['status'] = true;
        } else {
            $response['status'] = false;
        }
        return $response;
    }

}
