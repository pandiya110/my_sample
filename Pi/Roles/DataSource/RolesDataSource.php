<?php

namespace CodePi\Roles\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\Roles;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Eloquent\RolesItemsHeaders;
use CodePi\Base\Eloquent\RolesPermissions;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\Base\Eloquent\Users;
use Auth;
use CodePi\Base\Eloquent\UsersPermissions;
class RolesDataSource {
    /**
     * Add/Update Roles
     * @param object $command
     * @return object
     */
    function saveRoles($command) {
        $objRoles = new Roles();
        $objRoles->dbTransaction();
        $saveDetails = [];
        try {
            $params = $command->dataToArray();
            $saveDetails = $objRoles->saveRecord($params);
            $objRoles->dbCommit();
        } catch (\Exception $ex) {
            $objRoles->dbRollback();
        }

        return $saveDetails;
    }

    /**
     * Get Role list
     * @param object $command
     * @return collection
     */
    function getRolesList($params) {
        $totalCount = 0;
        $objRoles = new Roles();
        $userData = Users::find(Auth::user()->id)->toArray();
        
        $objRoles = $objRoles->dbTable('r')
                             ->leftJoin('roles_items_headers as rih', 'rih.roles_id', '=', 'r.id')
                             ->leftJoin('items_headers as ih', 'ih.id', '=', 'rih.items_headers_id')
                             ->leftJoin('master_data_options as mdo', 'rih.masters_color_id', '=', 'mdo.id')
                             ->select('r.id as role_id', 'r.name as role_name', 'rih.id as role_header_id', 'rih.headers_alias_name', 'r.status', 'ih.id as headers_id', 'r.description', 'mdo.id as color_id', 'mdo.name as color_code')
                             ->where(function($query)use($params) {
                                    if (isset($params['id']) && !empty($params['id'])) {
                                        $query->where('r.id', $params['id']);
                                    }
                             })->where(function($query)use($params) {
                                if (isset($params['search']) && trim($params['search']) != '') {
                                    $query->whereRaw("r.name like '%" . str_replace(" ", "", $params['search']) . "%' ");
                                }
                            })->where(function($query)use($params) {
                                if (isset($params['status']) && trim($params['status']) != '') {
                                    $query->where('r.status', $params['status']);
                                }
                            })->where(function($query)use($userData) {
                                if (isset($userData['is_first_login']) && $userData['is_first_login'] == '1') {
                                    $adminRoleID =  config('smartforms.adminRoleId');
                                    $query->where('r.id', '!=', $adminRoleID);
                                }
                            });
                            if (isset($params['sort']) && !empty($params['sort'])) {
                                $objRoles->orderBy('r.name', $params['sort']);
                            } else {
                                $objRoles->orderBy('r.last_modified', 'desc');
                            }
                            if (isset($params['page']) && !empty($params['page'])) {
                                $objRoles = $objRoles->paginate($params['perPage']);
                                $totalCount = $objRoles->total();
                            } else {
                                $objRoles = $objRoles->get();
                            }
                        $objRoles->totalCount = $totalCount;
        return $objRoles;
    }

