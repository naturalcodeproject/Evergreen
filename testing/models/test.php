<?php

echo '<PRE>';

require_once( "lib/db/newdb.factory.php" );
require_once( "lib/db/db.class.php" );
require_once( "lib/db.driver.class.php" );
require_once( "lib/db.driver.mysql.class.php" );
require_once( "lib/model.class.php" );

require_once( "models/blogpost.php" );
require_once( "models/bloguser.php" );
require_once( "models/blogsection.php" );
require_once( "models/blogcomment.php" );
require_once( "models/blogtag.php" );
require_once( "models/bloguserclass.php" );
require_once( "models/blogposttag.php" );

require_once( "Event.php" );

// TODO: MySQL Functions ex. curdate(), now(), md5()

// TODO: thinking about how to handle columns, leave them how they are now
// and just use the get() and set() functions
// Also, possibly just have the parent set the actual column names as members of the child
// class
// Possibly scrap the whole members and have them passed up in an array

// TODO: handle self-relations, possibly only allow lazy loads
// $this->relate( 'parent_id',  'BlogPost',    'id', 'parent'    )
                              
// TODO: need to set vars to something to know if they have been set
// if not, you know to do a lazy load

// TODO: relate on multiple keys

// TODO: single primary key that is not auto-increment

// TODO: check if a value 

$tests = array('retrieve1'					=> true,
               'retrieve2' 					=> true,
			   'db1'       					=> true,
               'find1'						=> true,
               'find2'						=> true,
			   'find3'						=> true,
			   'find4'						=> true,
               'getMany'					=> true,
			   'retrieveMultiPrimaryKey'	=> true,
               'getManyToMany'              => true,
			   'update1'                    => true,
			   'update2'					=> true,
			   'insert1'					=> true,
               'save1'						=> true,
			   'delete1'					=> true);

foreach ($tests as $func=>$test) {
	if ($test) {
		echo "\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";
		echo "********** TESTING: $func\n\n";
		
		$func();
		
		echo "\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";
		echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";
	}
}

function retrieve1() {
	$bp = new BlogPost();
	$found = $bp->retrieve(1);
	
	$createdBy = $bp->get('createdBy');
	$editedBy  = $bp->get('editedBy');
	$createdByClass = $createdBy->get('class');
	$editedByClass = $editedBy->get('class');
	
	echo "\n\n------------------------------------------\n\n";
	
	echo '             TITLE: ' . $bp->get('title') . "\n";
	echo '              BODY: ' . $bp->get('body') . "\n";
	echo '        CREATED BY: ' . $createdBy->get('name') . "\n";
	echo '         EDITED BY: ' . $editedBy->get( 'name' ) . "\n";
	echo '           SECTION: ' . $bp->get('section')->get('name') . "\n";
	echo 'CREATED BY (class): ' . $createdByClass->get('name') . "\n";
	echo ' EDITED BY (class): ' . $editedByClass->get('name') . "\n";
}

function retrieve2() {
	$bp = new BlogPost();
	$bp->retrieve(2);
	
	echo "BODY: {$bp->get('body')}\n";
}

function db1() {
	$bp = new BlogPost();
	$posts = $bp->db()->query("SELECT * FROM blog_posts");
	
	echo "\n\n------------------------------------------\n\n";
	
	foreach ($posts as $post) {
		echo "POST: " . $post['title'] . "\n";
	}
}

function find1() {
	$bp = new BlogPost();

	$options = array('limit'=>2, 'where'=>array('user_id > ?', 2), 'order'=>array('title desc'), 'offset'=>1);
	//$options = array('order'=>'title desc', 'limit'=>2, 'offset'=>1);
	//$options = array('order'=>array('title', 'body'), 'limit'=>2);
	//$options = array('where'=>array('user_id > ?', 2));
	//$options = array('where'=>array('user_id > ? && title .contains ?', 2, 'ik'));
	//$options = array('where'=>array('user_id >= ?', 2), 'limit'=>2, 'offset'=>1, 'order'=>'title');
	$posts = $bp->find($options);
	
	
	echo "\n\n------------------------------------------\n\n";
	
	echo "NUM ROWS FOUND: = " . $bp->get_found_rows_count() . "\n";
	foreach($posts as $post) {
		echo "TITLE: " . $post->get('title') . " USER ID: " . $post->get('user_id') . "\n";	
	}
}

function find2() {
	$bp = new BlogPost();
	$bp->retrieve(1);

	$options = array('limit'=>10, 'order'=>'text desc');
	//$comments = $bp->find('comments');
	$comments = $bp->find('comments', $options);
	
	echo "\n\n------------------------------------------\n\n";
	
	echo "NUM ROWS FOUND: = " . $bp->get_found_rows_count() . "\n";
	foreach($comments as $comment) {
		echo "COMMENT: " . $comment->get('text') . " USER ID: " . $comment->get('user_id') . "\n";	
	}
}


