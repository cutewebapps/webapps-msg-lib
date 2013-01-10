<?php
class Msg_MessageCtrl extends App_DbTableCtrl
{
    protected function _joinTables()
    {
        $this->_select->joinLeft( Msg_Sender::TableName(),   'mcm_sender_id = mcs_id');
        $this->_select->joinLeft( Msg_Receiver::TableName(), 'mcm_receiver_id = mcr_id');
    }

    public function clearAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        Msg_Message::Table()->truncate();
        Msg_Attachment::Table()->truncate();

        Sys_Io::out( 'Table of messages was truncated' );
    }

    protected function _filterField($strFieldName, $strFieldValue)
    {
        switch ($strFieldName) {
        	case 'mcs_from':
        	    $strWhere  = 'mcs_from LIKE "%' . $strFieldValue . '%"';
    			$this->_select->where($strWhere, $strFieldValue);
        	    break;
        	case 'mcr_to':
        	    $strWhere  = 'mcr_to LIKE "%' . $strFieldValue . '%"';
    			$this->_select->where($strWhere, $strFieldValue);
        	    break;
            default:
                parent::_filterField($strFieldName, $strFieldValue);
                break;                
        }
    }  

    public function getlistAction()
    {
        if ( $this->_getParam('bulk_delete') ) {
            // Sys_Debug::dumpDie( $this->_getAllParams() );
            foreach ( $this->_getAllParams() as $strParamKey => $strParamValue ) {
                $arrMatchParam = array();
                if ( preg_match( '/^chk_(.+)$/', $strParamKey, $arrMatchParam ) ) {
                    $nMsgId = $arrMatchParam[1];
                    $objMessage = Msg_Message::Table()->find( $nMsgId )->current();

                    if ( is_object( $objMessage ) )
                        $objMessage->delete();
                }
            }
        }
        parent::getlistAction();
    }
}