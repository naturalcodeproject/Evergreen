<?php

class Blog_Controller extends ApplicationController_Controller {
	public function __construct() {
		$this->_setLayout('index');
	}

	public function index () {
		$blogs = new Blogpost_Model();
		//$blogs->retrieve(1);
		echo "<pre>";
		//var_dump($blogs->get('createdBy'));
		echo "</pre>";
		//var_dump($blogs);
		/*
echo '<h2>Insert</h2>';
		
		$post = new Blogpost_Model();
		$post->time = time();
		$post->title = 'inserting - ' . date('r');
		$post->user_id = 1;
		
		echo '<pre>';
		//var_dump($post->save());
		//var_dump($post);
		echo '</pre>';

		$blogpost = new Blogpost_Model();

		// get one post by the primary key
		echo '<h2>Retrieve</h2>';
		//$blogpost->retrieve(1);
		echo '<p>' . $blogpost->title . '</p>';

		echo '<p>Isset: ' . var_export(isset($blogpost->title), true) . '</p>';
		echo '<p>Empty: ' . var_export(empty($blogpost->title), true) . '</p>';

		echo '<p>Unsetting</p>';
		unset($blogpost->title);

		echo '<p>Isset: ' . var_export(isset($blogpost->title), true) . '</p>';
		echo '<p>Empty: ' . var_export(empty($blogpost->title), true) . '</p>';

		echo '<h2>Update</h2>';

		$blogpost->time = time();
		$blogpost->title = 'this is my new title - ' . date('r');
		echo '<pre>';
		//var_dump($blogpost->save());
		echo '</pre>';

		echo '<p>';
		echo $blogpost->title;
		echo '</p>';
*/

		/*$blogpost = new Blogpost_Model();
		$blogpost->title = 'hiya';
		//echo '<pre>';
		if(!$blogpost->save()) {
			echo implode('<br />', $blogpost->getErrorMessages());
		}*/
		//echo '</pre>';

		// find multiple posts
		//$userdataRet = new Usersdata_Model();
		//$userdataRet->retrieve(1, 2);
		//echo "<pre>";
		//var_dump($userdataRet);
		//echo "</pre>";
		echo '<h2>Find</h2>';
		
		$userdata = new Usersdata_Model();
		$userdata->find();
		
		for($i = 0, $total = sizeof($userdata); $i < $total; $i++) {
			echo $userdata[$i]->info . '<br />';
			
			$userdata[$i]->info = 'foo';
			
			$userdata[$i] = 'blah';
		}
		echo '<br />';
		
		echo $userdata[3]->info . '<br />';
		var_dump(empty($userdata[5]));
		
		echo '<br /><br />';
		
		foreach($userdata as $user) {
			echo $user->info . '<br />';
		}
		
		
		/*foreach($userdata as $key => $item) {
			if ($key == 3) {
				$item->delete();
				continue;
			}
			$item->info = "World";
			$item->update();
			echo $item->info."<br />";
		}*/

		
		/*
$blogpost->find(array(
 			'where'	=> array('user_id = ?', 1),
 			'order'	=> array('time DESC'),
 			'limit'	=> array(76, 100),
 		));
		 		
		 var_dump(count($blogpost));
		 
		 echo "<br />";
		 foreach($blogpost as $key => $post) {
							//var_dump($post);
		 				echo '<h3>' . $key . ': ' . $post->title . '</h3>';
		 		
		 					echo $post->body;
		 			if ($key == 10) {
		 				$externalTest = $post;
		 			}
		}
		unset($blogpost, $key, $post);
		
		echo "<pre>";
		var_dump($externalTest);
		echo "</pre>";
*/

		// 
		// 		echo '<p>total: ' . $blogpost->totalRows() . '</p>';
		// 		$i = 0;
		// 		$current = null;
		// 		foreach($blogpost as $key => $post) {
		// 			//var_dump($post);
		// 		echo '<h3>' . $key . ': ' . $post->title . '</h3>';
		// 
		// 			echo $post->body;
		// 			if ($i == 5) {
		// 				$current = $post->extract();
		// 			}
		// 
		// 			$i++;
		// 		}
		// 
		// 		echo $current->title;

		echo '<pre>';
		//var_dump($current);
		//var_dump($blogpost);
		echo '</pre>';

		echo '<h2>DB::queryObject</h2>';

		//$results = DB::queryObject('SELECT blog_posts.id,blog_posts.time,blog_posts.title,blog_posts.user_id,blog_posts.body FROM blog_posts WHERE blog_posts.user_id = ? ORDER BY blog_posts.time DESC LIMIT 10', array(1), 'Blogpost_Model');
		
		// 		foreach($results as $key => $post) {
		// 					//var_dump($post);
		// 				echo '<h3>' . $key . ': ' . $post->title . '</h3>';
		// 		
		// 					echo $post->body;
		// 		}
		// unset($results, $key, $post);
		echo '<pre>';
		var_dump(sizeof($results));
		//var_dump($results);
		echo '</pre>';

		echo '<h2>Queries</h2>';
		echo '<pre>';
		var_dump(DB::$queries);
		echo '</pre>';
	}