function find3() {
	$bp = new BlogPost();
	//$posts = $bp->find()->where("title .contains ? && user_id >= ?", 'thing', 2)->go();
	$options = array('where'=>array('title .contains ? && user_id >= ?', 'thing', 2));
	$posts = $bp->find($options);
	
	echo "NUM ROWS FOUND: = " . $bp->get_found_rows_count() . "\n";
	foreach($posts as $post) {
		echo "TITLE: " . $post->get('title') . " USER ID: " . $post->get('user_id') . "\n";	
	}
}


function find4() {
	$bp = new BlogPost();
	//$posts = $bp->find()->where('blog_posts_createdBy.name = ?', 'User One')->go();
	$options = array('where'=>array('blog_posts_createdBy.name = ?', 'User One'));
	$posts = $bp->find($options);
	
	echo "NUM ROWS FOUND: = " . $bp->get_found_rows_count() . "\n";
	foreach($posts as $post) {
		echo "TITLE: " . $post->get('title') . " USER ID: " . $post->get('user_id') . "\n";	
	}
}


function getMany() {
	$bp = new BlogPost();
	$bp->retrieve(1);
	$comments = $bp->get('comments');
	
	foreach($comments as $comment) {
		echo "COMMENT: " . $comment->get('text') . " USER ID: " . $comment->get('user_id') . "\n";	
		
		$createdBy = $comment->get('createdBy');
		echo "^^ created by: {$createdBy->get('name')}\n";
		
		// A) This is just silly but making sure it still works
		$post = $comment->get('post');
		echo "Blog Post Title: {$post->get('title')}\n";
		
		// B) This is just silly but making sure it still works
		$testComments = $post->get('comments');
		foreach ($testComments as $testComment) {
			echo "TEST COMMENT YO: {$testComment->get('text')}\n";
		}
	}
}


function retrieveMultiPrimaryKey() {
	$bpt = new BlogPostTag();
	$bpt->retrieve(array(1, 1));
	
	echo "PostID: {$bpt->get('post_id')}\n";
}


function getManyToMany() {
	// TODO: Have to do it like this for now
	
	// Want to get tags attached to this blog post
	$blogPostID = 1;
	
	$bpt = new BlogPostTag();
	$blogPostTags = $bpt->find(array('where'=>array('post_id = ?', $blogPostID)));
	foreach ($blogPostTags as $blogPostTag) {
		$tag = $blogPostTag->get('tag');
		echo "TAG: {$tag->get('name')}\n";
	}
}


function update1() {
	$bp = new BlogPost();
	$bp->retrieve(1);
	$curBody = $bp->get('body');
	
	$rand = rand();
	$bp->set('body', "Updated Body Yo = $rand");
	$bp->update();
	$newBody = $bp->get('body');
	
	echo "OLD BODY: $curBody, NEW BODY: $newBody\n";
}

function update2() {
	$bu = new BlogUser();
	$bu->retrieve(1);
	
	echo "CURRENT PASSWORD: {$bu->get('password')}\n";
	
	$bu->set('password', 'new password');
	$bu->update();
	$bu->retrieve(1);
	
	echo "PASSWORD AFTER CHANGE ATTEMPT #1: {$bu->get('password')}\n";
	
	$bu = new BlogUser();
	$bu->retrieve(1);
	$bu->set('password', rand());
	$bu->set_column_operations('password', FL_UPDATE|FL_RETRIEVE);
	$bu->update();
	$bu->retrieve(1);
	
	echo "PASSWORD AFTER CHANGE ATTEMPT #2: {$bu->get('password')}\n";
}

function insert1() {
	$bp = new BlogPost();
	$postValues = array('user_id'=>1, 'section_id'=>1, 'title'=>'test title',
	                    'body'=>'test body', 'edited_by'=>1, 'parent_id'=>1);
	$bp->set_properties($postValues);
	$id = $bp->create();
	
	$newBp = new BlogPost();
	$newBp->retrieve($id);
	
	echo "NEW BLOG POST ID = " . $newBp->get('id') . "\n";
}

function save1() {
	$bp = new BlogPost();
	$bp->retrieve(1);
	$bp->save();
	
	$bp = new BlogPost();
	$postValues = array('user_id'=>1, 'section_id'=>1, 'title'=>'test save title',
	                    'body'=>'test save body', 'edited_by'=>1, 'parent_id'=>1);
	$bp->set_properties($postValues);
	$bp->save();
}

function delete1() {
	for ($i = 6; $i < 100; $i++) {
		$bp = new BlogPost();
		$found = $bp->retrieve($i);
		if ($found) {
			$bp->delete();
			break;
		}
	}
}

