<?php

class Msg_Message_Table extends DBx_Table
{
    protected $_name = 'msg_message';
    protected $_primary = 'mcm_id';
    
    protected $_referenceMap    = array(
        'Sender' => array(
            'columns'           => 'mcm_sender_id',
            'refTableClass'     => 'Msg_Sender_Table',
            'refColumns'        => 'mcs_id'
        ),        
        'Receiver' => array(
            'columns'           => 'mcm_receiver_id',
            'refTableClass'     => 'Msg_Receiver_Table',
            'refColumns'        => 'mcr_id'
        ));        
   
}

class Msg_Message_List extends DBx_Table_Rowset
{
}

class Msg_Message_Form_Filter extends App_Form_Filter
{
    public function  createElements()
    {
        $elemSubject = new App_Form_Element( 'mcm_subject', 'text');
        $this->addElement( $elemSubject );

        $elemBody = new App_Form_Element( 'mcm_body', 'text');
        $this->addElement( $elemBody );
    }
}

class Msg_Message_Form_Edit extends App_Form_Edit
{
    public function  createElements()
    {
        $elemSubject = new App_Form_Element( 'mcm_subject', 'text');
        $this->addElement( $elemSubject );

        $elemBody = new App_Form_Element( 'mcm_body', 'text');
        $this->addElement( $elemBody );
    }
}

class Msg_Message extends DBx_Table_Row
{
    /**
     * Internal message reply mail
     * @var string
     */
    protected static $_strInternalReplyMail = '';

    public static function getClassName() { return 'Msg_Message'; }
    public static function TableClass() { return self::getClassName().'_Table'; }
    public static function Table() { $strClass = self::TableClass();  return new $strClass; }
    public static function TableName() { return self::Table()->getTableName(); }
    public static function FormClass( $name ) { return self::getClassName().'_Form_'.$name; }
    public static function Form( $name ) { $strClass = self::getClassName().'_Form_'.$name; return new $strClass; }

    /**
     */
    protected function _insert()
    {
        $this->mcm_added = date('Y-m-d H:i:s');
        if ( ! $this->mcm_status ){
            $this->mcm_status = 1;
        }
        parent::_insert();
    }

    public function getDateAdded()
    {
        return $this->mcm_added;
    }
    public function getDateProcessed()
    {
        return $this->mcm_processed;
    }
    public function getSubject() { return $this->mcm_subject; }

    public function getStatus()
    {
        return $this->mcm_status;
    }
    public function getBody()
    {
        return $this->mcm_body;
    }

    public function getStatusName()
    {
        switch ($this->mcm_status ) {
            case Msg_Message_StatusList::PENDING:       return 'PENDING';
            case Msg_Message_StatusList::INPROGRESS:    return 'IN PROGRESS';
            case Msg_Message_StatusList::SENT:          return 'SENT';
            case Msg_Message_StatusList::BOUNCED:       return 'BOUNCED';
            case Msg_Message_StatusList::MUST_RESEND:   return 'MUST RESEND';
        }
        return '('.$this->mcm_status.')';
    }

    protected function _postInsert()
    {
        //$objSender = $this->findParentRow('Msg_Sender_Table');
        //if ($objSender->mcs_processor === 'Internal') {
            // hardcoded internal messaging...
            // self::addToQueue(self::$_strInternalReplyMail, $objReceiver->mcr_to, $strSubject, $strBody);
        //}
        parent::_postInsert();
    }
    
    protected function _update()
    {
        if ($this->isChanged('mcm_status')){
            $this->mcm_processed = date('Y-m-d H:i:s');
        }
        parent::_update();
    }

