<?php
class Loader {
	protected $branch_to_use;
	protected $class_to_load;
	protected $class_type;
	protected $args;
	
	public function __construct($name, $class_type, $branch="", $args = "") {
		$this->setClass($name, $class_type);
		$this->branch_to_use = $branch;
		$this->args = $args;
	}
	
	public function setClass($original_name, $class_type) {
		$name = strtolower($original_name);
		if (strpos($name, '.') !== FALSE) {
			$name = str_replace('.', ' ', $name);
			$name = ucwords($name);
			$name = str_replace(' ', '', $name);
		}
		$this->original_class_name = $original_name;
		$this->class_to_load = ucwords($name);
		$this->class_type = $class_type;
	}
	
	public function fromBranch($branch_name="") {
		if (empty($branch_name)) $branch_name = Config::read("Branch.name");
		$this->branch_to_use = $branch_name;
		
		return $this;
	}
	
	private function getPath() {
		switch ($this->class_type) {
			case 'helper':
				return Config::read("Path.physical").((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/helpers/{$this->original_class_name}.php";
			break;
			
			case 'controller':
				return Config::read("Path.physical").((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/controllers/{$this->original_class_name}.php";
			break;
			
			case 'model':
				return Config::read("Path.physical").((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/models/{$this->original_class_name}.php";
			break;
			
			case 'plugin':
				return Config::read("Path.physical").((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/plugins/{$this->original_class_name}/{$this->original_class_name}.php";
			break;
		}
	}
	
	public function exists() {
		$file_path = $this->getPath();
		
		return ((file_exists($file_path)) ? true : false);
	}
	
	public function load() {
		if ($this->exists()) {
			
			$file_path = $this->getPath();
			
			$class_name = $this->class_to_load."_".((strlen($this->branch_to_use)) ? ucwords($this->branch_to_use) : "").ucwords($this->class_type);
			
			if (!class_exists($class_name, false)) {
				include($file_path);
			}
			
			if (is_array($this->args)) {
				// if the args are an array then we'll send each one in as its own parameter
				$args = array();
				foreach($this->args as $key => $value) {
					$args[] = '$this->args[\'' . $key . '\']';
				}
				
				$string = '$object = new $class_name(' . implode(',', $args) . ');';
				eval($string);
			} else if (!empty($this->args)) {
				$object = new $class_name($this->args);
			} else {
				$object = new $class_name();
			}
			
			if (ucwords($this->class_type) == "Helper" || ucwords($this->class_type) == "Plugin") {
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
			return NULL;
		}
	}
}
?>