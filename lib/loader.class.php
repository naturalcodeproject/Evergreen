<?php

class Loader
{
	//protected $loaded_classes;
	protected $branch_to_use;
	protected $class_to_load;
	protected $class_type;
	
	public function __construct($name, $class_type)
	{
		$this->set_class($name, $class_type);
	}
	
	public function set_class($name, $class_type)
	{
		$name = strtolower($name);
		if (strpos($name, '.') !== FALSE)
		{
			$name = str_replace('.', ' ', $name);
			$name = ucwords($name);
			$name = str_replace(' ', '', $name);
		}
		$this->original_class_name = $name;
		$this->class_to_load = ucwords($name);
		$this->class_type = $class_type;
		
	}
	
	public function from_branch($branch_name="")
	{
		if (empty($branch_name)) $branch_name = Factory::get_config()->get_branch_name();
		$this->branch_to_use = $branch_name;
		
		return $this;
	}
	
	private function get_path()
	{
		switch ($this->class_type)
		{
			case 'helper':
				return Factory::get_config()->get_base_path().((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/helpers/{$this->original_class_name}.php";
			break;
			
			case 'model':
				return Factory::get_config()->get_base_path().((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/models/{$this->original_class_name}.php";
			break;
			
			case 'plugin':
				return Factory::get_config()->get_base_path().((strlen($this->branch_to_use)) ? "/branches/".$this->branch_to_use : "")."/plugins/{$this->original_class_name}/{$this->original_class_name}.php";
			break;
		}
	}
	
	public function exists()
	{
		$file_path = $this->get_path();
		
		return ((file_exists($file_path)) ? true : false);
	}
	
	public function load()
	{
		if ($this->exists())
		{
			$file_path = $this->get_path();
			
			$class_name = $this->class_to_load."_".((strlen($this->branch_to_use)) ? ucwords($this->branch_to_use) : "").ucwords($this->class_type);
			
			if (!class_exists($class_name, false))
			{
				include($file_path);
			}
			
			$object = new $class_name();
		
			return $object;
		}
		else
		{
			return NULL;
		}
	}
	
}

?>