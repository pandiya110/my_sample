<?php

namespace CodePi\Roles\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Roles\Commands\GetRolesList;
use CodePi\Roles\Commands\GetRolesDetails;
use CodePi\Roles\Commands\AddRoles;
use CodePi\Roles\Commands\GetRoleHeaders;

class RolesController extends PiController {

    /**
     * Get list of Role in Admin
     * @param Request $request
     * @return Response
     */
    public function getRolesList(Request $request) {
        $data = $request->all();
        $command = new GetRolesList($data);
        return $this->run($command, trans('Roles::messages.S_RoleList'), trans('Roles::messages.E_RoleList'));
    }

    /**
     * Get Role detail
     * @param Request $request
     * @return Response
     */
    public function getRolesDetails(Request $request) {
        $data = $request->all();
        $command = new GetRolesDetails($data);
        return $this->run($command, trans('Roles::messages.S_RoleDetails'), trans('Roles::messages.E_RoleDetails'));
    }

    /**
     * Get the color code dropdwon
     * @return Response
     */
    public function getColourCodeDropdown() {
        $objRoleDs = new \CodePi\Roles\DataSource\RolesDataSource();
        $result = $objRoleDs->getRoleColourCodeDropdown();
        $code = ($result['status']) ? 'S_ChannelAdtypes' : 'E_ChannelAdtypes';
        $response = new DataSourceResponse($result['colour'], $code, $result['status']);
        return Response::json($response->formatMessage());
    }

    /**
     * Add/Update the Role Permissions & Role Items headers
     * @param Request $request
     * @return Response
     */
    public function addRoles(Request $request) {
        $data = $request->all();
        $command = new AddRoles($data);
        return $this->run($command, trans('Roles::messages.S_Roles'), trans('Roles::messages.E_Roles'));
    }

    /**
     * Get Role Items Headers
     * @param Request $request
     * @return Response
     */
    public function getRoleHeaders(Request $request) {
        $data = $request->all();
        $command = new GetRoleHeaders($data);
        return $this->run($command, trans('Roles::messages.S_RoleHeaders'), trans('Roles::messages.E_RoleHeaders'));
    }

}
