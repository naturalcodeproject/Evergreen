<?php

// Model Defines

define('FL_CREATE', 1);     // Include field in creates
define('FL_RETRIEVE', 2);   // Include field in retrieves
define('FL_UPDATE', 4);     // Include field in updates
define('FL_FIND', 8);       // Include field in finds

abstract class DB_Driver {

    /*************************************************/
    /* Class Constants                               */
    /*************************************************/

    // Find Operators

    const EQUALS 				= '=';
    const NOT_EQUALS			= '!=';
    const STARTS_WITH 			= '.starts_with';
    const ENDS_WITH 			= '.ends_with';
    const CONTAINS 				= '.contains';
    const GREATER_THAN 			= '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const LESS_THAN 			= '<';
    const LESS_THAN_OR_EQUAL 	= '<=';
    const AND_THIS				= '&&';
    const OR_THIS				= '||';

    // Relationship Types

    const REL_ONE				= 0;
    const REL_MANY				= 1;

    /*************************************************/
    /* Class Members                                 */
    /*************************************************/

    protected $db;
    protected $all_flags;
    protected $column_flags;
    protected $table_name;
    protected $alias;
    protected $properties;
    protected $column_names;
    protected $primary_key;
    protected $single_primary_key;
    protected $relate_setup;
    protected $derived_name;
    protected $model;

    // Relationship Members

    protected $relationships;
    protected $has_one_relationships;
    protected $has_many_relationships;
    protected $relationship_classes;
    protected $join_columns;
    protected $relationship_join_columns;
    protected $relationship_join_types;
    protected $relationship_results;
    protected $relationship_of;
    protected $relationship_branches;

    // Find Members

    protected $find_where;
    protected $find_where_vars;
    protected $find_limit_count;
    protected $find_limit_offset;
    protected $find_order_by;
    protected $find_relationship;

    /*************************************************/
    /* Abstract Class Methods                        */
    /*************************************************/

    abstract protected function perform_create();
    abstract protected function perform_retrieve($id);
    abstract protected function perform_update();
    abstract protected function perform_delete();
    abstract protected function perform_find();

    abstract protected function get_value_equals_statement($value, $and);
    abstract protected function get_and_operator();
    abstract protected function get_equals_operator();
    abstract protected function process_where_clause(&$clause, &$where_vars);
    abstract protected function get_aliased_column_name($column_name);
    abstract protected function get_specific_aliased_column_name($column_name, $alias);
    abstract protected function get_aliased_column_as_aliased($parent, $column_name);
    abstract protected function get_alias_prefix();
    abstract protected function get_found_rows_count();
    abstract protected function get_from_table_statement($parent, $table_name, $alias);
    abstract protected function get_table_name_with_alias();
    
    abstract public function is_code_unique_field_error($code);

    /*************************************************/
    /* Class Methods                                 */
    /*************************************************/

    public function __construct($table_name, $model_name, $fields, $model) {
        $this->db = Factory::get_db();

        $this->all_flags = array();
        $this->all_flags[FL_CREATE] = FL_CREATE;
        $this->all_flags[FL_RETRIEVE] = FL_RETRIEVE;
        $this->all_flags[FL_UPDATE] = FL_UPDATE;
        $this->all_flags[FL_FIND] = FL_FIND;
        $this->column_flags = array();
        $this->model = $model;

        $this->derived_name = $model_name;
        $this->table_name = $table_name;
        $this->alias = $table_name;
        $this->column_names = array();
        $this->primary_key = array();
        $this->relate_setup = false;

        foreach ($fields as $field) {
            if ($field->key) {
                $this->primary_key[] = $this->property_to_column($field->name);
            }

            $this->column_names[] = $field->name;
            $this->properties[$field->name] = null;

            $flags = null;
            foreach ($this->all_flags as $flag) {
                $flags |= $flag;
            }
            $this->column_flags[$field->name] = $flags;
        }

        $this->single_primary_key = (count($this->primary_key) == 1);

        $this->relationships = false;
        $this->has_one_relationships = false;
        $this->has_many_relationships = false;
        $this->relationship_classes = array();
        $this->join_columns = array();
        $this->relationship_join_columns = array();
        $this->relationship_join_types = array();
        $this->relationship_results = array();
        $this->relationship_of = array();

        $this->find_limit_count = false;
        $this->find_limit_offset = false;
        $this->find_order_by = false;
    }

