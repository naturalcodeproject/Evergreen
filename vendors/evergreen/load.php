namespace {
	use Evergreen\Loader\ClassLoader;
	use Evergreen\Framework;
	
	
	$this->loader = new ClassLoader();
	
	// Setup the autoloader namespaces
	$this->loader->registerNamespaces(array(
	    'Evergreen' => __DIR__.'/src',
	));

	// register the autoloaders
	$this->loader->register();
	
	// Start Evergreen
	new Framework();
}