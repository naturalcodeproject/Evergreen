<?php

class DB_MySQL_Driver extends DB_Driver {

    public function __construct($table_name, $model_name, $model_properties, $model) {
        parent::__construct($table_name, $model_name, $model_properties, $model);
    }
    
    public function is_code_unique_field_error($code) {
        if ($code == 23000) {
            return true;
        }
        
        return false;
    }

    public function perform_create() {
        try {
            $statement = "INSERT INTO " .
            $this->table_name . " " .
                     "(" . $this->get_comma_separated_column_names(false, '', FL_CREATE) . ") " .
                     "VALUES " .
                     "(" . $this->get_comma_separated_question_marks(FL_CREATE) . ")";
                     
            if (Config::read('Database.viewQueries')) {
                echo "CREATE:<code>";
                print_r($statement);
                echo "<br /><br />";
                print_r($this->get_column_values(true, FL_CREATE));
                echo "</code>";
            }

            $stmt = $this->db->prepare($statement);
            $stmt->execute($this->get_column_values(true, FL_CREATE));

            // If multiple primary key, values should already have been set

            $return_value = null;

            if ($this->single_primary_key) {
                $id = $this->db->lastInsertId();
                $key_prop = $this->column_to_property($this->get_primary_key());
                $this->$key_prop = $id;
                $return_value = $id;
            } else {
                $keys = $this->get_primary_key();
                $values = array();
                foreach ($keys as $key) {
                    $values[] = $this->get($key);
                }
                $return_value = $values;
            }

            return $return_value;
        } catch (Exception $e) {
            $this->model->addError(null, $e->getMessage(), ModelError::TYPE_DB_OPERATION_FAILED, $e->getTraceAsString(), $e->getCode());
            return false;
        }
    }

    public function perform_retrieve($id) {
        try {
            $where = $this->get_where_statement();

            $primary_key = $this->get_primary_key(true);
            if (is_array($primary_key)) {
                $index = 0;
                foreach ($primary_key as $key) {
                    if ($index > 0) {
                        $where .= " AND ";
                    }

                    $keyArray = explode('.', $key);
                    $unAliasedKey = array_pop($keyArray);

                    $cur_id = $id[$index];
                    if (array_key_exists($unAliasedKey, $id)) {
                        $cur_id = $id[$unAliasedKey];
                    }

                    if (is_numeric($cur_id)) {
                        $where .= "$key=$cur_id";
                    } else {
                        $cur_id = mysql_real_escape_string($cur_id);
                        $where .= "$key='$cur_id'";
                    }

                    $index++;
                }
            } else {
                if (is_numeric($id)) {
                    $where .= "$primary_key=$id";
                } else {
                    $id = mysql_real_escape_string($id);
                    $where .= "$primary_key='$id'";
                }
            }

            $statement = "SELECT " .
            $this->get_select_statement(true, FL_RETRIEVE) . ' ' .
                     "FROM {$this->get_from_statement()} WHERE $where";

            if (Config::read('Database.viewQueries')) {
                echo "RETRIEVE: <code>$statement</code>\n";
            }

            $stmt = $this->db->prepare($statement);
            $stmt->execute();

            //echo "NUM ROWS FROM STATEMENT: " . $stmt->rowCount() . "\n";

            $rowFound = false;

            if ($stmt->rowCount() == 1) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                //print_r($result);
                // echo "\n\n";
                $this->populate($result, false);
                $rowFound = true;
            } else if ($stmt->rowCount() > 1) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // print_r($result);
                // echo "\n\n";
                $this->populate($result, true);
                $rowFound = true;
            }

