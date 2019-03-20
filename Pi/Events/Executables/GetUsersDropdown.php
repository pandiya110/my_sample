<?php

namespace CodePi\Events\Executables;

use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Base\Libraries\PiLib;
use CodePi\Events\DataTransformers\UsersDataTransformers as UsersDataTs;
use CodePi\Base\DataTransformers\DataResponse;

class GetUsersDropdown {

    /**
     *
     * @var type 
     */
    private $dataSource;

    /**
     *
     * @var type 
     */
    private $objDataResponse;

    public function __construct(EventsDataSource $objEventDs, DataResponse $response) {
        $this->dataSource = $objEventDs;
        $this->objDataResponse = $response;
    }

    /**
     * Get the list of users
     * @param object $command
     * @return array
     */
    public function execute($command) {

        $result = $this->dataSource->getUsersDropdown($command);
        $response['users'] = $this->objDataResponse->collectionFormat($result, new UsersDataTs(['id', 'firstname', 'lastname']));

//        if (!empty($command->page)) {
//            $response['count'] = $result->total();
//            $response['lastpage'] = $result->lastPage();
//        }

        return $response;
    }

}