    /**
     * Get Role Details view 
     * @param object $command
     * @return collection
     */
    function getRolesDetails($command) {
        $params = $command->dataToArray();
        $objRoles = new Roles();
        $objRoles = $objRoles->dbTable('r')
                        ->leftJoin('roles_items_headers as rih', 'rih.roles_id', '=', 'r.id')
                        ->leftJoin('items_headers as ih', 'ih.id', '=', 'rih.items_headers_id')                        
                        ->leftJoin('master_data_options as mdo', 'rih.masters_color_id', '=', 'mdo.id')
                        ->select('r.id as role_id', 'r.name as role_name', 'rih.id as role_header_id', 'rih.headers_alias_name',  'r.status', 'ih.id as headers_id', 'r.description', 'mdo.id as color_id', 'mdo.name as color_code', 'rih.headers_order_no')
                        ->where('r.id', $params['id'])->orderBy('headers_order_no', 'asc')->get();
        
        return $objRoles;
    }
   /**
     * Save Itemgrid Headers Role level
     * @param obj $command
     * @return boolean
     */
    function saveRoleItemsHeaders2($command) {

        $params = $command->dataToArray();
        $objRoleHeader = new RolesItemsHeaders();
        $arrCreatedInfo = $command->getCreatedInfo();
        $arrRoleHeaders = $arrRoleHeadersID = $arrNew = $arrSaveData = $arrExisting = [];
        $objRoleHeader->dbTransaction();
        
        try {

            
            $data_chk = $objRoleHeader->where('roles_id', $params['id'])->select('id')->limit(1)->get();
          $hdr_cnt = count($data_chk);
          $i = 1;
          
            foreach ($params['headers'] as $row) {
                //print_r($row);exit;
                if ( $hdr_cnt>0) {
                    
                    $arrRoleHeaders_val = ['roles_id' => $params['id'], 
                        //'items_headers_id' => $row['headers_id'],
                        'headers_alias_name' => $row['alias_name'],
                        'masters_color_id' => $row['color_code_id'],
                        'headers_order_no' => $i,
                        'status' => ($row['isChecked'])?'1':'0'
                    ];
                    
                    $objRoleHeader->where('roles_id', $params['id'])
                            ->where('items_headers_id', $row['headers_id'])
                            ->update($arrRoleHeaders_val);
                   
                }else {
                    
                    $arrRoleHeaders[] = ['roles_id' => $params['id'], 
                        'items_headers_id' => $row['headers_id'],
                        'headers_alias_name' => $row['alias_name'],
                        'masters_color_id' => $row['color_code_id'],
                        'headers_order_no' => $i,
                        'status' => ($row['isChecked'])?'1':'0'
                    ]; 
                     
                }
                 $i++;
            }
            if(count($arrRoleHeaders)){
            $objRoleHeader->insert($arrRoleHeaders);
            }
           
            $objRoleHeader->dbCommit();
            unset($arrRoleHeadersID, $arrRoleHeaders, $arrExisting, $arrNew, $arrUpdate);
        } catch (\Exception $ex) {
            $objRoleHeader->dbRollback();
        }
        return true;
    }
    /**
     * Save Itemgrid Headers Role level
     * @param obj $command
     * @return boolean
     */
    function saveRoleItemsHeaders($command) {

        $params = $command->dataToArray();
        $objRoleHeader = new RolesItemsHeaders();
        $arrCreatedInfo = $command->getCreatedInfo();
        $arrRoleHeaders = $arrRoleHeadersID = $arrNew = $arrSaveData = $arrExisting = [];
        $objRoleHeader->dbTransaction();
        try {
            $i = 1;
            foreach ($params['headers'] as $row) {
                if ($row != '') {
                    $arrRoleHeaders[$row['headers_id']] = ['roles_id' => $params['id'], 
                                                           'items_headers_id' => $row['headers_id'],
                                                           'headers_alias_name' => $row['alias_name'], 
                                                           'masters_color_id' => $row['color_code_id'], 
                                                           'headers_order_no' => $i, //isset($row['order_id'])?$row['order_id']: NULL, 
                                                           'status' => isset($row['isChecked']) && ($row['isChecked'] == true) ? '1' : '0'
                    ];
               }
            $i++;}
            
            $arrRoleHeadersID = array_keys($arrRoleHeaders);

            /**
             * Delete if not exists
             */
            $objRoleHeader->where('roles_id', $params['id'])
                    ->where(function($query) use($arrRoleHeadersID) {
                        if (!empty($arrRoleHeadersID)) {
                            $query->whereNotIn('items_headers_id', $arrRoleHeadersID);
                        }
                    })->delete();

            $objResult = $objRoleHeader->where('roles_id', $params['id'])->select('items_headers_id')->get();
            foreach ($objResult as $objRow) {
                $arrExisting[] = $objRow->items_headers_id;
            }

            $arrNew = array_diff($arrRoleHeadersID, $arrExisting);
            $arrUpdate = array_intersect($arrRoleHeadersID, $arrExisting);

            /**
             * Insert new Ad Types
             */
            if (!empty($arrNew)) {
                foreach ($arrNew as $intHeaderId) {
                    $arrSaveData[] = array_merge($arrRoleHeaders[$intHeaderId], $arrCreatedInfo);
                }
                $objRoleHeader->insertMultiple($arrSaveData);
            }
            /**
             * Update Exists Ad Types
             */
            
            if (!empty($arrUpdate)) {
                foreach ($arrUpdate as $value) {
                    unset($arrCreatedInfo['created_by'], $arrCreatedInfo['date_added'], $arrCreatedInfo['gt_date_added']);            
                    $objRoleHeader->where('roles_id', $params['id'])->where('items_headers_id', $value)->update(array_merge($arrRoleHeaders[$value], $arrCreatedInfo));
                }
            }
            $objRoleHeader->dbCommit();
            unset($arrRoleHeadersID, $arrRoleHeaders, $arrExisting, $arrNew, $arrUpdate);
        } catch (\Exception $ex) {
            
            $objRoleHeader->dbRollback();
        }
        return true;
    }

