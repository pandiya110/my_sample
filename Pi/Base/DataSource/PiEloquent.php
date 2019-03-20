<?php

namespace CodePi\Base\DataSource;

trait PiEloquent {
    public $parent_id;
    public $parent_value;
    public $child_id;
    public $child_value;
    
    public function saveRecord(array $data, $attribute = "id", $first='true') {
//       echo "<pre>";print_r($data);exit;
        if (isset($data [$attribute]) && !empty($data [$attribute])) {
            return $this->updateRecord($data, $data [$attribute], $attribute, $first);
        } else {
            return $this->createRecord($data);
        }
    }

    public function createRecord(array $data) {
        $objEloquent = new static();
        unset($data['id']); //need to add otherwise it is throwing error as id should not empty
        return $objEloquent->create($data);
    }

    /**
     *
     * @param array $data        	
     * @param
     *        	$id
     * @param string $attribute        	
     * @return mixed
     */
    public function updateRecord(array $data, $id, $attribute = "id", $first='true') {
        $objEloquent = new static();
        if($first){
            $objEloquent->where($attribute, '=', $id)->first()->update($data);
        }else{
            $objEloquent->where($attribute, '=', $id)->update($data);
        }
        
        return $this->findRecord($id);
    }
    
    /**
     *
     * @param array $data        	
     * @param
     *        	$id
     * @param string $attribute        	
     * @return mixed
     */
    public function updateRecordCustom(array $data, $where = []) {
        $objEloquent = new static();
        $objEloquent->where($where)->update($data);
        
        return true;
    }

    /**
     *
     * @param
     *        	$id
     * @return mixed
     */
    public function findRecord($id) {
        $objEloquent = new static();
        return $objEloquent->find($id);
    }

    /**
     *
     * @param
     *        	$id
     * @return mixed
     */
    public function deleteRecord($id) {
        $objEloquent = new static();
        //$objEloquent->deleted_at = date('Y-m-d H:i:s');
        return $objEloquent->destroy($id);
    }

    /**
     *
     * @param $sql
     * @param $bindings
     * @return mixed
     */
    public function dbSelect($sql, $bindings = []) {
        return \DB::select($sql, $bindings);
    }

    /**
     *
     * @param
     *        	$sql
     * @return mixed
     */
    public function dbInsert($sql) {
        return \DB::Insert($sql);
    }

    /**
     *
     * @param
     *        	$sql
     * @return mixed
     */
    public function dbUpdate($sql) {
        return \DB::update($sql);
    }

    /**
     *
     * @param
     *        	$sql
     * @return mixed
     */
    public function dbDelete($sql) {
        return \DB::delete($sql);
    }

    /**
     *
     * @param
     *          $sql
     * @return mixed
     */
    public function dbStatement($sql) {
        return \DB::statement($sql);
    }

    /**
     *
     * @param
     *          $alias
     * @return mixed
     */
    public function dbTable($alias = '') {
        $objEloquent = new static();
        $table = $objEloquent->getTable();
        if ($alias != '') {
            return \DB::table($table . ' as ' . $alias);
        }

        return \DB::table($table . ' as ' . $table);
    }

    public function dbRaw($sql) {
        return \DB::raw($sql);
    }

    public function deleteWithConditions($options = []) {
        if (empty($options)) {
            return false;
        }

        $objEloquent = new static();
        $queryBuilder = '';
        foreach ($options as $key => $value) {
            $queryBuilder = $objEloquent->where($key, $value);
        }
        if ($queryBuilder != '') {
            $queryBuilder->delete();
        }
    }

    public function insertMultiple($data = []) {
        if (empty($data)) {
            return false;
        }
        $objEloquent = new static();
        $objEloquent->insert($data);
    }
    
    
    public function updateMultiple(array $data, $idArr, $attribute = "id") {
        $objEloquent = new static();
        if (empty($data) || empty($idArr)) {
            return false;
        }
        $objEloquent->whereIn($attribute, $idArr)->update($data);
    }

//    public function restoreRecord($data=[]){
//        $objEloquent = new static();
//        $queryBuilder='';
//        $objData='';
//        foreach ($options as $key => $value) {
//            $queryBuilder=$objEloquent->where($key, $value);
//        }
//        if($queryBuilder!=''){
//            $objData = $queryBuilder->onlyTrashed()->first();
//        }
//        if(isset($objData) && $objData->id!=''){
//            $queryBuilder->onlyTrashed()->restore();
//        }
//    }

    public function forceDeleteRecord($id) {
        $objEloquent = new static();
        return $objEloquent->where('id', $id)->forceDelete();
    }

    public function dbUnprepared($sql) {
        return \DB::unprepared($sql);
    }

//    function insertTransactions() {
//        $objEloquent = new static();
//        $command = new \CodePi\Base\Commands\BaseCommand();
//        $arrCreateInfo = $command->getCreatedInfo();
//        $existIds = [];
//        $prepareInsertData = [];
//        $arrResult = $objEloquent->where($this->parent_id, $this->parent_value)
//                ->select($this->child_id)
//                ->whereIn($this->child_id, $objEloquent->child_value)
//                ->get()
//                ->toArray();
//
//        
//        if (!empty($arrResult)) {
//            foreach ($arrResult as $row) {
//                $existIds[] = $row[$this->child_id];
//            }
//        }
//
//        $insertIds = array_diff($this->child_value, $existIds);
//
//        
//        if (!empty($insertIds)) {
//            foreach ($insertIds as $id) {
//                $prepareInsertData[] = array_merge(array($this->parent_id => $this->parent_value,
//                    $this->child_id => $id), $arrCreateInfo);
//            }
//            $objEloquent->insertMultiple($prepareInsertData);
//        }
//
//        $objEloquent->where($this->parent_id, $this->parent_value)
//                ->whereNotIn($this->child_id, $this->child_value)
//                ->delete();
//    }
    
    public function dbTransaction() {
        return \DB::beginTransaction();
    }

    public function dbCommit() {
        return \DB::commit();
    }

    public function dbRollback() {
        return \DB::rollback();
    }

}
