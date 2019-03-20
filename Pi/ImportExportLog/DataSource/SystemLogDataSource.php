<?php

namespace CodePi\ImportExportLog\DataSource;

use CodePi\Base\Eloquent\ImportExportLogs;
use CodePi\ImportExportLog\Mailer\ErrorMailer;
use CodePi\Base\Commands\CommandFactory;
use CodePi\ImportExportLog\Commands\ErrorLog;

class SystemLogDataSource {

    function saveLog($command) {
        $data = $command->dataToArray();
        $objSystemLog = new ImportExportLogs ();
        $saveDetails = [];
        $objSystemLog->dbTransaction();
        try {
            $objSystemLog->saveRecord($data);
            $objSystemLog->dbCommit();
        } catch (\Exception $ex) {
            $objSystemLog->dbRollback();
        }
        return $saveDetails;
    }

    function getLogs($command) {
        $details = $command->dataToArray();

        $objSystemLog = new ImportExportLogs ();

        return $objSystemLog->select('master_id')
                        ->selectRaw('json_agg(distinct u.id) as user_ids')
                        ->selectRaw('json_agg(distinct system_logs.id) as ids')
                        ->selectRaw('json_agg(distinct u.recon_users_id) as recon_users_id')
                        ->selectRaw("concat('[',string_agg(distinct concat('{\"id\":',COALESCE(u.id,0)::character varying,',\"recon_users_id\":\"',COALESCE(u.recon_users_id,0)::character varying,'\",\"name\":\"',CONCAT(u.firstname,' ', u.lastname)::character varying,'\",\"email\":\"',COALESCE(u.email,'')::character varying, '\"}',NULL),','), ']') as user_details")
                        ->join('admin.users as u', 'u.id', '=', 'system_logs.created_by')
                        ->where('process_status', $details ['process_status'])
                        ->where(function ($query) use ($details) {
                            if (!empty($details ['action']))
                                $query->where('action', $details ['action']);
                        })->where(function ($query) use ($details) {
                            if (!empty($details ['master_table']))
                                $query->where('master_table', $details ['master_table']);
                        })->where(function ($query) use ($details) {
                            if (!empty($details ['master_id']))
                                $query->where('master_id', $details ['master_id']);
                        })->groupBy('master_id')
                        ->limit($details['limit'])
                        ->get();
    }

    function bulkUpdate($command) {
        \DB::beginTransaction();
        try {
            $details = $command->dataToArray();
            $update = $details;
            unset($update['created_by'], $update['last_modified_by'], $update['date_added'], $update['id'], $update['ids']);

            ImportExportLogs::whereIn('id', $details ['ids'])->update($update);
            $this->saveErrorLog($update);
            $this->sendMailForErrorStatus($update);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }

    function getSystemLogs($command) {
        $objLogs = [];
        $objResult = $this->getLogs($command);

        if (!empty($objResult)) {

            foreach ($objResult as $l => $m) {

                $objLogs [] = [
                    'master_id' => $m->master_id,
                    'ids' => json_decode($m->ids),
                    'user_details' => json_decode($m->user_details)
                        ]
                ;
            }
        }
        return $objLogs;
    }

    function updateLog($command) {
        $result = [];
        \DB::beginTransaction();
        try {
            $details = $command->dataToArray();
            $result = ImportExportLogs::where('master_id', $details ['master_id'])->where('action', $details ['action'])->where('process_status', $details ['process_status'])->update([
                'process_status' => $details ['status'],
                'message' => $details ['message'],
                'response' => json_encode($details ['response'], true),
                'filename' => json_encode($details ['filename'], true)
                    ])

            ;
            $this->sendMailForErrorStatus($details);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return $result;
    }

    function getListLogs($command) {
        $params = $command->dataToArray();
        $objSystemLog = new ImportExportLogs ();
        $response = array();
        $result = $objSystemLog->select('action')->groupBy($params['order'])->orderBy('action', $params['sort'])->get();
        if (isset($result)) {
            foreach ($result as $record) {
                $response[] = $record->action;
            }
        }
        return $response;
    }

    function sendMailForErrorStatus($data = []) {
        if (isset($data['process_status']) && $data['process_status'] == 3) {
            $objErrorMailer = new ErrorMailer();
            $objErrorMailer->validateMail($data);
        }
    }

    function saveErrorLog($data = []) {
        if (isset($data['process_status']) && $data['process_status'] == 3) {
            CommandFactory::getCommand(new ErrorLog(['message' => isset($data['message']) ? $data['message'] : '']), true);
        }
    }

}
