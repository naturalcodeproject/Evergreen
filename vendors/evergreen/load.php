<?php
namespace {
	use Evergreen\Lib\ClassLoader;
	use Evergreen\Lib\BundleLoader;
	use Evergreen\Common\Dispatch;
	use Evergreen\Http\Request;
	
	
	$loader = new ClassLoader();
	$loader->registerNamespaces(array(
	    'Evergreen' => __DIR__.'/src',
	));
	$loader->register();
	
	var_dump(Request::createFromGlobals());
	exit;
	
	// Start Evergreen
	//new Dispatch();
  $bundleLoader = new Dispatch();
}
