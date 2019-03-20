<?php
namespace CodePi\Base\Libraries\Agent;
use CodePi\Base\Libraries\Agent\Agent;
use Jenssegers\Agent\Agent as UserAgent;

class BrowserAgent implements Agent{
	
	function __construct(){
		
		
	}
	public function getDetails(){
		
		$agent = new UserAgent();
        $browser = $agent->browser();
        $browserversion = $agent->version($browser);
        $platform = $agent->platform();
        $platformversion = $agent->version($platform);

        $mobile = $agent->isPhone();
        $robot = $agent->isRobot();
        if (isset($mobile) && !empty($mobile))
            $device_type = 'Mobile';
        else if (isset($robot) && !empty($robot))
            $device_type = $robot;
        else
            $device_type = "PC";
            
        $returnArr = array(
            'browser' => $browser,
            'browser_version' => $browserversion,
            'user_agent' => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : "",
            'os' => $platform . " " . $platformversion,
            'device_type' => $device_type,
            'ip_address' => \Request::getClientIp(),
        );		
        
        return $returnArr;		
		
	}
	
	
}
