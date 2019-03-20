<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsEditable extends Model {

    use PiEloquent;

    protected $table = 'items_editable';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'items_id',
        'page',
        'ad_block',
        'theme',
        'priority',
        'theme_start',
        'theme_end',
        'link_type',
        'landing_comment',
        'box_image_name',
        'advertised_item_description',
        'size',
        'advertised_retail',
        'price_id',
        'was_price',
        'save_amount',
        'bonus_details',
        'new',
        'usda_organic',
        'qualifier_special_value',
        'qualifier_new',
        'qualifier_only_walmart',
        'store_count',
        'on_feature',
        'grouped_item',
        'line_list_item',
        'co_op',
        'versions',
        'forecast_sales',
        'buyer_user_id',
        'sr_merchant',
        'planner',
        'pricing_mgr',
        'repl_manager',
        'vendor_supplied_images',
        'trcnbr_vsi_fname_lctn',
        'color_r_flarank',
        'facing_brand_logo_bug',
        'logo_bug_details',
        'event_dates',
        'supplier_contact_name',
        'supplier_contact_email',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address',
        'rollback',
        'exclusive',
        'made_in_america',
        'special_value',
        'mixed_column1',
        'mixed_column2',
        'tracking_id',
        'ogp_column1',
        'ogp_column2',
        'rank',
        'facebook_item_desc',
        'attributes',
        'short_version_description',
        'merchant_email_address',
        'aprimo_campaign_id',
        'aprimo_campaign_name',
        'aprimo_project_id',
        'aprimo_project_name',
        'merchant_name',
        'merchant_email',
        'local_sources',
        'sample_logistics_notes',
        'grocery_url',
        'each_value',
        'status',
        'story'    
    );

}
