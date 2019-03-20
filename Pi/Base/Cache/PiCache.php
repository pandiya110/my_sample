<?php

namespace CodePi\Base\Cache;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Predis\Connection\ConnectionException;
use Predis;
use CodePi\Base\Mailer\MyMailer;

abstract class PiCache {

    //static $cacheDriver = 'redis';
    //static $cacheStatus = 'no';
    function __construct($cacheDriver) {
        
    }

    static function isCacheCheck() {
        try {
            //Just Trigger the redis cache 
            $cacheDriver = config('cache.default');
            if ($cacheDriver == 'redis') {
                Cache::tags('')->has('');
            } else {
                Cache::has('');
            }
            return static::$isCache;
        } catch (ConnectionException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    static function keyHasGet($parentKey, $objparams) {
        $data = '';
        $cacheDriver = config('cache.default');
        if (!self::isCacheCheck()) {
            return false;
        }
        if ($cacheDriver == 'redis') {
            if (Cache::tags([$parentKey])->has($objparams)) {
                $data = Cache::tags([$parentKey])->get($objparams);
            }
        } else {
            if ($data = Cache::has($objparams)) {
                $data = Cache::get($objparams);
            }
        }

        return $data;
    }

    static function get($prkey, $key) {
        $data = '';
        $cacheDriver = config('cache.default');
        if (!self::isCacheCheck()) {
            return false;
        }
        if ($cacheDriver == 'redis') {
            $data = Cache::tags([$prkey])->get($key);
        } else {
            $data = Cache::get($key);
        }
        return $data;
    }

    static function put($prkey, $key, $val) {
        $expiresAt = Carbon::now()->addMinutes(10080);  // 7 Days
        $cacheDriver = config('cache.default');
        $data = '';
        if (!self::isCacheCheck()) {
            return false;
        }
        if ($cacheDriver == 'redis') {
            Cache::tags([$prkey])->put($key, $val, $expiresAt);
        } else {
            $data = Cache::put($key, $val, $expiresAt);
        }
        return $data;
    }

    static function has($prkey, $key) {
        $data = '';
        $cacheDriver = config('cache.default');
        if (!self::isCacheCheck()) {
            return false;
        }
        if ($cacheDriver == 'redis') {
            $data = Cache::tags([$prkey])->has($key);
        } else {
            $data = Cache::has($key);
        }
        return $data;
    }

    static function deleteCache($prkey) {
        $cacheDriver = config('cache.default');
        if (!self::isCacheCheck()) {
            return false;
        }
        if ($cacheDriver == 'redis') {
            Cache::tags($prkey)->flush();
        } else {
            Cache::flush();
        }
    }

    static function deleteCacheall() {
        $cacheDriver = config('cache.default');
        if (!self::isCacheCheck()) {
            return false;
        }
        if ($cacheDriver == 'redis') {
            Cache::flush();
        } else {
            Cache::flush();
        }
    }

    static function getPrefix($key) {
        return $prefix = Cache::getPrefix($key);
    }

    static function tagPut($key, $val) {
        $expiresAt = Carbon::now()->addMinutes(10080);  // 7 Days
        Cache::tags(['users'])->put($key, $val, $expiresAt);
    }

    static function tagGet($key) {
        return $anne = Cache::tags(['users'])->get($key);
    }

    static function tagHas($key) {
        //echo  $this->cacheDriver;
        return $anne = Cache::tags(['users'])->has($key);
    }

    static function paramsKeySet($parentKey, $arrayParams) {
        $arrayRes = $parentKey . '_';
        $objres = '';
        $removeKeys = array('date_added', 'gt_date_added', 'created_by', 'instances_id', 'last_modified', 'ip_address', 'gt_last_modified', 'last_modified_by', 'isAdd:CodePi\Base\Commands\BaseCommand:private');
        foreach ($arrayParams as $key => $val) {
            if (isset($val) && !in_array($key, $removeKeys) && !empty($val)) {
                if (is_array($val)) {
                    $arrayRes.= implode('_', $val);
                } else {
                    $arrayRes.= $val . '_';
                }
            }
        }

//            if($arrayRes){
//                $objres = implode('_',$arrayRes);
//            }
        return $arrayRes;
    }

    static function checkCacheStatus() {
        return self::$cacheStatus;
    }

    static function test() {
        
    }

    static function sendEmailCacheNotWorking() {

//             $data = array(
//            'to_email' => 'm.sreenivasreddy314@gmail.com',
//            'to_fname' => 'F Name',
//            'from' => 'no-reply@ivieinc.com',
//            'from_name' => 'POET',
//            'subject' => 'Redis Server Not Working',
//            'body' => '',
//            'id' => '',
//            );
//            $view = 'emails.emailcontent';
//          MyMailer::sendEmail($view, $data); 
    }

}
