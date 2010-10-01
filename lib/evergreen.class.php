<?php
/**
 * Evergreen Class
 *
 * This is the main load point for the framework. This class sets up the Autoloader,
 * error handling, loads in the base configuration, loads in the base registered errors,
 * causes the Config class to process the uri and actually loads the controller.
 *
 *
 * Copyright 2007-2010, NaturalCodeProject (http://www.naturalcodeproject.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright 2007-2010, NaturalCodeProject (http://www.naturalcodeproject.com)
 * @package			evergreen
 * @subpackage		lib
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

require dirname(__FILE__) . '/autoloader.class.php';

/**
 * Evergreen Class
 *
 * This is the main load point for the framework. This class sets up the Autoloader,
 * error handling, loads in the base configuration, loads in the base registered errors,
 * causes the Config class to process the uri and actually loads the controller.
 * 
 * Hooks:
 * shutdown.before
 * shutdown.after
 * showPageLoadInfo.before
 * showPageLoadInfo.after
 * 
 *
 * @package       evergreen
 * @subpackage    lib
 */
final class Evergreen {
	/**
	 * Constructor for the evergreen class that sets up all the necessary parts of the framework so it can run.
	 * 
	 * @access public
	 */
	public function __construct() {
		$starttime = microtime(true);
		
		try {
			// register the autoloaders
			Autoloader::register();
			
			// setup error handling
			set_error_handler(array("Config", "logError"), ini_get("error_reporting"));
			
			// load the main config.php file
			if (file_exists(Reg::get("Path.physical").'/config/config.php')) {
				include_once(Reg::get("Path.physical").'/config/config.php');
			} else {
				echo "You are missing the configuration file and without it Evergreen cannot run.";
				exit;
			}
			
			// load the main errors.php file
			if (file_exists(Reg::get("Path.physical").'/config/errors.php')) {
				include(Reg::get("Path.physical").'/config/errors.php');
			}
			
			// check if the welcome content is present and if it is show it
			if (file_exists(Reg::get("Path.physical").'/public/welcome.php')) {
				// Load the welcome content
				include(Reg::get("Path.physical").'/public/welcome.php');
				exit;
			}
			
			// code that is run at the exit of the script
			register_shutdown_function(array($this, 'shutdown'), $starttime);
			
			// process the uri and setup the Reg variables
			Config::processURI();
			
			// wait till after all the config files are loaded before loading in the autoload files
			Autoloader::loadFiles();
			
			// build the controller class name
			$load['name'] = Config::uriToClass(Reg::get("URI.working.controller"));
			if (Reg::hasVal("Branch.name")) {
				$load['branch'] = Config::uriToClass(Reg::get("Branch.name"));
			}
			$load['type'] = 'Controller';
			$load = implode('_', $load);
			
			// create an instance of the controller
			$controller = new $load();
			// run the _showView method in the loaded controller
			$controller->_showView();
			
		} catch(EvergreenException $e) {
			// handler for the EvergreenException class
			$e->processError();
			
		} catch(Exception $e) {
			// handler for general exceptions
			if (Config::read("System.mode") != "development") {
				echo Config::read("Error.generalErrorMessage");
				exit;
			} else {
				echo $e;
			}
		}
	}
	
	public function shutdown($starttime) {
		// call hook
		Hook::call('shutdown.before', array(&$starttime));
		
		// display page load info as in how many queries were run, how much memory it took to run, and how long it took to run
		if (Reg::get('System.displayPageLoadInfo') == true) {
			$this->showPageLoadInfo($starttime);
		}
		
		// call hook
		Hook::call('shutdown.after');
	}
	
	/**
	 * Returns the page load info.
	 * 
	 * @access public
	 * @param integer $starttime The float microtime that the script started
	 */
	public function showPageLoadInfo($starttime) {
		// call hook
		Hook::call('showPageLoadInfo.before', array(&$starttime));
		
		$totaltime = microtime(true) - $starttime;
		echo sprintf('Time : %.3fs seconds', $totaltime);
		
		if (class_exists('DB', false)) {
			echo ' | Queries Executed : ' . DB::getQueryCount();
		}
		
		if (function_exists('memory_get_usage')) {
			// php has to be compiled with --enable-memory-limit for this to exist
			// prior to version 5.2.1
			echo ' | Memory Used : ' . $this->convertBytes(memory_get_usage(true));
		}
		
		if (function_exists('memory_get_peak_usage')) {
			// php 5.2+
			echo ' | Peak Memory Used: ' . $this->convertBytes(memory_get_peak_usage(true));
		}
		
		// call hook
		Hook::call('showPageLoadInfo.after');
	}
	
	/**
	 * Returns the converted bytes format for the page load info.
	 * 
	 * @access public
	 * @param integer $size Memory in bytes
	 * @return string
	 */
	public function convertBytes($size) {
		$unit = array('b','kb','mb','gb','tb','pb');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}
}
?>