/****************************************************************/
/*	RETRIEVE BLOG POST											*/
/****************************************************************/



/****************************************************************/
/*	FIND BLOG POSTS												*/
/****************************************************************/

//$bp = new BlogPost();
//$bp->setFindLimit( 1 );
//$bp->findWhere( "user_id > 0" );
//echo "NUM ROWS FOUND: = " . $bp->getFoundRowsCount();

/*$bp = new BlogPost();

$bp->find()->where('user_id > ?', 5)->go();
$bp->find(10)->where('user_id > ?', 5);
$bp->find(10,10)->where('user_id > ?', 5);
$bp->find()->limit(10)->where('user_id > ?', 5);
$bp->find(10)->orderBy('name asc')->where('user_id > ?', 5);

$bp->find('comments')->where('comment_id > ?', 5)->orderBy('user_id')->limit(10)->go();

$bp->find(10)->any();
$bp->findAll();

// TODO: adding ->go() or ->do() to the end

$bp->find(10, 'comments')->where();
$bp->find(10, 'comments')->where('user_id > ?', 5);*/

/*

// Retrieve

$bp = new BlogPost( 3 );
$body = $bp->get( 'body' );
$bp->set( 'body', 'this is the new body' );

// New-Update

$bp->save();

// Delete

$bp->delete();

// Find

$bp->findWhere( 'user_id > ?', 5 );

$bp->setFindOrder( 'user_id desc' );
$bp->setFindLimit( 10 );
$bp->findWhere( 'user_id > ?', 100 );

$bp->find( array( 'where' => array( 'user_id >= ? && name .contains ?', 5, 'lol' ),
                  'limit' => 5,
                  'offset' => 2,
				  'order_asc' => 'user_id' ) );

// Find relationships

$blogPosts = $bp->findWhere( 'user_id > ? && and editedBy.name .contains ?', 5, 'lol' );
foreach ($blogPosts as $post) {
	echo $post->get( 'body' );
}

$bp->get( 'comments', array( 'limit'=>5 ) );
*/


/****************************************************************/
/*	EVENT HANDLER												*/
/****************************************************************/
/*
$event = Event::get();

function test() {
	echo "LOL AT YOU\n";
}

function test1() {
	echo "NO WA!\n";
}

function test2() {
	return "hello thar";
}

class BigTest {
	public static function yes() {
		echo "BIG TEST SAYS YES\n";
	}
	
	public function no() {
		echo "BIG TEST SAYS NO\n";
	}
	
	public function deliver($data) {
		echo "THIS IS THE DATA: $data\n";
	}
	
	public function trigger() {
		return true;
	}
}

$bTest = new BigTest();

$event->addEventHandler( 'TestMessage', 'test' );
$event->addEventHandler( 'TestMessage', 'test1' );
$event->addEventHandler( 'TestMessage', 'BigTest::yes' );
$event->addEventHandler( 'TestMessage', array( $bTest, 'no' ) );
$event->addEventHandler( 'TestMessage', array( $bTest, 'deliver' ) );

$event->fireEvent( 'TestMessage', 12 );

$event->addEventHandler( 'TestMessage2', array( $bTest, 'trigger' ) );
$event->addEventHandler( 'TestMessage3', 'test2' );

$result = $event->fireEvent( 'TestMessage2' );
echo "RESULT: $result\n";

$result = $event->fireEvent( 'TestMessage3' );
echo "RESULT: $result\n";



*/


/*
$blog_post = new BlogPost();

echo "\n\n";

$blog_post->set( 'id', 2 );
$blog_post->retrieve();

print_r( $blog_post );

echo "\n\n";

$blog_post->set( 'user_id', 10 );

echo "BLOG POST: id = {$blog_post->get( 'id' )}, user_id = {$blog_post->get( 'user_id' )}, title = {$blog_post->get( 'title' )}\n";

$blog_post->save();

$nbp = new BlogPost();
$nbp->set( 'user_id', 111 );
$nbp->set( 'title', "This is a test title" );
$nbp->set( 'body', "Yep, just a test body, thats all" );
$nbp->save();

print_r( $nbp );

echo "\n\n";

$nbp->setTitle( "ok, new title big guy" );
$nbp->setBody( "hahaha, a whole new body" );
$nbp->update();

print_r( $nbp );

$nbp->delete();

->set("body >= all")
->set(" ( date <= today || title = hehehe) ", "and", "", "or")
->set("title = hehehe )" "or")


1 AND 2 OR 3
(1 AND 2) OR 3
1 AND (2 OR 3)

1 OR 2 AND 3

1 OR (2 AND 3)
1 

*/


//$bp = new BlogPost();
//$bp->retrieve( 1 );


