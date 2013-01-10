<?php

class Mail_Transport_File extends Mail_Transport_Abstract
{
    /**
     * Target directory for saving sent email messages
     *
     * @var string
     */
    protected $_path;

    /**
     * Callback function generating a file name
     *
     * @var string|array
     */
    protected $_callback;

    /**
     * Constructor
     *
     * @param  array|Sys_Config $options OPTIONAL (Default: null)
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Sys_Config) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            $options = array();
        }

        // Making sure we have some defaults to work with
        if (!isset($options['path'])) {
            $options['path'] = sys_get_temp_dir();
        }
        if (!isset($options['callback'])) {
            $options['callback'] = array($this, 'defaultCallback');
        }

        $this->setOptions($options);
    }

    /**
     * Sets options
     *
     * @param  array $options
     * @return void
     */
    public function setOptions(array $options)
    {
        if (isset($options['path'])&& is_dir($options['path'])) {
            $this->_path = $options['path'];
        }
        if (isset($options['callback']) && is_callable($options['callback'])) {
            $this->_callback = $options['callback'];
        }
    }

    /**
     * Saves e-mail message to a file
     *
     * @return void
     * @throws Mail_Transport_Exception on not writable target directory
     * @throws Mail_Transport_Exception on file_put_contents() failure
     */
    protected function _sendMail()
    {
        $file = $this->_path . DIRECTORY_SEPARATOR . call_user_func($this->_callback, $this);

        if (!is_writable(dirname($file))) {
            
            throw new Mail_Transport_Exception(sprintf(
                'Target directory "%s" does not exist or is not writable',
                dirname($file)
            ));
        }

        $email = $this->header . $this->EOL . $this->body;

        if (!file_put_contents($file, $email)) {
            
            throw new Mail_Transport_Exception('Unable to send mail');
        }
    }

    /**
     * Default callback for generating filenames
     *
     * @param Mail_Transport_File File transport instance
     * @return string
     */
    public function defaultCallback($transport) 
    {
        return 'Mail_' . $_SERVER['REQUEST_TIME'] . '_' . mt_rand() . '.tmp';
    }
}
