<?php

namespace CodePi\Users\DataSource;

use CodePi\Base\Eloquent\Users;
use CodePi\Users\DataSource\DataSourceInterface\iUsersData;
use CodePi\Base\Eloquent\UsersPermissions;
use CodePi\Base\Eloquent\Permissions;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Eloquent\UsersSubDepartments;
use CodePi\Base\Eloquent\Roles;
use CodePi\Users\Mailer\UsersMailer;
use CodePi\Roles\Commands\GetRoleHeaders;
use CodePi\Base\Commands\CommandFactory;
use DB;
use Request;
use CodePi\Base\Eloquent\ItemsHeaders;
use Auth;
use CodePi\Items\DataSource\ItemsDataSource;
/**
 * handle the Users
 */
class UsersData implements iUsersData {
    
    public $nonVisibleColumns = ['buyer_user_id', 'sr_merchant', 'planner', 'pricing_mgr', 'repl_manager', 'merchant_email_address', 'cost', 'forecast_sales'];


    /**
     * Get Users Data
     * @param object $command
     * @return object CodePi\Users\Eloquant\Users
     * 
     */
    function getUsersData($command) {
        $totalCount = 0;
        $params = $command->dataToArray();
        $objUsers = new Users;
        $objUsers = $objUsers->dbTable('u')
                             //->join('departments as d', 'd.id', '=', 'u.departments_id')
                             ->selectRaw('*')
                             ->selectRaw('(select d.name from departments as d where d.id = u.departments_id limit 1) as department_name')                             
                             ->where(function($query)use($params) {
                                if (isset($params['id']) && !empty($params['id'])) {
                                    $query->where('u.id', $params['id']);
                                }
                             })->where(function($query)use($params) {
                                if (isset($params['profile_id']) && !empty($params['profile_id'])) {
                                    $query->where('profile_id', $params['profile_id']);
                                }
                             })->where(function($query)use($params) {
                             if (isset($params['search']) && trim($params['search']) != '') {
                                $query->whereRaw("CONCAT(replace(firstname,' ',''),replace(lastname,' ','')) like '%" . str_replace(" ", "", $params['search']) . "%' ")
                                      ->orWhere('email', 'like', '%' . $params['search'] . '%');
                             }
                             })->where(function($query)use($params) {
                             if (isset($params['active']) && $params['active'] == true) {
                                $query->where('u.status', '1');
                             } else if (isset($params['active']) && $params['active'] == false) {
                                $query->where('u.status', '0');
                             }
                             });
                             if (isset($params['sort']) && !empty($params['sort'])) {
                                $objUsers->orderBy('firstname', $params['sort']);
                             } else {
                                $objUsers->orderBy('updated_at', 'DESC');
                             }
                             if (isset($params['page']) && !empty($params['page'])) {
                                $objUsers = $objUsers->paginate($params['perPage']);
                                $totalCount = $objUsers->total();
                             } else {
                                $objUsers = $objUsers->get();
                             }
        $objUsers->totalCount = $totalCount;
        return $objUsers;
    }

    /**
     * Save the users permissions
     * @param type $command
     * @return type $objUser
     */
    function savePermissions($command) {
        $params = $command->dataToArray();
        
        $arrCreatedInfo = $command->getCreatedInfo();
        $objUsers = new Users();
        $objUser = $objUsers->findRecord($params['users_id']);
        if (isset($params['permissions'])) {
            $this->saveSystemPermissions($params['permissions'], $arrCreatedInfo, $params['users_id']);
        }

        return $objUser;
    }
    
    /**
     * Get the list of users system permissions
     * @param type $command
     * @return type 
     */
    function getPermissions($command) {
        $params = !is_array($command) ? $command->dataToArray() : $command;
        $arrResult = [];
        $objUsers = new Users();
        $objUser = $objUsers->findRecord($params['users_id']);
        
        if (!empty($objUser)) {
            $objUser->system_permissions = $this->getSystemPermissions($objUser);
        }
        return collect(array($objUser));
    }

