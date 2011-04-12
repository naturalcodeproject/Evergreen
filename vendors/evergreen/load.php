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
	
	echo "<pre>";
	//var_dump(Request::createFromGlobals());
	var_dump(Request::create('https://mail.google.com/mail/u/0/?shva=1#mbox', 'GET', array('stuff'=>'2')));
	exit;
	
	// Start Evergreen
	//new Dispatch();
  $bundleLoader = new Dispatch();
}
