Made it!
<?php
	require __DIR__ . '/Evergreen/Loader/ClassLoader.php';
	
	use Evergreen\Loader\ClassLoader;
	
	// Setup the autoloader namespaces
	ClassLoader::registerNamespaces(array(
	    'Evergreen' => __DIR__,
	));
	
	// register the autoloaders
	ClassLoader::register();
	
	// Start Evergreen
	use Evergreen\Common\Init;
	new Init();
	
	echo "<pre>";
		var_dump(get_declared_classes());
?>