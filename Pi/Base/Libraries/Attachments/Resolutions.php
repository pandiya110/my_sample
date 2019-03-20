<?php
namespace CodePi\Base\Libraries\Attachments;

use Illuminate\Database\Eloquent\Model;
use DB,Exception;

class Resolutions extends Model {

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'resolutions';
    protected $fillable = array(
    );

    static public function DeatailByName($size) {

        $Result = DB::select("select * from resolutions where name =? ", array($size));

        if (isset($Result[0]))
            return $Result[0];
        else
            throw new Exception("Resolution" . $size . " not Exists");
    }

    static public function DeatailById($Id) {
        $Result = DB::select("select * from resolutions where id =? ", array($Id));
        if (isset($Result[0]))
            return $Result[0];
        else
            throw new Exception("Resolution" . $size . " not Exists");
    }

}
