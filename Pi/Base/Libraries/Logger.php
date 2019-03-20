<?php

namespace CodePi\Base\Libraries;

#use CodePi\Base\Libraries\ZipFileFunctions;
#use CodePi\Base\Libraries\Upload\Cloud;
#use CodePi\Base\Libraries\Attachments\Resolutions;

use CodePi\Base\Eloquent\ImportExportLogs;

class Logger {
    /*     * **
     * To add message to array
     * @params $logArr
     * @Returns logArr
     */

    static function importFilesLogs(array $logArr) {
        global $responseLog;
        $responseLog[] = $logArr;
    }

    /*     * **
     * To get message  array
     * @Returns array responseLog
     */

    static function getImportLog() {
        global $responseLog;
        return $responseLog;
    }

    /*     * **
     * To get the name with out spaces and special charecters
     * @params $exportFileName
     * @Returns filename
     */

    public function getExportFileName($exportFileName) {
        $exportFileName = $exportFileName . '_' . date('m-d-Y H:i:s');
        $exportFile = str_replace(' ', '_', $exportFileName);
        $exportFile = str_replace(':', '-', $exportFile);
        $zipFile = preg_replace('/[^A-Za-z0-9\-_]/', '', $exportFile);
        return $zipFile;
    }

    /*     * **
     * To update the system logs data and process status data
     * @params $logsData
     * @Returns result
     */

    public function updateSystemsLogs(array $details) {
        if (!empty($details)) {
            if (isset($details['message']) && !empty($details['message'])) {
                $details['message'] = json_encode($details['message']);
            } else {
                $details['message'] = '';
            }
            $result = ImportExportLogs::where('master_id', $details['master_id'])
                    ->where('action', $details['action'])
                    ->where('process_status', $details['process_status'])
                    ->update(['process_status' => $details['status'], 'message' => $details['message'], 'response' => json_encode($details['response'], true), 'filename' => json_encode($details['filename'], true)]);
            return $result;
        }
    }

}
