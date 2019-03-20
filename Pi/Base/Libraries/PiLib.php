<?php

namespace CodePi\Base\Libraries;

use App\User;

class PiLib {

    static function piIsset($source, $key, $default) {

        if (isset($source[$key]) && !empty($source[$key])) {

            return $source[$key];
        } else {
            return $default;
        }
    }

    static function time_elapsed_string($datetime, $full = false) {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'weak',
            'd' => 'day',
            'h' => 'h',
            'i' => 'm',
            's' => 's'
        );
        $arrPlurals = array('y', 'm', 'w', 'd');
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . '' . $v . ($diff->$k > 1 && in_array($k, $arrPlurals) ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);

        if (isset($string['w']) && $string['w'] != '1w') {
            return 'On ' . date('m/d', strtotime($datetime));
        }
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    static function piDate($date = null, $format = 'Y-m-d H:i:s') {

        if (!empty($date)) {
            $dateFormat = date($format, strtotime($date));
        } else {
            $currentDate = date('Y-m-d H:i:s');
            $dateFormat = date($format, strtotime($currentDate));
        }
        return $dateFormat;
    }

    public function getSlug($string) {
        return preg_replace('/\s+/', '_', strtolower($string));
    }

    static function piRound() {
        
    }

    public function status() {
        
    }

    static function piEncrypt($id) {
        if (is_array($id)) {
            return array_map(array(__CLASS__, 'arrayEncryptMap'), $id);
        }
        $objEncrypt = new MyEncrypt();
        $encrypt = $objEncrypt->encode($id);
        return $encrypt;
    }

    static function piDecrypt($id) {
        if (is_array($id)) {
            return array_map(array(__CLASS__, 'arrayDecryptMap'), $id);
        }
        $objEncrypt = new MyEncrypt();
        $decrypt = $objEncrypt->decode($id);
        return $decrypt;
    }

    static function arrayEncryptMap($id) {

        $objEncrypt = new MyEncrypt();
        return $objEncrypt->encode($id);
    }

    static function arrayDecryptMap($id) {

        $objEncrypt = new MyEncrypt();
        return $objEncrypt->decode($id);
    }

    public function getTimezoneDate($date, $offset) {
        $date = strtotime($date);
        // echo $offset; die; 
        if (!empty($offset)) {
            $ms = $offset * 60;
            $gmdate = gmdate("Y:m:d H:i:s", $date - ($ms)); // the "-" can be switched to a plus if that's what your time zone is.
            return $gmdate;
        }
    }

    public function getUserTimezoneDate($date, $format = 'Y-m-d H:i:s', $users_id = 0) {
        $timezone_offset = '';
        if (!empty($users_id)) {
            $objUser = User::find($users_id);
            $timezone_offset = $objUser->timezone_offset;
        } else {
            if (\Auth::check()) {
                $timezone_offset = \Auth::user()->timezone_offset;
            }
        }

        $date = strtotime($date);
        // echo $offset; die; 
        if (!empty($timezone_offset)) {
            $ms = $timezone_offset * 60;
            $gmdate = gmdate('Y-m-d H:i:s', $date - ($ms)); // the "-" can be switched to a plus if that's what your time zone is.
            return date($format, strtotime($gmdate)); //$gmdate;
        } else {
            self::piDate($date, $date);
        }
    }

    /**
     * Search and filter the string
     * @param type $source
     * @param type $key
     * @param type $default
     * @return string $str | $default
     */
    static function piSearchFilter($source, $key, $default) {

        if (isset($source[$key])) {
            $str = $source[$key];
            if (function_exists('pg_escape_string')) {
                $str = pg_escape_string($str);
            } else {
                $str = addslashes($str);
            }

            $strRegMatchs = ['%', '_', '#'];
            $strReplace = ['\%', '\_', '\#'];

            // $str = (trim(htmlentities($str, ENT_QUOTES, "UTF-8")));
            $str = str_replace($strRegMatchs, $strReplace, $str);

            $str = str_replace("&amp;", "&", $str);

            return $str;
        } else {
            return $default;
        }
    }

    /*     * **
     * To check file exists or not and count to the file if already exists
     * @params path,filename
     * @Returns string
     */

