<?php

class Msg_Processor_Internal extends Msg_ProcessorAbstract
{
    /**
     * @var array
     */
    protected $_mType = array (
    	'TEXT' => 1,
        'HTML' => 2,
    );
    
    /**
     * @param Msg_Sender $objSender
     */
    public function initialization(Msg_Sender $objSender)
    {
        parent::initialization($objSender);
    }
	
	/**
     * @param Msg_Message $objMessage
     */
    protected function _sendMessage(Msg_Message $objMessage)
    {
    }
}