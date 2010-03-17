<?php

class Main_Controller extends ApplicationController {
	var $layout = "index";
	//public $not_a_view = array('mypage');
	//var $bounceback = array("bounce"=>"loud", "check"=>"soft");
	//var $filter = "";
	//var $filter_only = array("", array("wibble"));
	//var $filter_except = array("", array("wibble"));
	public function __construct() {
		$this->_addFilterExcept('another_default', array('index'));
		
		$this->_addFilter(array(
			'filter' => 'dash_test_page',
		));
		
		parent::__construct();
	}
	
	public function index () {
		echo "<pre>";
		var_dump($this->filters);
		echo "</pre>";
		$this->home_selected = "selected";
		
		Config::read('Param.action');
		
		$test = System::helper("test", '', array('two', 'three'));
		echo $test->getSomething()."<br />";
		
		
		//var_dump($test);
		
		//$this->projax = System::plugin("projax")->load();
		//$this->render_view('wibble');
		
		//echo "<pre>";
			//print_r(get_loaded_extensions());
			//print_r(get_included_files());
		//echo "</pre>";
		
		//$this->branch_projax = System::plugin("projax")->from_branch("developer")->load();
	
		//$hookhelper = System::load_helper("somethingelse");
		//$hookhelper->aFunction();
		
		// echo "<p/>";
		// 		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		// 		echo "Site ='[site]' - <a href='[site]'>Test</a><br/>";
		// 		echo "View ='[view]' - <a href='[view]'>Test</a><br/>";
		// 		echo "Controller ='[controller]' - <a href='[controller]'>Test</a><br/>";
		// 		echo "Branch ='[branch]' - <a href='[branch]'>Test</a><br/>";
		// 		
		// 		echo "<br /><br />";
		// 		var_dump(Factory::get_config()->get_working_uri());
		echo $this->mypage('Jack Attack');
		//$this->getView($this->newView, '', true);
	}
	
	protected function mypage($post) {
		return $post;
	}
	
	public function another_default() {
		echo "this is another default<br />";
	}
	
	public function custom_error() {
		echo "custom_error_stuff<br />";
		//Error::trigger("custom 404 error", array('code'=>404));
		Error::trigger("custom 404 error page", array('code'=>404, 'url'=>'/anothererror'));
		//Error::trigger("hello world");
	}
	
	public function dash_test_page()
	{
		echo "Hello to the dash test page.";
	}
	
	// public function soft()
	// {
	// 	echo "soft";
	// 	return false;
	// }
	// 
	// public function loud()
	// {
	// 	echo "Loud";
	// }
	
	public function error404()
	{
		echo "This is an error page. The error message is: ".Error::getMessage();
	}
	
	public function anothererror()
	{
		echo "This is <b>another</b> 404 error page. The error message is: ".Error::getMessage();
	}
	
	public function wibble()
	{
		$this->wibblenum = 35;
		
		$this->layout = "";
	}
	
    public function models() {
        $blog_tag_model = System::model('blogtag');
        $this->blog_tags = $blog_tag_model->find();

        $bp = System::model('blogpost');
        $found = $bp->retrieve(1);

        $createdBy = $bp->get('createdBy');
        $editedBy  = $bp->get('editedBy');
        $createdByClass = $createdBy->get('class');
        $editedByClass = $editedBy->get('class');

        echo "<PRE>";
        echo "\n\n------------------------------------------\n\n";

        echo '             TITLE: ' . $bp->title . "\n";
        echo '              BODY: ' . $bp->body . "\n";
        echo '        CREATED BY: ' . $createdBy->name . "\n";
        echo '         EDITED BY: ' . $editedBy->name . "\n";
        echo '           SECTION: ' . $bp->get('section')->name . "\n";
        echo 'CREATED BY (class): ' . $createdByClass->name . "\n";
        echo ' EDITED BY (class): ' . $editedByClass->name . "\n";
        echo "</PRE>";
    }

}
?>