    /**
     * Get Color dropdwon in admin role section
     * @return array
     */
    function getRoleColourCodeDropdown() {
        $objItemsDs = new ItemsDataSource();
        $module_id = 3;
        $arrResult = $objItemsDs->getMasterDataOptions($module_id);
        $arrColour = [];
        $status = false;
        if (!empty($arrResult)) {
            foreach ($arrResult as $key => $row) {
                $arrColour[] = ['id' => $key, 'value' => $row];
            }
            $status = true;
        }
        
        return ['colour' => $arrColour, 'status' => $status];
    }
        
    /**
     * Save Role Permissions
     * @param obj $command
     * @return obj
     */
    function saveRolePermissions($command) {
        $params = $command->dataToArray();
        $arrCreatedInfo = $command->getCreatedInfo();
        $objRoles = new Roles();
        $objRoles = $objRoles->findRecord($params['roles_id']);
        if (isset($params['permissions'])) {
            $this->saveRoleSystemPermissions($params['permissions'], $arrCreatedInfo, $params['roles_id']);
        }

        return $objRoles;
    }
    /**
     * Save sytem role permissions
     * @param array $arrRolesPermissions
     * @param array $arrCreatedInfo
     * @param int $intRoleId
     * @return boolean
     */
    function saveRoleSystemPermissions(array $arrRolesPermissions, array $arrCreatedInfo, $intRoleId = 0) {
                
        $arrExisting = $arrNewIds = $arrUpdate = $arrSystemPermissions = $arrInsertId = array();
        $objRolesPermission = new RolesPermissions();
        $objRolesPermission->dbTransaction();
        try {
            foreach ($arrRolesPermissions as $row) {
                $permissions = array_values(array_filter($row['permission']));
                if (!empty($permissions)) {
                    $arrSystemPermissions[$row['id']] = ['permission' => array_pop($permissions)];
                }
            }

            $arrPermisionsId = array_keys($arrSystemPermissions);

            $objRolesPermission->where('roles_id', $intRoleId)
                    ->where(function($query) use($arrPermisionsId) {
                        if (!empty($arrPermisionsId)) {
                            $query->whereNotIn('permissions_id', $arrPermisionsId);
                        }
                    })->delete();

            $objResult = $objRolesPermission->where('roles_id', $intRoleId)->select('permissions_id')->get();
            foreach ($objResult as $objRow) {
                $arrExisting[] = $objRow->permissions_id;
            }

            $arrNewIds = array_diff($arrPermisionsId, $arrExisting);
            $arrUpdate = array_intersect($arrPermisionsId, $arrExisting);

            foreach ($arrNewIds as $intPermissionId) {
                if (isset($arrSystemPermissions[$intPermissionId])) {
                    $arrSystemPermissions[$intPermissionId]['roles_id'] = $intRoleId;
                    $arrSystemPermissions[$intPermissionId]['permissions_id'] = $intPermissionId;
                    $arrInsertId [] = array_merge($arrSystemPermissions[$intPermissionId], $arrCreatedInfo);
                }
            }

            $objRolesPermission->insertMultiple($arrInsertId);

            foreach ($arrUpdate as $intPermissionId) {
                if (isset($arrSystemPermissions[$intPermissionId])) {
                    $objRolesPermission->where('roles_id', $intRoleId)->where('permissions_id', $intPermissionId)->update($arrSystemPermissions[$intPermissionId]);
                }
            }
            /**
             * Set role permissions in users
             */
            $this->setUsersPermissionsByRole($arrRolesPermissions, $intRoleId, $arrCreatedInfo);
            
            $objRolesPermission->dbCommit();
            unset($arrSystemPermissions, $arrInsertId, $arrUpdate, $arrExisting, $arrPermisionsId, $arrNewIds);
            return true;
        } catch (\Exception $ex) {
            $objRolesPermission->dbRollback();
        }
    }

