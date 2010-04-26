<?php

class Blogpost_Model extends Model {

	public function __construct() {
		$this->setTableName('blog_posts');

        $this->addField('id', array(
        	'key',
        	'format'	=> array('onSet' => 'integer'),
        ));

        $this->addField('time', array(
        	'required',
        	'format'	=> array(
        		'onGet' => array('Blogpost_model', 'formatter'),
        		'onSet'	=> 'timestamp',
        	),
        ));

        $this->addField('title'	, array(
        	'required',
        	'validate' => array(
        		'maxlength',
        		'minlength' => 'Custom error message',
        	),
        	'format' => 'plaintext',
        ));

        $this->addField('user_id', array(
        	'validate' => array(
        		'isValidUser',
        	),
        	'format' => array('onSet' => 'integer'),
        ));

        $this->addField('body', array(
        	'validate' => 'test',
        	'format' => 'htmltext',
        ));

		$this->hasOne('Client_Model', array('local'=>'user_id', 'foreign'=>'id', 'alias'=>'createdBy'));
		/*$this->hasOne('Bloguser', array('local'=>'edited_by', 'foreign'=>'id', 'alias'=>'editedBy'));
		$this->hasOne('Blogsection', array('local'=>'section_id', 'foreign'=>'id', 'alias'=>'section'));

		$this->hasMany('Blogcomment', array('local'=>'id', 'foreign'=>'blog_post_id', 'alias'=>'comments'));*/
	}

	private function isValidUser($field, $value) {
		return true;
	}

	protected function maxlength($field, $value) {
		return true;
		//return $field.' failed';
	}

	protected function minlength() {
		return true;
	}

	private function test() {
		return true;
	}
	
	public function formatter($value) {
		return date('m/d/y', $value);
	}
}

/*
--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` mediumint(11) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(11) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_id` mediumint(11) NOT NULL,
  `body` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=2 ;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `time`, `title`, `user_id`, `body`) VALUES
(1, 1270008945, 'this is my title', 1, 'this is the body to my blog post.');
*/

?>