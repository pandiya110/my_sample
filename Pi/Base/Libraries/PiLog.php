<?php
namespace CodePi\Base\Libraries;

class PiLog{
	
	var $log;
	var $separator;
	
	function __construct($separator='\n'){
		
		$this->separator=$separator;
	}
	
	function setLog($log){
	
	  $objDate=date('Y-m-d H:i:s');	
	  $this->log[]=$log." ->".$objDate;	
		
	}
	
	function finalLog(){
		
		return implode($this->separator,$this->log);
	}
	
	
}