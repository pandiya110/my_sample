<?php

namespace CodePi\Base\Mailer;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model {

    protected $table = 'email_templates';
    protected $fillable = array(
        'domain',
        'name',
        'from',
        'from_name',
        'body',
        'subject',
        'status',
        'is_send_unsub_users',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

    function emailTemplateFormat($id) {


        $data = array();
        //$objEmailTemplate = $this->find ($id);
        $objEmailTemplate = $this->where('id', $id)->where('status', 1)->first();

        if (!empty($objEmailTemplate)) {
            $data = array(
                'from' => $objEmailTemplate->from,
                'from_name' => $objEmailTemplate->from_name,
                'subject' => $objEmailTemplate->subject,
                'body' => $objEmailTemplate->body
            );
        }
        return $data;
    }

    
    function emailTemplateFormatByName($name){
        $data = array();
        //$objEmailTemplate = $this->find ($id);
        $objEmailTemplate = $this->where('name', $name)->where('status', 1)->first();

        if (!empty($objEmailTemplate)) {
            $data = array(
                'from' => $objEmailTemplate->from,
                'from_name' => $objEmailTemplate->from_name,
                'subject' => $objEmailTemplate->subject,
                'body' => $objEmailTemplate->body
            );
        }
        return $data;
    }

}
