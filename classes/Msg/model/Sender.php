<?php

class Msg_Sender_Table extends DBx_Table
{
    protected $_name = 'msg_sender';
    
    protected $_primary = 'mcs_id';
    
    /**
     * Simple array of class names of tables that are "children" of the current
     * table, in other words tables that contain a foreign key to this one.
     * Array elements are not table names; they are class names of classes that
     * extend Zend_Db_Table_Abstract.
     *
     * @var array
     */
    protected $_dependentTables = array('Msg_Message_Table');
        
}

class Msg_Sender_List extends DBx_Table_Rowset
{
}

class Msg_Sender extends DBx_Table_Row
{
    public static function getClassName() { return 'Msg_Sender'; }
    public static function TableClass() { return self::getClassName().'_Table'; }
    public static function Table() { $strClass = self::TableClass();  return new $strClass; }
    public static function TableName() { return self::Table()->getTableName(); }
    public static function FormClass( $name ) { return self::getClassName().'_Form_'.$name; }
    public static function Form( $name ) { $strClass = self::getClassName().'_Form_'.$name; return new $strClass; }


    /**
     * @return int
     */
    public function getProcessor() 
    {
        return $this->mcs_processor;
    }
    
    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->mcs_from;
    }

    /**
     * @return Sys_Config
     */
    public function getConfig()
    {
        $conf = $this->mcs_config;
        if ( $conf == '' )
            return new Sys_Config( array() );
        else
            return new Sys_Config( unserialize( $this->mcs_config ) );
    }
}
