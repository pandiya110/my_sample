<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class CampaignsProjects extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'campaigns_projects';
    protected $fillable = array(
        'id',
        'campaigns_id',
        'aprimo_project_id',
        'activity_id',
        'title',
        'begin_date',
        'work_flow_id',
        'project_type_id',
        'project_status',
        'project_manager',
        'time_zone_id',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'ip_address',
    );

}