    /**
     * @param string $strTo
     * @param string $strSubject
     * @param string $strBody
     * @param string $strType
     * @param array $lstAttachments
     * @param Msg_Sender $objSender
     */
    public static function add( $strTo, $strSubject, $strBody = '', $strType = 'HTML', 
            $lstAttachments = array(), $objSender  = null )
    {
        $tblMessage     = Msg_Message::Table();
        $tblSender      = Msg_Sender::Table();
        $tblReceiver    = Msg_Receiver::Table();
        $tblAttachment  = Msg_Attachment::Table();

        $strProcessor   = 'Internal';
        if ( !is_object( $objSender ) ) {
            // Detect sender object - default sender for processor....
            $objSender = $tblSender->fetchRow(array( 'mcs_default = 1' ) );
            if ( !is_object( $objSender ))
                throw new Msg_Exception( 'No default sender was set up' );
            $strProcessor   = $objSender->getProcessor();
            if ( $strTo == '' ) {
                $strTo = $objSender->getConfig()->receiver;
            }
        }
        if ( $strProcessor == 'Internal' && $strTo == '' ) {
            $strTo = 'Nobody';
        }

        if ( $strTo == '' )
            throw new Msg_Exception( 'No destination for the message' );

        if (strstr($strTo, ',') ) {
            $arrTo = explode(',', $strTo);
            if (is_array($arrTo) ) {
                foreach ( $arrTo as $strTo) {
                    $strTo = trim( $strTo );
                    $objReceiver = $tblReceiver->fetchRow(array('mcr_processor = ?' => $strProcessor, 'mcr_to = ?' => $strTo));
                    if (!is_object($objReceiver)){
                        $objReceiver = $tblReceiver->createRow();
                        $objReceiver->mcr_processor = $strProcessor;
                        $objReceiver->mcr_to = $strTo;
                        $objReceiver->save();
                    }
                }
            }
        } else {
            $strTo = trim( $strTo );
            $objReceiver = $tblReceiver->fetchRow(array('mcr_processor = ?' => $strProcessor, 'mcr_to = ?' => $strTo));
            if (!is_object($objReceiver)){
                $objReceiver = $tblReceiver->createRow();
                $objReceiver->mcr_processor = $strProcessor;
                $objReceiver->mcr_to = $strTo;
                $objReceiver->save();
            }
        }

        // $strClassName = Sys_Application::getConfig()->msg_classes->$strProcessor;
        
        // $intType = $objProcessor->_getMessageTypeValue($strType);
        // if ($intType == null) {
            // throw new Msg_Exception('Message Type ' . $strType . ' is unknown');
        // }

        $objMessage = $tblMessage->createRow();
        $objMessage->mcm_sender_id = $objSender->mcs_id;
        $objMessage->mcm_receiver_id = $objReceiver->mcr_id;
        $objMessage->mcm_subject = $strSubject;
        $objMessage->mcm_body = $strBody;
        $objMessage->mcm_type = $strType == 'HTML' ? 2 : 1;

        if ( $strProcessor == 'Internal' ) {
            $objMessage->mcm_status = Msg_Message_StatusList::SENT;
            $objMessage->mcm_processed = date('Y-m-d H:i:s');
        }

        $objMessage->save();

        $nOrder = 0;
        if ( is_array( $lstAttachments ) )
            foreach ( $lstAttachments as $strFileName => $strFilePath ) {
                $objAttachment = $tblAttachment->createRow();
                if ( preg_match( '/^\d+$/', $strFileName ) )
                    $strFileName = basename( $strFilePath );

                $objAttachment->mca_file_name = $strFileName;
                $objAttachment->mca_file_path = $strFilePath;
                $objAttachment->mca_order = $nOrder;
                $objAttachment->mca_message_id = $objMessage->getId();
                $objAttachment->save();
                $nOrder++;
            }
    }

    

