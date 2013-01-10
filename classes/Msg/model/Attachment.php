<?php

class Msg_Attachment_Table extends DBx_Table
{
        protected $_name = 'msg_mail_attachment';
        protected $_primary = 'mca_id';
    
}
class Msg_Attachment_List extends DBx_Table_Rowset
{

}

class Msg_Attachment extends DBx_Table_Row
{
    public static function getClassName() { return 'Msg_Attachment'; }
    public static function TableClass() { return self::getClassName().'_Table'; }
    public static function Table() { $strClass = self::TableClass();  return new $strClass; }
    public static function TableName() { return self::Table()->getTableName(); }
    public static function FormClass( $name ) { return self::getClassName().'_Form_'.$name; }
    public static function Form( $name ) { $strClass = self::getClassName().'_Form_'.$name; return new $strClass; }

    /**
     * @return string name of file in attachment
     */
    public function getFileName()
    {
        return $this->mca_file_name;
    }
    /**
     * @return string path of file
     */
    public function getFilePath()
    {
        return $this->mca_file_path;
    }
    
    /**
     * @return string, "application/octet-stream" by default
     */
    public function getMimeType() 
    {
        return $this->mca_mime_type;
    }
    /**
     * @return string, "attachment" by default
     */
    public function getDisposition() 
    {
        return $this->mca_disposition;
    }
    /**
     * @return string, "base64" by default
     */
    public function getEncoding() 
    {
        return $this->mca_encoding;
    }
 
    /**
     * @return Mime_Part
     * @param Mail_Msg $mail
     */
    public function attachTo( Mail_Msg $mail )
    {
        if ( !file_exists( $this->getFilePath() ) )
            throw new Msg_Exception( 'Attachment not found at '.$this->getFilePath  );
            
        return $mail->createAttachment( 
                    file_get_contents( $this->getFilePath() ),
                    $this->getMimeType(),
                    $this->getDisposition(),
                    $this->getEncoding(),
                    $this->getFileName() );
    }
}