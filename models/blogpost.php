<?php

class Blogpost_Model extends Model {

	public function __construct() {
		$this->setTableName('blog_posts');
        
        $this->addField('id', array('key'));
        $this->addField('user_id', array('required'));
        $this->addField('section_id', array('required'));
        $this->addField('title', array('required', 'validate'=>'validateTitle'));
        $this->addField('body', array('required'));
        $this->addField('edited_by');
        $this->addField('parent_id');

		$this->hasOne('Bloguser', array('local'=>'user_id', 'foreign'=>'id', 'alias'=>'createdBy'));
		$this->hasOne('Bloguser', array('local'=>'edited_by', 'foreign'=>'id', 'alias'=>'editedBy'));
		$this->hasOne('Blogsection', array('local'=>'section_id', 'foreign'=>'id', 'alias'=>'section'));

		$this->hasMany('Blogcomment', array('local'=>'id', 'foreign'=>'blog_post_id', 'alias'=>'comments'));
	}

    public function validateTitle($value) {
        if (strlen($value) < 3) {
            $this->addError('title', 'Title must be at least 3 characters long');
        } else if (strlen($value) > 32) {
            $this->addError('title', 'Title must be less than 32 characters long');
        }
    }
}

?>