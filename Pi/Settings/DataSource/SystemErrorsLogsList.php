<?php

namespace CodePi\Settings\DataSource;

use CodePi\Base\Eloquent\ErrorLogs;

class SystemErrorsLogsList {

    /**
     * get all users_log details. 
     * @param $data
     * @return array $users
     */
    function systemErrorsData($data) {

        $data['limit'] = '';
        if (isset($data['page'])) {
            $data['page'] = $data['page']+1;
            $data['limit'] = ($data['page'] - 1) * $data['perPage'];
        }
        $sortBy = $data['sortBy'];
        $objErrorLogs = new ErrorLogs();
        if ($data['order'] == 'user_name') {
            $data['order'] = 'fullname';
        }
        $Logs = $objErrorLogs->dbTable('i')->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                        ->select('i.*')
                        ->selectRaw("concat(u.firstname,' ',u.lastname) as fullname")
                        ->Where(function ($query) use ($sortBy) {
                            if (isset($sortBy) && $sortBy != '') {
                                $query->where('i.action', $sortBy);
                            }
                        })->orderBy($data['order'], $data['sort'])->paginate($data['perPage'], ['*'], '', $data['page']);
                        // print_r($Logs->toArray());die;
        return $Logs;
    }
    /**
     * 
     * @param type $data
     * @return type
     */
    function importExportLogsCount($data) {
        $sortBy = $data['sortBy'];
        $Logs = ImportExportLogs::select('import_export_log.id')
                ->Where(function ($query) use ($sortBy) {
            if (isset($sortBy) && $sortBy != '') {
                $query->where('action', $sortBy);
            }
        });
        $Logs = $Logs->get();
        return count($Logs);
    }
    /**
     * 
     * @param type $data
     */
    function updateSystemErrorlogsStatus($data) {
        $objErrorLogs = new ErrorLogs;
        $objErrorLogs->dbTransaction();
        try {
//            if ($data['status'] == 1) {
//                $data['status'] = 'true';
//            } else {
//                $data['status'] = 'false';
//            }
            $objErrorLogs->saveRecord($data);
            $objErrorLogs->dbCommit();
        } catch (\Exception $ex) {
            $objErrorLogs->dbRollback();
        }
    }

}
