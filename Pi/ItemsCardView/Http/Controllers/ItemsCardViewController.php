<?php

namespace CodePi\ItemsCardView\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use CodePi\ItemsCardView\Commands\GetItemsCardView;
use CodePi\Base\Libraries\ZipFileFunctions;
use CodePi\ItemsCardView\Commands\ExportCardViewPdf;

class ItemsCardViewController extends PiController {

    public function __construct() {
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
        header("Pragma: no-cache"); // HTTP 1.0.
        header("Expires: 0");
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function getCardView(Request $request) {
        $data = $request->all();
        $command = new GetItemsCardView($data);
        return $this->run($command, trans('Items::messages.S_GetItems'), trans('Items::messages.E_GetItems'));
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function exportCardViewPdf(Request $request) {
        $data = $request->all();
        $command = new ExportCardViewPdf($data);
        return $this->run($command, trans('Items::messages.S_GetItems'), trans('Items::messages.E_GetItems'));
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    function downloadCardView(Request $request) {
        $data = $request->all();
        if (!empty($data['file_name'])) {
            $filePath = storage_path('app/public') . '/Export/export_items_to_pdf/' . $data['file_name'];

            if (file_exists($filePath)) {
                $temp = '';
                $headers = array(header('Content-Type: application/zip'));
                $dirPath = storage_path('app/public') . '/Export/export_items_to_zip/';
                return Response::download($dirPath . $data['file_name'], $data['file_name'], $headers);
            } else {
                return ['status' => false, 'message' => 'Fail to download'];
            }
        }
    }

}
