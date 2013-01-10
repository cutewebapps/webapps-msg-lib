<?php

class Msg_IndexCtrl  extends App_AbstractCtrl
{
    /**
     * Action to edit first senders record
     * used for minimalistic site configuration
     */
    public function mailOptionsAction()
    {
        $tblSender = Msg_Sender::Table();
        $objSender = null;
        if ( $this->_getParam( 'sender_id' )) {
            $objSender  = $tblSender->find( $this->_getParam( 'sender_id' ) )->current();

            // updating senders database record...
            
            $objSender->mcs_processor = $this->_getParam( 'msg_processor_type' );
            $objSender->mcs_from  = $this->_getParam( 'sender_from' );
            $arrConfig = array();

            // TRICKY: small vulnerability here - place check all input params!
            foreach (  $this->_getAllParams() as $strParamKey => $strParamValue ) {
                $arrMatches = array();
                if ( preg_match( '/^sender_config_(.+)$/', $strParamKey, $arrMatches ) ) {
                    $arrConfig[ $arrMatches[1] ] = $strParamValue;
                }
            }
            $objSender->mcs_config = serialize( $arrConfig );
            $objSender->save();
        } else {
            $objSender  = $tblSender->fetchAll( array( 'mcs_default = ?' => 1 ) )->current();
        }
        
        if ( !is_object( $objSender ) ) {
            $objSender = $tblSender->createRow();
            $objSender->mcs_from      = '';
            $objSender->mcs_processor = 'Internal';
            $objSender->mcs_config    = '';
            $objSender->mcs_default   = '1';
            $objSender->save();
        }
        $this->view->object = $objSender;
    }


    /**
     
    public function addMessageAction()
    {
        $paramFrom = $this->_request->getParam('from');
        $paramTo = $this->_request->getParam('to');
        $paramType = $this->_request->getParam('type', 'HTML');
        $paramProcessor = $this->_request->getParam('processor', 'Mail');
        $paramSubject = $this->_request->getParam('subject');
        $paramBody = $this->_request->getParam('body');

        Msg_Message::addToQueue($paramFrom, $paramTo, $paramSubject, $paramBody, $paramProcessor, $paramType);
        $this->_helper->viewRenderer->setNoRender(true);
    }
    
    public function sendMessagesAllAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    	
        $tblSender = new Msg_Sender_Table();
        $tblMessage = new Msg_Message_Table();
        
        $objSenderSelect = $tblSender->select()
                                     ->from($tblSender)
                                     ->setIntegrityCheck(FALSE);
                                     
        $objMessageSelect = $tblMessage->select()
                                       ->from($tblMessage, array('mcm_sender_id', 
                                                                 'messages_count' => new Zend_Db_Expr('COUNT(*)')))
                                       ->where('mcm_status IN (' . Msg_Message_StatusList::PENDING . ','
                                                                 . Msg_Message_StatusList::MUST_RESEND . ')')
                                       ->group('mcm_sender_id');
        
        $objSenderSelect->joinInner($objMessageSelect, 't.mcm_sender_id = mcs_id');
        $lstSenders = $tblSender->fetchAll($objSenderSelect);
        
        foreach ($lstSenders as $objSender) {
	        $objMessageSelect = $tblMessage->select()
	                                       ->from($tblMessage)
	                                       ->where('mcm_status IN (' . Msg_Message_StatusList::PENDING . ','
	                                                                 . Msg_Message_StatusList::MUST_RESEND . ')')
	                                       ->where('mcm_sender_id = ?', $objSender->getId());
        	
        	$lstMessages = $tblMessage->fetchAll($objMessageSelect);
        	
	        $strClassName = 'Msg_Processor_' . $objSender->mcs_processor;
	        $objProcessor = new $strClassName;
	        $objProcessor->initialization($objSender);
	        $objProcessor->sendMessages($lstMessages);
        }
    }
    
    public function sendMessagesAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $paramSenderID = $this->_request->getParam('sender');
        if (!$paramSenderID){
            throw new Msg_Exception('Parameter "sender" should be defined!');
        }
        
        $tblSenders = new Msg_Sender_Table();
        $tblMessages = new Msg_Message_Table();
        
        $listSender = $tblSenders->find($paramSenderID);
        if (!$listSender->count()){
            throw new Msg_Exception('Object "Sender" not found in system!');
        }
        $objSender = $listSender->current();
        
        $listMessageStatuses = new Msg_Message_StatusList();
        $arrWhere = array(
        	'mcm_sender_id = ?' => $objSender->mcs_id, 
        	'mcm_status = ' . $listMessageStatuses->getValue('PENDING') . ' OR mcm_status = ' . $listMessageStatuses->getValue('MUST_RESEND'),
        );
        $listMessages = $tblMessages->fetchAll($arrWhere);
        if (!$listMessages->count()){
            return;
        }
        
        $strClassName = 'Msg_Processor_' . $objSender->mcs_processor;
        $objProcessor = new $strClassName;
        $objProcessor->initialization($objSender);
        $objProcessor->sendMessages($listMessages);
    }
    
*/

    public function sendAllAction()
    {
        Msg_Message::sendQueue( true ); die;

    }
    public function testMailAction()
    {
//-- start
        $strTo = $this->_getParam('to');

        if ( $strTo == '' )
            throw new Msg_Exception( 'No destination for the message' );
        
        $strBody = 'This is a sample content<br />'
            . 'This is a test from message component<br />'.date('Y-m-d H:i:s');
        $strSubject  = 'Test Message '.date('Y-m-d H:i:s');

        Msg_Message::add( $strTo, $strSubject, $strBody, 'HTML' );
        Msg_Message::sendQueue( true ); die;
    }

    public function contactMailAction()
    {
        $strForm = $this->_getParam( 'form', '' );
        $arrConfig = App_Application::getInstance()->getConfig()->toArray();
        
        if ( !isset( $arrConfig[ 'contact' ] ) )
            throw new App_Exception( 'Contact Form is not configured correctly' );
        if ( !isset( $arrConfig[ 'contact'][ $strForm ] ) )
            throw new App_Exception( 'Contact form "'. $strForm. '" is not configured' );

        $strSubject = $arrConfig[ 'contact' ][ $strForm ]['subject'].' ';
        $arrFormConfig = $arrConfig[ 'contact'][ $strForm ]['params'];
        
        if ( count( $_POST ) > 0 && count( $arrFormConfig ) > 0 ) {
            $arrStrings = array();
            $arrMatches = array();
            
            foreach( $this->_getAllParams() as $strParam => $strParamValue ) {
                if ( isset( $arrFormConfig[ $strParam ] ) ) {
                    $strName = $arrFormConfig[ $strParam ];
                    $arrStrings [ $strName ]= '<div>'.$strName . ': '.$strParamValue.'</div>';
                }
            }
            $strBody = implode( "\n", $arrStrings );
            
            //if ( isset( $arrStrings [ $strName ] ) )
                // $strSubject .= ' :: from '. $arrStrings [ $strName ];

            $strTo = '';
            Msg_Message::add( $strTo, $strSubject, $strBody, 'HTML' );

            if ( $this->_getParam( 'process' ) ) {
                if ( Sys_Mode::isProduction() ) Msg_Message::sendQueue( false );
            }
            $this->setRender( 'sent' );
        }
        $this->view->return = $this->_getParam( 'return' , '');
    }
}
