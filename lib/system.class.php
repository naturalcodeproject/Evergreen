<?php
final class System {
	final public static function load($args) {
		if (empty($args['name'])) return NULL;
		if (empty($args['type'])) return NULL;
		if (empty($args['args'])) $args['args'] = '';
		
		if ($args['branch'] == Config::read('System.rootIdentifier')) {
			$args['branch'] = "";
		}
		
		$load = new Loader($args['name'], $args['type'], $args['branch'], $args['args']);
		return $load->load();
	}
	
	final public static function exists($args) {
		if (empty($args['name'])) return NULL;
		if (empty($args['type'])) return NULL;
		
		if ($args['branch'] == Config::read('System.rootIdentifier')) {
			$args['branch'] = "";
		}
		
		$load = new Loader($args['name'], $args['type'], $args['branch']);
		return $load->exists();
	}
	
	final public static function helper($name, $branch="") {
		$helper = false;
		
		$args = array_slice(func_get_args() , 2);
		
		if (Config::read("Branch.name") && empty($branch)) {
			$helper = self::load(array("name"=>$name, "type"=>"helper", "branch"=>Config::read("Branch.name"), "args" => $args));
		}
		
		if (!$helper) {
			$helper = self::load(array("name"=>$name, "type"=>"helper", "branch"=>$branch, "args" => $args));
		}
		
		if (!$helper) {
            Error::trigger("HELPER_NOT_FOUND");
        }
        
        return $helper;
	}
	
	final public static function model($name, $branch="") {
		$model = false;
		
		$args = array_slice(func_get_args() , 2);
		
		if (Config::read("Branch.name") && empty($branch)) {
			$model = self::load(array("name"=>$name, "type"=>"model", "branch"=>Config::read("Branch.name"), "args" => $args));
		}
		
		if (!$model) {
			$model = self::load(array("name"=>$name, "type"=>"model", "branch"=>$branch, "args" => $args));
		}

        if (!$model) {
            Error::trigger("MODEL_NOT_FOUND");
        }

        return $model;
	}
	
	final public static function plugin($name, $branch="") {
		$plugin = false;
		
		$args = array_slice(func_get_args() , 2);
		
		if (Config::read("Branch.name") && empty($branch)) {
			$plugin = self::load(array("name"=>$name, "type"=>"plugin", "branch"=>Config::read("Branch.name"), "args" => $args));
		}
		
		if (!$plugin) {
			$plugin = self::load(array("name"=>$name, "type"=>"plugin", "branch"=>$branch, "args" => $args));
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
        
        if (Config::read('Error.viewErrors')) {
            $display = true;
        }
        
        if (Config::read('Error.logErrors')) {
            $notify = true;
        }
   		
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
        
        $error_msg = '['.date('d-M-Y H:i:s').'] ';
        $error_msg .= "$type: ";
        $error_msg .= "\"$errstr\" occurred in $errfile on line $errline\n";
        
        if($display) echo '<PRE>' . $error_msg . '</PRE>';

		if($notify) {
            $logDir = Config::read("Error.logDirectory");
            if (empty($logDir)) {
                error_log($error_msg, $errno);
            } else {
                $log_file = Config::read("Path.physical")."/".$logDir."/";
                
                $year = date('Y');
                $month = date('m');
                $day = date('d');
                
                $log_file .= $year;
                mkdir($log_file);
                $log_file .= "/$month";
                mkdir($log_file);
                $log_file .= "/$day";
                mkdir($log_file);
                
                $log_file .= "/error.log";
                
                if(empty($log_file)) {
                    error_log($error_msg, 0);
                } else {
                    error_log($error_msg, 3, $log_file);
                }
            }
   		}
   
   		if($halt_script) exit -1;
	}
}
?>
