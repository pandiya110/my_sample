<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class ItemsHeaders extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';
    protected $table = 'items_headers';
    protected $fillable = array (
                        'id',
			'column_name',
                        'column_label,',
                        'is_editable',
                        'field_type',
                        'column_order',
                        'status',
                        'is_mandatory',
                        'column_width',
                        'is_linked_item',
                        'column_source'
	);

}
