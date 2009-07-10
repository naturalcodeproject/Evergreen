<?php
final class Error {
	final public static function load404() {
		header("HTTP/1.0 404 Not Found");
		
		if (Factory::get_config()->get_error('404')) {
			echo "string";
			Factory::get_config(true)->set_working_uri(Factory::get_config()->get_error('404'));
			Factory::get_config()->check_uri();
			if (!System::load(array("name"=>reset(Factory::get_config()->get_working_uri()), "type"=>"controller"))) {
				include("public/errors/404.php");
			}

		} else {
			include("public/errors/404.php");
		}
	}
}
?>