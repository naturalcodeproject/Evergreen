<?php
namespace {
	use Evergreen\Lib\ClassLoader;
	use Evergreen\Common\Evergreen;
	
	
	$loader = new ClassLoader();
	
	// Setup the autoloader namespaces
	$loader->registerNamespaces(array(
	    'Evergreen' => __DIR__.'/src',
	));

	// register the autoloaders
	$loader->register();
	
	// Start Evergreen
	new Evergreen();
}