<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use DB,
    Exception;
use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Support\Facades\Cache,
    Carbon\Carbon;

use CodePi\Base\Cache\ResolutionsCache;

class Resolutions extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'resolutions';
    protected $fillable = array(
    );

    static public function DeatailByName($size) {
        $parentKey='resolution_name';
        $objparams = ResolutionsCache::paramsKeySet($parentKey,array('r_name'=>$size));
        if($Result = ResolutionsCache::keyHasGet($parentKey,$objparams)){
        }else{ 
            $Result = DB::select("select * from resolutions where name =? ", array($size));
            ResolutionsCache::put($parentKey,$objparams,$Result);
        }
        /*$expiresAt = Carbon::now()->addMinutes(30);
        $Result = Cache::remember('resolution_name_' . $size, $expiresAt, function () use($size) {
        return DB::select("select * from resolutions where name =? ", array($size));
        }); */

        if (isset($Result[0])) {
            return $Result[0];
        } else {
            throw new Exception("Resolution" . $size . " not Exists");
        }
    }

    static public function DeatailById($Id) {
        $Result = DB::select("select * from resolutions where id =? ", array($Id));
        if (isset($Result[0])) {
            return $Result[0];
        } else {
            throw new Exception("Resolution" . $Id . " not Exists");
        }
    }

}