            return $rowFound;
        } catch (Exception $e) {
            $this->model->addError(null, $e->getMessage(), ModelError::TYPE_DB_OPERATION_FAILED, $e->getTraceAsString(), $e->getCode());
            return false;
        }
    }

    public function perform_update() {
        try {
            $statement = "UPDATE " .
            $this->table_name . " SET ";

            $first = true;
            foreach($this->column_names as $column_name) {
                if ($this->includeColumnInOperation($column_name, FL_UPDATE)) {
                    if (!$first) {
                        $statement .= ", ";
                    }
                    $first = false;
                    $statement .= "$column_name=?";
                }
            }

            $statement .= " WHERE ";

            $primary_key = $this->get_primary_key();
            if (is_array($primary_key)) {
                $first = true;
                foreach($primary_key as $key) {
                    if (!$first) {
                        $statement .= " AND ";
                    }

                    $first = false;
                    $statement .= "$key=?";
                }
            } else {
                $statement .= "$primary_key=?";
            }

            $statement .= " LIMIT 1";

            $columnValues = $this->get_column_values(true, FL_UPDATE);
            $primaryKeyValues = $this->get_column_values(true, FL_UPDATE, true);
            $values = array_merge($columnValues, $primaryKeyValues);
            
            if (Config::read('Database.viewQueries')) {
                echo "UPDATE:<code>";
                print_r($statement);
                echo "<br /><br />";
                print_r($values);
                echo "</code>";
            }

            $stmt = $this->db->prepare($statement);
            $stmt->execute($values);
            return true;
        } catch (Exception $e) {
            $this->model->addError(null, $e->getMessage(), ModelError::TYPE_DB_OPERATION_FAILED, $e->getTraceAsString(), $e->getCode());
            return false;
        }
    }

    public function perform_delete() {
        try {
            $statement = "DELETE FROM " .
            $this->table_name . " WHERE ";

            $primary_key = $this->get_primary_key();
            if (is_array($primary_key)) {
                $first = true;
                foreach ($primary_key as $key) {
                    if (!$first) {
                        $statement .= " AND ";
                    }

                    $first = false;
                    $statement .= "$key=?";
                }
            } else {
                $statement .= "$primary_key=?";
            }

            $statement .= " LIMIT 1";
            
            if (Config::read('Database.viewQueries')) {
                echo "DELETE:<code>";
                print_r($statement);
                echo "</code>";
            }

            $values = array();
            if (is_array($primary_key)) {
                foreach ($primary_key as $key) {
                    $prop = $this->column_to_property($key);
                    $values[] = $this->$prop;
                }
            } else {
                $prop = $this->column_to_property($primary_key);
                $values[] = $this->$prop;
            }

            $stmt = $this->db->prepare($statement);
            $stmt->execute($values);
            return true;
        } catch (Exception $e) {
            $this->model->addError(null, $e->getMessage(), ModelError::TYPE_DB_OPERATION_FAILED, $e->getTraceAsString(), $e->getCode());
            return false;
        }
    }

    protected function perform_find() {
        try {
            $found_rows = '';
            if ($this->find_limit_count) {
                $found_rows = 'SQL_CALC_FOUND_ROWS';
            }

            $statement = "SELECT $found_rows " .
            $this->get_select_statement(true, FL_FIND) . ' ' .
                     "FROM {$this->get_from_statement()}";

            if (!empty($this->find_where)) {
                $statement .= " WHERE {$this->find_where} ";
            }


            if ($this->find_order_by) {
                $first = true;
                foreach($this->find_order_by as $order_by) {
                    if (!$first) {
                        $statement .= ', ';
                    } else {
                        $statement .= ' ORDER BY ';
                        $first = false;
                    }

                    if ($this->relationships) {
                        $this->add_alias_to_column_names($order_by);
                    }

                    $statement .= $order_by . ' ';
                }
            }

            if ($this->find_limit_count || $this->find_limit_offset) {
                $statement .= 'LIMIT ';

                if ($this->find_limit_offset) {
                    $statement .= $this->find_limit_offset;
                }

                if ($this->find_limit_count) {
                    if ($this->find_limit_offset) {
                        $statement .= ', ';
                    }

                    $statement .= $this->find_limit_count;
                }
            }

            if (Config::read('Database.viewQueries')) {
                echo "FIND:<code>";
                print_r($statement);
                echo "<br /><br />";
                print_r($this->find_where_vars);
                echo "</code>";
            }

            $stmt = $this->db->prepare($statement);
            $stmt->execute($this->find_where_vars);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $objects = array();
            $class_name = $this->derived_name;
            foreach ($results as $result) {
                $object = new $class_name();
                $object->db_driver()->set_alias($this->get_alias());
                $object->db_driver()->populate($result, false);
                $objects[] = $object;
            }

            return $objects;
        } catch (Exception $e) {
            $this->model->addError(null, $e->getMessage(), ModelError::TYPE_DB_OPERATION_FAILED, $e->getTraceAsString(), $e->getCode());
            return false;
        }
    }

    protected function get_value_equals_statement($value, $and) {
        $statement = "$value = ?";

        if ($and) {
            $statement = "AND " . $statement;
        }

        return $statement;
    }

    protected function get_and_operator() {
        return 'AND';
    }

    protected function get_equals_operator() {
        return '=';
    }

    protected function get_alias_prefix() {
        return $this->get_alias() . '.';
    }

    protected function get_aliased_column_name($column_name) {
        return $this->get_alias() . '.' . $column_name;
    }

    protected function get_specific_aliased_column_name($column_name, $alias) {
        return $alias . '.' . $column_name;
    }

    protected function get_aliased_column_as_aliased($parent, $column_name) {
        $value = "$parent" . $this->get_alias() . '.' . $column_name . " AS '" .
                 "$parent" . $this->get_alias() . '.' . $column_name . "'";

        return $value;
    }

    protected function get_from_table_statement($parent, $table_name, $alias) {
        $from = ',' . $table_name . " {$parent}_{$alias}";
        return $from;
    }

    protected function get_table_name_with_alias() {
        $from = $this->get_table_name();
        $alias = $this->get_alias();
        if (!empty($alias)) {
            $from .= " $alias";
        }
        return $from;
    }

    public function get_found_rows_count() {
        $stmt = $this->db->query( 'SELECT FOUND_ROWS()' );
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result[0];
    }

    protected function process_where_clause(&$clause, &$where_vars) {
        // Find any likes, need to add % to where_vars
        $offset = 0;
        $length = 0;
        $var_index = 0;
        do {
            $pos = strpos($clause, '?', $offset);

            if ($pos !== false) {
                $length = $pos+1;

                if (substr_count($clause, self::STARTS_WITH, $offset, $length-$offset)) {
                    $where_vars[$var_index] = $where_vars[$var_index] . '%';
                } else if (substr_count($clause, self::ENDS_WITH, $offset, $length-$offset)) {
                    $where_vars[$var_index] = '%' . $where_vars[$var_index];
                } else if (substr_count($clause, self::CONTAINS, $offset, $length-$offset)) {
                    $where_vars[$var_index] = '%' . $where_vars[$var_index] . '%';
                }

                $var_index++;
                $offset = $length;
            }

        } while ($pos !== false);

        $clause = str_replace(self::EQUALS, 				'=', 	$clause );
        $clause = str_replace(self::NOT_EQUALS, 			'!=', 	$clause );
        $clause = str_replace(self::STARTS_WITH, 			'like', $clause );
        $clause = str_replace(self::ENDS_WITH,   			'like', $clause );
        $clause = str_replace(self::CONTAINS, 				'like', $clause );
        $clause = str_replace(self::GREATER_THAN, 			'>', 	$clause );
        $clause = str_replace(self::GREATER_THAN_OR_EQUAL,  '>=', 	$clause );
        $clause = str_replace(self::LESS_THAN, 			    '<', 	$clause );
        $clause = str_replace(self::LESS_THAN_OR_EQUAL, 	'<=', 	$clause );
        $clause = str_replace(self::AND_THIS, 				'AND', 	$clause );
        $clause = str_replace(self::OR_THIS, 				'OR', 	$clause );

        if ($this->relationships) {
            $this->add_alias_to_column_names($clause);
        }
    }
}

?>