/*$bp->setFindLimit( 2, 1 );
$bp->setFindOrder( 'user_id desc', 'body asc' );
$bp->findWhere( 'user_id > ? && user_id < ? && body .contains ?', 0, 11, 'thats && all' );
$bp->findWhere( 'user_id = ?', 5 );
$bp->findWhere( 'user_id = 5' );*/


/*
//$bp->set( 'user_id', <, 5, AND );
//$bp->set( 'user_id', ++6, ELSE );
//$bp->find();


//$testUser = new BlogUser();
//$testUser->retrieve( 1 );
//print_r( $testUser );


//$bp = new BlogPost();
// TODO: $bp->find( 'comments' );



//$bp->retrieve( 2 );

// TODO: perhaps put find limit at start of find

//$bp->setFindLimit( 2 );
//$bp->setFindOrder( 'Section.name ASC' );
//$bp->findWhere( 'user_id > ? && user_id < ? && body .contains ?', 0, 11, 'thats all' );
//$bp->findWhere( 'user_id > ? && user_id < ?', 0, 11 );


/*$posts = $bp->findWhere( 'user_id = ?', 2 );

foreach ( $posts as $bp ) {
	echo "BLOG POST:\n";
	echo "ID = {$bp->get( 'id' )}, USER_ID = {$bp->get( 'user_id' )}, SECTION_ID = {$bp->get( 'section_id' )}, TITLE = {$bp->get( 'title' )}, BODY = {$bp->get( 'body' )}\n\n";

	echo "BLOG USER (createdBy):\n";
	$bu = $bp->get( 'createdBy' );
	echo "ID = {$bu->get( 'id' )}, NAME = {$bu->get( 'name' )}\n\n";
	
	echo "BLOG USER (editedBy):\n";
	$bu = $bp->get( 'editedBy' );
	echo "ID = {$bu->get( 'id' )}, NAME = {$bu->get( 'name' )}\n\n";
	
	echo "BLOG SECTION:\n";
	$bs = $bp->get( 'section' );
	echo "ID = {$bs->get( 'id' )}, NAME = {$bs->get( 'name' )}\n\n";
}*/

//$bp->retrieve( 2 );
/*$posts = $bp->findWhere( 'comments.user_id = 1' );
echo "\n\n**************************\n\n";
foreach ($posts as $post)
{
	$comments = $post->get( 'comments' );
	echo "Text: " . $comments->get( 'text' ) . "\n";
}*/

//$bp->retrieve( 2 );
/*$bp->setFindOrder( 'id ASC' );
$posts = $bp->findWhere( 'user_id = ?', 1 );

$comment = new BlogComment();
foreach ($posts as $post) {
	echo "BLOG POST {$post->get( 'id' )} - {$post->get( 'title' )}:\n";
	$comments = $comment->findWhere( 'blog_post_id = ?', $post->get( 'id') );
	foreach ($comments as $comment) {
		echo "COMMENT TEXT: {$comment->get( 'text' )}\n";
	}
}*/

//$bp->find( 'comments', 'user_id = ? && comments.text .contains ?', 5, 'lol' );

/*echo "BLOG POST:\n";
echo "ID = {$bp->get( 'id' )}, USER_ID = {$bp->get( 'user_id' )}, SECTION_ID = {$bp->get( 'section_id' )}, TITLE = {$bp->get( 'title' )}, BODY = {$bp->get( 'body' )}\n\n";

echo "BLOG USER (CreatedBy):\n";
$bu = $bp->get( 'createdBy' );
echo "ID = {$bu->get( 'id' )}, NAME = {$bu->get( 'name' )}\n\n";


echo "BLOG USER (EditedBy):\n";
$bu = $bp->get( 'editedBy' );
echo "ID = {$bu->get( 'id' )}, NAME = {$bu->get( 'name' )}\n\n";

$bp->findWhere( "user_id = ? && section.name = ?", 5, 'programming' );*/

//$bp->get( 'comments', 'findWhere'=>array('', 'sdsd', 'sdsd', ));

/*
echo "BLOG SECTION:\n";
$bs = $bp->get( 'Section' );
echo "ID = {$bs->get( 'id' )}, NAME = {$bs->get( 'name' )}\n\n";

$bp->set( 'comments', 'blah comment, next comment' );
echo "Check if comments where set: " . $bp->get( 'comments' ) . "\n";
*/
//$testUser->find( 'comments' );

//$bu->set( 'name', 'new name biatches' );
//$bp->set( 'BlogUser', $bu );

//$newbu = $bp->get( 'BlogUser' );
//echo "ID = {$newbu->get( 'id' )}, NAME = {$newbu->get( 'name' )}\n\n";

echo '</PRE>';

?>