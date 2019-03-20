<?php

namespace CodePi\Templates\Http\Controllers;

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
use CodePi\Templates\Commands\SaveUsersTemplates;
use CodePi\Templates\Commands\CopyTemplates;
use CodePi\Templates\DataSource\UsersTemplatesDS;
use CodePi\Templates\Commands\AssignDefaultTemplate;
use CodePi\Templates\Commands\DeleteTemplates;
use CodePi\Templates\Commands\GetTemplatesList;

class TemplatesController extends PiController {

    public function __construct() {
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
        header("Pragma: no-cache"); // HTTP 1.0.
        header("Expires: 0");
    }

    /**
     * Creating new template view
     * @param Request $request
     * @return Response
     */
    public function saveUsersTemplates(Request $request) {
        $data = $request->all();
        $command = new SaveUsersTemplates($data);
        return $this->run($command, trans('Templates::messages.S_SaveTemplate'), trans('Templates::messages.E_SaveTemplate'));
    }

    /**
     * Copy Templates
     * @param Request $request
     * @return Response
     */
    public function copyTemplates(Request $request) {
        $data = $request->all();
        $command = new CopyTemplates($data);
        return $this->run($command, trans('Templates::messages.S_CopyTemplate'), trans('Templates::messages.E_CopyTemplate'));
    }

    /**
     * Delete the templates
     * @param Request $request
     * @return Response
     */
    public function deleteTemplates(Request $request) {
        $data = $request->all();
        $command = new DeleteTemplates($data);
        return $this->run($command, trans('Templates::messages.S_DeleteTemplate'), trans('Templates::messages.E_DeleteTemplate'));        
    }

    /**
     * Get the Template list
     * @param Request $request
     * @return Response
     */
    public function getTemplatesList(Request $request) {
        $data = $request->all();
        $command = new GetTemplatesList($data);
        return $this->run($command, trans('Templates::messages.S_TemplateList'), trans('Templates::messages.E_TemplateList'));
//        
//        $intUserId = isset($data['users_id']) ? $data['users_id'] : 0;
//        $objTempalte = new UsersTemplatesDS();
//        $result = $objTempalte->getTemplateListByUserId($intUserId);
//        
//        $code = ($result) ? trans('Templates::messages.S_TemplateList') : trans('Templates::messages.E_TemplateList');
//        $status = ($result) ? true : false;
//        $response = new DataSourceResponse(['items' => $result], $code, $status);
//        return Response::json($response->formatMessage());
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function assignDefaultTemplate(Request $request) {
        $data = $request->all();
        $command = new AssignDefaultTemplate($data);
        return $this->run($command, trans('Templates::messages.S_AssignTemplate'), trans('Templates::messages.E_AssignTemplate'));
    }

}