    /**
     * Get role permissions
     * @param obj $command
     * @return collection
     */
    function getRolePermissions($command) {
        $params = !is_array($command) ? $command->dataToArray() : $command;
        $arrResult = [];
        $objRoles = new Roles();
        $objRoles = $objRoles->findRecord($params['roles_id']);
        
        if (!empty($objRoles)) {
            $objRoles->system_permissions = $this->getSystemPermissions($objRoles);
        }
        
        return collect(array($objRoles));
    }
    /**
     * 
     * @param Roles $objRoles
     * @return obj
     */
    function getSystemPermissions($objRoles) {
        $objPermission = new Roles();
        $objPermissions = $objPermission->dbTable('r')
                ->join('roles_permissions as rp', 'r.id', '=', 'rp.roles_id')
                ->join('permissions as p', 'rp.permissions_id', '=', 'p.id')
                ->where('r.id', $objRoles->id)->where('p.status', '1')
                ->select('p.id as permissions_id', 'p.name', 'p.code', 'rp.permission')->get();
        
        foreach ($objPermissions as &$value) {
            $value->permission = [(int) $value->permission];
            $value->id = $value->permissions_id;
        }
        return $objPermissions;
    }
    /**
     * Get Role Headers list
     * @param type $roleID
     * @return collecyion
     */
    function getRolesHeaders($roleID) {
        $objHeaders = new ItemsHeaders();
        $objRoleHeaders = new RolesItemsHeaders();
        $arrData = [];
        $importSource = config('smartforms.importSource');
        $dbResult_1 = $objHeaders->dbTable('ih')
                                 ->leftJoin('roles_items_headers as rih', function($join) use($roleID) {
                                    $join->on('ih.id', '=', 'rih.items_headers_id')->where('rih.roles_id', '=', $roleID);
                                 })->select('ih.id as headers_id', 'rih.headers_order_no', 'ih.column_order', 'rih.status', 'ih.column_label AS column_name')
                                 ->selectRaw('ifnull(rih.headers_alias_name,ih.column_label) as alias_name')
                                 ->selectRaw('ifnull(rih.masters_color_id,8) as color_code_id')
                                 ->selectRaw('ifnull(headers_order_no,column_order) as order_no')
                                 ->where('ih.status', '1')
                                 ->where('ih.id', '!=', $importSource);
        $dbResult_2 = $objRoleHeaders->dbTable('rih')
                                     ->leftJoin('items_headers as ih', 'rih.items_headers_id', '=', 'ih.id')
                                     ->select('rih.items_headers_id as headers_id', 'rih.headers_order_no', 'ih.column_order as column_order','rih.status')
                                     ->selectRaw('"Channels" as column_name')
                                     ->selectRaw('ifnull(rih.headers_alias_name,ih.column_label) as alias_name')
                                     ->selectRaw('ifnull(rih.masters_color_id,8) as color_code_id')
                                     ->selectRaw('ifnull(headers_order_no,column_order) as order_no')                        
                                     ->where('rih.items_headers_id', '999')
                                     ->where('rih.roles_id', $roleID)
                                     ->unionAll($dbResult_1)
                                     ->orderBy('order_no' ,'asc')
                                     ->orderBy('column_order', 'asc')
                                     ->get();    

        return $dbResult_2;
    }
    
