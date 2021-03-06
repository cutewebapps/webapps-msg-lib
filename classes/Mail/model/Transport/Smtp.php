<?php

class Mail_Transport_Smtp extends Mail_Transport_Abstract
{
    /**
     * EOL character string used by transport
     * @var string
     * @access public
     */
    public $EOL = "\n";

    /**
     * Remote smtp hostname or i.p.
     *
     * @var string
     */
    protected $_host;


    /**
     * Port number
     *
     * @var integer|null
     */
    protected $_port;


    /**
     * Local client hostname or i.p.
     *
     * @var string
     */
    protected $_name = 'localhost';


    /**
     * Authentication type OPTIONAL
     *
     * @var string
     */
    protected $_auth;


    /**
     * Config options for authentication
     *
     * @var array
     */
    protected $_config;


    /**
     * Instance of Mail_Protocol_Smtp
     *
     * @var Mail_Protocol_Smtp
     */
    protected $_connection;


    /**
     * Constructor.
     *
     * @param  string $host OPTIONAL (Default: 127.0.0.1)
     * @param  array|null $config OPTIONAL (Default: null)
     * @return void
     * 
     * @todo Someone please make this compatible
     *       with the SendMail transport class.
     */
    public function __construct($host = '127.0.0.1', Array $config = array())
    {
        if (isset($config['name'])) {
            $this->_name = $config['name'];
        }
        if (isset($config['port'])) {
            $this->_port = $config['port'];
        }
        if (isset($config['auth'])) {
            $this->_auth = $config['auth'];
        }

        $this->_host = $host;
        $this->_config = $config;
    }


    /**
     * Class destructor to ensure all open connections are closed
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->_connection instanceof Mail_Protocol_Smtp) {
            try {
                $this->_connection->quit();
            } catch (Mail_Protocol_Exception $e) {
                // ignore
            }
            $this->_connection->disconnect();
        }
    }


    /**
     * Sets the connection protocol instance
     *
     * @param Mail_Protocol_Abstract $client
     *
     * @return void
     */
    public function setConnection(Mail_Protocol_Abstract $connection)
    {
        $this->_connection = $connection;
    }


    /**
     * Gets the connection protocol instance
     *
     * @return Mail_Protocol|null
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Send an email via the SMTP connection protocol
     *
     * The connection via the protocol adapter is made just-in-time to allow a
     * developer to add a custom adapter if required before mail is sent.
     *
     * @return void
     * @todo Rename this to sendMail, it's a public method...
     */
    public function _sendMail()
    {
        // If sending multiple messages per session use existing adapter
        if (!($this->_connection instanceof Mail_Protocol_Smtp)) {
            // Check if authentication is required and determine required class
            $connectionClass = 'Mail_Protocol_Smtp';
            if ($this->_auth) {
                $connectionClass .= '_Auth_' . ucwords($this->_auth);
            }
            $this->setConnection(new $connectionClass($this->_host, $this->_port, $this->_config));
            $this->_connection->connect();
            $this->_connection->helo($this->_name);
        } else {
            // Reset connection to ensure reliable transaction
            $this->_connection->rset();
        }

        // Set sender email address
        $this->_connection->mail($this->_mail->getReturnPath());

        // Set recipient forward paths
        foreach ($this->_mail->getRecipients() as $recipient) {
            $this->_connection->rcpt($recipient);
        }

        // Issue DATA command to client
        $this->_connection->data($this->header . Mime_Const::LINEEND . $this->body);
    }

    /**
     * Format and fix headers
     *
     * Some SMTP servers do not strip BCC headers. Most clients do it themselves as do we.
     *
     * @access  protected
     * @param   array $headers
     * @return  void
     * @throws  Mail_Transport_Exception
     */
    protected function _prepareHeaders($headers)
    {
        if (!$this->_mail) {
            /**
             * @see Mail_Transport_Exception
             */
            throw new Mail_Transport_Exception('_prepareHeaders requires a registered Mail_Msg object');
        }

        unset($headers['Bcc']);

        // Prepare headers
        parent::_prepareHeaders($headers);
    }
}