    public function db() {
        return $this->db;
    }

    public function db_driver() {
        return $this;
    }

    public function query($statement) {
        if (Config::read('Database.viewQueries')) {
            echo "QUERY:<code>";
            print_r($statement);
            echo "</code>";
        }

        $result = $this->db->query($statement);
        if (!$result) {
            $e = $this->db->getCurException();
            $this->model->addError(null, $e->getMessage(), ModelError::TYPE_DB_OPERATION_FAILED, $e->getTraceAsString(), $e->getCode());
        }
		try {
        	return $result->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (Exception $e) {
			return NULL;
		}
    }

    public function set_column_operations($column, $flags) {
        $this->column_flags[$column] = $flags;
    }

    public function create() {
        $result = $this->perform_create();
        return $result;
    }

    public function retrieve($id=false) {
        $this->relate();

        if ($id === false) {
            $prop = $this->get_primary_key();

            if (is_array($prop)) {
                $id = array();
                foreach ($prop as $primary_key) {
                    $key_prop = $this->column_to_property($primary_key);
                    $id[] = $this->model->$key_prop;
                }
            } else {
                $prop = $this->column_to_property($prop);
                $id = $this->model->$prop;
            }
        }

        $result = $this->perform_retrieve($id);
        return $result;
    }

    public function update() {
        $result = $this->perform_update();
        return $result;
    }

    public function delete() {
        $result = $this->perform_delete();
        return $result;
    }

    public function save() {

        // For multiple primary key models, save will always call create, as update must
        // be called explicitly.  For single primary key models, create will be called if
        // a value has not been set for the primary key, otherwise update will be called.

        $key_value = null;
        if ($this->single_primary_key) {
            $key_value = $this->get($this->get_primary_key());
        }

        if (empty($key_value)) {
            return $this->create();
        } else {
            return $this->update();
        }
    }

    public function find($arg1=null, $arg2=null) {
        $options = null;
        $relate = null;

        if (is_array($arg1)) {
            $options = $arg1;
            $relate = $arg2;
        } else if (is_array($arg2)) {
            $options = $arg2;
            $relate = $arg1;
        } else if ($arg1) {
            $relate = $arg1;
        }

        $this->relate();

        $this->find_limit_count = false;
        $this->find_limit_offset = false;
        $this->find_order_by = false;

        if ($options) {
            if (array_key_exists('limit', $options)) {
                $this->find_limit_count = $options['limit'];
            }

            if (array_key_exists('offset', $options)) {
                $this->find_limit_offset = $options['offset'];
            }

            if (array_key_exists('order', $options)) {
                if (is_array($options['order'])) {
                    $this->find_order_by = $options['order'];
                } else {
                    $order = array($options['order']);
                    $this->find_order_by = $order;
                }
            }
        }

        if ($relate) {
            $alias = $relate;
            if (array_key_exists($alias, $this->relationship_classes)) {
                $this->find_relationship = $this->relationship_classes[$alias];
                $col = $this->join_columns[$alias];
                $rel = $this->relationship_join_columns[$alias];
                $value = $this->get($col);

                if ($value) {
                    if (is_array($options) && array_key_exists('where', $options)) {
                        $where = $options['where'];
                        $where[0] .= ' ' . $this->get_value_equals_statement($rel, true);
                        array_push($where, $value);
                        $options['where'] = $where;
                    } else {
                        $options['where'] = array($this->get_value_equals_statement($rel, false), $value);
                    }
                }
                return $this->find_relationship->find($options);
            }
        }

        $where = array();
        if (array_key_exists('where', (array)$options)) {
            $where = $options['where'];
        }
        $this->where($where);

        return $this->perform_find();
    }

    public function get($property) {
        $prop = $this->column_to_property($property);

        if ($this->has_property($prop)) {
            return ((isset($this->$prop)) ? $this->$prop : '');
        } else if ($this->relationships && array_key_exists($property, $this->relationship_classes)) {
            return $this->get_relationship_results($property);
        }
    }

    public function set($property, $value) {
        $prop = $this->column_to_property($property);

        if ($this->has_property($prop)) {
            $this->$prop = $value;
            $this->model->$prop = $value;
        } else if ($this->relationships && array_key_exists($property, $this->relationship_classes)) {
            $this->relationship_classes[$property] = $value;
        }
    }

    public function set_properties($properties) {
        foreach($properties as $key=>$value) {
            $this->set($key, $value);
        }
    }

    public function get_properties() {
        $properties = array();

        foreach($this->get_column_names() as $column_name) {
            $property = $this->column_to_property($column_name);
            $value = $this->model->$property;
            $properties[$property] = $value;
        }

        return $properties;
    }

    public function set_alias($alias) {
        $this->alias = $alias;
    }

    public function get_alias() {
        return $this->alias;
    }

    protected function get_table_name() {
        return $this->table_name;
    }

    protected function includeColumnInOperation($column, $flag) {
        if ($flag == FL_CREATE || $flag == FL_UPDATE) {
            // If property is not part of model, do not include in
            // inserts and updates
            if (!property_exists($this->model, $column)) {
                return false;
            }
        }

        $flags = $this->column_flags[$column];
        return $flags & $flag;
    }

    protected function has_property($property) {
        foreach ($this->properties as $prop=>$val) {
            if ( $property == $prop ) {
                return true;
            }
        }

        return false;
    }

    protected function is_aliased_field_model_property($field, $use_relate_alias=false) {
        $pos = strpos($field, $this->get_alias_prefix());

        if ($pos !== false) {
            $prop = $this->column_to_property($field, true);
            if ($this->has_property($prop)) {
                return true;
            }
        }

        return false;
    }

    protected function get_column_names($aliased=false, $parent='', $type=null) {
        if ($aliased) {
            $column_names = array();

            if (!empty($parent)) {
                $parent .= '_';
            }

            foreach ($this->column_names as $name) {
                if ($type == null || $this->includeColumnInOperation($name, $type)) {
                    $value = $this->get_aliased_column_as_aliased($parent, $name);
                    $column_names[] = $value;
                }
            }

            return $column_names;
        } else {
            $column_names = array();

            foreach ($this->column_names as $name) {
                if ($type == null || $this->includeColumnInOperation($name, $type)) {
                    $column_names[] = $name;
                }
            }

            return $column_names;
        }
    }

    protected function get_primary_key($aliased=false) {
        if (!$this->single_primary_key) {

            // Multiple primary key
            if ($aliased) {
                $primary_key = $this->primary_key;
                foreach($primary_key as $index=>$value) {
                    $primary_key[$index] = $this->get_aliased_column_name($value);
                }
                return $primary_key;
            } else {
                return $this->primary_key;
            }
        } else {

            // Single primary key
            if ($aliased) {
                return $this->get_aliased_column_name($this->primary_key[0]);
            } else {
                return $this->primary_key[0];
            }
        }
    }

    public function isPrimaryKey($field) {
        $prop = $this->get_primary_key();

        if (is_array($prop)) {
            foreach ($prop as $primary_key) {
                if ($primary_key == $prop) {
                    return true;
                }
            }
        } else {
            return ($prop == $field);
        }

        return false;
    }

    public function add_relationship_of($class_name) {
        $this->relationship_of[] = $class_name;
    }

    public function has_one($rel_class_name, array $refs) {
        $column = $refs['local'];
        $foreign_key = $refs['foreign'];
        $alias = $refs['alias'];
        $branch = (!empty($refs['branch'])) ? $refs['branch'] : '';

        if (empty($alias)) {
            $alias = $rel_class_name;
        }

        if (array_key_exists($alias, $this->relationship_classes)) {
            die("Relationship Alias already exists");
        }

        $this->join_columns[$alias] = $column;
        $this->relationship_classes[$alias] = $rel_class_name;
        $this->relationship_join_columns[$alias] = $foreign_key;
        $this->relationship_join_types[$alias] = self::REL_ONE;
        $this->relationship_results[$alias] = null;
        $this->relationship_branches[$alias] = $branch;
        $this->relationships = true;
        $this->has_one_relationships = true;
    }

    public function has_many($rel_class_name, array $refs) {
        $column = $refs['local'];
        $foreign_key = $refs['foreign'];
        $alias = $refs['alias'];
        $branch = (!empty($refs['branch'])) ? $refs['branch'] : '';

        if (empty($alias)) {
            $alias = $rel_class_name;
        }

        if (array_key_exists($alias, $this->relationship_classes)) {
            die("Relationship Alias already exists");
        }

        $this->join_columns[$alias] = $column;
        $this->relationship_classes[$alias] = $rel_class_name;
        $this->relationship_join_columns[$alias] = $foreign_key;
        $this->relationship_join_types[$alias] = self::REL_MANY;
        $this->relationship_results[$alias] = null;
        $this->relationship_branches[$alias] = $branch;
        $this->relationships = true;
        $this->has_many_relationships = true;
    }

    protected function relate() {
        if ($this->relate_setup) {
            return;
        }

        foreach($this->relationship_classes as $alias=>$class_name) {
            if (in_array($class_name, $this->relationship_of)) {
                // echo "!!!! Inside Model {$this->derived_name}, Class $class_name EXISTS!\n";

                // TODO: instead of unsetting here, just need to stop
                // the infinite loop, but keep the relationship

                unset($this->join_columns[$alias]);
                unset($this->relationship_classes[$alias]);
                unset($this->relationship_join_columns[$alias]);
                unset($this->relationship_join_types[$alias]);
                unset($this->relationship_results[$alias]);
                unset($this->relationship_branches[$alias]);
            } else {
                $foreign_object = System::model(strtolower($class_name), $this->relationship_branches[$alias]);
                $foreign_object->db_driver()->set_alias($alias);

                foreach ($this->relationship_of as $rel) {
                    $foreign_object->db_driver()->add_relationship_of($rel);
                }
                $foreign_object->db_driver()->add_relationship_of($this->derived_name);

                $this->relationship_classes[$alias] = $foreign_object;
            }
        }

        /*echo "\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";
        echo "CLASS = " . $this->derived_name . "\n";
        print_r($this->relationship_of);
        echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";*/

        $this->relationships = false;
        $this->has_one_relationships = false;
        $this->has_many_relationships = false;

        foreach ($this->relationship_join_types as $join_type) {
            $this->relationships = true;
            if ($join_type == self::REL_ONE) {
                $this->has_one_relationships = true;
            } else {
                $this->has_many_relationships = true;
            }
        }

        $this->relate_setup = true;
    }

    public function get_relationship_classes($only_has_one=true) {
        $classes = $this->relationship_classes;

        if ($only_has_one) {
            foreach ($classes as $alias=>$class) {
                if ($this->relationship_join_types[$alias] != self::REL_ONE) {
                    unset($classes[$alias]);
                }
            }
        }

        return $classes;
    }

    protected function get_join_column($alias) {
        return $this->join_columns[$alias];
    }

    protected function get_relationship_join_column($alias) {
        return $this->relationship_join_columns[$alias];
    }

    protected function relate_many($column, $foreign_class, $foreign_key, $alias=false) {
        $foreign_object = new $foreign_class();
        $class_name = get_class($foreign_object);

        if ($alias === false) {
            $alias = $class_name;
        }

        if (array_key_exists( $alias, $this->relationship_classes )) {
            die("Relationship Alias already exists");
        }

        $foreign_object->set_alias($alias);

        $this->join_columns[$alias] = $column;
        $this->relationship_classes[$alias] = $foreign_object;
        $this->relationship_join_columns[$alias] = $foreign_key;
        $this->relationship_join_types[$alias] = self::REL_MANY;
        $this->relationships = true;
    }

    protected function get_relationship_results($alias) {
        $rel_class = $this->relationship_classes[$alias];

        if ($this->relationship_join_types[$alias] == self::REL_ONE) {
            // TODO: Need to check if this is an instantiated class
            // or if this is just a class name, if just a class name
            // need to instantiate

            $instantiated = true;
            $primary_key = $rel_class->db_driver()->get_primary_key();
            if (is_array($primary_key)) {
                foreach ($primary_key as $key) {
                    $value = $rel_class->get($key);
                    if (empty($value)) {
                        $instantiated = false;
                        break;
                    }
                }
            } else {
                $value = $rel_class->get($primary_key);
                if (empty($value)) {
                    $instantiated = false;
                }
            }

            if (!$instantiated) {
                $results = $this->find($alias);
                if (count($results)) {
                    $rel_class = array_pop($results);
                    $this->relationship_classes[$alias] = $rel_class;
                }
            }

            return $rel_class;
        } else {
            $results = $this->relationship_results[$alias];

            if ($results == null) {
                // TODO: Also, might force a query every time, otherwise
                // results could become stale, although this is the same
                // for has_one
                $results = $this->find($alias);
                $this->relationship_results[$alias] = $results;
            }

            return $results;
        }
    }

    protected function rel_populate($model, $key, $value) {
        $model->db_driver()->relate();

        if ($model->db_driver()->is_aliased_field_model_property($key)) {
            $prop = $model->db_driver()->column_to_property($key, true);
            $model->set($model->db_driver()->property_to_column($prop), $value);
            return true;
        } else {
            $check_alias = $model->db_driver()->get_alias() . '_';
            if (strpos($key, $check_alias) === 0) {
                $key = substr($key, strlen($check_alias));

                foreach($model->db_driver()->get_relationship_classes() as $rel_class) {
                    if ($this->rel_populate($rel_class, $key, $value)) {
                        break;
                    }
                }
            }
        }
    }

    protected function add_alias_to_column_names(&$clause) {
        $column_names = $this->get_column_names();

        foreach ($column_names as $column_name) {
            $pos = strpos($clause, $column_name);
            if ($pos !== false) {
                if ($pos === 0) {
                    $clause = str_replace($column_name, $this->get_aliased_column_name($column_name), $clause);
                } else {
                    $clause = str_replace(" $column_name ", ' ' . $this->get_aliased_column_name($column_name) . ' ', $clause);
                }
            }
        }
    }

    protected function where($where_args) {
        $where_clause = '';
        if (count($where_args)) {
            $where_clause = $where_args[0];
            $num_where_vars = substr_count($where_clause, '?');
            $this->find_where_vars = array_slice($where_args, 1, $num_where_vars);

            $this->process_where_clause($where_clause, $this->find_where_vars);
        } else {
            $this->find_where_vars = null;
        }

        $where = $this->get_where_statement();

        if (empty($where_clause)) {
            // No search where clause, so cut off the
            // trailing " AND" from the default where

            $and = $this->get_and_operator();
            $and_length = strlen($and) + 1;
            $and_length *= -1;

            $where = substr($where, 0, $and_length);
        } else {
            $where .= $where_clause;
        }

        $this->find_where = $where;

        return $this;
    }

    protected function populate($result, $many_results) {
        if ($this->relationships) {
            if ($many_results) {
                foreach($result as $single_result) {
                    foreach($single_result as $key=>$value) {
                        $this->rel_populate($this, $key, $value);
                    }
                }
            } else {
                foreach($result as $key=>$value) {
                    $this->rel_populate($this, $key, $value);
                }
            }
        } else {
            foreach($result as $column_name=>$value) {
                $prop = $this->column_to_property($column_name, true);
                $this->set($this->property_to_column($prop), $value);
            }
        }
    }

    protected function build_rel_select($model, &$select_columns, $parent) {
        foreach($model->db_driver()->get_relationship_classes() as $rel_class) {
            $rel_class->db_driver()->relate();

            if (!empty($select_columns)) {
                $select_columns .= ',';
            }

            $relate_columns = $rel_class->db_driver()->get_comma_separated_column_names(true, $parent);
            $select_columns .= $relate_columns;

            $temp_parent = $parent . '_' . $rel_class->db_driver()->get_alias();

            $model_rels = $rel_class->db_driver()->get_relationship_classes();
            if (!empty( $model_rels )) {
                $this->build_rel_select($rel_class, $select_columns, $temp_parent);
            }
        }
    }

    protected function get_select_statement($single=true, $type=null) {
        if (($single && $this->has_one_relationships) || $this->relationships) {
            $select_columns = $this->get_comma_separated_column_names(true, '', $type);
            $this->build_rel_select($this, $select_columns, $this->get_alias());
            return $select_columns;
        } else {
            return $this->get_comma_separated_column_names(false, '', $type);
        }
    }

    protected function build_rel_from($model, &$from, $parent) {
        foreach($model->db_driver()->get_relationship_classes() as $rel_class) {
            $rel_class->db_driver()->relate();

            $from .= $this->get_from_table_statement($parent, $rel_class->db_driver()->get_table_name(), $rel_class->db_driver()->get_alias());

            $temp_parent = $parent . '_' . $rel_class->db_driver()->get_alias();

            $model_rels = $rel_class->db_driver()->get_relationship_classes();
            if (!empty($model_rels)) {
                $this->build_rel_from($rel_class, $from, $temp_parent);
            }
        }
    }

    protected function get_from_statement($single=true) {
        if (($single && $this->has_one_relationships) || $this->relationships) {
            $from = $this->get_table_name_with_alias();
            $this->build_rel_from($this, $from, $this->get_alias());
            return $from;
        } else {
            return $this->get_table_name();
        }
    }

    protected function build_rel_where($model, &$where, $parent) {
        foreach($model->db_driver()->get_relationship_classes() as $rel_alias=>$rel_class) {
            $rel_class->db_driver()->relate();

            if (empty($parent)) {
                $where .= $this->get_specific_aliased_column_name($model->db_driver()->get_join_column($rel_alias), $model->db_driver()->get_alias());
            } else {
                $where .= $this->get_specific_aliased_column_name($model->db_driver()->get_join_column($rel_alias), $parent . '_' . $model->db_driver()->get_alias());
            }

            $where .= ' ' . $this->get_equals_operator() . ' ';

            $temp_parent = $model->db_driver()->get_alias();
            if (!empty($parent)) {
                $temp_parent = $parent . '_' . $model->db_driver()->get_alias();
            }

            $where .= $this->get_specific_aliased_column_name($model->db_driver()->get_relationship_join_column($rel_alias), "{$temp_parent}_{$rel_class->db_driver()->get_alias()}");
            $where .= ' ' . $this->get_and_operator() . ' ';

            $model_rels = $rel_class->db_driver()->get_relationship_classes();
            if (!empty($model_rels)) {
                $this->build_rel_where($rel_class, $where, $temp_parent);
            }
        }
    }

    protected function get_where_statement($single=true) {
        if (($single && $this->has_one_relationships) || $this->relationships) {
            $where = '';
            $this->build_rel_where($this, $where, '');
            return $where;
        } else {
            return '';
        }
    }

    protected function is_property_primary_key($property) {
        return in_array($property, $this->get_primary_key());
    }

    protected function is_property_a_column($property) {
        return in_array($property, $this->get_column_names());
    }

    protected function property_to_column($property) {
        return $property;
    }

    protected function column_to_property($column, $aliased=false) {
        if ($aliased) {
            $column = str_replace($this->get_alias() . '.', '', $column);
        }

        $property = $column;
        return $property;
    }

    protected function get_comma_separated_column_names($aliased=false, $parent='', $type=null) {
        $column_names = $this->get_column_names($aliased, $parent, $type);
        $column_names = implode(',', $column_names);
        return $column_names;
    }

    protected function get_column_values($primary_key_first=true, $type=null, $onlyPrimaryKeys=false) {
        $values = array();

        if ($onlyPrimaryKeys) {
            $primary_key = $this->get_primary_key();
            if (is_array($primary_key)) {
                foreach($primary_key as $key) {
                    $property = $this->column_to_property($key);
                    $value = $this->model->$property;

                    if (isset($this->$property) && $this->$property != $this->model->$property) {
                        $value = $this->$property;
                    }

                    $values[] = $value;
                }
            } else {
                $property = $this->column_to_property($primary_key);
                $value = $this->model->$property;

                if (isset($this->$property) && $this->$property != $this->model->$property) {
                    $value = $this->$property;
                }

                $values[] = $value;
            }
        } else {
            foreach($this->get_column_names() as $column_name) {
                if ($type == null || $this->includeColumnInOperation($column_name, $type)) {
                    if ($primary_key_first || !in_array($column_name, $this->primary_key)) {
                        $property = $this->column_to_property($column_name);
                        $values[] = $this->model->$property;
                    }
                }
            }

            if (!$primary_key_first) {
                $primary_key = $this->get_primary_key();
                if (is_array($primary_key)) {
                    foreach($primary_key as $key) {
                        $property = $this->column_to_property($key);
                        $values[] = $this->model->$property;
                    }
                } else {
                    $property = $this->column_to_property($primary_key);
                    $values[] = $this->model->$property;
                }
            }
        }

        return $values;
    }

    protected function get_comma_separated_question_marks($type=null) {
        $values = array();

        foreach($this->get_column_names() as $column_name) {
            if ($type == null || $this->includeColumnInOperation($column_name, $type)) {
                $values[] = '?';
            }
        }

        return implode( ',', $values );
    }
}

?>