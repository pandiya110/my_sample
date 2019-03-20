<?php

namespace CodePi\ItemsCardView\DataTransformers;

use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Base\Libraries\PiLib;
use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\ItemsCardView\DataSource\ItemsCardViewDs;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Events\DataSource\EventsDataSource;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CardViewTransformers
 *
 * @author enterpi
 */
class CardViewTransformers {

    /**
     *
     * @var type 
     */
    public $itemDs;

    /**
     *
     * @var type 
     */
    public $channelDs;

    /**
     *
     * @var type 
     */
    public $cardViewDs;

    function __construct() {

        $this->itemDs = new ItemsDataSource();
        $this->channelDs = new ChannelsDataSource();
        $this->cardViewDs = new ItemsCardViewDs();
        $this->eventDs = new EventsDataSource();
    }

    /**
     * Custom format for cardview
     * @param type $objCollection
     * @param type $arrFilterColms
     * @param type $intEventId
     * @param type $command
     * @param type $itemsColumns
     * @return type
     * @throws DataValidationException
     */
    function customFormatCardView($objCollection, $arrFilterColms = array(), $intEventId = 0, $command, $itemsColumns) {

        $arrResult = [];
        try {

            $dbColumns = $this->itemDs->getItemDefaultHeaders($type = 0);
            $defaultColumns = !empty($arrFilterColms) ? $arrFilterColms : $this->cardViewDs->defaultColumns;
            $arrChannles = $this->cardViewDs->getMappedChannels($intEventId);
            $objCollectionData = isset($objCollection['collection']) ? $objCollection['collection'] : [];
            $arrPermission = isset($objCollection['permissions']) ? $objCollection['permissions'] : [];
            $isArchived = $this->eventDs->isArchivedEvents($intEventId);

            if (!empty($objCollectionData)) {
                $arrItemsId = [];
                foreach ($objCollectionData as $obj) {
                    //echo '<pre>'; print_r($obj); die; 
                    $page = 0;
                    $fieldValitem = null;
                    $obj->no_of_linked_item = $obj->link_count;
                    foreach ($obj as $key => $fieldVal) {
                        if ($command->multi_sort[0]['column'] == $key) {
                            $fieldValitem = $fieldVal;
                            $page = $fieldVal;
                            break;
                        }
                    }
                    $itemId = $obj->id;
                    if ($command->multi_sort[0]['column'] == 'attributes') {
                        $arrResult[$page][$command->multi_sort[0]['column']] = $this->formatAttributesValue($fieldValitem);
                    } else if ($command->multi_sort[0]['column'] == 'local_sources') {
                        $arrResult[$page][$command->multi_sort[0]['column']] = $this->formatLocalSourceValue($fieldValitem);
                    } else {
                        $arrResult[$page][$command->multi_sort[0]['column']] = $fieldValitem;
                    }
                    //$arrResult[$page]['versions'][] = $obj->versions;
                    $arrItemsId[] = $itemId;
                    foreach ($obj as $key => $data) {
                        /**
                         * Format special characters
                         */
                        $data = PiLib::filterStringDecode($data);
                        if (in_array($key, $defaultColumns) && isset($dbColumns[$key])) {
                            if (in_array($key, $this->getPriceFormatColumns())) { //Check price olumns & format values
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => ItemsUtils::formatPriceValues($data));
                            } else if (in_array($key, $this->getUrlColumns())) { //Check Url Columns and Format
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => ItemsUtils::addhttp($data));
                            } else if (in_array($key, $this->getImageColumns())) { //Check Image Columns & Format
                                if ($key == 'item_image_url') {
                                    $itemImgUrl = PiLib::isValidURL($data);
                                    if (!empty($itemImgUrl)) {
                                        $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $itemImgUrl . config('smartforms.iqsThumbnail_60x60'));
                                    }
                                } else if ($key == 'dotcom_thumbnail') {
                                    $dotComImgUrl = PiLib::isValidURL($data);
                                    if (!empty($dotComImgUrl)) {
                                        $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $dotComImgUrl . config('smartforms.iqsThumbnail_60x60'));
                                    }
                                }
                            } else if ($key == 'advertised_retail') { //Check column is advertised_retail
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => ItemsUtils::formatAdRetaliValue($data));
                            } else if ($key == 'attributes') { //Check the Attribute columns
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $this->formatAttributesValue($data));
                            } else if ($key == 'no_of_linked_item') {
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $obj->no_of_linked_item);
                            } else if ($key == 'local_sources') {
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $this->formatLocalSourceValue($data));
                            } else {
                                $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $data);
                            }
                        } else {
                            if (in_array($key, $this->getEnumColumns())) { //Check Enum columns and convert to boolean
                                $arrResult[$page]['values'][$itemId][$key] = $this->changeEnumToBoolean($data);
                            } else if ($key == 'item_image_url') {
                                $arrResult[$page]['values'][$itemId]['original_image'] = PiLib::isValidURL($data);
                                $itemImgUrl = PiLib::isValidURL($data);
                                if (!empty($itemImgUrl)) {
                                    $arrResult[$page]['values'][$itemId][$key] = $data . config('smartforms.iqsThumbnail_200x200');
                                }
                            } else if ($key == 'dotcom_thumbnail') {
                                $dotComImgUrl = PiLib::isValidURL($data);
                                if (!empty($dotComImgUrl)) {
                                    $arrResult[$page]['values'][$itemId]['columnValues'][] = array('name' => $dbColumns[$key]['name'], 'value' => $dotComImgUrl . config('smartforms.iqsThumbnail_60x60'));
                                }
                            } else if ($key == 'id') {
                                if (isset($arrChannles[$data])) {
                                    $arrResult[$page]['values'][$itemId]['channels'] = $arrChannles[$itemId];
                                } else {
                                    $arrResult[$page]['values'][$itemId]['channels'] = [];
                                }
                            } else if ($key == 'attributes') { //Check the Attribute columns
                                $arrResult[$page]['values'][$itemId][$key] = $this->formatAttributesValue($data);
                            } else if ($key == 'local_sources') { //Check the Attribute columns                                
                                $arrResult[$page]['values'][$itemId][$key] = $this->formatLocalSourceValue($data);
                            } else {
                                $arrResult[$page]['values'][$itemId][$key] = $data;
                            }
                        }
                        // $arrResult[$page]['values'][$itemId]['itemsColumns'] = $itemsColumns;
                        $arrResult[$page]['values'][$itemId]['id'] = $itemId;
                        $arrResult[$page]['values'][$itemId]['last_modified_by'] = (isset($command->users_id) && $command->users_id != 0) ? $command->users_id : $command->last_modified_by;
                        $arrResult[$page]['values'][$itemId]['is_row_edit'] = ItemsUtils::is_row_edit($arrPermission, $obj, $isArchived);
                        $arrResult[$page]['values'][$itemId]['is_row_empty'] = ItemsUtils::isRowEmpty($obj);
                        $arrResult[$page]['values'][$itemId]['color_codes'] = ItemsUtils::getColorCodeValues($obj->cell_color_codes);
                        $arrResult[$page]['values'][$itemId]['is_grouped_item'] = (!empty($obj->parentGroup)) ? true : false;
                    }
                }
            }
            unset($arrChannles, $objCollectionData, $arrPermission);
            /**
             * Construct array return only array values with numeric index
             */
            $arrNew = [];
            if (!empty($arrResult)) {
                foreach ($arrResult as $key => $values) {
                    if (isset($values['values'])) {
                        foreach ($values['values'] as $k => $v) {
                            foreach ($v['columnValues'] as $kk => $vv) {
                                $keyVal = $this->search_exif($itemsColumns, $vv['name']);
                                $values['values'][$k][$keyVal] = $vv['value'];
                            }
                        }
                    }
                    $arrNew[$key][$command->multi_sort[0]['column']] = $values[$command->multi_sort[0]['column']];
//                    if (isset($values['versions'])) {
//                        $arrNew[$key]['versions_count'] = $this->findVersionsCount($values['versions']);
//                    }
                    $arrNew[$key]['total_items'] = count($values['values']);
                    $arrNew[$key]['values'] = array_values($values['values']);
                }
            }
            unset($arrResult);
        } catch (\Exception $ex) {
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        return array_values($arrNew);
    }

    function search_exif($exif, $field) {
        foreach ($exif as $data) {
            if ($data['lable'] == $field)
                return $data['key'];
        }
    }

    /**
     * Get Unique price code count based on items
     * @param array $values
     * @return int
     */
    function findVersionsCount($values) {
        $arrVersions = $uniqueCode = [];
        if (is_array($values) && !empty($values)) {
            $noPriceVersions = ['No Price Zone found.'];
            foreach ($values as $string) {
                if (!in_array($string, $noPriceVersions)) {
                    $arrVersions = explode(", ", $string);
                    foreach ($arrVersions as $code) {
                        $uniqueCode[] = $code;
                    }
                }
            }
            unset($arrVersions);
        }
        $versionsCount = count(array_unique($uniqueCode));
        return $versionsCount;
    }

    /**
     * 
     * @return type
     */
    function getPriceFormatColumns() {
        return array(
            'dotcom_price',
            'was_price',
            'save_amount',
            'cost',
            'base_unit_retail',
            'forecast_sales'
        );
    }

    /**
     * 
     * @return type
     */
    function getUrlColumns() {
        return array('landing_url');
    }

    /**
     * 
     * @return type
     */
    function getImageColumns() {
        return array('item_image_url', 'dotcom_thumbnail');
    }

    /**
     * 
     * @return type
     */
    function getEnumColumns() {
        return array('is_excluded', 'is_no_record', 'item_sync_status', 'publish_status');
    }

    /**
     * 
     * @param type $val
     * @return type
     */
    function changeEnumToBoolean($val) {
        return ($val == '1') ? true : false;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    function formatLocalSourceValue($value) {
        $string = !empty($value) && (strtolower($value) != 'yes') ? 'No - ' . $value : 'Yes';
        return $string;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    function formatAttributesValue($value) {
        $attrArr = [];
        if (!empty($value)) {
            $string = preg_replace("/[^.,0-9]{1,4}/", '', $value);
            $array = explode(',', $string);
            $arrMasterOption = $this->cardViewDs->getMasterDataOptions();
            foreach ($array as $row) {
                if (isset($arrMasterOption[$row])) {
                    $attrArr[] = $arrMasterOption[$row];
                }
            }
        }
        return implode(', ', $attrArr);
    }

}
