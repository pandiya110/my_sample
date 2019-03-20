<?php

namespace CodePi\Items\Utils;

use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Base\Libraries\PiLib;
use CodePi\Events\DataSource\EventsDataSource;

class ItemsGridDataResponse {

    private $objItemDs;
    private $objChannelDs;
    private $objEventDs;

    function __construct() {
        $this->objItemDs = new ItemsDataSource();
        $this->objChannelDs = new ChannelsDataSource();
        $this->objEventDs = new EventsDataSource();
    }

    /**
     * 
     * @param type $returnResult
     * @param type $command
     * @return array
     */
    function getGridResponse($returnResult, $command = array(), $intEventid = 0) {
        $arrResponse = $arrItems = $arrItemsId = [];
        $arrResult = isset($returnResult['objResult']) ? $returnResult['objResult'] : [];
        $permissions = isset($returnResult['permissions']) ? $returnResult['permissions'] : [];
        $groups = $this->objItemDs->getGroupNameByEventId($intEventid);
        if (!empty($arrResult)) {
            //$permissions = isset($returnResult['permissions']) ? $returnResult['permissions'] : [];
            //$users_id = (isset($command->users_id) && $command->users_id != 0) ? $command->users_id : $command->last_modified_by;

            $arrChannels = $this->objChannelDs->getItemsChannelsAdtypes($intEventid);
            foreach ($arrResult as $val) {

                $val = (object) $this->objItemDs->filterStringDecode((array) $val);
                // $val->last_modified_by = (isset($command->users_id) && $command->users_id != 0) ? $command->users_id : $command->last_modified_by;
                $val->is_row_edit = !empty($permissions) ? ItemsUtils::is_row_edit($permissions, $val) : true;
                $val->is_excluded = ($val->is_excluded == '1') ? true : false;
                $val->item_sync_status = ($val->item_sync_status == '1') ? true : false;
                $val->publish_status = ($val->publish_status == '1') ? true : false;
                $val->is_no_record = ($val->is_no_record == '1') ? true : false;
                $val->price_id = trim(strtoupper($val->price_id));
                /**
                 * Format the price columns values
                 */
                $val->dotcom_price = ItemsUtils::formatPriceValues($val->dotcom_price);
                $val->advertised_retail = ItemsUtils::formatAdRetaliValue($val->advertised_retail);//formatPriceValues($val->advertised_retail);
                $val->was_price = ItemsUtils::formatPriceValues($val->was_price);
                $val->save_amount = ItemsUtils::formatPriceValues($val->save_amount);
                $val->cost = ItemsUtils::formatPriceValues($val->cost);
                $val->base_unit_retail = ItemsUtils::formatPriceValues($val->base_unit_retail);
                $val->forecast_sales = ItemsUtils::formatPriceValues($val->forecast_sales);
                /**
                 * Set Price rule conditions
                 */
                $ruleConditions = ItemsUtils::setWaspriceEditorNonEdit(trim(strtoupper($val->price_id)));
                $val->is_wasprice_edit = $ruleConditions['is_wasprice_edit'];
                $val->is_wasprice_req = $ruleConditions['is_wasprice_req'];
                $val->is_adretail_edit = $ruleConditions['is_adretail_edit'];
                $val->is_adretail_req = $ruleConditions['is_adretail_req'];
                $val->is_saveamount_edit = $ruleConditions['is_saveamount_edit'];
                /**
                 * Set default values for below columns
                 */
                $val->made_in_america = ItemsUtils::setDefaultNoValuesCol('made_in_america', $val->made_in_america);
                $val->day_ship = ItemsUtils::setDefaultNoValuesCol('day_ship', $val->day_ship);
                $val->co_op = ItemsUtils::setDefaultNoValuesCol('co_op', $val->co_op);
                $val->local_sources = !empty($val->local_sources) && ($val->local_sources != 'Yes') ? 'No - '.$val->local_sources : 'Yes';
                $val->status = !empty($val->status) ? $val->status : '';
                $val->landing_url = ItemsUtils::addhttp($val->landing_url);
                
                /**
                 * Check URL is valid or not
                 */
                $val->landing_url = PiLib::isValidURL($val->landing_url);
                $val->item_image_url = PiLib::isValidURL($val->item_image_url);
                $val->original_image = PiLib::isValidURL($val->item_image_url);
                if (!empty($val->item_image_url)) {
                    $val->item_image_url = $val->item_image_url . config('smartforms.iqsThumbnail_60x60');
                }
                $val->dotcom_thumbnail = ($val->dotcom_thumbnail) ? PiLib::isValidURL($val->dotcom_thumbnail) : PiLib::isValidURL($val->original_image);
                if (!empty($val->dotcom_thumbnail)) {
                    $val->dotcom_thumbnail = $val->dotcom_thumbnail . config('smartforms.iqsThumbnail_60x60');
                }

                $val->acitivity = $val->id;
                $val->no_of_linked_item = (int)$val->link_count;
                $val->adretails_highlight = ItemsUtils::getAdRetailSoldOutStaus($val);
                $val->attributes = $this->objItemDs->getAttributesSelectedValues($val->attributes);
                $val->color_codes = ItemsUtils::getColorCodeValues($val->cell_color_codes);
                $val->is_row_empty = ItemsUtils::isRowEmpty($val);
                $val->is_movable = false;
//                if ($val->is_no_record == false && $val->is_row_empty == false) {
//                    $val->is_movable = $this->objItemDs->isMovable($val->id);
//                }
                $val->is_grouped_item = (!empty($val->parentGroup)) ? true : false;//$this->dataSource->isGrouped($val->id);
                $val->grouped_item = !empty($val->grouped_item) ? $val->grouped_item : '';
                //$isGroupedItems = !empty($val->childGroup) ? true : false;  //$this->dataSource->checkExistsInGroupedItems($val->id);
                $val->is_grouped_item_edit = !empty($val->is_grouped_item) || empty($groups) || !empty($val->childGroup) ? false : true;                    
                //$val->versions = $this->objItemDs->formatVersionsCode($val->versions, $val->id);
                //$val->is_completed = false;
                if ($val->is_row_empty == false) {
                    $arrItemsId[] = $val->id;
                }
                $arrItems[$val->id] = array_merge((array) $val, isset($arrChannels[$val->id]) ? $arrChannels[$val->id] : []);
            }
            $arrMove = $this->objItemDs->isMovable($arrItemsId, $intEventid);
            unset($arrItemsId);
            foreach ($arrMove as $values) {
                if (array_search($values, array_column($arrItems, 'id')) !== False) {
                    $arrItems[$values]['is_movable'] = true;
                } else {
                    if (isset($arrItems[$values])) {
                        $arrItems[$values]['is_movable'] = false;
                    }
                }
            }
            unset($arrMove);

//            if(!empty($arrItems)){
//                $count = count($arrItems);
//                $arrItems[$count-1]['is_completed']=true;
//            }
            //$arrEvent = $this->objEventDs->getEventAdditionalInfoByPermissions($command);
            $arrResponse = array('itemValues' => array_values($arrItems));
            unset($arrItems);
        }

        unset($arrResult);
        return $arrResponse;
    }

}

?>
