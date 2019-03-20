<?php

namespace CodePi\Base\Eloquent;


use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model {
    use PiEloquent;

    protected $table = 'settings';
    public $timestamps = false;
    protected $fillable = array(
        'object_key',
        'object_type',
        'object_string',
        'object_enum',
        'object_int',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

    static function key($key = 'stop_outgoing_emails') {
        $results = self::where('object_key', '=', $key)->get();


        //$type = 'yes';

        $type = true;
        if (!empty($results)) {
            foreach ($results as $l => $m) {
                $object_key = "object_" . $m->object_type;
                //$type = $m->{$object_key};
		$type = ($m->{$object_key} == '1') ? true : false;
            }
        }

        return $type;
    }

    
    static function getSettings($arrKeys = ['stop_outgoing_emails']) {
        $results = self::whereIn('object_key', $arrKeys)->get();
//        print_r($results);exit;
//        $type = 'yes';
        if (!empty($results)) {
            foreach ($results as $l => $m) {
                $object_key = "object_" . $m->object_type;                    
                $type[$m->object_key] = $m->{$object_key};                   
            }
        }
        return $type;
    }


}
