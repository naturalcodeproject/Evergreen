<?php
namespace {
	/**
	*  Load in the required files to get the framework started
	*/
	require __DIR__ . '/ClassLoader.php';
	
	/**
	*  Load in the classes that we need.
	*/
	use Evergreen\Common\ClassLoader;
	use Evergreen\Framework;
	
	/**
	*  Class that starts the system.
	*/
	class StartUp {
		private $loader = null;
		function __construct() {
			$this->loader = new ClassLoader();
			// Setup the autoloader namespaces
			$this->loader->registerNamespaces(array(
			    'Evergreen' => __DIR__.'/evergreen/src',
			));

			// register the autoloaders
			$this->loader->register();
			
			// Start Evergreen
			new Framework();
		}
	}

	/**
	*  Run the class.
	*/
	new StartUp();

	echo "<pre>";
		var_dump(get_declared_classes());
	
}