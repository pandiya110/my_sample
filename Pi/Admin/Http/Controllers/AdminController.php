<?php

namespace CodePi\Admin\Http\Controllers;

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
use CodePi\Admin\Commands\AddDepartments;
use CodePi\Admin\Commands\GetDepartments;

class AdminController extends PiController {

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function departments() {
        return view('departments');
    }

    /**
     * Save the department info
     * @param Request $request
     * @return Response
     */
    public function addDepartments(Request $request) {
        $data = $request->all();
        $command = new AddDepartments($data);
        return $this->run($command, trans('Admin::messages.S_AddDepartments'), trans('Admin::messages.E_AddDepartments'));
    }

    /**
     * Get list of departments
     * @param Request $request
     * @return Response
     */
    public function getDepartments(Request $request) {
        $data = $request->all();
        $command = new GetDepartments($data);
        return $this->run($command, trans('Admin::messages.S_GetDepartments'), trans('Admin::messages.E_GetDepartments'));
    }

}
