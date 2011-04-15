<?php
namespace {
	use Evergreen\Lib\ClassLoader;
	use Evergreen\Lib\BundleLoader;
	use Evergreen\Dispatch;
	use Evergreen\Http\Request;
	use Evergreen\Version;
	
	
	$loader = new ClassLoader();
	$loader->registerNamespaces(array(
	    'Evergreen' => __DIR__.'/src',
	));
	$loader->register();
	
	$request = Request::createFromGlobals();
	//'https://mail.google.com/mail/u/0/?shva=1#mbox', 'GET', array('stuff'=>'2')
	
	// Start Evergreen
	//new Dispatch();
  $bundleLoader = new Dispatch($request);
}
