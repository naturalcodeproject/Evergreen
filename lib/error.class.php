<?php
final class Error {
	final public static function load404() {
		header("HTTP/1.0 404 Not Found");
		Factory::get_config(true)->set_working_uri("/error404");
		Factory::get_config()->check_uri();
		System::load(array("name"=>reset(Factory::get_config()->get_working_uri()), "type"=>"controller"));
	}
}
?>