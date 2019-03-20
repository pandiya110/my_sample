<?php

namespace CodePi\Settings\DataSource;

use CodePi\Base\Eloquent\Settings as SettingsEl;
use CodePi\Base\Eloquent\HierarchySettings;

class Settings {

    /**
     * get settings details.
     *
     * @param array $data
     * @return $arrSettings
     */
    function getSettings($command) {
        $data = $command->dataToArray();
        $arrSettings = [];

        if (!empty($data['stop_outgoing_emails']) && isset($data['stop_outgoing_emails'])) {
            //$data['stop_outgoing_emails'] = ($data['stop_outgoing_emails'] == 1) ? true : false;
            $data['stop_outgoing_emails'] = ($data['stop_outgoing_emails'] == 1) ? '1' : '0';
            foreach ($data as $object_key => $object_value) {
                if ($object_key == 'stop_outgoing_emails') {
                    $arrSettings[] = $this->updateSettings($object_key, $object_value);
                }
            }
            return $arrSettings[0];
        } else {
            $return = SettingsEl::where('object_key', 'stop_outgoing_emails')->first()->object_enum;
            $return = !empty($return) ? 1 : 2;
            return $return;
        }
    }

    /**
     * update settings.
     *
     * @param $object_key, $object_value
     * @return array $return
     */
    function updateSettings($object_key, $object_value) {
        $results = SettingsEl::where('object_key', '=', $object_key)->first();
        $objSettings = new SettingsEl();
        $objSettings->dbTransaction();
        try {
            if ($results) {
                $column_key = "object_" . $results->object_type;
                SettingsEl::where('object_key', $object_key)
                        ->update([$column_key => $object_value]);
                $arrValue = array($object_key => $object_value);
                $return = !empty($arrValue['stop_outgoing_emails']) ? 1 : 2;
                $objSettings->dbCommit();
                return $return;
            }
        } catch (\Exception $ex) {
            $objSettings->dbRollback();
        }
    }
    /**
     * 
     * @param array $subInfo
     * @param type $divId
     * @param type $jobId
     * @return type
     */
    static function getSamplePayable(array $subInfo = array(), $divId = 0, $jobId = 0) {

        $arrSamplePayable = [];

        $objResult = HierarchySettings::select('hierarchy_id', 'object_serialize')
                        ->where(function($query) use($divId) {
                            if (!empty($divId)) {
                                $query->where('hierarchy_id', $divId);
                            }
                        })->get();
        if (empty($subInfo)) {
            $subInfo = self::getDefaultPayable();
        }
        foreach ($subInfo as $row) {

            if (strpos(strtolower($row['name']), 'alb') !== false) {
                $arrSamplePayable[$row['division_id']] = $row['id'];
            } elseif (isset($arrSamplePayable[$row['division_id']]) && strpos(strtolower($arrSamplePayable[$row['division_id']]['name']), 'alb') !== false) {
                
            } else {
                $arrSamplePayable[$row['division_id']] = $row['id'];
            }
        }

        foreach ($objResult as $hSettings) {
            $arrSettings = json_decode($hSettings->object_serialize, true);
            if (!empty($arrSettings) && isset($arrSettings['samplePayable']) && isset($arrSamplePayable[$hSettings->hierarchy_id])) {
                $arrSamplePayable[$hSettings->hierarchy_id] = $arrSettings['samplePayable'];
            }
        }
        return $arrSamplePayable;
    }
    /**
     * 
     * @param type $divId
     * @return type
     */
    static function getDefaultPayable($divId = 0) {
        $ObjSettings = new HierarchySettings();
        $sql = "select div.id as division_id,
            sub.id as subdivision_id,
            hlsub.name as subdivision_name
             from stores.hierarchy as div
            inner join stores.hierarchy_labels as hlban on div.hierarchy_label_id = hlban.id
            inner join stores.hierarchy_types as ht on hlban.hierarchy_type_id = ht.id
            and ht.instances_id = 1
            inner join stores.hierarchy as sub on div.id = sub.parent_id
            inner join stores.hierarchy_labels as hlsub on sub.hierarchy_label_id = hlsub.id";

        if (!empty($divId)) {
            $sql.=" where div.id = " . $divId;
        }

        $objResult = $ObjSettings->dbSelect($sql);
        $subInfo = [];
        foreach ($objResult as $s) {
            $subInfo[$s->subdivision_id]['id'] = $s->subdivision_id;
            $subInfo[$s->subdivision_id]['division_id'] = $s->division_id;
            $subInfo[$s->subdivision_id]['name'] = $s->subdivision_name;
        }

        return $subInfo;
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function getGeneralSettings($command){
        $objSettings = new SettingsEl();
        $dbResult = $objSettings->get()->toArray();
        $arrData = [];
        if(!empty($dbResult)){
            foreach ($dbResult as $row){
                $type = 'object_'.$row['object_type'];                
                $row['value'] = $row[$type];
                $lable = str_replace('stop','',str_replace('_', ' ', $row['object_key']));                
                $arrData[] = array('id' => $row['id'], 'value' => $row['value'], 'key' => $row['object_key'], 'lable' => ucwords($lable));
            }
        }
        
        return $arrData;
    }
    /**
     * 
     * @param type $params
     * @return boolean
     */
    function saveGeneralSettings($params) {
        $status = false;        
        try {
            \DB::beginTransaction();
            $objSettings = new SettingsEl();
            $dbValue = $objSettings->where('object_key', $params['key'])->first();
            $column = 'object_' . $dbValue->object_type;            
            $objSettings->where('object_key', $params['key'])->update([$column => $params['value']]);
            $status = true;
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return $status;
    }

}