   /**
    * Get Role wise mapped columns and order sequence
    * @return array
    */
    function getRoleMappedHeaders($event_id) {
        $roles_id = 0;
        if (\Auth::check()) {
            $roles_id = \Auth::user()->roles_id;
        }else{
            $roles_id = config('smartforms.adminRoleId');
        }
        $objItemDs = new ItemsDataSource();
        $objHeaders = new ItemsHeaders();
        $arrResponse = [];
        
        $objHeaders = $objHeaders->dbTable('ih')
                        ->leftJoin('master_data_options as mdo', 'mdo.module_id', '=', 'ih.module_id')
                        ->leftJoin('roles_items_headers as rih', 'rih.items_headers_id', '=', 'ih.id')                        
                        ->select('ih.id', 'ih.column_name', 'is_editable', 'field_type', 'column_width', 'is_mandatory', 'format', 'is_copy', 'column_count', 'table_aliases_name', 'column_source', 'column_value', 'ih.module_id', 'headers_order_no as order_no')                       
                        ->selectRaw('ifnull(rih.headers_alias_name, ih.column_label) as column_label')->where('ih.status', '1')
                        ->selectRaw('(select m.name from master_data_options as m where m.id =  rih.masters_color_id) as color_code')
                        ->where('rih.status', '1')
                        ->where('rih.roles_id', $roles_id)
                        ->groupBy('ih.id')
                        ->orderBy('rih.headers_order_no', 'asc')->get();
        
        if (!empty($objHeaders)) {
            foreach ($objHeaders as $column) {
                $isEdit = ($column->is_editable == 1) ? TRUE : FALSE;
                $IsMandatory = ($column->is_mandatory == 1) ? TRUE : FALSE;
                $isFormat = ($column->format == 1) ? TRUE : FALSE;
                $IsCopy = ($column->is_copy == '1') ? TRUE : FALSE;
                $columnCount = ($column->column_count == '1') ? TRUE : FALSE;
                $arrResponse[$column->column_name] = ['id' => $column->id,
                    'column' => $column->column_name,
                    'color_code' => ($column->color_code) ? $column->color_code : '#dadada',
                    'channel_id' => '',
                    'name' => $column->column_label,
                    'IsEdit' => $isEdit,
                    'type' => $column->field_type,
                    'width' => $column->column_width,
                    'order_no' => $column->order_no,
                    'IsMandatory' => $IsMandatory,
                    'aliases_name' => $column->table_aliases_name,
                    'column_source' => $column->column_source, 'format' => $isFormat, 'IsCopy' => $IsCopy, 'columnCount' => $columnCount];

                if ($column->field_type == 'dropdown' && $column->column_name != 'grouped_item') {
                    $arrResponse[$column->column_name]['column_value'] = array_values($objItemDs->getMasterDataOptions($column->module_id));
                }
                
                if($column->field_type == 'dropdown' && $column->column_name == 'local_sources'){                   
                    $arrResponse[$column->column_name]['column_value'] = $objItemDs->getVendorSupplyOptions();
                }
                
                if($column->column_name == 'grouped_item' && !empty($event_id)){
                    $arrResponse[$column->column_name]['column_value'] = $objItemDs->getGroupNameByEventId($event_id);                
                }
            }
        }
        $data = $this->isChannelsAccess($roles_id);        
        $arrResponse = array_merge( array_values($arrResponse), $data);        
        return $arrResponse;
    }
    /**
     * Check channels is enabled or in admin role level
     * @param int $roleID
     * @return array
     */
    function isChannelsAccess($roleID) {
        
        $objRoleHeader = new RolesItemsHeaders();
        $arrData = [];
        $objRoleHeader = $objRoleHeader->where('roles_id', $roleID)->where('items_headers_id', '999')->get();
        if (!empty($objRoleHeader)) {
            foreach ($objRoleHeader as $row) {
                $arrData['channel'] = ['status' => $row->status, 'order_no' => $row->headers_order_no];
            }
        }
        return $arrData;
    }
    
    /**
     * Save role permissions into users permissions
     * @param array $arrPermissions
     * @param type $intRoleID
     * @param array $arrCreatedInfo
     */
    function setUsersPermissionsByRole(array $arrPermissions, $intRoleID, array $arrCreatedInfo) {

        $objUsers = new Users();
        $objUserPerms = new UsersPermissions();
        $objUserPerms->dbTransaction();
        try {
            $objUsers = $objUsers->dbTable('u')
                    ->join('roles as r', 'r.id', '=', 'u.roles_id')
                    ->select('u.id as users_id')
                    ->where('r.id', $intRoleID)
                    ->get();
            $arrData = [];
            if (!empty($objUsers)) {
                foreach ($objUsers as $row) {
                    $arrData[] = $row->users_id;
                }
            }
            
            foreach ($arrData as $intUserID) {
                $checkCount = $objUserPerms->where('users_id', $intUserID)->count();               
                if ($checkCount > 0) {
                    $objUserPerms->where('users_id', $intUserID)->delete();
                    foreach ($arrPermissions as $intPermissions) {
                        $permissions = array_values(array_filter($intPermissions['permission']));

                        if (!empty($permissions)) {
                            $arrSystemPermissions[] = array_merge(['users_id' => $intUserID, 'permissions_id' => $intPermissions['id'], 'permission' => array_pop($permissions)], $arrCreatedInfo);
                        }
                    }
                }
            }
            
            if (!empty($arrSystemPermissions)) {
                $objUserPerms->insertMultiple($arrSystemPermissions);
            }
            $objUserPerms->dbCommit();
        } catch (\Exception $ex) {
            $objUserPerms->dbRollback();
        }
    }

}
