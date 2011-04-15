<?php

namespace Evergreen\Common;

class ParameterStore {
	protected $parameters = array();
	
	public function __construct(array $items = array()) {
		$this->parameters = $items;
	}
	
	public function get($key) {
		if($this->has($key)) {
			return $this->parameters[$key];
		}
		return null;
	}
	
	public function set($key, $value) {
		if(is_array($key)) {
			$this->parameters = array_merge($this->parameters, $key);
			return true;
		} else {
			$this->parameters[$key] = $value;
			return true;
		}
		return false;
	}
	
	public function all() {
		return $this->parameters;
	}
	
	public function keys() {
        return array_keys($this->parameters);
    }
	
	public function replace(array $parameters = array()) {
        $this->parameters = $parameters;
    }
	
	public function has($key) {
		if(isset($this->parameters[$key])) {
			return true;
		}
		return false;
	}
	
	public function hasVal($key) {
		if(in_array($key, $this->parameters) && !empty($this->parameters[$key])) {
			return true;
		}
		
		return false;
	}
	
	public function del($key) {
		if($this->has($key)) {
			unset($this->parameters[$key]);
			return true;
		}
		return false;
	}
}