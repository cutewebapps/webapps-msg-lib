<?php

class Msg_Processor_Table extends DBx_Table
{
    protected $_name = 'msg_processor';

    protected $_primary = 'mcp_id';
}

class Msg_Processor_List extends DBx_Table_Rowset
{

}
class Msg_Processor extends DBx_Table_Row
{
    public static function getClassName() { return 'Msg_Processor'; }
    public static function TableClass() { return self::getClassName().'_Table'; }
    public static function Table() { $strClass = self::TableClass();  return new $strClass; }
    public static function TableName() { return self::Table()->getTableName(); }
    public static function FormClass( $name ) { return self::getClassName().'_Form_'.$name; }
    public static function Form( $name ) { $strClass = self::getClassName().'_Form_'.$name; return new $strClass; }

}