<?php

namespace CodePi\Export\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use CodePi\Export\Commands\ExportItems;
use \Response;
use CodePi\Base\Libraries\PiLib;
use CodePi\Export\Commands\ExportItemsToSftp;
use CodePi\Export\Commands\ExportItemsFlatFile;

class ExportController extends PiController {

    /**
     * Export Items as Excel or CSV
     * @param object $request
     * @return Response
     * 
     */
    public function exportItems(Request $request) {
        $data = $request->all();
        $command = new ExportItems($data);
        return $this->run($command, trans('Export::messages.SER_Success'), trans('Export::messages.SER_failure'));
    }

    /**
     * Download the Files 
     * @param Request $request
     * @return type
     */
    public function downloadFiles(Request $request) {

        $data = $request->all();
        if (!empty($data['file_name'])) {
            $fileName = PiLib::piDecrypt($data['file_name']);
            $filePath = storage_path('app') . '/public/Export/export_items/' . $fileName;

            if (file_exists($filePath)) {
                $temp = '';
                $headers = array(header('Content-Type: application/vnd.ms-excel'));
                $dirPath = storage_path('app') . '/public/Export/export_items/';
                return Response::download($dirPath . $fileName, $fileName, $headers);
            } else {
                return ['status' => false, 'message' => 'Fail to download'];
            }
        }
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    function moveFileToSftp(Request $request) {
        $data = $request->all();
        $command = new ExportItemsToSftp($data);
        return $this->run($command, trans('Export::messages.SER_Success'), trans('Export::messages.SER_failure'));
    }

    /**
     * 
     * @param Request $request
     * @return Response
     */
    function exportFlatFile(Request $request) {
        $data = $request->all();
        $command = new ExportItemsFlatFile($data);
        return $this->run($command, trans('Export::messages.SER_Success'), trans('Export::messages.SER_failure'));
    }

    /**
     * 
     * @param Request $request
     * @return Response
     */
    public function downloadZipFile(Request $request) {

        $data = $request->all();
        if (!empty($data['file_name'])) {
            $filePath = storage_path('app') . '/public/Export/export_items_to_zip/' . $data['file_name'];

            if (file_exists($filePath)) {
                $temp = '';
                $headers = array(header('Content-Type: application/zip'));
                $dirPath = storage_path('app') . '/public/Export/export_items_to_zip/';
                return Response::download($dirPath . $data['file_name'], $data['file_name'], $headers);
            } else {
                return ['status' => false, 'message' => 'Fail to download'];
            }
        }
    }

}