    function mapCreatedInfo($array, $createdInfo) {
        return array_merge($array, $createdInfo);
    }
    /**
     * Save the system permissions
     * @param array $arrUsersPermissions
     * @param array $arrCreatedInfo
     * @param type $intUserId
     * @return boolean
     */
    function saveSystemPermissions(array $arrUsersPermissions, array $arrCreatedInfo, $intUserId = 0) {
        
        $arrExisting = $arrNewIds = $arrUpdate = $arrSystemPermissions = $arrInsertId = array();
        $objUsersPermission = new UsersPermissions();
        $objUsersPermission->dbTransaction();
        try {
            foreach ($arrUsersPermissions as $row) {
                
                $permissions = array_values(array_filter($row['permission']));
                if (!empty($permissions)) {
                    $arrSystemPermissions[$row['id']] = ['permission' => array_pop($permissions)];
                }
            }
            
            $arrPermisionsId = array_keys($arrSystemPermissions);

            //delete if not exist
            $objUsersPermission->where('users_id', $intUserId)
                    ->where(function($query) use($arrPermisionsId) {
                        if (!empty($arrPermisionsId)) {
                            $query->whereNotIn('permissions_id', $arrPermisionsId);
                        }
                    })->delete();

            $objResult = $objUsersPermission->where('users_id', $intUserId)->select('permissions_id')->get();
            foreach ($objResult as $objRow) {
                $arrExisting[] = $objRow->permissions_id;
            }

            $arrNewIds = array_diff($arrPermisionsId, $arrExisting);
            $arrUpdate = array_intersect($arrPermisionsId, $arrExisting);

            //insert
            foreach ($arrNewIds as $intPermissionId) {
                if (isset($arrSystemPermissions[$intPermissionId])) {
                    $arrSystemPermissions[$intPermissionId]['users_id'] = $intUserId;
                    $arrSystemPermissions[$intPermissionId]['permissions_id'] = $intPermissionId;
                    $arrInsertId [] = array_merge($arrSystemPermissions[$intPermissionId], $arrCreatedInfo);
                }
            }
            $objUsersPermission->insertMultiple($arrInsertId);

            //update
            foreach ($arrUpdate as $intPermissionId) {
                if (isset($arrSystemPermissions[$intPermissionId])) {
                    $objUsersPermission->where('users_id', $intUserId)->where('permissions_id', $intPermissionId)->update($arrSystemPermissions[$intPermissionId]);
                }
            }
            $objUsersPermission->dbCommit();
            unset($arrSystemPermissions, $arrInsertId, $arrUpdate, $arrExisting, $arrPermisionsId, $arrNewIds);
            return true;
        } catch (\Exception $ex) {
            $objUsersPermission->dbRollback();
            echo $ex->getMessage();exit;
        }
    }

    /**
     * Get System permissions
     * @param Users $objUser
     * @return type
     */
    function getSystemPermissions(Users $objUser) {
        
        $objPermissions = UsersPermissions::userPermissions($objUser->id);
        foreach ($objPermissions as &$value) {
            $value->permission = [(int) $value->permission];
            $value->id = $value->permissions_id;
        }
        return $objPermissions;
    
    }

    /**
     * Get global details for the project
     * @param Users $objUser
     * @return type
     */
    
    function getGlobalData() {
        $objUser = new Users();
        $objUser->global_system_permissions = $this->getGlobalPermissions($objUser);        
        $objUser->global_system_roles = $this->getGlobalRoles($objUser);
        $objUser->global_nonvisible_columns = array_values($this->getNonvisibleColumns());
        return collect(array($objUser));
    }
    
    /**
     * Getting permissions to display in users area
     * @return array
     */

//    function getGlobalPermissions() {
//        $objPermissions = new Permissions;
//        $data = $objPermissions->where('status', '=', '1')->get();
//        if(!empty($data)){
//            $objPermissions = $data->toArray();
//        }
//        $arrPermissions = $this->doArray($objPermissions);
//        return $this->buildTree($arrPermissions, 0);
//    }
    
    /**
     * 
     * @param Users $objUser
     * @return array
     */
    function getGlobalPermissions(Users $objUser) {
        $sql = "SELECT 
                    p.id, p.name,
                    p.code, p.parent_id AS parent_id,
                    p.type, p.level, p.options
                FROM permissions AS p
                WHERE p.status = '1'
                ORDER BY p.parent_id, p.id ";
        $objPermissions = $objUser->dbSelect($sql);
        foreach ($objPermissions as &$value) {
            $value->options = json_decode($value->options);
        }
        
        $arrPermissions = $this->doArray($objPermissions);
        return $this->buildTree($arrPermissions, 0);
    }
    
