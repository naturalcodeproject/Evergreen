<?php
class Main_Controller extends ApplicationController_Controller {
	public function __construct() {
		parent::__construct();
		$this->_setLayout('index');
		//$this->_setBounceBack('testBounce', 'another_default');
		$this->_addFilterOn('_testFilter', 'index');
	}
	
	public function login() {
		echo "Type: ".Reg::get('Param.action');
	}
	
	public function testBounce() {
		return true;
	}
	
	public function _testFilter() {
		//$this->setSomething = 'foo bar';
	}
	
	public function index () {
		//throw new Exception('something happened');
		$test = new TestClass();
		var_dump($test);
		$this->test = $test;
		//throw new EvergreenException("VIEW_NOT_FOUND");
		//BadStaticClass::$someVariable;
		//$badclass = new BadClass();
		//$barcode = new Zend_Barcode_Object_Code39();
		//var_dump($barcode);
		//var_dump($this->setSomething);
		$this->home_selected = "selected";
		// if ($this->_viewExists('index', 'main', 'developer', true)) {
		// 			echo 'The view exists';
		// 		}
		
		//echo hash('sha256', (string)array('something', 'more'));
		//$this->_getView('index', 'main', 'MAIN');
		//$helper = new Test_Helper();
		//$model = new Users_Model();
		//$model->query('select *');
		//$results = DB::query("select * from users", "Users_Model");
		
		// $book = new Book();
		// 		$book->title = 'Some book';
		// 		$book->save();
		
		// $book = new Book();
		// $book->find(1);

		//var_dump($book);

		// $user = new User();
		// 				$user->username = 'jwage';
		// 				$user->password = 'changeme';
		// 				$user->created_at = time();
		// 				$user->updated_at = time();
		// 				$user->save();
		// // 		
				// $q = Doctrine_Query::create()
				// 	    ->from('User u')
				// 		->fetchArray();
				// 	var_dump($q);
	}
	
	public function anotherDefault() {
		echo "this is another default<br />";
	}
	
	public function customError() {
		echo "custom_error_stuff<br />";
		//Error::trigger("custom 404 error", array('code'=>404));
		throw new EvergreenException("custom 404 error page", array('code'=>404, 'url'=>'/anothererror'));
		//Error::trigger("hello world");
	}
	
	public function dashTestPage()
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

}
?>