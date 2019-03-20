<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class Items extends Model {

    use PiEloquent;

    protected $table = 'items';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'events_id',
        'searched_item_nbr',         
        'upc_nbr',
        'fineline_number',
        'plu_nbr',  
        'itemsid',
        'items_unique_key',
        'acitivity',
        'is_excluded',
        'is_no_record',
        'publish_status',
        'items_type',
        'item_sync_status',
        'link_item_parent_id',
        'created_by',
        'last_modified_by',        
        'date_added' ,
        'last_modified' ,
        'gt_date_added' ,
        'gt_last_modified',
        'ip_address' ,
        'master_items_id',
        'versions_code',
        'copy_items_id',
        'tracking_id',
        'cell_color_codes',
        'items_import_source',        
    );

}