	public function populate() {
		for($i = 0; $i < 300; $i++) {
			DB::query("INSERT INTO blog_posts (time, title, user_id, body)
				VALUES (" . time() . ", '" . $i . " title blog post - " . time() . "', 1, '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc id quam ut sem dictum aliquam. Nullam consectetur convallis quam. Nunc eget leo eros. Curabitur ut odio massa, sit amet bibendum nisi. Etiam dapibus purus ac leo accumsan facilisis at vitae sem. In hac habitasse platea dictumst. Morbi nunc mauris, pulvinar ut sagittis et, fermentum a magna. Suspendisse vel risus est, quis bibendum justo. Nunc aliquet vehicula lorem eget sagittis. Cras pretium erat sed nisl tincidunt mollis. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Vivamus nec tortor orci. Donec commodo bibendum ligula sit amet ultrices. Phasellus eu metus turpis, quis gravida turpis. Curabitur fermentum arcu nec lectus posuere ut porttitor libero hendrerit. Sed non porttitor felis. Nullam sem nisl, tempus vitae consectetur imperdiet, bibendum sed tortor. Duis vestibulum risus non augue ornare fringilla. Praesent non eros ut tortor volutpat sollicitudin.</p>

<p>Donec sit amet dolor purus, ac dapibus augue. Sed et lectus felis. Quisque laoreet sagittis lectus et tempor. Integer porttitor tellus id nibh lacinia tristique. Fusce mollis pulvinar lorem a dignissim. Nulla imperdiet nibh sed massa mollis non mattis urna fringilla. Vivamus mollis nisi a justo ultricies sit amet ultrices tortor porttitor. Cras et lobortis mi. Aenean ac scelerisque urna. Vivamus fermentum tempor ante ut euismod. In et metus accumsan augue sollicitudin lacinia. Aliquam pretium, urna sed vestibulum tincidunt, mi eros ultrices risus, aliquam vulputate mauris magna sed purus. Nullam feugiat volutpat ligula ac vehicula. Quisque risus justo, tincidunt vel dapibus sed, rutrum volutpat nisl.</p>

<p>Morbi id quam non turpis iaculis molestie interdum et lorem. Etiam convallis dolor neque. Morbi id nunc sed magna cursus ultricies a ut neque. Cras lacinia semper leo, et interdum nisl pharetra vel. Nunc imperdiet lorem tellus, eu consequat dolor. Praesent eu pharetra enim. Duis sagittis ornare purus et dapibus. Nullam ut odio tortor. Integer pellentesque velit nec ipsum euismod quis congue sem pretium. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam hendrerit, ipsum nec commodo ultrices, leo nulla porta elit, a viverra ipsum ligula id erat. Nullam iaculis quam vel nulla iaculis vitae fermentum risus fringilla. Duis elit dui, mollis vitae sagittis et, egestas vitae quam. Quisque odio nulla, pharetra eu tempor vitae, suscipit nec urna. Maecenas in erat ante, nec adipiscing justo. Nam placerat lacinia orci, et tempus felis porttitor sed. Pellentesque tristique scelerisque posuere. Sed egestas lacinia quam, blandit ultrices felis sagittis nec.</p>

<p>Fusce egestas, felis et convallis mattis, felis magna malesuada neque, id malesuada nulla velit at augue. Morbi sed urna at purus venenatis rutrum et eget ante. Suspendisse potenti. Sed scelerisque, est id sollicitudin facilisis, neque augue mollis mauris, tempus rhoncus dui risus vitae nunc. Nam lacinia felis ac sem imperdiet porta. Fusce quis enim nulla, egestas ultricies enim. Phasellus dui nibh, vestibulum in dapibus at, iaculis in sem. Etiam nisl lorem, blandit vulputate posuere ut, hendrerit sodales augue. Morbi ac nisl in tortor auctor mattis et sed leo. Integer vel risus nibh, pulvinar adipiscing nunc. Donec ornare leo sit amet mauris imperdiet tincidunt vitae vel quam. Praesent porta massa et felis imperdiet vehicula. Praesent laoreet, dui id convallis sodales, nunc eros ullamcorper lacus, id venenatis nibh enim sed lacus. Curabitur dapibus, ipsum sed faucibus auctor, urna velit pretium nisi, sed pharetra magna quam ut velit. Aliquam sed tellus sit amet diam rhoncus congue at ullamcorper elit. Cras vel est sit amet risus vestibulum venenatis sed a nunc. Nullam eu diam purus. In hac habitasse platea dictumst. Integer bibendum massa ante, a aliquam urna.</p>

<p>Suspendisse est sem, dapibus sit amet consectetur vitae, euismod eu nulla. Vivamus et rhoncus sem. Cras massa sapien, dapibus et sollicitudin in, ultricies eget nisl. Nam vehicula feugiat commodo. Nulla mattis hendrerit enim ut posuere. Phasellus lobortis auctor eros, quis varius felis commodo et. Praesent dignissim lectus vel sem rutrum pharetra. Aenean varius fermentum nisi, at pulvinar elit laoreet nec. Phasellus cursus blandit augue quis aliquam. Nulla facilisi. Aenean venenatis, velit egestas condimentum tempus, libero leo feugiat nisl, vitae malesuada lacus justo vitae nisl. Nunc tortor turpis, pulvinar id porttitor ut, egestas eget velit. Ut sit amet ligula quis ante porttitor luctus vel et nibh. Duis eget feugiat diam. Ut tempus quam sit amet eros tempor faucibus. Praesent fermentum lectus sit amet tellus ullamcorper tincidunt. In massa augue, lacinia in ullamcorper sit amet, imperdiet ac justo. </p>')");
			echo $i . '<br />';
			flush();
		}
	}
}
?>