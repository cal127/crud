<?php

namespace CRUD;


/**
 * Wrapper class for Paris' ORMWrapper.
 */
class ParisORMWrapperWrapper extends \ORMWrapper
{
    public function getClassName()
    {
        return $this->_class_name;
    }


    public function resetOrder()
    {
        $this->_order_by = array();
        return $this;
    }


    public function getWhereConditions()
    {
        return $this->_where_conditions;
    }


    public function setWhereConditions($where_conditions)
    {
        $this->_where_conditions = $where_conditions;
    }

    
    /**
     * Factory method, return an instance of this
     * class bound to the supplied table name.
     *
     * A repeat of content in parent::for_table, so that
     * created class is ParisORMWrapperWrapper, not ORMWrapper
     */
    public static function for_table($table_name, $connection_name = parent::DEFAULT_CONNECTION) {
        self::_setup_db($connection_name);
        return new self($table_name, array(), $connection_name);
    }
}
