<?php

namespace CodePi\ImportExportLog\DataSource;

use CodePi\Base\Libraries\Agent\BrowserAgent;
use CodePi\Base\Eloquent\ImportExportLogs as ImportExportLog;
use CodePi\ImportExportLog\Commands\ImportExportLog as ImportExportLogCmd;
use CodePi\Base\Commands\CommandFactory as CmdFactory;
#use CodePi\Base\Libraries\Download;
#use Illuminate\Support\Facades\DB;
use CodePi\Base\Libraries\PiLib;

class ImportExportLogDataSource {
    /* function model() {
      $new = 'CodePi\ImportExportLog\Eloquant\ImportExportLog';
      return $new;
      } */

    function saveImportExportLog($command) {
        $data = $command->dataToArray();
        $objImpExpLog = new ImportExportLog();
        $result = [];
        $objImpExpLog->dbTransaction();
        try {
            if (isset($data['action']) && $data['action'] == 'sync_tactics_for_order') {
                return $this->syncTacticsForOrder($command);
            }
            $objBrowserAgent = new BrowserAgent;
            $browserDet = $objBrowserAgent->getDetails();
            $data['browser'] = $browserDet['browser'] . " " . $browserDet['browser_version'];
            $data['os'] = $browserDet['os'];
            $data['params'] = json_encode($data['params'], true);
            $data['response'] = json_encode($data['response'], true);
            $data['user_agent'] = $browserDet['user_agent'];
            $data['csrf_token'] = csrf_token();
            $data['session_id'] = \Session::getId();
            //if (!\Auth::check()) {
            //     $data['created_by'] = isset($data['params']['users_id']) ? $data['params']['users_id'] : '';
            //} else {
            //    $data['created_by'] = \Auth::user()->id;
            // }
            $data['date_added'] = date('Y-m-d H:i:s');
            $data['gt_last_modified'] = gmdate('Y-m-d H:i:s');
            $data['gt_date_added'] = gmdate('Y-m-d H:i:s');
            unset($data['id']);

            // echo "<pre>";print_r($data);exit;
            $result = ImportExportLog::create($data);
            // echo "<pre>";print_r($result);exit;
            //$result = ImportExportLog::firstOrCreate($data);
            $objImpExpLog->dbCommit();
        } catch (\Exception $ex) {
            $objImpExpLog->dbRollback();
        }
        return $result;
    }

    function syncTacticsForOrder($command) {
        $data = $command->dataToArray();
        $objImpExpLog = new ImportExportLog();
        $objImpExpLog->dbTransaction();
        try {
            $objBrowserAgent = new BrowserAgent;
            $browserDet = $objBrowserAgent->getDetails();
            $data['browser'] = $browserDet['browser'] . " " . $browserDet['browser_version'];
            $data['os'] = $browserDet['os'];
            $data['params'] = json_encode($data['params'], true);
            $data['response'] = json_encode($data['response'], true);
            $data['user_agent'] = $browserDet['user_agent'];
            $data['csrf_token'] = csrf_token();
            $data['session_id'] = \Session::getId();
            /* if (!\Auth::check()) {
              $data['created_by'] = isset($data['params']['users_id']) ? $data['params']['users_id'] : '';
              } else {
              $data['created_by'] = \Auth::user()->id;
              } */
            $data['date_added'] = date('Y-m-d H:i:s');
            $data['gt_last_modified'] = gmdate('Y-m-d H:i:s');
            $data['gt_date_added'] = gmdate('Y-m-d H:i:s');
            unset($data['id']);

            $intCount = ImportExportLog::where('action', $data['action'])
                    ->where('master_id', $data['master_id'])
                    ->where('process_status', '0')
                    ->count();
            if ($intCount == 0) {
                $result = ImportExportLog::create($data);
            } else {
                $result = [];
            }
            $objImpExpLog->dbCommit();
        } catch (\Exception $ex) {
            $objImpExpLog->dbRollback();
        }
        //$result = ImportExportLog::firstOrCreate($data);
        return $result;
    }

