<?php

namespace CodePi\Settings\DataSource;

use CodePi\Base\Eloquent\CronsList;

class ListCronsDataSource {

    public $cronslistAll = array();
    public $nameClass;

    public function __construct() {
        
    }

    /**
     * 
     * @param type $data
     * @return type
     */
//    function listCrons($data) {
//        // $schemas = \DB::select("select schema_name from information_schema.schemata where schema_owner != 'postgres' ");
//        $arr_crons = array(
//                        ['name' => 'cron1', 'action' => ''],
//                        ['name' => 'cron1', 'action' => ''],
//            );
//        return $arr_crons;
//    }
    /**
     * 
     * @param type $data
     * @return type
     */
    function listCrons($data) {
        $objCronsList = new CronsList();
        $sql = 'SELECT * FROM crons_list AS cl WHERE cl.`status` = \'1\' ORDER BY id ASC';
        $objClist = $objCronsList->dbSelect($sql);
        $result = array();
        if ($objClist) {
            foreach ($objClist as $clist) {
                $result[] = array('cron_name' => $clist->cron_name, 'cron_code' => $clist->cron_code);
            }
        }

        return $result;
    }
    /**
     * 
     * @param type $command
     */
    public function cronsHandleManual($command) {
        $params = $command->dataToArray();
        $nameClass = $params['cron_code'];
        $cname = 'App\\Console\\Commands\\' . $nameClass;
        if (!empty($nameClass)) {
            $objConsoleCmd = new $cname;
            $objConsoleCmd->handle();
        }
    }

}
