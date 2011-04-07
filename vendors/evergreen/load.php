<?php
namespace {
	use Evergreen\Lib\ClassLoader;
  use Evergreen\Lib\BundleLoader;
	use Evergreen\Common\Dispatch;
	
	
	$loader = new ClassLoader();
	$loader->registerNamespaces(array(
	    'Evergreen' => __DIR__.'/src',
	));
	$loader->register();
	
	// Start Evergreen
	//new Dispatch();
  $bundleLoader = new BundleLoader(new Dispatch());
}
