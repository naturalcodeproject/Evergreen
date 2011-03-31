<?php
/**
 * Error Class
 *
 * This class is a helper for thrown errors in that it holds the data for an evergreen exception
 * to allow the error information to be accessed from url's or pages loaded by the thrown error.
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

/**
 * Error Class
 *
 * This class is a helper for thrown errors in that it holds the data for an evergreen exception
 * to allow the error information to be accessed from url's or pages loaded by the thrown error.
 *
 * Hooks:
 * Error.setupError.before
 * Error.setupError.after
 * Error.getMessage
 * Error.getCode
 * Error.getFile
 * Error.getLine
 * Error.getTrace
 * Error.getParams
 * Error.triggered
 *
 * @package       evergreen
 * @subpackage    lib
 */
final class Error {
	/**
	 * Indicator used to tell if an error has been triggered.
	 * 
	 * @access private
	 * @static
	 * @var boolean
	 */
	private static $triggered = false;
	
	/**
	 * Current triggered error's message.
	 * 
	 * @access private
	 * @static
	 * @var string
	 */
	private static $message = "Unknown exception";
	
	/**
	 * Current triggered error's code.
	 * 
	 * @access private
	 * @static
	 * @var mixed
	 */
	private static $code = 0;
	
	/**
	 * Current triggered error's file.
	 * 
	 * @access private
	 * @static
	 * @var string
	 */
	private static $file = null;
	
	/**
	 * Current triggered error's line.
	 * 
	 * @access private
	 * @static
	 * @var integer
	 */
	private static $line = 0;
	
	/**
	 * Current triggered error's trace.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $trace = array();
	
	/**
	 * Current triggered error's params.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $params = array();
	
	/**
	 * Sets up the error class with data from the thrown error.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @param object $e The exception that was thrown
	 */
	final public static function setupError($e) {
		// call hook
		Hook::call('Error.setupError.before', array(&$e));
		
		if (is_object($e)) {
			self::$message = $e->getMessage();
			self::$code = $e->getCode();
			self::$file = $e->getFile();
			self::$line = $e->getLine();
			self::$trace = $e->getTrace();
			self::$params = $e->getParams();
			self::$triggered = true;
		}
		
		// call hook
		Hook::call('Error.setupError.after');
	}
	
	/**
	 * Returns the error message.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return string
	 */
	final public static function getMessage() {
		$message = self::$message;
		
		// call hook
		Hook::call('Error.getMessage', array(&$message));
		
		return $message;
	}
	
	/**
	 * Returns the error code.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return mixed
	 */
	final public static function getCode() {
		$code = self::$code;
		
		// call hook
		Hook::call('Error.getCode', array(&$code));
		
		return $message;
	}
	
	/**
	 * Returns the file that the error occurred in.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return string
	 */
	final public static function getFile() {
		$file = self::$file;
		
		// call hook
		Hook::call('Error.getFile', array(&$file));
		
		return $file;
	}
	
	/**
	 * Returns the line number the error occurred at.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return integer
	 */
	final public static function getLine() {
		$line = self::$line;
		
		// call hook
		Hook::call('Error.getLine', array(&$line));
		
		return $line;
	}
	
	/**
	 * Returns the error trace.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return array
	 */
	final public static function getTrace() {
		$trace = self::$trace;
		
		// call hook
		Hook::call('Error.getTrace', array(&$trace));
		
		return $trace;
	}
	
	/**
	 * Returns the params from the error.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return array
	 */
    final public static function getParams() {
        $params = self::$params;
		
		// call hook
		Hook::call('Error.getParams', array(&$params));
		
		return $params;
    }
	
	/**
	 * Returns if an error has been triggered or not.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return boolean true if an error has been triggered and boolean false if not
	 */
	final public static function triggered() {
		$triggered = self::$triggered;
		
		// call hook
		Hook::call('Error.triggered', array(&$triggered));
		
		return $triggered;
	}
}
?>