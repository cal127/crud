<?php

namespace CRUD;


use \ORMWrapper;


/**
 * Wrapper class for Paris' Model.
 * It has given the same name for convenience, as this is part of the API
 */
class Model extends \Model
{
    public static function buildForeignKeyName(
        $specified_foreign_key_name, $class_name
    ) {
        $table_name = self::_get_table_name($class_name);

        return parent::_build_foreign_key_name(
            $specified_foreign_key_name, $table_name
        );
    }


    // The class name of the join model, if not supplied, is
    // formed by concatenating the names of the base class
    // and the associated class, in alphabetical order.
    public static function buildJoinClassName(
        $join_class_name = null,
        $base_class_name,
        $associated_class_name
    )
    {
        if (!is_null($join_class_name)) {
            return $join_class_name;
        }

        if ($len = strlen(self::$auto_prefix_models)) {
            $base_class_name = substr($base_class_name, $len);
            $associated_class_name = substr($associated_class_name, $len);
        }

        $class_names = array($base_class_name, $associated_class_name);

        sort($class_names, SORT_STRING);

        return join("", $class_names);
    }


    /**
     * Exact clone of the Paris Model::factory() method,
     * excepts it uses an ParisORMWrapperWrapper object,
     * instead of Paris' original ORMWrapper object.
     */
    public static function factory($class_name, $connection_name = null) {
        $class_name = self::$auto_prefix_models . $class_name;
        $table_name = self::_get_table_name($class_name);

        if ($connection_name == null) {
           $connection_name = self::_get_static_property(
               $class_name,
               '_connection_name',
               ORMWrapper::DEFAULT_CONNECTION
           );
        }
        $wrapper = ParisORMWrapperWrapper::for_table($table_name, $connection_name);
        $wrapper->set_class_name($class_name);
        $wrapper->use_id_column(self::_get_id_column_name($class_name));
        return $wrapper;
    }

    /* exact copy of parent::_has_one_or_many */
    protected function _has_one_or_many($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_current_models_table=null, $connection_name=null) {
        $base_table_name = self::_get_table_name(get_class($this));
        $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $base_table_name);
        
        $where_value = ''; //Value of foreign_table.{$foreign_key_name} we're 
                           //looking for. Where foreign_table is the actual 
                           //database table in the associated model.
        
        if(is_null($foreign_key_name_in_current_models_table)) {
            //Match foreign_table.{$foreign_key_name} with the value of 
            //{$this->_table}.{$this->id()}
            $where_value = $this->id(); 
        } else {
            //Match foreign_table.{$foreign_key_name} with the value of 
            //{$this->_table}.{$foreign_key_name_in_current_models_table}
            $where_value = $this->$foreign_key_name_in_current_models_table;
        }
        
        return self::factory($associated_class_name, $connection_name)->where($foreign_key_name, $where_value);
    }

    /* exact copy of parent::belongs_to */
    protected function belongs_to($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_associated_models_table=null, $connection_name=null) {
        $associated_table_name = self::_get_table_name(self::$auto_prefix_models . $associated_class_name);
        $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $associated_table_name);
        $associated_object_id = $this->$foreign_key_name;
        
        $desired_record = null;
        
        if( is_null($foreign_key_name_in_associated_models_table) ) {
            //"{$associated_table_name}.primary_key = {$associated_object_id}"
            //NOTE: primary_key is a placeholder for the actual primary key column's name
            //in $associated_table_name
            $desired_record = self::factory($associated_class_name, $connection_name)->where_id_is($associated_object_id);
        } else {
            //"{$associated_table_name}.{$foreign_key_name_in_associated_models_table} = {$associated_object_id}"
            $desired_record = self::factory($associated_class_name, $connection_name)->where($foreign_key_name_in_associated_models_table, $associated_object_id);
        }
        
        return $desired_record;
    }

    
    /* exact copy of parent::has_many_through */
    protected function has_many_through($associated_class_name, $join_class_name=null, $key_to_base_table=null, $key_to_associated_table=null,  $key_in_base_table=null, $key_in_associated_table=null, $connection_name=null) {
        $base_class_name = get_class($this);

        // The class name of the join model, if not supplied, is
        // formed by concatenating the names of the base class
        // and the associated class, in alphabetical order.
        if (is_null($join_class_name)) {
            $model = explode('\\', $base_class_name);
            $model_name = end($model);
            if (substr($model_name, 0, strlen(self::$auto_prefix_models)) == self::$auto_prefix_models) {
                $model_name = substr($model_name, strlen(self::$auto_prefix_models), strlen($model_name));
            }
            $class_names = array($model_name, $associated_class_name);
            sort($class_names, SORT_STRING);
            $join_class_name = join("", $class_names);
        }

        // Get table names for each class
        $base_table_name = self::_get_table_name($base_class_name);
        $associated_table_name = self::_get_table_name(self::$auto_prefix_models . $associated_class_name);
        $join_table_name = self::_get_table_name(self::$auto_prefix_models . $join_class_name);

        // Get ID column names
        $base_table_id_column = (is_null($key_in_base_table)) ?
            self::_get_id_column_name($base_class_name) :
            $key_in_base_table;
        $associated_table_id_column = (is_null($key_in_associated_table)) ?
            self::_get_id_column_name(self::$auto_prefix_models . $associated_class_name) :
            $key_in_associated_table;

        // Get the column names for each side of the join table
        $key_to_base_table = self::_build_foreign_key_name($key_to_base_table, $base_table_name);
        $key_to_associated_table = self::_build_foreign_key_name($key_to_associated_table, $associated_table_name);

        /*
            "   SELECT {$associated_table_name}.*
                  FROM {$associated_table_name} JOIN {$join_table_name}
                    ON {$associated_table_name}.{$associated_table_id_column} = {$join_table_name}.{$key_to_associated_table}
                 WHERE {$join_table_name}.{$key_to_base_table} = {$this->$base_table_id_column} ;"
        */

        return self::factory($associated_class_name, $connection_name)
            ->select("{$associated_table_name}.*")
            ->join($join_table_name, array("{$associated_table_name}.{$associated_table_id_column}", '=', "{$join_table_name}.{$key_to_associated_table}"))
            ->where("{$join_table_name}.{$key_to_base_table}", $this->$base_table_id_column); ;
    }
    
    public function __call($name, $arguments) {
        $method = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        if (!method_exists(array($this, $method))) { return false; }
        return call_user_func_array(array($this, $method), $arguments);
    }
}
