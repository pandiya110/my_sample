<?php 
namespace CodePi\Base\Providers;

#use Illuminate\Routing\Router;
#use CodePi\Base\Eloquent\Instances;
class AppInstance
{
  
 
  
  public static function getAppInstanceId(){
      return 1;
      //$url = url('');
      //$objInstances = new Instances();
      //$result = $objInstances->where('url',$url)->first();
      
      //if(isset($result) && $result->id!=''){
        //return $result->id;
     // }return 1;
      // else{
      //   die('Instance Id not mapped');
      // }
      // die('Instance Id not mapped');
      //return config()->get('poet.appinstanceid');
  }
    
}
