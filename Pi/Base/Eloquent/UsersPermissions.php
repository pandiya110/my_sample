<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;


class UsersPermissions extends Model {

    use PiEloquent;
    
    protected $table = 'users_permissions';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'users_id',
        'permissions_id',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );
    
     static function userPermissions($user_id){          
          $result = Users::from('users as u')
                    ->join('users_permissions as up','u.id','=', 'up.users_id')
                    ->join('permissions as p','up.permissions_id','=', 'p.id')
                    ->where('u.id',$user_id)
                    ->where('p.status','1')
                    ->select('p.id as permissions_id','p.name','p.code','up.permission')
                    ->get();          
          return $result;         
      }
    

}
