<?php
/**
 *
 */
class Msg_Processor_Mail extends Msg_ProcessorAbstract
{
    /**
     * @var Mail_Transport_Smtp
     */
    protected $_transport;

    /**
     * @var array
     */
    protected $_mType = array (
        'TEXT' => 1,
        'HTML' => 2,
    );
    
    /**
     * Функция инициализации процессора. Объединение конфигов сендера и процессора. Установка соединения
     * @param Msg_Sender $objSender
     */
    public function initialization(Msg_Sender $objSender)
    {
        parent::initialization($objSender);
        $arrConfig = $objSender->getConfig()->toArray();
        
        $strHost = $arrConfig['host'];
        unset($arrConfig['host']);
        $this->_transport = new Mail_Transport_Smtp($strHost, $arrConfig);
    }
    
    /**
     * @param Msg_Message $objMessage
     */
    protected function _sendMessage (Msg_Message $objMessage)
    {
        $objReceiver = $objMessage->findParentRow('Msg_Receiver_Table');
        
        $mail = new Mail_Msg('utf-8');
        $mail->setFrom($this->_sender->mcs_from);
        $mail->addTo( $objReceiver->mcr_to );
        $mail->setSubject($objMessage->mcm_subject);

        $listAttachments = $objMessage->getAttachments();
        foreach ( $listAttachments as $objAttachment ) {
            $objAttachment->attachTo( $mail );
            // echo '<br />'.$objMessage->getId().' - '.$objAttachment->getFileName();
        }       
        
        if ($objMessage->mcm_type == $this->_getMessageTypeValue('TEXT', null, Mime_Const::ENCODING_BASE64)) {
            $mail->setBodyText($objMessage->mcm_body);
        } else if ($objMessage->mcm_type == $this->_getMessageTypeValue('HTML', null, Mime_Const::ENCODING_BASE64)) {
            $mail->setBodyHtml($objMessage->mcm_body);
        } else {
            throw new Msg_Exception( 'Error in message type' );
        }
        // echo '<div>from: '.htmlspecialchars( $this->_sender->mcs_from, ENT_QUOTES ).'</div>';
        $mail->send($this->_transport);
    }
}
