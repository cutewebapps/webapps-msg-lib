<?php

abstract class Msg_ProcessorAbstract // extends Sys_Db_Object_Config
{
    /**
     * Объект отправителя
     * @var Msg_Sender
     */
    protected $_sender;
    
    /**
     * Массив типов Message для процессора
     * @var array
     */
    protected $_mType;
    
    public function __construct()
    {
        if (empty($this->_mType)) {
            throw new Msg_Exception('Object Processor doesn\'t contain array with types of messages');
        }
        
        foreach ($this->_mType as $key => $value) {
            if (!is_string($key) or !is_int($value)) {
                throw new Msg_Exception('Object Processor: array with types must contain key => value assocs');
            }
        }
    }
    
    /**
     * Find key by value in array $_arrType (Types of Message)
     * @param int $findValue
     * @return string or false
     */
    public function _getMessageTypeName($findValue)
    {
        foreach ($this->_mType as $key => $value) {
            if ($value == $findValue) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Return value by key in array $_arrType (Types of Message)
     * @param string $findName
     * @return int or false
     */
    public function _getMessageTypeValue($findName)
    {
        if (isset($this->_mType[$findName])) {
            return $this->_mType[$findName];
        }
        return null;
    }
    
    /**
     * Функция инициализации процессора. Объединение конфигов сендера и процессора. Установка соединения
     * @param Msg_Sender $objSender
     */
    public function initialization(Msg_Sender $objSender)
    {
        $this->_sender = $objSender;
    }
    
    /**
     * @param Msg_Message_List $listMessages
     */
    public function sendMessages(Msg_Message_List $listMessages)
    {
        foreach($listMessages as $objMessage){
            $objMessage->mcm_status = Msg_Message_StatusList::INPROGRESS;
            $objMessage->save();
        }
        foreach($listMessages as $objMessage){
            //try {
                $this->_sendMessage($objMessage);
                // echo '<div>'. $objMessage->getId().' - sent</div>';
                $objMessage->mcm_status = Msg_Message_StatusList::SENT;
                $objMessage->save();    
//            }catch (Exception $objException){
//                $objMessage->mcm_status = Msg_Message_StatusList::BOUNCED;
//                $objMessage->save();
//            }
        }
    }
    
    /**
     * Абстрактная функция отправки одного сообщения для переопределения в наследниках. 
     * @param Msg_Message $objMessage
     */
    abstract protected function _sendMessage(Msg_Message $objMessage);
}