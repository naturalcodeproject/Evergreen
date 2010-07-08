<?php
/**
* 
*/
class Validation_Helper {
	public function checkLanguage($element) {
		return preg_match("/^(en|es)$/i", $element);
	}
}

?>