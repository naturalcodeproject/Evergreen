<?php
class User extends Doctrine_Record {
  public function setTableDefinition() {
    $this->setTableName('user');
    $this->hasColumn('id', 'integer', 8, array('type' => 'integer', 'length' => 8, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('first_name', 'string', 255, array('type' => 'string', 'length' => 255));
    $this->hasColumn('last_name', 'string', 255, array('type' => 'string', 'length' => 255));
    $this->hasColumn('username', 'string', 255, array('type' => 'string', 'length' => 255));
    $this->hasColumn('password', 'string', 255, array('type' => 'string', 'length' => 255));
    $this->hasColumn('type', 'string', 255, array('type' => 'string', 'length' => 255));
    $this->hasColumn('is_active', 'integer', 1, array('type' => 'integer', 'length' => 1, 'default' => '1'));
    $this->hasColumn('is_super_admin', 'integer', 1, array('type' => 'integer', 'length' => 1, 'default' => '0'));
    $this->hasColumn('created_at', 'timestamp', null, array('type' => 'timestamp', 'notnull' => true));
    $this->hasColumn('updated_at', 'timestamp', null, array('type' => 'timestamp', 'notnull' => true));
  }
}