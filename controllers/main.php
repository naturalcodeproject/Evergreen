<?php

class Main_Controller extends ApplicationController {
	//public $not_a_view = array('mypage');
	//var $bounceback = array("bounce"=>"loud", "check"=>"soft");
	public function __construct() {
		parent::__construct();
		$this->_setLayout('index');
		//$this->_setBounceBack('testBounce', 'another_default');
	}
	
	public function testBounce() {
		return true;
	}
	
	public function index () {
		$this->home_selected = "selected";
		//System::model('worker');
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