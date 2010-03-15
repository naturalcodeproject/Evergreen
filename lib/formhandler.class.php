<?php
/*
*	Copyright (C) 1999-2006 Contagious
*	All Rights Reserved
*	
*	@author Daniel Baldwin
*	
*	Module: Common
*	Script: formhandler.class.php
*	Created: Wed Mar 21 10:35:37 MDT 2007
*/

class Formhandler {
	private $regex_form = "/<form (.+)>/i";
	private $regex_form_end = "/<\/form>/i";
	private $regex_input = "/<input ([^>]+)>/i";
	private $regex_textarea = "/<textarea ([^>]+)>([^(<\/textarea>)]+)<\/textarea>/i";
	private $regex_properties = "/(.+)=[\'\"](.+)/i";
	
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
			if (preg_match($this->regex_properties, trim($item), $matches)) {
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
		## Newline Fix
		$content = str_replace("\n", "<newline>", $content);
		## Form Fix
		$content = preg_replace("/<form\s*(.*?)[^(->)]>(.*?)<\\/form>/ixmse", "\$this->formReplace('\\1', '\\2')", $content);
		## Back to Normal
		$content = str_replace("<newline>", "\n", $content);
	}
	
	private function formReplace ($attr, $insides) {
		## Fix Slashes
		$attr = stripslashes($attr);
		$insides = stripslashes($insides);
		
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
			
			unset($properties['update']);
			unset($properties['default']);
		}
		
		if (!empty($properties['name'])) $this->forms_arr[$properties['name']]['attributes'] = $properties;
		
		## Set Up Elements
		$this->current_form = $properties['name'];
		$insides = preg_replace("/<(input)\s*(.*?)>/imsxe", "\$this->formInsides('\\1', '\\2', '')", $insides);
		$insides = preg_replace("/<(textarea|select)\s*(.*?)>(.*?)<\\/([(textarea|select)]*?)>/imsxe", "\$this->formInsides('\\1', '\\2', '\\3')", $insides);
		
		## Replace Form
		unset($this->current_form);
		if ($randomName == true) {
			unset($properties['name']);
		}
		return "<form ".$this->propertiesString($properties).">
				{$insides}</form>";
	}
	
	private function formInsides ($type, $attr, $insides) {
		$type = strtolower(stripslashes($type));
		$attr = stripslashes($attr);
		$insides = stripslashes($insides);
		
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
				$insides = preg_replace("/<option (.*?)>(.*?)<\\/option>/imsxe", "\$this->selectInsides(\"\\1\", \"\\2\")", $insides);
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
	
	private function selectInsides ($attr, $insides) {
		## Fix Slashes
		$attr = stripslashes($attr);
		$insides = stripslashes($insides);
		
		## Set up Properties
		$properties = $this->propertiesArray($attr);
		
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