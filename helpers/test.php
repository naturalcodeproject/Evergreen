<?php
	class Test_Helper {
		/*
public $requiredSystemMode = "production";
		public $minimumSystemVersion = "1.0.0b";
		public $maximumSystemVersion = "2.0";
*/
		
		function __construct($one = 'onedefault', $two = 'default') {
			echo '<p>one: ' . $one . '<br />two: ' . $two . '</p>';
		}
		
		function getSomething() {
			echo "This is the function output.";
		}
	}
?>