    public function checkFileExists($path, $filename) {
        if ($pos = strrpos($filename, '.')) {
            $name = substr($filename, 0, $pos);
            $ext = substr($filename, $pos);
        } else {
            $name = $filename;
        }

        $newpath = $path . '/' . $filename;
        $newname = $filename;
        $counter = 0;
        while (file_exists($newpath)) {
            $newname = $name . '_' . $counter . $ext;
            $newpath = $path . '/' . $newname;
            $counter++;
            $this->checkFileExists($path, $newname);
        }
        return $newname;
    }

    /* Move file to cloud
     * @params $source,$file
     * @returns string i.e Download link
     */

    function uploadZipCloud($source, $file, $resId) {
        $objFile = new ZipFileFunctions;
        $url = '';
        try {
            $objPiUpload = new Cloud;
            $resolDet = Resolutions::find($resId);
            if (!empty($resolDet)) {
                $status = $objPiUpload->uploadObject($source, $resolDet->folders . "/" . $file, $resolDet->container);
                $url = $resolDet->url . "/" . $resolDet->folders . "/" . $file;
                sleep(1);
                chmod($source, 0777);
                sleep(1);
                $objFile->deleteFolder($source);
                return $url;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    /**
     * change to name format
     * @param int $firstName, $lastName
     * @return Result name
     */
    public function nameFormatter($firstName, $lastName) {
        $firstName = ucfirst($firstName);
        $lastName = ucfirst($lastName);
        $lastName = substr($lastName, 0, 1);
        return $firstName . ' ' . $lastName;
    }

    static function importFilesLogs(array $logArr) {
        global $responseLog;
        $responseLog[] = $logArr;
    }

    static function getImportLog() {
        global $responseLog;
        return $responseLog;
    }

    static function fileSizeConvert($bytes = 0) {
        $bytes = floatval($bytes);
        if (empty(trim($bytes))) {
            $bytes = 0;
        }
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );
        $result = '0';
        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = strval(round($result, 2)) . " " . $arItem["UNIT"];
                break;
            }
        }
        return $result;
    }
    /**
     * Check given string is valid URL or not
     * @param string $url
     * @return string
     */
    static function isValidURL($url) {
        $isValid = null;
        if ($url != '') {
            //if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            if (preg_match('/^(http|https):\\/\\/[a-z0-9]+([\\-\\.]{1}[a-z0-9]+)*\\.[a-z]{2,5}' . '((:[0-9]{1,5})?\\/.*)?$/i', $url)) {
                $isValid = $url;
            } else {
                $isValid = null;
            }
        }
        return $isValid;
    }

    /**
     * 
     * @param type $gmttime
     * @param type $usertimezone
     * @return type
     */
    static function UserTimezone($gmttime,$usertimezone=0)
    {
            if(empty($usertimezone)) {
               $usertimezoneVal=(session('timezone_offset'))?session('timezone_offset'):0;
            }  else {
                $usertimezoneVal=$usertimezone;
            }  
            $userTime=strtotime($gmttime);
            $tm = $userTime-($usertimezoneVal*60);
            return  date("Y-m-d H:i:s",$tm);
    }
    /**
     * Converting Encode UTF-8 values
     * @param String $str
     * @return string
     */
    static function mbConvertEncoding($str) {

        if ($str != '') {
            $encode_value = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        } else {
            $encode_value = $str;
        }
        return $encode_value;
    }
    /**
     * 
     * @param string $str
     * @return string
     */
    static function filterString($str) {
        if (isset($str) && $str != '' && $str != '0' && !is_array($str)) {
            $str = (trim(htmlentities($str, ENT_QUOTES, "UTF-8")));
            $str = str_replace("&amp;", "&", $str);

            return $str;
        } else
            return $str;
    }
    /**
     * 
     * @param string $str
     * @param boolean $spchar
     * @return string
     */
    static function filterStringDecode($str, $spchar = false) {
        if (isset($str) && $str != '' && $str != '0') {
            $str = trim(htmlspecialchars_decode(html_entity_decode($str, ENT_QUOTES, 'UTF-8')));

            if ($spchar) {
                $str = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $str);
                // $str=utf8_decode($str);
            }
            return $str;
        } else
            return $str;
    }
    /**
     * This method will handle the , port is running or not
     * @param type $ip
     * @param type $port
     * @return boolean
     */
    static function isPortOpen($ip, $port) {
        $connection = @fsockopen($ip, $port);

        if (is_resource($connection)) {
            //echo 'Open!';
            fclose($connection);
            return true;
        } else {
            //echo 'Closed / not responding. :(';
            return false;
        }
    }

}
