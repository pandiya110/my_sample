<?php

namespace CodePi\Settings\Http\Controllers;

use CodePi\Base\Http\PiController;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Request;
use Crypt;
use Auth,
    URL;
use Redirect;
use Mail;
use Validator,
    Session,
    DB;
use CodePi\Settings\Commands\EmailControllerList;
use CodePi\Settings\Commands\EmailControllerDetails;
use CodePi\Settings\Commands\EmailControllerMessage;
use CodePi\Settings\Commands\EmailControllerSendMail;
use CodePi\Settings\Commands\EmailDetailsList;
use CodePi\Settings\Commands\EmailDetailsMessage;
use CodePi\Settings\Commands\UsersLogsList;
use CodePi\Settings\Commands\DivisionsBanners;
use CodePi\Settings\Commands\Settings;
use CodePi\Settings\Commands\ImportExportLogs;
use CodePi\Base\Commands\CommandFactory;
use Illuminate\Support\Facades\Input;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Settings\Commands\SystemErrors;
use CodePi\Settings\Commands\UpdateSystemErrorStatus;
use CodePi\Settings\Commands\TableSequences;
use CodePi\Settings\Commands\UpdateSequences;
use CodePi\Settings\Commands\ListSchemas;
use CodePi\Settings\Commands\EmailTemplates;
use CodePi\Settings\Commands\ListCrons;
use CodePi\Settings\Commands\GetGeneralSettings;
use CodePi\Settings\Commands\SaveGeneralSettings;
use CodePi\Settings\Commands\CronsHandleManual;
use CodePi\Base\Libraries\DefaultIniSettings;

class EmailController extends PiController {

    public function __construct() {
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
        header("Pragma: no-cache"); // HTTP 1.0.
        header("Expires: 0");
    }

    /**
     * get email controller data.
     * 
     * @return Response
     */
    public function emailControllerData(Request $request) {

        $data = Request::all();
        return $this->run(new EmailControllerList($data), trans('Settings::messages.S_EmailControllerList'), trans('Settings::messages.E_EmailControllerList'));
    }

    /**
     * get specific email controller data. 
     *
     * @return Response
     */
    public function getEmailControllerData(Request $request) {

        try {
            $data = Request::all();
            $command = new EmailControllerDetails($data['id']);
            $response = CommandFactory::getCommand($command);
            $response = new DataSourceResponse($EmailControllerdata, 'S_EmailControllerdata');
        } catch (DataValidationException $De) {
            $response = new DataSourceResponse($De->getMessage(), $De->getMessage(), FALSE, 403);
        } catch (\Exception $e) {
            $response = new DataSourceResponse($e->getMessage(), $De->getMessage(), FALSE, 500);
        } finally {
            return \Response::json($response->formatMessage());
        }
    }

    /**
     * getting specific email message. 
     *
     * @return Response
     */
    public function getEmailControllerMessage(Request $request) {

        $data = Request::all();
        return $this->run(new EmailControllerMessage($data), trans('Settings::messages.S_EmailControllerMessage'), trans('Settings::messages.E_EmailControllerMessage'));
    }

    /**
     * getting specific email message of sent mails. 
     *
     * @return Response
     */
    public function getEmailDetailsMessage(Request $request) {

        $data = Request::all();
        return $this->run(new EmailDetailsMessage($data), trans('Settings::messages.S_EmailDetailsMessage'), trans('Settings::messages.E_EmailDetailsMessage'));
    }

    /**
     * Send mails to selected data controllers.
     *
     * @return Response
     */
    public function sendEmailControllerMail(Request $request) {

        $data = Request::all();
        return $this->run(new EmailControllerSendMail($data), trans('Settings::messages.S_EmailControllerSendMail'), trans('Settings::messages.E_EmailControllerSendMail'));
    }

    /**
     * get all email details list.
     *
     * @return Response
     */
    public function getEmailDetails(Request $request) {

        $data = Request::all();
        return $this->run(new EmailDetailsList($data), trans('Settings::messages.S_EmailDetailsList'), trans('Settings::messages.E_EmailDetailsList'));
    }

    /**
     * get settings details.
     *
     * @param Request $request
     * @return response
     */
    public function settings(Request $request) {
        $data = Request::all();
        return $this->run(new Settings($data), trans('Settings::messages.S_Settings'), trans('Settings::messages.E_Settings'));
    }

    /**
     * get users_logs details
     * @param Request $request
     * @return response
     */
    public function getUserLogsList(Request $request) {

        $data = Request::all();
        return $this->run(new UsersLogsList($data), trans('Settings::messages.S_UsersLogsList'), trans('Settings::messages.E_UsersLogsList'));
    }

    /**
     * get Import Export Logs details
     * @param Request $request
     * @return response
     */
    public function importExportLogs(Request $request) {
        $data = Request::all();
        return $this->run(new ImportExportLogs($data), trans('Settings::messages.S_ImportExportLogs'), trans('Settings::messages.E_ImportExportLogs'));
    }

    public function removeCache() {
        \Cache::flush();
    }

    public function errorLogs(Request $request) {
        $data = Request::all();
        $data['pageNumber'] = isset($data['pageNumber']) ? intval($data['pageNumber']) : 1;
        $data['pageSize'] = isset($data['perPage']) ? intval($data['perPage']) : 50;
        $directory = "" . storage_path() . "/logs";
        $files = \File::allFiles($directory);
        $offset = ($data['pageNumber'] - 1) * $data['pageSize']; //get this as input from the user, probably as a GET from a link
        $quantity = $data['pageSize']; //number of items to display
        //get subset of file array
        $files = array_slice($files, $offset, $quantity);
        $arr = array();
        $result = array();
        foreach ($files as $file) {
            $arr[] = pathinfo($file);
        }
        foreach ($arr as $key => $val) {
            $sortArr[$val['filename']] = $val;
        }
        krsort($sortArr);
        foreach ($sortArr as $key => $value) {
            $finalArr[] = $value;
        }
        $result['data'] = $finalArr;
        $result['count'] = count($finalArr);
        $response = new DataSourceResponse($result, 'S_logsList');
        return \Response::json($response->formatMessage());
    }

    public function systemErrors(Request $request) {
        $data = Request::all();
        return $this->run(new SystemErrors($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

    public function tableSequences(Request $request) {
        $data = Request::all();
        return $this->run(new TableSequences($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

    public function updateSequences(Request $request) {
        $data = Request::all();
        return $this->run(new UpdateSequences($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

    public function listSchemas(Request $request) {
        $data = Request::all();
        return $this->run(new ListSchemas($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

    public function emailTemplates(Request $request) {
        $data = Request::all();
        return $this->run(new EmailTemplates($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

    public function updateSystemErrorStatus(Request $request) {
        $data = Request::all();
        return $this->run(new UpdateSystemErrorStatus($data), trans('Settings::messages.S_UpdateSystemErrorStatus'), trans('Settings::messages.E_UpdateSystemErrorStatus'));
    }

    public function listCrons(Request $request) {
        $data = Request::all();
        return $this->run(new ListCrons($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function getGeneralSettings(Request $request) {
        $data = Request::all();
        return $this->run(new GetGeneralSettings($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function saveGeneralSettings(Request $request) {
        $data = Request::all();
        return $this->run(new SaveGeneralSettings($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    function cronsHandleManual(Request $request) {
        DefaultIniSettings::apply();
        $data = Request::all();
        return $this->run(new CronsHandleManual($data), trans('Settings::messages.S_SystemErrorsLog'), trans('Settings::messages.E_SystemErrorsLog'));
    }

}
