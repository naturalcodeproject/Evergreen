<?php
namespace {
	/**
	*  Load in the required files to get the framework started
	*/
	require __DIR__ . '/src/Evergreen/Lib/ClassLoader.php';
	
	/**
	*  Load in the classes that we need.
	*/
	use Evergreen\Lib\ClassLoader;
	
	$loader = new ClassLoader();
	// Setup the autoloader namespaces
	$loader->registerNamespaces(array(
	    'Evergreen' => __DIR__.'/src',
	));

	// register the autoloaders
	$loader->register();
	
	foreach(glob(__DIR__.'/../vendors/*/load.php') as $loadFile) {
		require $loadFile;
	}
	
	echo "<pre>";
		var_dump(get_declared_classes());
}