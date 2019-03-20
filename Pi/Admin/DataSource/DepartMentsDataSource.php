<?php

namespace CodePi\Admin\DataSource; 

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Departments;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Base\Commands\CommandFactory;

class DepartmentsDataSource { 
	
    /**
     * This method will handle the create and update the Department
     * @param array $params
     * @return type object
     */
    function saveDepartments($params) {
        $objDepartments = new Departments();
        $saveDetails = [];
        $objDepartments->dbTransaction();
        try {
            $saveDetails = $objDepartments->saveRecord($params);
            $objDepartments->dbCommit();
        } catch (\Exception $ex) {
            $objDepartments->dbRollback();
            $exMsg = 'SaveDepartment->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        return $saveDetails;
    }

    /**
     * Get the All Departments list        
     * @param object $command
     * @return type collection
     */
    function getDepartments($command) {
        $totalCount = 0;
        $params = $command->dataToArray();
        $objDepartments = new Departments();
        $objDepartments = $objDepartments->where(function($query)use($params) {
                                            if (isset($params['id']) && !empty($params['id'])) {
                                                $query->where('id', $params['id']);
                                            }
                                        })->where(function($query)use($params) {
                                            if (isset($params['search']) && trim($params['search']) != '') {
                                                $query->whereRaw("name like '%" . str_replace(" ", "", $params['search']) . "%' ");
                                            }
                                        })->where(function($query)use($params) {
                                            if (isset($params['status']) && trim($params['status']) != '') {
                                                $query->where('status', $params['status']);
                                            }
                                        });
                                        /**
                                         * Sorting, default recently modified by desc order
                                         */
                                        if (isset($params['sort']) && !empty($params['sort'])) {
                                            $objDepartments->orderBy('name', $params['sort']);
                                        } else {
                                            $objDepartments->orderBy('last_modified', 'DESC');
                                        }
                                        /**
                                         * Paginations
                                         */
                                        if (isset($params['page']) && !empty($params['page'])) {
                                            $objDepartments = $objDepartments->paginate($params['perPage']);
                                            $totalCount = $objDepartments->total();
                                        } else {
                                            $objDepartments = $objDepartments->get();
                                        }
        $objDepartments->totalCount = $totalCount;
        return $objDepartments;
    }

}
