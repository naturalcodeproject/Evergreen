<?php
final class System {
	final public static function load($args) {
		if (empty($args['name'])) return NULL;
		if (empty($args['type'])) return NULL;
		
		if ($args['branch'] == "MAIN") {
			$args['branch'] = "";
		}
		
		$load = new Loader($args['name'], $args['type'], $args['branch']);
		return $load->load();
	}
	
	final public static function exists($args) {
		if (empty($args['name'])) return NULL;
		if (empty($args['type'])) return NULL;
		
		if ($args['branch'] == "MAIN") {
			$args['branch'] = "";
		}
		
		$load = new Loader($args['name'], $args['type'], $args['branch']);
		return $load->exists();
	}
	
	final public static function helper($name, $branch="") {
		if (Config::read("Branch.name") && empty($branch)) {
			$helper = self::load(array("name"=>$name, "type"=>"helper", "branch"=>Config::read("Branch.name")));
		}
		
		if (!$helper) {
			$helper = self::load(array("name"=>$name, "type"=>"helper", "branch"=>$branch));
		}
		
		if (!$helper) {
            Error::trigger("HELPER_NOT_FOUND");
        }
        
        return $helper;
	}
	
	final public static function model($name, $branch="") {
		if (Config::read("Branch.name") && empty($branch)) {
			$model = self::load(array("name"=>$name, "type"=>"model", "branch"=>Config::read("Branch.name")));
		}
		
		if (!$model) {
			$model = self::load(array("name"=>$name, "type"=>"model", "branch"=>$branch));
		}

        if (!$model) {
            Error::trigger("MODEL_NOT_FOUND");
        }

        return $model;
	}
	
	final public static function plugin($name, $branch="") {
		if (Config::read("Branch.name") && empty($branch)) {
			$plugin = self::load(array("name"=>$name, "type"=>"plugin", "branch"=>Config::read("Branch.name")));
		}
		
		if (!$plugin) {
			$plugin = self::load(array("name"=>$name, "type"=>"plugin", "branch"=>$branch));
		}
		
		if (!$plugin) {
            Error::trigger("PLUGIN_NOT_FOUND");
        }
        
        return $plugin;
	}
	
	public static function logError($errno, $errstr, $errfile, $errline, $errcontext) {
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
			$log_file = Config::read("System.physicalPath")."/".Config::read("Errors.logDirectory")."/error.log";
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
