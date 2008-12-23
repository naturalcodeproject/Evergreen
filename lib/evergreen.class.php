<?php
final class Evergreen
{
	protected $config;
	
	function __construct()
	{
		## Register Autoloader Class ##
		spl_autoload_register(array('AutoLoaders', 'main'));
		
		## Register Error Handler Class ##
		set_error_handler(array("System", "log_error"), ini_get('error_reporting'));
		
		## Get Config ##
		$this->config = Factory::get_config();
		
		## Run Evergreen ##
		$this->run();
	}
	
	private function run()
	{
		#################
		# URI Managment #
		#################
		$this->config->check_uri();
		$uri_params = $this->config->get_working_uri();
		$url_vals = array_keys($uri_params);
		$branch_name = $this->config->get_branch_name();
		
		#######################
		# Load in Controller  #
		#######################
		if (file_exists($this->config->get_base_path()."/controllers/".$uri_params[$url_vals[0]].".php") && empty($branch_name))
		{
			## Run Controller ##
			$class_name = ucwords($uri_params[$url_vals[0]]);
			$controller = new $class_name($settings, $uri_params, $url_vals);
			
		}
		elseif (is_dir($this->config->get_base_path()."/branches/{$branch_name}") && !empty($branch_name))
		{
			## Unload Main Autoloader ##
			spl_autoload_unregister(array('AutoLoaders', 'main'));
			
			## Load Branch Autoloader ##
			spl_autoload_register(array('AutoLoaders', 'branches'));
			
			## Run Branch Controller ##
			if (file_exists($this->config->get_base_path()."/branches/{$branch_name}/controllers/".reset($uri_params).".php"))
			{
				$class_name = ucwords(reset($uri_params));
				$controller = new $class_name($settings, $uri_params, $url_vals);
			}
			else
			{
				header("HTTP/1.0 404 Not Found");
				//header("Location: ".URI_ROOT."/error");
				exit;
			}
			
		}
		else
		{
			header("HTTP/1.0 404 Not Found");
			//header("Location: ".URI_ROOT."/error");
			exit;
		}
	}
	
}

class AutoLoaders
{
	static function main($class_name)
	{
		$class_name[0] = strtolower($class_name[0]);
		self::parse_class_name($class_name);
		self::base_includes($class_name);
		
		## Controller Include ##
		if (file_exists(Factory::get_config()->get_base_path()."/controllers/{$class_name}.php"))
		{
			include_once("controllers/{$class_name}.php");
		}
	}
	
	static function branches($class_name)
	{
		$class_name[0] = strtolower($class_name[0]);
		self::parse_class_name($class_name);
		$branch_name = Factory::get_config()->get_branch_name();
		self::base_includes($class_name);
		
		## Branch Controller Include ##
		if (file_exists(Factory::get_config()->get_base_path()."/branches/{$branch_name}/controllers/{$class_name}.php"))
		{
			include_once("branches/{$branch_name}/controllers/{$class_name}.php");
		}
	}
	
	static function base_includes($class_name)
	{
		## Base System Includes ##
		require_once("lib/factory.class.php");
		require_once("lib/system.class.php");
		require_once("lib/config.class.php");
		
		## Other Lib Includes ##
		if (file_exists(Factory::get_config()->get_base_path()."/lib/{$class_name}.class.php"))
		{
			require_once("lib/{$class_name}.class.php");
		}
	}
	
	static function parse_class_name(&$class_name)
	{
		if (!ctype_lower($class_name))
		{
			$new_name = '';
			for($i = 0; $i < strlen($class_name); $i++)
			{
				$char = $class_name[$i];
				if (ctype_upper($char))
				{
					$new_name .= '.' . strtolower($char);
				} else {
					$new_name .= $char;
				}
			}
			$class_name = $new_name;
		}
	}
}

?>