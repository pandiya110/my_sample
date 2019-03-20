<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\UsersColumnsWidth;
use GuzzleHttp;

class UsersColumnWidthDS {

    /**
     * 
     * @param type $command
     * @return type
     */
    function saveCustomColumnWidthByUsers($command) {

        $params = $command->dataToArray();
        $width = [];
        $status = false;
        \DB::beginTransaction();
        try {
            if (is_array($params['columns']) && !empty($params['columns'])) {
                $objColWidth = new UsersColumnsWidth();

                $checkCount = $objColWidth->where('users_id', $params['users_id'])->get();

                foreach ($params['columns'] as $values) {
                    $width[$values['column']] = $values['width'];
                }
                unset($params['columns']);

                if (!empty($width)) {
                    $params['column_width'] = \GuzzleHttp\json_encode($width);
                    if (count($checkCount) > 0) {
                        $params['id'] = $checkCount[0]->id;
                        unset($params['date_added'], $params['created_by'], $params['gt_date_added']);
                    }
                    $objColWidth->saveRecord($params);
                    \DB::commit();
                    $status = true;
                }
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage() . '::' . $ex->getFile() . '::' . $ex->getLine();
            \DB::rollback();
        }
        return ['status' => $status];
    }

    /**
     * 
     * @param int $intUsersId
     * @return array
     */
    function getCustomColumnWidthByUserId($intUsersId = 0) {
        $arrColumnWidth = [];
        $objColWidth = new UsersColumnsWidth();
        $dbResult = $objColWidth->where('users_id', $intUsersId)->get();
        if (!empty($dbResult)) {
            foreach ($dbResult as $row) {
                $json_decode = GuzzleHttp\json_decode($row->column_width);
                foreach ($json_decode as $column => $width) {
                    $arrColumnWidth[] = ['column' => $column, 'width' => $width];
                }
            }
        }
        return $arrColumnWidth;
    }

}
