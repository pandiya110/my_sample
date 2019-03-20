<?php

namespace CodePi\ImportExportLog\DataSource;

use CodePi\Base\Eloquent\ErrorLogs;

class ErrorLogDataSource {

    function saveLog($command) {
        $data = $command->dataToArray();
        $objSystemLog = new ErrorLogs ();
        $saveDetails = [];
        $objSystemLog->dbTransaction();
        try {
            $saveDetails = $objSystemLog->saveRecord($data);
            $objSystemLog->dbCommit();
        } catch (\Exception $ex) {
            $objSystemLog->dbRollback();
        }
        return $saveDetails;
    }

}
