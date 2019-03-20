<?php

namespace CodePi\Settings\DataSource;

use CodePi\Base\Eloquent\EmailTemplates;

class EmailTemplatesDataSource {

    /**
     * get all users_log details. 
     * @param $data
     * @return array $users
     */
    function emailTemplatesData($data) {
        $sortBy = $data['sortBy'];
        $objEmailTemplates = new EmailTemplates();
        $templates = $objEmailTemplates->select('name', 'subject', 'body', 'id')->orderBy('id', $data['sort'])->get();
        $arr = [];
        foreach ($templates as $key => $template) {
            $arr[$key]['id'] = $template->id;
            $arr[$key]['name'] = $template->name;
            $arr[$key]['subject'] = $template->subject;
            $arr[$key]['body'] = $template->body;
        }
        // dd($Logs);
        //echo "<pre>";print_r($arr);exit;
        return $arr;
    }

}
