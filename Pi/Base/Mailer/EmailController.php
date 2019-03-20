<?php

namespace CodePi\Base\Mailer;

use Illuminate\Database\Eloquent\Model;
use Request;

class EmailController extends Model {

    protected $table = 'email_controller';

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $fillable = array(
        'to',
        'from',
        'from_name',
        'cc',
        'bcc',
        'subject',
        'message',
        'attachment',
        'status',
        'sent_date',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

    function saveEmailController($data) {

        $emailDetails = array();
        $emailDetails['to'] = $data['to_email'];
        $emailDetails['from'] = $data['from'];

        $emailDetails['from_name'] = $data['from_name'];

        $emailDetails['subject'] = $data['subject'];
        $emailDetails['message'] = $data['body'];
        $emailDetails['created_by'] = $data['id'];
        $emailDetails['last_modified_by'] = $data['id'];
        $emailDetails['last_modified'] = date('Y-m-d H:i:s');
        $emailDetails['gt_date_added'] = date('Y-m-d H:i:s');
        $emailDetails['gt_last_modified'] = date('Y-m-d H:i:s');
        $emailDetails['ip_address'] = Request::getClientIp();
        if(isset($data['attachment'])){
            $emailDetails['attachment'] = $data['attachment'];
        }

        //$url = \URL::to('') ;
        // $emailDetails = array('to' => $data['to_email'], 'from' => $data['from'], 'subject' => $data['subject'], 'attachment' => '', 'page' => $url, 'message' => $data['body'], 'created_by' => $data['id'], 'last_modified_by' => $data['id']);
        // print_r($emailDetails); die;
        $save = $this->insert($emailDetails);
        $return = true;
    }

    function updateEmailController($data) {
        $save = $this->where('id', '=', $data['id'])->update($data);
        return $return = true;
    }

}
