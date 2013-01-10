<?php

class Mail_Protocol_Smtp_Auth_Crammd5 extends Mail_Protocol_Smtp
{
    /**
     * Constructor.
     *
     * @param  string $host   (Default: 127.0.0.1)
     * @param  int    $port   (Default: null)
     * @param  array  $config Auth-specific parameters
     * @return void
     */
    public function __construct($host = '127.0.0.1', $port = null, $config = null)
    {
        if (is_array($config)) {
            if (isset($config['username'])) {
                $this->_username = $config['username'];
            }
            if (isset($config['password'])) {
                $this->_password = $config['password'];
            }
        }

        parent::__construct($host, $port, $config);
    }


    /**
     * @todo Perform CRAM-MD5 authentication with supplied credentials
     *
     * @return void
     */
    public function auth()
    {
        // Ensure AUTH has not already been initiated.
        parent::auth();

        $this->_send('AUTH CRAM-MD5');
        $challenge = $this->_expect(334);
        $challenge = base64_decode($challenge);
        $digest = $this->_hmacMd5($this->_password, $challenge);
        $this->_send(base64_encode($this->_username . ' ' . $digest));
        $this->_expect(235);
        $this->_auth = true;
    }


    /**
     * Prepare CRAM-MD5 response to server's ticket
     *
     * @param  string $key   Challenge key (usually password)
     * @param  string $data  Challenge data
     * @param  string $block Length of blocks
     * @return string
     */
    protected function _hmacMd5($key, $data, $block = 64)
    {
        if (strlen($key) > 64) {
            $key = pack('H32', md5($key));
        } elseif (strlen($key) < 64) {
            $key = str_pad($key, $block, "\0");
        }

        $k_ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
        $k_opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);

        $inner = pack('H32', md5($k_ipad . $data));
        $digest = md5($k_opad . $inner);

        return $digest;
    }
}
