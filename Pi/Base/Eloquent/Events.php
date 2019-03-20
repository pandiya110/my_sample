<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class Events extends Model {

    use PiEloquent;

    protected $table = 'events';
    public $timestamps = false;
    protected $fillable = array(
                                'id ',        
                                'statuses_id',
                                'is_draft',
                                'name',
                                'access_type',
                                'start_date',
                                'end_date',        
                                'created_by' ,
                                'last_modified_by' ,
                                'date_added' ,
                                'last_modified' ,
                                'gt_date_added' ,
                                'gt_last_modified' ,
                                'ip_address' ,
                                'campaigns_id',
                                'access_type',
                                'campaigns_projects_id'

                            );

}
