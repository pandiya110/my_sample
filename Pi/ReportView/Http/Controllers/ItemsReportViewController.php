<?php

namespace CodePi\ReportView\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Redirector;
use Response;
use Session;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\ReportView\Commands\GetItemsReportView;
use CodePi\ReportView\Commands\GetLinkedItemsReportView;
use CodePi\Base\Libraries\PiLib;

class ItemsReportViewController extends PiController {

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
    public function getItemsReportView(Request $request) {
        $data = $request->all();
        $command = new GetItemsReportView($data);
        return $this->run($command, trans('Items::messages.S_ReportView'), trans('Items::messages.E_ReportView'));
    }
/**
     * 
     * @param Request $request
     * @return type
     */
    public function getLinkedItemsReportView(Request $request) {
        $data = $request->all();
        $command = new GetLinkedItemsReportView($data);
        return $this->run($command, trans('Items::messages.S_ReportView'), trans('Items::messages.E_ReportView'));
    }
}
