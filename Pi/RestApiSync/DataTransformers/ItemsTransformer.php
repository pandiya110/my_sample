<?php

namespace CodePi\RestApiSync\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;

class ItemsTransformer extends PiTransformer {

    /**
     * @param object $objContacts
     * @return array It will loop all records of events table
     */
    function transform($row) {
        
        
            $arrResult = $this->mapColumns($row);
            $arrResult['searched_item_nbr'] = PiLib::piIsset($row, 'searched_item_nbr', '');
            $arrResult['event_dates'] = PiLib::piIsset($row, 'event_dates', '');
            $arrResult['fineline_number'] = PiLib::piIsset($row, 'fineline_number', '');
            $arrResult['plu_nbr'] = PiLib::piIsset($row, 'plu_nbr', '');
            $arrResult['upc_nbr'] = PiLib::piIsset($row, 'upc_nbr', '');
            $arrResult['item_image_url'] = PiLib::piIsset($row, 'item_image_url', '');
            $arrResult['size'] = PiLib::piIsset($row, 'size', '');
            $arrResult['landing_url'] = PiLib::piIsset($row, 'landing_url', '');
            $arrResult['landing_comment'] = PiLib::piIsset($row, 'landing_comment', '');
            $arrResult['page'] = PiLib::piIsset($row, 'page', '');
            $arrResult['ad_block'] = PiLib::piIsset($row, 'ad_block', '');
            $arrResult['priority'] = PiLib::piIsset($row, 'priority', '');
            $arrResult['color_r_flarank'] = PiLib::piIsset($row, 'color_r_flarank', '');
            $arrResult['rank'] = PiLib::piIsset($row, 'rank', '');
            $arrResult['price_id'] = PiLib::piIsset($row, 'price_id', '');
            $arrResult['was_price'] = PiLib::piIsset($row, 'was_price', '');
            $arrResult['save_amount'] = PiLib::piIsset($row, 'save_amount', '');
            $arrResult['advertised_retail'] = PiLib::piIsset($row, 'advertised_retail', '');
            $arrResult['bonus_details'] = PiLib::piIsset($row, 'bonus_details', '');
            $arrResult['attributes'] = PiLib::piIsset($row, 'attributes', '');
            $arrResult['versions'] = PiLib::piIsset($row, 'versions', '');
            $arrResult['short_version_description'] = PiLib::piIsset($row, 'short_version_description', '');
            $arrResult['advertised_item_description'] = PiLib::piIsset($row, 'advertised_item_description', '');
            $arrResult['facing_brand_logo_bug'] = PiLib::piIsset($row, 'facing_brand_logo_bug', '');
            $arrResult['facebook_item_desc'] = PiLib::piIsset($row, 'facebook_item_desc', '');
            $arrResult['theme'] = PiLib::piIsset($row, 'theme', '');
            $arrResult['co_op'] = PiLib::piIsset($row, 'co_op', '');
            $arrResult['store_count'] = PiLib::piIsset($row, 'store_count', '');
            $arrResult['on_feature'] = PiLib::piIsset($row, 'on_feature', '');
            $arrResult['grouped_item'] = PiLib::piIsset($row, 'grouped_item', '');
            $arrResult['mixed_column1'] = PiLib::piIsset($row, 'mixed_column1', '');
            $arrResult['mixed_column2'] = PiLib::piIsset($row, 'mixed_column2', '');
            $arrResult['buyer_user_id'] = PiLib::piIsset($row, 'buyer_user_id', '');
            $arrResult['sr_merchant'] = PiLib::piIsset($row, 'sr_merchant', '');
            $arrResult['planner'] = PiLib::piIsset($row, 'planner', '');
            $arrResult['pricing_mgr'] = PiLib::piIsset($row, 'pricing_mgr', '');
            $arrResult['repl_manager'] = PiLib::piIsset($row, 'repl_manager', '');
            $arrResult['planner'] = PiLib::piIsset($row, 'planner', '');
            $arrResult['supplier_contact_name'] = PiLib::piIsset($row, 'supplier_contact_name', '');
            $arrResult['supplier_contact_email'] = PiLib::piIsset($row, 'supplier_contact_email', '');
            $arrResult['vendor_supplied_images'] = PiLib::piIsset($row, 'vendor_supplied_images', '');
            $arrResult['trcnbr_vsi_fname_lctn'] = PiLib::piIsset($row, 'trcnbr_vsi_fname_lctn', '');
            $arrResult['channels'] = PiLib::piIsset($row, 'channels', []);
            $arrResult['channels'] = PiLib::piIsset($row, 'channels', []);
            $arrResult['last_modified'] = PiLib::piIsset($row, 'last_modified', '');
            $arrResult['is_excluded'] = (isset($row['is_excluded']) && $row['is_excluded'] == true) ? 'Excluded' : 'Activated';
            $arrResult['is_no_record'] = (isset($row['is_no_record']) && $row['is_no_record'] == true) ? 'No Data Found' : false;
            $arrResult['item_sync_status'] = (isset($row['item_sync_status']) && $row['item_sync_status'] == true) ? 'New Data Available' : false;
            $arrResult['publish_status'] = (isset($row['publish_status']) && $row['publish_status'] == true) ? 'Published' : 'Unpublished';
       
            return $this->filterData($arrResult);
         
    }

}