    function buildTree(array $elements, $parentId = 0) {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    function doArray($data) {
        return collect($data)->map(function($x) {
                    return (array) $x;
                })->toArray();
    }
    /**
     * Get Logged Users permissions
     * @param type $params
     * @return type
     */
    function getUserPermissions($params) {
        $objUserPermissions = new UsersPermissions ();
        return $objUserPermissions->where('users_id', $params['id'])->get();
    }
    
    /**
     * Logged in users deatils
     * @param type $params
     * @return type
     */
    function getUsersDetails($params) {
        $objUsers = new Users();
        return $objUsers->where('id', $params['id'])->get();
    }

    /**
     * This is for to Add/Update the Users informations into users table
     * @params $params;
     * @response $result as object
     */
    function saveUser($params) {
        unset($params['permissions']);
        $objUser = new Users();
        $result = [];
        $objUser->dbTransaction();
        try {
            $result = $objUser->saveRecord($params);
            $objUser->dbCommit();
        } catch (\Exception $ex) {
            $objUser->dbRollback();
        }
        return $result;
    }

    /**
     * Get Color Codes list
     * @return array
     */
    function getColors() {
        $objUsers = new Users();
        $sql = "SELECT * FROM color_codes";
        $dbResult = $objUsers->dbSelect($sql);
        $colorCode = [];
        foreach ($dbResult as $row) {
            $colorCode[] = $row->color;
        }

        return $colorCode;
    }
    
    /**
     * Set Color code for new users
     * 
     * @param type $user_id
     */
    function setUsersColorCode($user_id) {

        $objUsers = new Users();
        $objUsers->dbTransaction();
        try {
            if ($user_id) {

                $dbResult = $objUsers->get(['color_code'])->toArray();
                foreach ($dbResult as $value) {
                    if (!empty($value['color_code']))
                        $dbColor[] = $value['color_code'];
                }

                $colroCodes = $this->getColors();

                $userCount = $objUsers->count();
                $posisions = 0;

                if ($userCount > count($colroCodes)) {
                    $posisions = fmod($userCount, count($colroCodes));
                } else {
                    $posisions = $userCount;
                }
                
                $diffColor = $colroCodes[$posisions];

                if (!empty($diffColor)) {
                    $saveData['id'] = $user_id;
                    $saveData['color_code'] = $diffColor;
                    $objUsers->saveRecord($saveData);
                }
                $objUsers->dbCommit();
            }
        } catch (\Exception $ex) {
            $objUsers->dbRollback();
        }
    }

    function addTestUser() {
        $count = 50;
        $objUser = new Users();
        $first = 0;
        $last = 1;
        for ($i = 0; $i <= $count; $i++) {
            if ($last == 10) {
                $first++;
                $last = 1;
            }
            $data['firstname'] = $first . 'Admin';
            $data['departments_id'] = 1;
            $data['lastname'] = $last . 'User';
            $data['is_register'] = '1';
            $data['status'] = '1';
            $data['email'] = 'admin@' . $i . '.com';
            $user = $objUser->saveRecord($data);
            $this->setUsersColorCode($user->id);
            $last++;
        }
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function saveSubDepartments($command) {
        $params = $command->dataToArray();        
        $arrCreatedInfo = $command->getCreatedInfo();
        $objUsers = new Users();
        $objUser = $objUsers->findRecord($params['id']);
        if (isset($params['sub_departments'])) {
            $this->saveUsersSubDepartments($params['sub_departments'], $arrCreatedInfo, $params['id'], $params['departments_id']);
        }

        return $objUser;
    }
    /**
     * 
     * @param array $arrDepartments
     * @param array $arrCreatedInfo
     * @param type $intUserId
     * @param type $intPrimDeptId
     * @return boolean
     */
    function saveUsersSubDepartments(array $arrDepartments, array $arrCreatedInfo, $intUserId = 0, $intPrimDeptId = 0) {
        $arrExisting = $arrNewIds = $arrUpdate = $arrInsertId = $arrSaveData = array();
        $objUserDepartments = new UsersSubDepartments();

        //delete if not exist
        $objUserDepartments->where('users_id', $intUserId)
                ->where(function($query) use($arrDepartments) {
                    if (!empty($arrDepartments)) {
                        $query->whereNotIn('sup_departments_id', $arrDepartments);
                    }
                })->delete();

        $objResult = $objUserDepartments->where('users_id', $intUserId)->select('sup_departments_id')->get();
        foreach ($objResult as $objRow) {
            $arrExisting[] = $objRow->sup_departments_id;
        }

        $arrNewIds = array_diff($arrDepartments, $arrExisting);
        $arrUpdate = array_intersect($arrDepartments, $arrExisting);

        //insert
        foreach ($arrNewIds as $intDepartmentId) {

            $arrSaveData['users_id'] = $intUserId;
            $arrSaveData['sup_departments_id'] = $intDepartmentId;
            $arrSaveData['primary_departments_id'] = $intPrimDeptId;
            $arrInsertId [] = array_merge($arrSaveData, $arrCreatedInfo);
        }

        $objUserDepartments->insertMultiple($arrInsertId);

        //update
        foreach ($arrUpdate as $intDepartmentId) {
            $objUserDepartments->where('users_id', $intUserId)
                    ->where('sup_departments_id', $intDepartmentId)
                    ->update(['users_id' => $intUserId, 'sup_departments_id' => $intDepartmentId, 'primary_departments_id' => $intPrimDeptId]);
        }
        unset($arrSaveData, $arrInsertId, $arrUpdate, $arrExisting, $arrNewIds);
        return true;
    }
    /**
     * Get the active roles
     * @param Users $objUser
     * @return obj
     */
    function getGlobalRoles(Users $objUser) {

        $sql = "select id, name from roles where status = '1' order by name asc";
        $objUser = $objUser->dbSelect($sql);

        foreach ($objUser as &$value) {
            $value->id = $value->id;
            $value->name = $value->name;
        }
        return $objUser;
    }
    /**
     * Resend the user activations link
     * @param array $params
     * @return array
     */
    function resendActivationLink($params) {
        $objUsers = new Users();
        $objUsers->dbTransaction();
        try {
            $userInfo = $objUsers->where('id', $params['id'])->first();
            
            if ($userInfo) {               
                $objMailer = new UsersMailer();
                $userMail = $objMailer->registrationEmail($userInfo);
                
                if (true) {
                    $acitvateExptime = date('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . '+2 days'));
                    $data = ['id' => $userInfo->id, 'activate_exp_time' => $acitvateExptime, 'is_register' => '0'];
                    $objUsers->saveRecord($data);
                    $objUsers->dbCommit();
                    $response = ['status' => true, 'message' => 'Activation link send successfully'];
                } else {
                    $response =  ['status' => false, 'message' => 'Failure to send Activations link'];
                }
            } else {
                $response =  ['status' => false, 'message' => 'User is not exists'];
            }
        } catch (\Exception $ex) {
            $objUsers->dbRollback();
            $response =  ['status' => false, 'message' => $ex->getMessage()];
        }
        return $response;
    }
    /**
     * Add Role based permissions, when user first time login into applications
     * Command Class : SaveSSOUserPermissions
     * @param Object $command
     * @return Bollean
     */
    function saveSSOUserPermissions($command) {
        $status = false;
        DB::beginTransaction();
        try {
            $params = $command->dataToArray();
            $objUsers = new Users();
            /**
             * Command Class : GetRoleHeaders
             * Get Permissions
             */
            $objRoleDs = new GetRoleHeaders(array('id' => $params['roles_id']));
            $rolePerms = CommandFactory::getCommand($objRoleDs);
            
            $arrPerms = [];
            if (!empty($rolePerms)) {
                $systemPermissions = isset($rolePerms['system_permissions']) ? $rolePerms['system_permissions'] : [];
                foreach ($systemPermissions as $row) {
                    $arrPerms[] = ['id' => $row->permissions_id, 'permission' => $row->permission];
                }
                
                if (!empty($arrPerms)) {
                    $command->permissions = $arrPerms;
                    $command->users_id = $command->id;
                    $params['is_first_login'] = '0';
                    $userInfo = $objUsers->saveRecord($params);
                    if ($userInfo) {
                        $permissions = $this->savePermissions($command);
                    }
                    $status = true;
                }
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
        }

        return ['status' => $status];
    }
    /**
     * Set User Account Inactive, If there is no activity last six months
     * @return boolean
     */
    function setUsersAccInActive() {
        DB::beginTransaction();
        $status = false;
        $arrUsers = [];
        try {            
            $objUsers = new Users();
            $dbResult = $objUsers->dbTable('u')
                                 ->leftJoin('users_logs as ul', 'u.id', '=', 'ul.users_id')
                                 ->select('u.id')
                                 ->where('status', '1')
                                 ->havingRaw('MAX(DATE(ul.login_time)) < CURRENT_DATE() - INTERVAL 6 MONTH OR MAX(DATE(ul.login_time)) IS NULL')
                                 ->groupBy('u.id')
                                 ->orderBy('u.id', 'asc')
                                 ->get()
                                 ->toArray();
            if (!empty($dbResult)) {
                foreach ($dbResult as $data) {
                    $arrUsers[] = $data->id;
                }
                
                if (!empty($arrUsers)) {
                    $objUsers->whereIn('id', $arrUsers)
                             ->update(['status' => '0', 'updated_at' => PiLib::piDate(),
                                       'ip_address' => Request::getClientIp()]);
                }
                $status = true;
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        
        return $status;
    }
    /**
     * Set Non visible columns , login with user name and password
     * @return array
     */
    function getNonvisibleColumns() {
        $userData = [];
        $arrColumns = [];
        if (Auth::check()) {
            $userData = Users::find(Auth::user()->id)->toArray();
            if (isset($userData['login_from_sso']) && $userData['login_from_sso'] == '1') {
                $objItemsDs = new ItemsDataSource();
                $dbColumns = $objItemsDs->getItemDefaultHeaders($type = 0);
                foreach ($dbColumns as $key => $data) {
                    if (in_array($key, $this->nonVisibleColumns)) {
                        $arrColumns[$key] = ['id' => $data['id'], 'column' => trim($data['column']), 'name' => $data['name']];
                    }
                }
            }
        }
        return $arrColumns;
    }

}
