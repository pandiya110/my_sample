<?php

namespace CodePi\Base\Mailer;

use Illuminate\Database\Eloquent\Model;
use URL,
    Request;

class EmailDetails extends Model {

    protected $table = 'email_details';

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $fillable = array(
        'to',
        'from',
        'subject',
        'attachment',
        'page',
        'message',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

    function saveEmailDetails($data) {

        $emailDetails = array();
        $emailDetails['to'] = $data['to_email'];
        $emailDetails['from'] = $data['from'];
        $emailDetails['subject'] = $data['subject'];
        $emailDetails['message'] = $data['body'];
        $emailDetails['page'] = URL::to('');
        $emailDetails['created_by'] = $data['id'];
        $emailDetails['last_modified_by'] = $data['id'];
        $emailDetails['date_added'] = date('Y-m-d H:i:s');
        $emailDetails['last_modified'] = date('Y-m-d H:i:s');
        $emailDetails['gt_date_added'] = date('Y-m-d H:i:s');
        $emailDetails['gt_last_modified'] = date('Y-m-d H:i:s');
        $emailDetails['ip_address'] = Request::getClientIp();
        if(isset($data['attachment'])){
            $emailDetails['attachment'] = $data['attachment'];
        }
        $save = $this->insert($emailDetails);

        return true;
    }

}
