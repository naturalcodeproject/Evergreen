Made it!
<?php
	require dirname(__FILE__) . '/Evergreen/Autoloader.php';
	
	// register the autoloaders
	Autoloader::register();
	
	// Start Evergreen
	//new Evergreen();
	
	echo "<pre>";
		var_dump(get_declared_classes());
?>