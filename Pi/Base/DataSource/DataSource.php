<?php

namespace CodePi\Base\DataSource;

abstract class DataSource {

    /**
     *
     * @param array $data        	
     * @param string $attribute        	
     * @return mixed
     */
    protected $model;

    function __construct() {
        $model = $this->model();
        $this->model = new $model (); //\App::make($model);// 
    }

    abstract function model();

    public function save(array $data, $attribute = "id") {
        if (isset($data [$attribute]) && !empty($data [$attribute])) {
            return $this->update($data, $data [$attribute], $attribute);
        } else {
            
            return $this->create($data);
        }
    }

    public function create(array $data) {
       return  $this->model->create($data);
       
         
       
    }

    /**
     *
     * @param array $data        	
     * @param
     *        	$id
     * @param string $attribute        	
     * @return mixed
     */
    public function update(array $data, $id, $attribute = "id") {
        return $this->model->where($attribute, '=', $id)->first()->update($data);
    }

    /**
     *
     * @param
     *        	$id
     * @return mixed
     */
    public function find($id) {
        return $this->model->find($id);
    }

    /**
     *
     * @param
     *        	$id
     * @return mixed
     */
    public function delete($id) {
        return $this->model->destroy($id);
    }

    /**
     *
     * @param
     *        	$sql
     * @return mixed
     */
    public function dbSelect($sql) {
        return \DB::select($sql);
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

}
