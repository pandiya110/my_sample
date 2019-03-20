<?php

namespace CodePi\Settings\DataSource;

use CodePi\Base\Eloquent\ImportExportLogs;

class ImportExportLogsList {

    /**
     * get all users_log details. 
     * @param $data
     * @return array $users
     */
    function importExportLogsData($data) {
        $sortBy = $data['sortBy'];
        $objImportExportLogs = new ImportExportLogs();
        if ($data['order'] == 'user_name') {
            $data['order'] = 'fullname';
        }
        $Logs = $objImportExportLogs->dbTable('i')->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                        ->select('i.action', 'i.filename', 'i.params', 'i.response', 'i.message', 'i.process_status', 'i.id', 'i.date_added')
                        ->selectRaw("concat(u.firstname,' ',u.lastname) as fullname")
                        ->selectRaw("concat(i.master_id,',',i.master_table) as master_info")
                        ->Where(function ($query) use ($sortBy) {
                            if (isset($sortBy) && $sortBy != '') {
                                $query->where('i.action', $sortBy);
                            }
                        })->orderBy($data['order'], $data['sort'])->paginate($data['perPage'], ['*'], '', $data['page']);
        
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

}
