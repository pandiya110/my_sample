<?php

namespace CodePi\Base\DataSource;

use DB;
use CodePi\Base\Exceptions\SystemException;
use Illuminate\Database\QueryException;

trait QueryBuilder {

    private $select = array();
    private $where = array();
    private $join = array();
    private $orderBy = array();
    private $ascOrDesc = array();
    private $groupBy = array();
    private $limit = NULL;
    private $query;

    function clearParams() {
        $this->select = array();
        $this->where = array();
        $this->join = array();
        $this->orderBy = array();
        $this->ascOrDesc = array();
        $this->groupBy = array();
        $this->limit = NULL;
        $this->from=NULL;
        $this->query = '';
        return $this;
    }

    function select($params) {
        $this->select[] = $params;
        return $this;
    }

    function from($from) {
    	$this->from = $from;
    	return $this;
    } 
    
    function joins($params) {
        $this->joins[] = $params;
        return $this;
    }

    function where($params) {
        $this->where[] = $params;
        return $this;
    }

    function orderBy($params) {
        $this->orderBy[] = $params;
        return $this;
    }

    function ascOrDesc($params) {
        $this->ascOrDesc[] = $params;
        return $this;
    }

    function groupBy($params) {
        $this->groupBy[] = $params;
        return $this;
    }

    function limit($params) {
        $this->limit = $params;
        return $this;
    }

    function getQuery() {

        return $this->query;
    }

    function getSelect($isSelect = TRUE) {

        if ($isSelect)
            $select = ' select  ';
        else
            $select = ' delete  ';

        if (empty($this->select) && $isSelect) {
            $select.= " * ";
        } else {
            foreach ($this->select as $fieldLabels) {
                $select.=$fieldLabels . ", ";
            }

            $select = trim($select, ", ");
        }

        $select.=" from ";

        return $select;
    }

    function getJoin() {

        return static::getJoin();
    }

    function getWhere() {

        $where = " ";

        if (empty($this->where)) {
            
        } else {
            $where.= " where ";
            foreach ($this->where as $whereQuery) {
                $where.=$whereQuery . "  ";
            }
        }
        return $where;
    }

    function getOrderBy() {

        $orderBy = "";
        if (!empty($this->orderBy)) {
            $orderBy.="order by ";
            foreach ($this->orderBy as $l => $m) {
                $orderBy.= $m . " ";
                $orderBy.= isset($this->ascOrDesc[$l]) ? $this->ascOrDesc[$l] : '' . ", ";
            }
            $orderBy = trim($orderBy, ", ");
        }
        $orderBy .= " ";
        return $orderBy;
    }

    function getGroupBy() {
        $groupBy = NULL;
        if (!empty($this->groupBy)) {
            $groupBy.="group by ";
            foreach ($this->groupBy as $l => $m) {
                $groupBy.= $m . ", ";
            }
            $groupBy = trim($groupBy, ", ");
        }
        $groupBy .=" ";
        return $groupBy;
    }

    function getLimit() {
        $limit = NULL;
        if (NULL != $this->limit) {
            $limit .= "limit " . $this->limit;
        }
        return $limit;
    }

    function buildQuery($isSelect = TRUE) {

        $select = $this->getSelect($isSelect);
        $join = $this->getJoin();
        $where = $this->getWhere();
        $orderBy = $this->getOrderBy();
        $groupBy = $this->getGroupBy();
        $limit = $this->getLimit();
        $this->query = $select . $join . $where . $groupBy . $orderBy . $limit;
//        echo $this->query;exit;
        try {
            if ($isSelect)
                $objResult = DB::select($this->query);
            else
                $objResult = DB::delete($this->query);
        } catch (QueryException $qe) {
            throw new SystemException($qe->getMessage());
        }
        return $objResult;
    }

}
