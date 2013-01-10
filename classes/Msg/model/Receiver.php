<?php

class Msg_Receiver_Table extends DBx_Table
{
    protected $_name = 'msg_receiver';
    protected $_primary = 'mcr_id';
}

class Msg_Receiver_List extends DBx_Table_Rowset
{

}
class Msg_Receiver extends DBx_Table_Row
{
    public static function getClassName() { return 'Msg_Receiver'; }
    public static function TableClass() { return self::getClassName().'_Table'; }
    public static function Table() { $strClass = self::TableClass();  return new $strClass; }
    public static function TableName() { return self::Table()->getTableName(); }
    public static function FormClass( $name ) { return self::getClassName().'_Form_'.$name; }
    public static function Form( $name ) { $strClass = self::getClassName().'_Form_'.$name; return new $strClass; }

    /**
     * @return string
     */
    public function getTo() {
        return $this->mcr_to;
    }

    /**
     * @return int
     */
    public function getString() {
        return $this->mcr_status;
    }
    /**
     * @return string
     */
    public function getProcessor() {
        return $this->mcr_processor;
    }

}