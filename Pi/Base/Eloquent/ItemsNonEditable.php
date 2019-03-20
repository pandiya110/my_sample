<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsNonEditable extends Model {

    use PiEloquent;

    protected $table = 'items_non_editable';
    public $timestamps = false;
    protected $fillable = array(
                                'id ',       
                                'items_id',
                                'acctg_dept_nbr',
                                'sbu',
                                'dept_description',
                                'category_description',
                                'items_status_code',                                
                                'offers_id',
                                'season_year',
                                'landing_url',
                                'item_image_url',
                                'item_file_description',
                                'signing_description',
                                'dotcom_description',
                                'marketing_description',
                                'cost',
                                'base_unit_retail',
                                'dotcom_price',
                                'day_ship',
                                'supplier',
                                'supplier_nbr',
                                'brand_name',
                                'created_by',
                                'last_modified_by',        
                                'date_added' ,
                                'last_modified' ,
                                'gt_date_added' ,
                                'gt_last_modified' ,
                                'ip_address',
                                'gtin_nbr',
                                'pronto_img_name','tracking_id','dotcom_thumbnail'
                            );

}
