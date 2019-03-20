<?php

namespace CodePi\Settings\Eloquant;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model; 
class Settings extends Model {
    public $timestamps=false;
    protected $table = 'settings';
    protected $fillable = array('object_key', 'object_type', 'object_string','object_enum','object_int', 'created_by', 'last_modified_by' , 'date_added' , 'last_modified' , 'gt_date_added' , 'gt_last_modified' , 'ip_address'); 

	
	static	function key( $key='stop_outgoing_emails') {
    	$results  = self::where('object_key','=', $key)->get();
    	$type='yes';
    	if(!empty($results))
    	{
    		foreach($results as $l=>$m)
    		{
    		        $object_key="object_".$m->object_type;
    	             $type=$m->{$object_key};    	
    		}
    
    	}    
    	return $type;    
    }
		
	}
