<?php
/**
 * Formhandler Class
 *
 * This class handles automatically populating a form using an array to make
 * handling forms much easier for the developer.
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

class Formhandler {
	protected $caller;
	private $errors_arr = array();
	private $forms_arr = array();
	
	function __construct (&$caller) {
		$this->caller = $caller;
	}
	
	private function propertiesArray ($propertiesString) {
		$propertiesArray = array();
		$test = preg_split("/[\'\"] /i", trim($propertiesString)." ");
		foreach ($test as $item) {
			if (preg_match("/(.+)=[\'\"](.+)/i", trim($item), $matches)) {
				$propertiesArray[strtolower($matches[1])] = $matches[2];
			}
		}
		return $propertiesArray;
	}
	
	private function propertiesString ($propertiesArray) {
		$propertiesStringing = array();
		foreach ($propertiesArray as $key => $item) {
			$propertiesStringing[] = "{$key}=\"{$item}\"";
		}
		return implode(" ", $propertiesStringing);
	}
	
	public function decode (&$content) {
		## Form Fix
		$content = preg_replace_callback("/<form\s*(.*?)[^(->)]>(.*?)<\\/form>/is", array($this, 'formReplace'), $content);
	}
	
	private function formReplace($args) {
		## Fix Slashes
		$args = array_pad($args, 3, '');
		$attr = stripslashes($args[1]);
		$insides = stripslashes($args[2]);
		
		## Set up Properties
		$randomName = false;
		$properties = $this->propertiesArray($attr);
		if (empty($properties['name'])) {
			$properties['name'] = uniqid(mt_rand());
			$randomName = true;
		}
		if (empty($properties['update'])) {
			if (!empty($properties['method'])) {
				if (strtolower(trim($properties['method'])) == "post") {
					$properties['update'] = $_POST;
				} else {
					$properties['update'] = $_GET;
				}
			} else {
				$properties['update'] = $_GET;
			}
		}
		if (!empty($properties['update']) || !empty($properties['default']))  {
			if (isset($properties['update'])) {
				if (is_string($properties['update'])) {
					$properties['update'] = str_replace("\$this->", "\$this->caller->", $properties['update']);
					eval("\$properties['update'] = ".$properties['update'].";");
				}
				
				if (!empty($properties['update']) && $properties['update'] != false) {
					$this->forms_arr[$properties['name']]['update'] = $properties['update'];
				}
			}
			if (isset($properties['default'])) {
				$properties['default'] = str_replace("\$this->", "\$this->caller->", $properties['default']);
				eval("\$properties['default'] = ".$properties['default'].";");
				
				if (!empty($properties['default']) && $properties['default'] != false) {
					$this->forms_arr[$properties['name']]['default'] = $properties['default'];
				}
			}
			
			unset($properties['update'], $properties['default']);
		}
		
		if (!empty($properties['name'])) $this->forms_arr[$properties['name']]['attributes'] = $properties;
		
		## Set Up Elements
		$this->current_form = $properties['name'];
		$insides = preg_replace_callback("/<(input)\s*(.*?)>/is", array($this, 'formInsides'), $insides);
		$insides = preg_replace_callback("/<(textarea|select)\s*(.*?)>(.*?)<\\/([(textarea|select)]*?)>/is", array($this, 'formInsides'), $insides);
		
		## Replace Form
		unset($this->current_form);
		if ($randomName == true) {
			unset($properties['name']);
		}
		return "<form ".$this->propertiesString($properties).">
				{$insides}</form>";
	}
	
	private function formInsides($args) {
		$args = array_pad($args, 4, '');
		$type = strtolower(stripslashes($args[1]));
		$attr = stripslashes($args[2]);
		$insides = stripslashes($args[3]);
		
		## Properties Set Up
		$properties = $this->propertiesArray($attr);
		
		if (empty($properties['name'])) {
			$properties['name'] = '';
		}
		
		## Parse Name ##
		$this->parsed_name = explode("[", str_replace("]", "", str_replace("\"", "", str_replace("'", "", str_replace("[]", "", $properties['name'])))));
		if (!is_array($this->parsed_name)) {
			$this->parsed_name = array($properties['name']);
		}
		
		if ($type == "input") {
			if (!isset($properties['value'])) {
				$properties['value'] = '';
			}

			switch ($properties['type']) {
				case 'button':
				case 'submit':
					// do nothing
				break;
				case 'radio':
					if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
						if ($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $properties['value'] || (is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) && in_array($properties['value'], $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default'])) {
						if ($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $properties['value'] || (is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) && in_array($properties['value'], $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					}
				break;
				case 'checkbox':
					if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
						if ($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $properties['value'] || (is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) && in_array($properties['value'], $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default']) && isset($this->forms_arr[$this->current_form]['default'][$properties['name']])) {
						if ($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $properties['value'] || (is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) && in_array($properties['value'], $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					}
				break;
				case 'password':
				default:
					if ($properties['type'] != 'password' || ($properties['type'] == 'password' && (isset($properties['autopopulate']) && $properties['autopopulate'] == 'true'))) {
						if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
							$properties['value'] = $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name);
						} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default'])) {
							$value = $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name);
							
							// check to see if the above method found a value before we set it in the form
							if ($value != null) {
								$properties['value'] = $value;
							}
						}
					} else {
						$properties['value'] = '';
					}
					
					$properties['value'] = stripslashes(htmlspecialchars($properties['value']));
				break;
			}
			return "<{$type} ".$this->propertiesString($properties)." />";
		} elseif ($type == "textarea" || $type == "select") {
			if ($type == "select") {
				$this->current_select = preg_replace("/(.+)\[\]/i", "\\1", str_replace(" ", "_", $properties['name']));
				$insides = preg_replace_callback("/<option (.*?)>(.*?)<\\/option>/is", array($this, 'selectInsides'), $insides);
				$this->current_select = "";
			} elseif ($type == "textarea") {
				if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
					$insides = $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name);
				} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default'])) {
					$value = $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name);
					
					// check to see if the above method found a value before we set it in the form
					if ($value != null) {
						$insides = $value;
					}
				}
				
				$insides = stripslashes(htmlspecialchars($insides));
			}
			
			return "<{$type} ".$this->propertiesString($properties).">{$insides}</{$type}>";
		}
	}
	
	private function selectInsides ($args) {
		## Fix Slashes
		$args = array_pad((array)$args, 3, '');
		$attr = stripslashes($args[1]);
		$insides = stripslashes($args[2]);
		
		## Set up Properties
		$properties = $this->propertiesArray($attr);
		
		$properties = array_merge(array('value' => ''), $properties);
		
		if (isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
			if (($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $properties['value'] || $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $insides)  && !is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name))) {
				$properties['selected'] = "selected";
			} elseif (is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) && (in_array($properties['value'], $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) || in_array($insides, $this->getFormNameValue($this->forms_arr[$this->current_form]['update'], $this->parsed_name)))) {
				$properties['selected'] = "selected";
			} else {
				unset($properties['selected']);
			}
		} elseif (isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default'])) {
			if (($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $properties['value'] || $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $insides)  && !is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name))) {
				$properties['selected'] = "selected";
			} elseif (is_array($this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) && (in_array($properties['value'], $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) || in_array($insides, $this->getFormNameValue($this->forms_arr[$this->current_form]['default'], $this->parsed_name)))) {
				$properties['selected'] = "selected";
			} else {
				unset($properties['selected']);
			}
		}
		
		return "<option ".$this->propertiesString($properties).">{$insides}</option>";
	}
	
	function getFormNameValue($haystack, $find, $position=0) {
		if ((count($find)-1) == $position) {
			return isset($haystack[$find[$position]]) ? $haystack[$find[$position]] : null;
		}
		
		if (isset($haystack[$find[$position]])) {
			return $this->getFormNameValue($haystack[$find[$position]], $find, $position+1);
		} else {
			return false;
		}
	}
}
?>