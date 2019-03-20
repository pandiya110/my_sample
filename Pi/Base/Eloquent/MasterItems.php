<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class MasterItems extends Model {

    use PiEloquent;

    protected $table = 'master_items';
    public $timestamps = false;
    protected $fillable = array(
                                'id ',  
                                'searched_item_nbr', 'signing_description', 
                                'no_of_linked_item', 'page', 'ad_block', 
                                'priority', 'event_dates', 'theme',
                                'theme_start', 'theme_end', 
                                'link_type', 'acctg_dept_nbr',
                                'sbu', 'dept_description', 
                                'category_description', 'items_status_code', 
                                'fineline_number', 'upc_nbr',
                                'plu_nbr', 'itemsid', 
                                'offers_id', 'season_year',
                                'landing_url', 'landing_comment', 
                                'item_image_url', 'box_image_name', 
                                'item_file_description', 'dotcom_description',
                                'marketing_description', 'advertised_item_description', 
                                'size',  'cost', 
                                'base_unit_retail', 'dotcom_price', 
                                'advertised_retail',
                                'price_id', 'was_price', 
                                'save_amount', 'bonus_details',
                                'new', 'usda_organic',
                                'qualifier_special_value', 'qualifier_new', 
                                'qualifier_only_walmart', 'day_ship','store_count', 
                                'on_feature', 'grouped_item', 
                                'line_list_item', 'co_op','versions', 
                                'forecast_sales', 'supplier_contact_name',
                                'supplier_contact_email', 'buyer_user_id', 
                                'sr_merchant', 'planner', 'pricing_mgr', 
                                'repl_manager', 'supplier',
                                'supplier_nbr', 'brand_name', 
                                'vendor_supplied_images', 'result_item_nbr',
                                'color_r_flarank', 'facing_brand_logo_bug',                                
                                'date_added' ,
                                'last_modified' ,                                
                                'ip_address' ,
                                'gtin_nbr', 'pronto_img_name', 'exclusive',
                                'made_in_america', 'rollback','special_value','version_code', 'parent_id',
                                'is_primary'
                            );
    

}
