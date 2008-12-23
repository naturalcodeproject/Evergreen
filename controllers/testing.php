<?php

class Testing extends ApplicationController {
	var $layout = "index";
	
	public function look_here () {
		$this->home_selected = "selected";
		
		
		//print_r($_GET);
		
		//$this->render_view("wibble", "main");
		
		//$this->render_view("run_code");
		
	}
	
	public function validateform()
	{
		/*if (empty($_GET['first_name']) || empty($_GET['last_name']))
		{
			$this->flash['message'] = "Please fill in your name!";
		}
		else
		{
			$this->flash['message'] = "Congratulations, {$_GET['first_name']} {$_GET['last_name']}. You win!";
		}*/
		
		//$test_model = System::load_model("test");
		//$test_model->set_name($_POST['first_name']." ".$_POST['last_name']);
		//$test_model->create();
		
		header("Location: ".URI_CONTROLLER."/look_here");
		
	}
	
	public function run_code()
	{
		$this->number = 5;
	}
}
?>