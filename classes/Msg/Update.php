<?php

class Msg_Update extends App_Update
{
    const VERSION = '0.0.1';
    public static function getClassName() { return 'Msg_Update'; }
    public static function TableClass() { return self::getClassName().'_Table'; }
    public static function Table() { $strClass = self::TableClass();  return new $strClass; }
    public static function TableName() { return self::Table()->getTableName(); }


    public function update()
    {
        if ( $this->isVersionBelow( '0.0.1' ) ) {
            $this->_install();
        }
        // $this->save( self::VERSION );
    }
    /**
     * @return array
     */
    public function getTables()
    {
        return array(
            Msg_Message::TableName(),
            Msg_Sender::TableName(),
            Msg_Attachment::TableName(),
            Msg_Receiver::TableName(),
        );
    }


    protected function _install() 
    {
        if (!$this->getDbAdapterRead()->hasTable('msg_message')) {

            Sys_Io::out( 'Creating Messages Table' );
            $this->getDbAdapterWrite()->addTableSql('msg_message', '
                `mcm_id` int(10)  unsigned NOT NULL AUTO_INCREMENT, 
                `mcm_sender_id`   int(10) unsigned NOT NULL DEFAULT \'0\', 
                `mcm_receiver_id` int(10) unsigned NOT NULL DEFAULT \'0\', 
                `mcm_subject`     varchar(255) NOT NULL DEFAULT \'\', 
                `mcm_type`        int(10) unsigned NOT NULL DEFAULT \'0\', 
                `mcm_added`       datetime NOT NULL, 
                `mcm_processed`   datetime NOT NULL, 
                `mcm_status`      int(10) unsigned NOT NULL DEFAULT \'0\',
                `mcm_body`        mediumtext
            ', 'mcm_id' );
        }

        if (!$this->getDbAdapterRead()->hasTable( 'msg_sender' ) ) {
            Sys_Io::out( 'Creating Senders Table' );
            $this->getDbAdapterWrite()->addTableSql( 'msg_sender', '
                `mcs_id`        int(10) unsigned NOT NULL AUTO_INCREMENT, 
                `mcs_from`      varchar(255) NOT NULL DEFAULT \'\', 
                `mcs_processor` varchar(255) NOT NULL DEFAULT \'Internal\',
                `mcs_config`    mediumtext,
                `mcs_default`   int(10) DEFAULT 0,
            ', 'mcs_id' );
        }

        if (!$this->getDbAdapterRead()->hasTable( 'msg_receiver' ) ) {
            Sys_Io::out( 'Creating Receivers Table' );

            $this->getDbAdapterWrite()->addTableSql( 'msg_receiver', '
                `mcr_id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
                `mcr_to`        varchar(255) NOT NULL DEFAULT \'\',
                `mcr_status`    int(10) unsigned NOT NULL DEFAULT \'0\',
                `mcr_processor` varchar(255) NOT NULL DEFAULT \'\',
                KEY( mcr_to )
            ', 'mcr_id' );
        }

        if (!$this->getDbAdapterRead()->hasTable( 'msg_mail_attachment' ) ) {

            Sys_Io::out( 'Creating Attachments Table' );
            $this->getDbAdapterWrite()->addTableSql( 'msg_mail_attachment', '

                `mca_id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
                `mca_message_id`  int(10) NOT NULL,
                `mca_order`       int(10) NOT NULL DEFAULT \'0\',
                `mca_file_name`   varchar(100) NOT NULL,
                `mca_file_path`   varchar(200) NOT NULL,
                `mca_mime_type`   varchar(30) NOT NULL DEFAULT \'application/octet-stream\',
                `mca_disposition` varchar(30) NOT NULL DEFAULT \'attachment\', 
                `mca_encoding`    varchar(20) NOT NULL DEFAULT \'base64\',

                KEY `mca_message_id` (`mca_message_id`) 
            ', 'mca_id' );
        }
    }

}