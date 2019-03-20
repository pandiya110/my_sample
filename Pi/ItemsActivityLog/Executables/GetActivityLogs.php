<?php

namespace CodePi\ItemsActivityLog\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\ItemsActivityLog\DataSource\ItemsActivityLogsDs;
use CodePi\Base\Libraries\PiLib;
use URL;
use CodePi\Base\DataTransformers\DataResponse;
#use CodePi\ItemsActivityLog\DataTransformers\ActivityLogsTransformers as ActivitylogTs;
class GetActivityLogs implements iCommands {
    /**
     *
     * @var class 
     */
    private $dataSource;
    private $dataResponse;
    /**
     * 
     * @param ItemsActivityLogsDs $objItemDs
     */
    function __construct(ItemsActivityLogsDs $objItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objItemDs;
        $this->dataResponse = $objDataResponse;
    }
    /**
     * Executions of get activity logs
     * @param object $command
     * @return array
     */
    function execute($command) {
        $response = [];
        $response['status'] = false;

        if (!empty($command->events_id)) {
            $objResult = $this->dataSource->getActivityLogs($command);           
//            $response = $this->dataResponse->collectionFormat($objResult, new ActivitylogTs([]));
//            return array_values($response);
//            dd($response);
            
            $data = [];
            if ($objResult) {
                foreach ($objResult as $row) {
                    $date = PiLib::piDate($row->last_modified, 'Y-m-d');
                    $user_time = PiLib::UserTimezone($row->last_modified);
                    $timeFormat = PiLib::piDate($user_time, 'h:i A');
                    $dateFormat = "";
                    if ($date == date('Y-m-d')) {
                        $dateFormat = 'Today';
                    } else if ($date == date('Y-m-d', strtotime("-1 days"))) {
                        $dateFormat = 'Yesterday';
                    } else {
                        $dateFormat = PiLib::piDate($row->last_modified, 'M j, Y');
                    }

                    $name = ($command->last_modified_by == $row->users_id) ? '<b>Me</b>' : '<b>' . $row->firstname . ' ' . $row->lastname . '</b>';
                    $message = $row->descriptions . ' by ' . $name;
                    $data[$date]['date'] = $dateFormat;
                    $profile_image = null;
                    $ext = pathinfo($row->profile_image_url, PATHINFO_EXTENSION);
                    if (empty(!$ext)) {
                        $fileInfo = pathinfo($row->profile_image_url);
                        $profile_image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_small.' . $fileInfo['extension']);
                    }

                    $data[$date]['list'][] = ['date_format' => $dateFormat, 'time' => $timeFormat, 'username' => $row->firstname . ' ' . $row->lastname, 'message' => $message, 'image' => $profile_image, 
                                              'tracking_id' => $row->tracking_id, 'action' => $row->actions, 'type' => $row->type];
                }
            }
            $response['items'] = array_values($data);
            $response['status'] = true;
        }
        if (!empty($command->page)) {
            $response['count'] = $objResult->total();
            $response['lastpage'] = $objResult->lastPage();
        }

        return $response;
    }

}