    /**
     * Add Message to Data Storage
     * @param string $strFrom
     * @param string $strTo
     * @param string $strSubject
     * @param string $strBody
     * @param string $strProcessor
     * @param string $strType
     * @param array of (name => path) $lstAttachments
     
    public static function addToQueue($strFrom, $strTo, $strSubject, $strBody = '',
            $strProcessor = 'Mail', $strType = 'HTML', $lstAttachments = array() )
    {   
        $tblMessage     = new Msg_Message_Table();
        $tblSender      = new Msg_Sender_Table();
        $tblReceiver    = new Msg_Receiver_Table();
        $tblAttachment  = new Msg_Attachment_Table();
        
        //Находим процессор
//        Develop_Debug::dumpDie(Msg_Component::getInstance()->getConfig());
        $objProcessorConfig = Msg_Component::getInstance()->getConfig()->get('processors')->get($strProcessor);
        if (!is_object($objProcessorConfig)) {
            throw new Msg_Exception('Object Processor with name = ' . $strProcessor . ' not found in system!');
        }
        
        $strClassName = $objProcessorConfig->get('class');
        $objProcessor = new $strClassName;
        
        $intType = $objProcessor->_getMessageTypeValue($strType);
        
        if ($intType == null) {
            throw new Msg_Exception('Message Type ' . $strType . ' is unknown');
        }
        
        //Находим отправителя
        $objSender = $tblSender->fetchRow(array('mcs_processor = ?' => $objProcessorConfig->get('name'), 'mcs_from = ?' => $strFrom));
        if (!is_object($objSender)){
            $objSender = $tblSender->createRow();
            $objSender->mcs_processor = $objProcessorConfig->get('name');
            $objSender->mcs_from = $strFrom;
            $objSender->save();
        }
        
        //Находим получателя
        $objReceiver = $tblReceiver->fetchRow(array('mcr_processor = ?' => $objProcessorConfig->get('name'), 'mcr_to = ?' => $strTo));
        if (!is_object($objReceiver)){
            $objReceiver = $tblReceiver->fetchNew();
            $objReceiver->mcr_processor = $objProcessorConfig->get('name');
            $objReceiver->mcr_to = $strTo;
            $objReceiver->save();
        }
        //Добавляем сообщение 
        $objMessage = $tblMessage->fetchNew();
        $objMessage->mcm_sender_id = $objSender->mcs_id;
        $objMessage->mcm_receiver_id = $objReceiver->mcr_id;
        $objMessage->mcm_subject = $strSubject;
        $objMessage->mcm_body = $strBody;
        $objMessage->mcm_type = $intType;
        $objMessage->save();    
        
        $nOrder = 0;
        if ( is_array( $lstAttachments ) )
            foreach ( $lstAttachments as $strFileName => $strFilePath ) {
                $objAttachment = $tblAttachment->createRow();
                if ( preg_match( '/^\d+$/', $strFileName ) )
                    $strFileName = basename( $strFilePath );
                    
                $objAttachment->mca_file_name = $strFileName;
                $objAttachment->mca_file_path = $strFilePath;
                $objAttachment->mca_order = $nOrder;
                $objAttachment->mca_message_id = $objMessage->getId();
                $objAttachment->save(); $nOrder++;
            }
    }    
    */
    
    /**
     * Getter for self::$_strInternalReplyMail
     * @return string
     */
    public static function getInternalReplyMail()
    {
        return self::$_strInternalReplyMail;
    }
    
    /**
     * @return array of Msg_Attachment
     */
    public function getAttachments() 
    {
        $tblAttachment = Msg_Attachment::Table();
        $select = $tblAttachment->select()
            ->where( 'mca_message_id = ?', $this->getId() )
            ->order( 'mca_order' );
        return $tblAttachment->fetchAll( $select );
    }

    public function getReceiverObject()
    {
        return $this->getJoinedObject( 'Msg_Receiver_Table', 'mcm_receiver_id', 'mcr_id' );
    }

    public function getSenderObject()
    {
        return $this->getJoinedObject( 'Msg_Sender_Table', 'mcm_sender_id', 'mcs_id' );
    }

    public static function sendQueue( $bVerbose = false )
    {
        $tblSender = Msg_Sender::Table();
        $tblMessages = Msg_Message::Table();
        $objSender  = $tblSender->fetchAll( array( 'mcs_default = ?' => 1 ) )->current();
        if ( !is_object( $objSender ) ) {
            throw new Msg_Exception( 'No senders configured in the database' );
        }

        $objProcessor = new Msg_Processor_Mail();
        $objProcessor->initialization( $objSender );
        $arrWhere = array(
            'mcm_sender_id = ?'  => $objSender->mcs_id,
            'mcm_status IN (?) ' => Msg_Message_StatusList::PENDING .', '. Msg_Message_StatusList::MUST_RESEND
        );
        $listMessages = $tblMessages->fetchAll( $arrWhere );

        if ( $bVerbose )
            Sys_Io::out( 'Sending messages ('.count( $listMessages ).')' );
        
        $objProcessor->sendMessages( $listMessages );
    }
}