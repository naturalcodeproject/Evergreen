<?php
final class System {
	final public static function load($args) {
		if (empty($args['name'])) return false;
		if (empty($args['type'])) return false;
		if (empty($args['args'])) $args['args'] = array();
		
		if ($args['branch'] == Config::read('System.rootIdentifier')) {
			$args['branch'] = "";
		}
		
		$className = strtolower($args['name']);
		if (strpos($className, '.') !== FALSE) {
			$className = str_replace('.', ' ', $className);
			$className = ucwords($className);
			$className = str_replace(' ', '', $className);
		} else {
			$className = ucwords($className);
		}
		
		$className = $className."_".((strlen($args['branch'])) ? ucwords($args['branch']) : "").ucwords($args['type']);
		
		if (self::exists(array('name'=>$args['name'], 'type'=>$args['type'], 'branch'=>((empty($args['branch'])) ? Config::read('System.rootIdentifier') : $args['branch'])))) {
			if (is_array($args['args']) && count($args['args'])) {
				$methodArgs = array();
				foreach($args['args'] as $key => $value) {
					$methodArgs[] = '$args[\'args\'][\'' . $key . '\']';
				}
				eval('$object = new '.$className.'(' . implode(',', $methodArgs) . ');');
				unset($methodArgs);
			} else if (!empty($args['args'])) {
				$object = new $className($args['args']);
			} else {
				$object = new $className();
			}
			unset($className);
			if (ucwords($args['type']) == "Helper" || ucwords($args['type']) == "Plugin") {
				if (isset($object->requiredSystemMode) && $object->requiredSystemMode != Config::read("System.mode")) {
					// The system does not have the required mode so don't load the object
					Error::trigger("LOADER_REQUIRED_SYSTEM_MODE");
				}

				if (isset($object->minimumSystemVersion) && !version_compare(Config::read("System.version"), $object->minimumSystemVersion, ">")) {
					// The system version is lower than the object's required minimum so don't load the object
					Error::trigger("LOADER_MINIMUM_SYSTEM_VERSION");
				}

				if (isset($object->maximumSystemVersion)  && !version_compare(Config::read("System.version"), $object->maximumSystemVersion, "<")) {
					// The system version is higher than the object's required maximum so don't load the object
					Error::trigger("LOADER_MAXIMUM_SYSTEM_VERSION");
				}
			}
			
			return $object;
		} else {
			return false;
		}
	}
	
	final public static function exists($args) {
		if (empty($args['name'])) return false;
		if (empty($args['type'])) return false;
		
		if ($args['branch'] == Config::read('System.rootIdentifier')) {
			$args['branch'] = "";
		}
		
		$filePath = null;
		switch ($args['type']) {
			case 'helper':
				$filePath = Config::read("Path.physical").((strlen($args['branch'])) ? "/branches/".$args['branch'] : "")."/helpers/".$args['name'].".php";
			break;
			
			case 'controller':
				$filePath = Config::read("Path.physical").((strlen($args['branch'])) ? "/branches/".$args['branch'] : "")."/controllers/".$args['name'].".php";
			break;
			
			case 'model':
				$filePath = Config::read("Path.physical").((strlen($args['branch'])) ? "/branches/".$args['branch'] : "")."/models/".$args['name'].".php";
			break;
			
			case 'plugin':
				$filePath = Config::read("Path.physical").((strlen($args['branch'])) ? "/branches/".$args['branch'] : "")."/plugins/".$args['name']."/".$args['name'].".php";
			break;
		}
		
		if (file_exists($filePath)) {
			unset($filePath);
			return true;
		}
		unset($filePath);
		return false;
	}
	
	final public static function helper($name, $branch="") {
		$helper = false;
		
		$args = array_slice(func_get_args() , 2);
		
		if (Config::read("Branch.name") && empty($branch)) {
			$helper = self::load(array("name"=>$name, "type"=>"helper", "branch"=>Config::read("Branch.name"), "args" => $args));
		}
		
		if (!is_object($helper)) {
			$helper = self::load(array("name"=>$name, "type"=>"helper", "branch"=>$branch, "args" => $args));
		}
		
		if (!is_object($helper)) {
            Error::trigger("HELPER_NOT_FOUND");
        }
        unset($args);
        return $helper;
	}
	
	final public static function model($name, $branch="") {
		$model = false;
		
		$args = array_slice(func_get_args() , 2);
		
		if (Config::read("Branch.name") && empty($branch)) {
			$model = self::load(array("name"=>$name, "type"=>"model", "branch"=>Config::read("Branch.name"), "args" => $args));
		}
		
		if (!is_object($model)) {
			$model = self::load(array("name"=>$name, "type"=>"model", "branch"=>$branch, "args" => $args));
		}
		
		if (!is_object($model)) {
            Error::trigger("MODEL_NOT_FOUND");
        }
		unset($args);
        return $model;
	}
	
	final public static function plugin($name, $branch="") {
		$plugin = false;
		
		$args = array_slice(func_get_args() , 2);
		
		if (Config::read("Branch.name") && empty($branch)) {
			$plugin = self::load(array("name"=>$name, "type"=>"plugin", "branch"=>Config::read("Branch.name"), "args" => $args));
		}
		
		if (!is_object($plugin)) {
			$plugin = self::load(array("name"=>$name, "type"=>"plugin", "branch"=>$branch, "args" => $args));
		}
		
		if (!is_object($plugin)) {
            Error::trigger("PLUGIN_NOT_FOUND");
        }
        unset($args);
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
