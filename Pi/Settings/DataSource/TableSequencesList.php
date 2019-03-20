<?php

namespace CodePi\Settings\DataSource;

class TableSequencesList {

    /**
     * get all users_log details. 
     * @param $data
     * @return array $users
     */
    function tableSequencesData($data) {
        $sortBy = $data['sortBy'];
        $orderBy = $data['order'];
        //echo "<pre>";print_r($data);exit;
        // $sortBy  = 'admin';
        // $orderBy = 'asc';        
        if (isset($sortBy) && $sortBy != '') {
            $tables = \DB::select("SELECT table_schema,table_name FROM information_schema.tables WHERE table_schema = '" . $sortBy . "' and table_type = 'BASE TABLE' order by table_name " . $orderBy);
        } else {
            $schemas = \DB::select("select schema_name from information_schema.schemata where schema_owner != 'postgres' ");
            foreach ($schemas as $schema) {
                $sortBy = $schema->schema_name;
                $tables = \DB::select("SELECT table_schema,table_name FROM information_schema.tables WHERE table_schema = '" . $sortBy . "' and table_type = 'BASE TABLE' order by table_name " . $orderBy);
            }
        }
        return $this->formatData($tables);
    }
    /**
     * 
     * @param type $data
     * @return type
     */
    function formatData($data) {      
        $result = [];
        $seq = $this->findAllSequnces();
        foreach ($data as $key => $table) {
            $seqname = $table->table_name . '_id_seq';
            $schema = $table->table_schema;
            $tableName = $table->table_name;

            if (in_array($seqname, $seq)) {
                //$seq_val = \DB::select("select currval('".$table->table_schema.".".$seqname."')");
                $seq_val = \DB::select("select last_value FROM " . $schema . "." . $seqname);
                $result[$key]['seq_val'] = $seq_val[0]->last_value;

                $max_value = \DB::select("select max(id) as max_id from " . $schema . "." . $tableName);
                $result[$key]['max_val'] = $max_value[0]->max_id;
            } else {
                $result[$key]['seq_val'] = '';
                $result[$key]['max_val'] = '';
            }
            $result[$key]['schema'] = $schema;
            $result[$key]['tableName'] = $tableName;
        }
        return $result;
    }
    /**
     * 
     * @return type
     */
    function findAllSequnces() {
        $seq = [];
        $sequnces = \DB::select("SELECT c.relname FROM pg_class c WHERE c.relkind = 'S'");
        foreach ($sequnces as $sequnce) {
            $seq[] = $sequnce->relname;
        }
        return $seq;
    }
    /**
     * 
     * @param type $data
     * @return boolean
     */
    function updateSequences($data) {
        // echo "<pre>";print_r($data);exit;
        $table = $data['schema'] . "." . $data['tableName'];
        $seq = $data['schema'] . "." . $data['tableName'] . '_id_seq';
        \DB::select("select setval('" . $seq . "' , (SELECT MAX(id) FROM " . $table . ")+1 )");
        return true;
    }
    /**
     * 
     * @param type $data
     * @return type
     */
    function listSchema($data) {
        $schemas = \DB::select("select schema_name from information_schema.schemata where schema_owner != 'postgres' ");
        foreach ($schemas as $schema) {
            $Arrschema[] = $schema->schema_name;
        }
        return $Arrschema;
    }

}
