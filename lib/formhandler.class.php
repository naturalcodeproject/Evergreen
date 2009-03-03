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
	
	private function properties_arr ($properties_str) {
		$properties_arr = array();
		$test = preg_split("/[\'\"] /i", trim($properties_str)." ");
		foreach ($test as $item) {
			if (preg_match($this->regex_properties, trim($item), $matches)) {
				$properties_arr[strtolower($matches[1])] = $matches[2];
			}
		}
		return $properties_arr;
	}
	
	private function properties_str ($properties_arr) {
		$properties_string = array();
		foreach ($properties_arr as $key => $item) {
			$properties_string[] = "{$key}=\"{$item}\"";
		}
		return implode(" ", $properties_string);
	}
	
	public function decode (&$content) {
		## Newline Fix
		$content = str_replace("\n", "<newline>", $content);
		## Form Fix
		$content = preg_replace("/<form\s*(.*?)[^(->)]>(.*?)<\\/form>/ixmse", "\$this->form_replace('\\1', '\\2')", $content);
		## Back to Normal
		$content = str_replace("<newline>", "\n", $content);
	}
	
	private function form_replace ($attr, $insides) {
		## Fix Slashes
		$attr = stripslashes($attr);
		$insides = stripslashes($insides);
		
		## Set up Properties
		$properties = $this->properties_arr($attr);
		if (!empty($properties['update']) || !empty($properties['default']))  {
			$properties['update'] = str_replace("\$this->", "\$this->caller->", $properties['update']);
			$properties['default'] = str_replace("\$this->", "\$this->caller->", $properties['default']);
			@eval("\$properties['update'] = ".$properties['update'].";");
			@eval("\$properties['default'] = ".$properties['default'].";");
			if (!empty($properties['default'])) $this->forms_arr[$properties['name']]['default'] = $properties['default'];
			if (!empty($properties['update'])) $this->forms_arr[$properties['name']]['update'] = $properties['update'];
			
			unset($properties['update']);
			unset($properties['default']);
		}
		
		if (!empty($properties['name'])) $this->forms_arr[$properties['name']]['attributes'] = $properties;
		
		## Set Up Elements
		$this->current_form = $properties['name'];
		$insides = preg_replace("/<(input)\s*(.*?)>/imsxe", "\$this->form_insides('\\1', '\\2', '')", $insides);
		$insides = preg_replace("/<(textarea|select)\s*(.*?)>(.*?)<\\/([(textarea|select)]*?)>/imsxe", "\$this->form_insides('\\1', '\\2', '\\3')", $insides);
		
		## Replace Form
		$this->current_form = "";
		return "<form ".$this->properties_str($properties).">
				{$insides}</form>";
	}
	
	private function form_insides ($type, $attr, $insides) {
		$type = strtolower(stripslashes($type));
		$attr = stripslashes($attr);
		$insides = stripslashes($insides);
		
		## Properties Set Up
		$properties = $this->properties_arr($attr);
		
		## Parse Name ##
		$this->parsed_name = explode("[", str_replace("]", "", str_replace("\"", "", str_replace("'", "", str_replace("[]", "", $properties['name'])))));
		if (!is_array($this->parsed_name)) {
			$this->parsed_name = array($properties['name']);
		}
		
		if ($type == "input") {
			switch ($properties['type']) {
				case 'radio':
					if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
						if ($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $properties['value'] || (is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) && in_array($properties['value'], $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default'])) {
						if ($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $properties['value'] || (is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) && in_array($properties['value'], $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					}
				break;
				case 'checkbox':
					if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
						if ($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $properties['value'] || (is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) && in_array($properties['value'], $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default']) && isset($this->forms_arr[$this->current_form]['default'][$properties['name']])) {
						if ($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $properties['value'] || (is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) && in_array($properties['value'], $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)))) {
							$properties['checked'] = "checked";
						} else {
							unset($properties['checked']);
						}
					}
				break;
				default:
					if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
						$properties['value'] = $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name);
					} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default']) && isset($this->forms_arr[$this->current_form]['default'][$properties['name']])) {
						$properties['value'] = $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name);
					}
				break;
			}
			return "<{$type} ".$this->properties_str($properties).">";
		} elseif ($type == "textarea" || $type == "select") {
			if ($type == "select") {
				$this->current_select = preg_replace("/(.+)\[\]/i", "\\1", str_replace(" ", "_", $properties['name']));
				$insides = preg_replace("/<option (.*?)>(.*?)<\\/option>/imsxe", "\$this->select_insides(\"\\1\", \"\\2\")", $insides);
				$this->current_select = "";
			} elseif ($type == "textarea") {
				if (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
					$insides = $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name);
				} elseif (!empty($properties['name']) && isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default']) && isset($this->forms_arr[$this->current_form]['default'][$properties['name']])) {
					$insides = $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name);
				}
			}
			return "<{$type} ".$this->properties_str($properties).">{$insides}</{$type}>";
		}
	}
	
	private function select_insides ($attr, $insides) {
		## Fix Slashes
		$attr = stripslashes($attr);
		$insides = stripslashes($insides);
		
		## Set up Properties
		$properties = $this->properties_arr($attr);
		
		if (isset($this->forms_arr[$this->current_form]['update']) && is_array($this->forms_arr[$this->current_form]['update'])) {
			if (($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $properties['value'] || $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name) == $insides)  && !is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name))) {
				$properties['selected'] = "selected";
			} elseif (is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) && (in_array($properties['value'], $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)) || in_array($insides, $this->get_form_name_value($this->forms_arr[$this->current_form]['update'], $this->parsed_name)))) {
				$properties['selected'] = "selected";
			} else {
				unset($properties['selected']);
			}
		} elseif (isset($this->forms_arr[$this->current_form]['default']) && is_array($this->forms_arr[$this->current_form]['default'])) {
			if (($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $properties['value'] || $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name) == $insides)  && !is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name))) {
				$properties['selected'] = "selected";
			} elseif (is_array($this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) && (in_array($properties['value'], $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)) || in_array($insides, $this->get_form_name_value($this->forms_arr[$this->current_form]['default'], $this->parsed_name)))) {
				$properties['selected'] = "selected";
			} else {
				unset($properties['selected']);
			}
		}
		
		return "<option ".$this->properties_str($properties).">{$insides}</option>";
	}
	
	function get_form_name_value($haystack, $find, $position=0) {
		if ((count($find)-1) == $position) {
			return $haystack[$find[$position]];
		}
		
		return $this->get_form_name_value($haystack[$find[$position]], $find, $position+1);
	}
}
?>