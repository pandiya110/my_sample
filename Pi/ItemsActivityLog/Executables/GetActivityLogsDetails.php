<?php

namespace CodePi\ItemsActivityLog\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\ItemsActivityLog\DataSource\ItemsActivityLogsDs;
use CodePi\Base\Libraries\PiLib;
use URL;
use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Users\DataSource\UsersData;

class GetActivityLogsDetails implements iCommands {

    /**
     *
     * @var class 
     */
    private $dataSource;    
    private $objUserDs;

    /**
     * 
     * @param ItemsActivityLogsDs $objItemDs
     */
    function __construct(ItemsActivityLogsDs $objItemDs, UsersData $objUserDs) {
        $this->dataSource = $objItemDs;
        $this->objUserDs = $objUserDs;
    }

    function execute($command) {
        try {
            $array = $arrayValue = $item_nbr = $itemsType = $item_key = $item_type = [];
            $objResult = $this->dataSource->getActivityLogsDetails($command);

            $actionArray = ['insert', 'delete', 'copy', 'moved', 'duplicate'];
            $flag = 0;
            if (!empty($objResult)) {
                foreach ($objResult as $row) {
                    $changed_fields = array_filter(explode(",", str_replace('"', '', str_replace(']', '', str_replace('[', '', $row->changed_fields)))));

                    if (isset($changed_fields[0]) && $changed_fields[0] != 'last_modified') {
                        $fields[] = $changed_fields;
                        $isValid = $this->jsonValidator((array) json_decode($row->total_history));
                        if ($isValid) {
                            $json_values = \GuzzleHttp\json_decode($row->total_history);
                            $arrayValue[$row->items_id] = (array) $json_values;
                        }
                    }
                    $item_nbr[] = $this->dataSource->getItemNbrFromHistory($row->prim_id, $row->items_id);
                    $itemsType[] = $this->dataSource->getItemsTypeByHistoryId($row->prim_id, $row->items_id);
                }


                if (!empty($itemsType)) {
                    foreach ($itemsType as $key => $final) {
                        if (!empty($final)) {
                            foreach ($final as $k => $item) {
                                $item_type[$k] = $item;
                            }
                        }
                    }
                }

                unset($itemsType);
                if (!empty($item_nbr)) {
                    foreach ($item_nbr as $finalValue) {
                        if (!empty($finalValue)) {
                            foreach ($finalValue as $k => $item) {
                                $item_key[$k] = $item;
                            }
                        }
                    }
                }

                unset($item_nbr);
                if (!in_array($command->action, $actionArray)) {
                    $flag = 2;
                    $arrayKey = $arrId = [];
                    $arrNonVisible = $this->objUserDs->getNonvisibleColumns();
                    
                    foreach ($arrayValue as $key => $value) {
                        foreach ($fields as $val) {
                            foreach ($val as $col) {
                                if (!in_array($col, array_keys($arrNonVisible))) {
                                    if (isset($value[$col])) {
                                        if (isset($item_type[$key])) {
                                            if ($command->type == $item_type[$key]) {
                                                $arrId[$key] = $key;
                                                $array[$key]['action'] = 'update';
                                                $array[$key]['itemNumber'] = $item_key[$key];
                                                $arrayKey[$key][$col]['column'] = $this->dataSource->getColumnLabelByColumnKey($col);
                                                $arrayKey[$key][$col]['prev'] = $this->dataSource->actionsColArray($col, $value[$col][0]);
                                                $arrayKey[$key][$col]['new'] = $this->dataSource->actionsColArray($col, $value[$col][1]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    foreach ($arrId as $key) {
                        $array[$key]['columns'] = isset($arrayKey[$key]) && isset($arrayKey[$key]) ? array_values($arrayKey[$key]) : array();
                    }
                    unset($fields, $arrayValue);
                } else {
                    $flag = 1;
                    $array['items']['action'] = $command->action;
                    $array['items']['itemNumber'] = array_values($item_key);
                }
                unset($item_key, $item_type);
                if ($command->action == 'addrow' || $command->action == 'export') {
                    $message = "";
                    if ($command->action == 'export') {
                        $objEvents = new EventsDataSource();
                        $command->id = $command->events_id;
                        $eventInfo = $objEvents->getEventDetails($command);
                        $eventName = isset($eventInfo[0]) ? $eventInfo[0]->event_name : "";
                        $message = $eventName . ' Event Exported Successfully';
                    }
                    if ($command->action == 'addrow') {
                        $message = 'New Row Added Successfully';
                    }
                    $array['items']['action'] = $command->action;
                    $array['items']['message'] = $message;
                    $flag = 3; /* Show only message * */
                }
                $logDetails['items'] = array_values($array);
                $logDetails['flag'] = $flag;
                unset($array);
            }
        } catch (\Exception $ex) {
            $message = 'Message ::' . $ex->getMessage() . '::File ::' . $ex->getFile() . '::Line No ::' . $ex->getLine();
            $array['items']['message'] = $message;
            $logDetails['items'] = array_values($array);
        }
        return $logDetails;
    }

    /**
     * 
     * @param type $data
     * @return boolean
     */
    function jsonValidator($data = NULL) {
        if (!empty($data)) {
            @json_decode($data);
            return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
    }

}
