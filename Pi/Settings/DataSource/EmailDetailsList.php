<?php

namespace CodePi\Settings\DataSource;

use CodePi\Base\Eloquent\EmailDetails;

class EmailDetailsList {

    /**
     * get all email Controller  details. 
     * @param $usersListCommand
     * @return array $users
     */
    function getEmailDetailsList($data) {

        $data['limit'] = '';
        if (isset($data['page'])) {
            $data['limit'] = ($data['page'] - 1) * $data['perPage'];
        }
        $emailDetails = EmailDetails::skip($data['limit'])->take($data['perPage'])->orderBy($data['order'], $data['sort'])->get();
        //$emailDetails =EmailDetails::skip($data['limit'])->take($data['perPage'])->orderBy('id', 'desc')->get();
        // print_r($emailDetails->toArray());die;
        return $emailDetails;
    }

    /*
     * getting count of email_details count.
     * @return $emailControllersCount
     */

    function emailDetailsCount() {
        return $emailControllersCount = EmailDetails::count();
    }

    /*
     * getting message of email
     * @param int $id
     * @return $emailDetails
     */

    function getEmailDetailsMessage($command) {
        $params = $command->dataToArray();
        $emailDetails = EmailDetails::where('id', $params['id'])->get();
        return $emailDetails;
    }

}