    function updateImportExpLog(array $details) {
        $objImpExpLog = new ImportExportLog();
        $result = [];
        $objImpExpLog->dbTransaction();
        try {
            if (!empty($details)) {
                $result = ImportExportLog::where('master_id', $details['master_id'])
                        ->where('action', $details['action'])
                        ->where('process_status', $details['process_status'])
                        ->update(['process_status' => $details['status'], 'response' => json_encode($details['response'], true), 'filename' => json_encode($details['filename'], true), 'date_added' => date('Y-m-d H:i:s')]);
                $objImpExpLog->dbCommit();
            }
        } catch (\Exception $ex) {
            $objImpExpLog->dbRollback();
        }
        return $result;
    }

    /*     * **
     * Manage Show Images Report Download Log
     * @params command
     */

    function saveDownloadLog($command) {
        $data = $command->dataToArray();
        if (!empty($data['id'])) {
            $logDet = ImportExportLog::find($data['id']);
            if (!empty($logDet)) {
                $logData['action'] = $data['status'];
                $logData['params'] = $data;
                $logData['response'] = $logDet->response;
                $logData['message'] = ($data['status'] == 'download_show_images_report') ? "Downloaded Show Images Report Successfully" : 'Downloaded All Promoted Items Report Successfully';
                $logData['master_id'] = $data['id'];
                $logData['master_table'] = 'import_export_log';
                $logData['filename'] = $logDet->filename;
                $logData['process_status'] = 0;
                $importCmd = new ImportExportLogCmd($logData);
                $response = CmdFactory::getCommand($importCmd);
            }
        } else {
            $downloadLink = '';
            $saveLink = '';
            $resDet = \CodePi\Base\Libraries\Attachments\Resolutions::find($data['resid']);
            if (!empty($resDet)) {
                $path = (!empty($resDet->folders)) ? $resDet->folders . '/' . $data['link'] : '';
                $cloud = new \CodePi\Base\Libraries\Upload\Cloud;
                // $downloadLink = $cloud->downloadObject($resDet->container,$path);
                $saveLink = $resDet->url . "/" . $resDet->folders . "/" . $data['link'];
            }
            if ($data['resid'] == 8) {
                $logData['action'] = 'mail_download_all_promoted_report';
            } else {
                $logData['action'] = 'mail_download_show_images_report';
            }
            $logData['params'] = $data;
            $logData['response'] = ['mail_link' => $saveLink];
            $logData['message'] = "Downloaded Show Images Report From Mail Successfully";
            $logData['master_id'] = $data['events_id'];
            $logData['master_table'] = 'events';
            $logData['filename'] = $data['link'];
            $logData['process_status'] = 0;
            $logData['created_by'] = $data['users_id'];
            $logData['date_added'] = date('Y-m-d H:i:s');
            $importCmd = new ImportExportLogCmd($logData);
            $response = CmdFactory::getCommand($importCmd);
            if (!empty($saveLink)) {
                //  $pathInfo = pathinfo($data['link']);
                // return Download::start($downloadLink,$data['link']);   
                header('location:' . $saveLink);
            }
            exit;
        }
    }

    static function getLogLatestByAction($strAction = null, $intMasterId = 0) {

        $objResult = ImportExportLog::where(function($query) use($strAction) {
                    if ($strAction != '') {
                        $query->where('action', $strAction);
                    }
                })->where(function($query) use($intMasterId) {
                    if ($intMasterId != 0) {
                        $query->where('master_id', $intMasterId);
                    }
                })->latest('date_added')->select('date_added')->first();

        $strDate = '';
        if (!empty($objResult)) {
            $strDate = (new PiLib)->getUserTimezoneDate($objResult->date_added, 'M d, Y h:i A');
        }
        return $strDate;
    }

}
