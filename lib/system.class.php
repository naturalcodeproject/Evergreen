<?php
final class System
{
	
	final private function __construct() {}
	
	final public static function helper($name)
	{
		return new Loader($name, "helper");
	}
	
	final public static function model($name)
	{
		return new Loader($name, "model");
	}
	
	final public static function plugin($name)
	{
		return new Loader($name, "plugin");
	}
	
	public static function log_error($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$type = '';
   		$display = false;
   		$notify = false;
   		$halt_script = true;
   		
		switch($errno) {
   			case E_USER_NOTICE:
   				$notify = true;
   			case E_NOTICE:
   				$halt_script = false;        
       			$type = "Notice";
       			break;
   			case E_USER_WARNING:
   			case E_COMPILE_WARNING:
   			case E_CORE_WARNING:
   			case E_WARNING:
      			$halt_script = false;       
       			$type = "Warning";
       			break;
   			case E_USER_ERROR:
       		case E_COMPILE_ERROR:
   			case E_CORE_ERROR:
   			case E_ERROR:
       			$type = "Fatal Error";
       			$display = true;
       			$notify = true;
       			break;
   			case E_PARSE:
       			$type = "Parse Error";
       			$display = true;
       			$notify = true;
       			break;
   			default:
      			$type = "Unknown Error";
      			$display = true;
      			$notify = true;
       			break;
		}

		if($notify) {
			$log_file = dirname(dirname(__FILE__))."/errors/error.log";
			$date = date('Y-m-d');
			$log_file .= ".$date";
		
			$error_msg = '['.date('d-M-Y H:i:s').'] ';
       		$error_msg .= "$type: ";
       		$error_msg .= "\"$errstr\" occurred in $errfile on line $errline\n";

       		if($display) echo $error_msg;
       		
          	if(empty($log_file)) {
             	error_log($error_msg, 0);
          	} else {
          		error_log($error_msg, 3, $log_file);
          	}
   		}
   
   		if($halt_script) exit -1;
	}
}
?>