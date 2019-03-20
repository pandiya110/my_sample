<?php

namespace CodePi\Base\Mailer;

use Illuminate\Database\Eloquent\Model;
use Request;
use DB;
use CodePi\Base\DataSource\PiEloquent;

class UserForgotTokens extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'users_forgot_tokens';
    protected $fillable = array(
        'users_id',
        'token',
        'valid_upto',
        'verification_status',
        'ip_address'
    );

    /**
     * Save Password/Forgotpassword Token generations
     * @param int $users_id
     * @param string $enc_token
     * @return boolean
     */
    function usersForgotTokens($users_id = 0, $enc_token = 0) {
        $return = 0;
        $this->dbTransaction();
        try {

            if (!empty($users_id) && is_numeric($users_id)) {
                $token = array();
                $token['users_id'] = $users_id;
                $token['token'] = ($enc_token) ? $enc_token : md5(time());
                $token['valid_upto'] = strtotime("+2 days", time());
                $token['created_by'] = $users_id;
                $token['last_modified_by'] = $users_id;
                $token['date_added'] = date('Y-m-d H:i:s');
                $token['last_modified'] = date('Y-m-d H:i:s');
                $token['gt_date_added'] = date('Y-m-d H:i:s');
                $token['gt_last_modified'] = date('Y-m-d H:i:s');
                $token['verification_status'] = '0';
                $token['ip_address'] = Request::getClientIp();

                $saveusersForgotTokens = $this->insert($token);
                $return = true;
            }
            $this->dbCommit();
        } catch (\Exception $ex) {
            echo $ex->getMessage();exit;
            $this->dbRollback();
        }

        return $return;
    }

    /**
     * Check Token Expire time
     * @param int $users_id
     * @param string $enc_token
     * @return collection
     */
    function checkResetTokenId($users_id, $enc_token) {
        // return $users_id.' '.$enc_token;
        $result = $this->where('users_id', $users_id)->where('token', $enc_token)->where('verification_status', '0')->first();
        return $result;
    }

    /**
     * 
     * @param int $users_id
     * @param string $users_token_id
     */
    function updateForgotTokens($users_id, $users_token_id) {

        $token = array();
        $this->dbTransaction();
        try {
            $token = array();
            $token['token'] = $users_token_id;
            $token['users_id'] = $users_id;
            $token['verification_status'] = '1';
            $token['valid_upto'] = time();
            $token['created_by'] = $users_id;
            $token['last_modified_by'] = $users_id;
            $token['date_added'] = date('Y-m-d H:i:s');
            $token['last_modified'] = date('Y-m-d H:i:s');
            $token['gt_date_added'] = date('Y-m-d H:i:s');
            $token['gt_last_modified'] = date('Y-m-d H:i:s');            
            $token['ip_address'] = Request::getClientIp();
            $saveusersForgotTokens = $this->where('users_id', $users_id)->where('token', $users_token_id)->update($token);
            $this->dbCommit();
        } catch (\Exception $ex) {
            $this->dbRollback();            
        }
    }

}
