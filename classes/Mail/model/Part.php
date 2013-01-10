<?php

class Mail_Part implements RecursiveIterator, Mail_Part_Interface
{
    /**
     * headers of part as array
     * @var null|array
     */
    protected $_headers;

    /**
     * raw part body
     * @var null|string
     */
    protected $_content;

    /**
     * toplines as fetched with headers
     * @var string
     */
    protected $_topLines = '';

    /**
     * parts of multipart message
     * @var array
     */
    protected $_parts = array();

    /**
     * count of parts of a multipart message
     * @var null|int
     */
    protected $_countParts;

    /**
     * current position of iterator
     * @var int
     */
    protected $_iterationPos = 1;

    /**
     * mail handler, if late fetch is active
     * @var null|Mail_Storage_Abstract
     */
    protected $_mail;

    /**
     * message number for mail handler
     * @var int
     */
    protected $_messageNum = 0;

    /**
     * Public constructor
     *
     * Mail_Part supports different sources for content. The possible params are:
     * - handler    a instance of Mail_Storage_Abstract for late fetch
     * - id         number of message for handler
     * - raw        raw content with header and body as string
     * - headers    headers as array (name => value) or string, if a content part is found it's used as toplines
     * - noToplines ignore content found after headers in param 'headers'
     * - content    content as string
     *
     * @param   array $params  full message with or without headers
     * @throws  Mail_Exception
     */
    public function __construct(array $params)
    {
        if (isset($params['handler'])) {
            if (!$params['handler'] instanceof Mail_Storage_Abstract) {
                /**
                 * @see Mail_Exception
                 */
                throw new Mail_Exception('handler is not a valid mail handler');
            }
            if (!isset($params['id'])) {
                /**
                 * @see Mail_Exception
                 */
                throw new Mail_Exception('need a message id with a handler');
            }

            $this->_mail       = $params['handler'];
            $this->_messageNum = $params['id'];
        }

        if (isset($params['raw'])) {
            Mime_Decode::splitMessage($params['raw'], $this->_headers, $this->_content);
        } else if (isset($params['headers'])) {
            if (is_array($params['headers'])) {
                $this->_headers = $params['headers'];
            } else {
                if (!empty($params['noToplines'])) {
                    Mime_Decode::splitMessage($params['headers'], $this->_headers, $null);
                } else {
                    Mime_Decode::splitMessage($params['headers'], $this->_headers, $this->_topLines);
                }
            }
            if (isset($params['content'])) {
                $this->_content = $params['content'];
            }
        }
    }

    /**
     * Check if part is a multipart message
     *
     * @return bool if part is multipart
     */
    public function isMultipart()
    {
        try {
            return stripos($this->contentType, 'multipart/') === 0;
        } catch(Mail_Exception $e) {
            return false;
        }
    }


    /**
     * Body of part
     *
     * If part is multipart the raw content of this part with all sub parts is returned
     *
     * @return string body
     * @throws Mail_Exception
     */
    public function getContent()
    {
        if ($this->_content !== null) {
            return $this->_content;
        }

        if ($this->_mail) {
            return $this->_mail->getRawContent($this->_messageNum);
        } else {
            /**
             * @see Mail_Exception
             */
            throw new Mail_Exception('no content');
        }
    }

    /**
     * Return size of part
     *
     * Quite simple implemented currently (not decoding). Handle with care.
     *
     * @return int size
     */
    public function getSize() {
        return strlen($this->getContent());
    }


    /**
     * Cache content and split in parts if multipart
     *
     * @return null
     * @throws Mail_Exception
     */
    protected function _cacheContent()
    {
        // caching content if we can't fetch parts
        if ($this->_content === null && $this->_mail) {
            $this->_content = $this->_mail->getRawContent($this->_messageNum);
        }

        if (!$this->isMultipart()) {
            return;
        }

        // split content in parts
        $boundary = $this->getHeaderField('content-type', 'boundary');
        if (!$boundary) {
            /**
             * @see Mail_Exception
             */
            throw new Mail_Exception('no boundary found in content type to split message');
        }
        $parts = Mime_Decode::splitMessageStruct($this->_content, $boundary);
        if ($parts === null) {
            return;
        }
        $counter = 1;
        foreach ($parts as $part) {
            $this->_parts[$counter++] = new self(array('headers' => $part['header'], 'content' => $part['body']));
        }
    }

    /**
     * Get part of multipart message
     *
     * @param  int $num number of part starting with 1 for first part
     * @return Mail_Part wanted part
     * @throws Mail_Exception
     */
    public function getPart($num)
    {
        if (isset($this->_parts[$num])) {
            return $this->_parts[$num];
        }

        if (!$this->_mail && $this->_content === null) {
            /**
             * @see Mail_Exception
             */
            throw new Mail_Exception('part not found');
        }

        if ($this->_mail && $this->_mail->hasFetchPart) {
            // TODO: fetch part
            // return
        }

        $this->_cacheContent();

        if (!isset($this->_parts[$num])) {
            /**
             * @see Mail_Exception
             */
            throw new Mail_Exception('part not found');
        }

        return $this->_parts[$num];
    }

    /**
     * Count parts of a multipart part
     *
     * @return int number of sub-parts
     */
    public function countParts()
    {
        if ($this->_countParts) {
            return $this->_countParts;
        }

        $this->_countParts = count($this->_parts);
        if ($this->_countParts) {
            return $this->_countParts;
        }

        if ($this->_mail && $this->_mail->hasFetchPart) {
            // TODO: fetch part
            // return
        }

        $this->_cacheContent();

        $this->_countParts = count($this->_parts);
        return $this->_countParts;
    }


    /**
     * Get all headers
     *
     * The returned headers are as saved internally. All names are lowercased. The value is a string or an array
     * if a header with the same name occurs more than once.
     *
     * @return array headers as array(name => value)
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            if (!$this->_mail) {
                $this->_headers = array();
            } else {
                $part = $this->_mail->getRawHeader($this->_messageNum);
                Mime_Decode::splitMessage($part, $this->_headers, $null);
            }
        }

        return $this->_headers;
    }

    /**
     * Get a header in specificed format
     *
     * Internally headers that occur more than once are saved as array, all other as string. If $format
     * is set to string implode is used to concat the values (with Mime_Const::LINEEND as delim).
     *
     * @param  string $name   name of header, matches case-insensitive, but camel-case is replaced with dashes
     * @param  string $format change type of return value to 'string' or 'array'
     * @return string|array value of header in wanted or internal format
     * @throws Mail_Exception
     */
    public function getHeader($name, $format = null)
    {
        if ($this->_headers === null) {
            $this->getHeaders();
        }

        $lowerName = strtolower($name);

        if ($this->headerExists($name) == false) {
            $lowerName = strtolower(preg_replace('%([a-z])([A-Z])%', '\1-\2', $name));
            if($this->headerExists($lowerName) == false) {
                /**
                 * @see Mail_Exception
                 */
                throw new Mail_Exception("no Header with Name $name or $lowerName found");
            }
        }
        $name = $lowerName;

        $header = $this->_headers[$name];

        switch ($format) {
            case 'string':
                if (is_array($header)) {
                    $header = implode(Mime_Const::LINEEND, $header);
                }
                break;
            case 'array':
                $header = (array)$header;
            default:
                // do nothing
        }

        return $header;
    }

    /**
     * Check wheater the Mail part has a specific header.
     *
     * @param  string $name
     * @return boolean
     */
    public function headerExists($name)
    {
        $name = strtolower($name);
        if(isset($this->_headers[$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a specific field from a header like content type or all fields as array
     *
     * If the header occurs more than once, only the value from the first header
     * is returned.
     *
     * Throws a Mail_Exception if the requested header does not exist. If
     * the specific header field does not exist, returns null.
     *
     * @param  string $name       name of header, like in getHeader()
     * @param  string $wantedPart the wanted part, default is first, if null an array with all parts is returned
     * @param  string $firstName  key name for the first part
     * @return string|array wanted part or all parts as array($firstName => firstPart, partname => value)
     * @throws Mail_Exception
     */
    public function getHeaderField($name, $wantedPart = 0, $firstName = 0) {
        return Mime_Decode::splitHeaderField(current($this->getHeader($name, 'array')), $wantedPart, $firstName);
    }


    /**
     * Getter for mail headers - name is matched in lowercase
     *
     * This getter is short for Mail_Part::getHeader($name, 'string')
     *
     * @see Mail_Part::getHeader()
     *
     * @param  string $name header name
     * @return string value of header
     * @throws Mail_Exception
     */
    public function __get($name)
    {
        return $this->getHeader($name, 'string');
    }

    /**
     * Isset magic method proxy to hasHeader
     *
     * This method is short syntax for Mail_Part::hasHeader($name);
     *
     * @see Mail_Part::hasHeader
     *
     * @param  string
     * @return boolean
     */
    public function __isset($name)
    {
        return $this->headerExists($name);
    }

    /**
     * magic method to get content of part
     *
     * @return string content
     */
    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * implements RecursiveIterator::hasChildren()
     *
     * @return bool current element has children/is multipart
     */
    public function hasChildren()
    {
        $current = $this->current();
        return $current && $current instanceof Mail_Part && $current->isMultipart();
    }

    /**
     * implements RecursiveIterator::getChildren()
     *
     * @return Mail_Part same as self::current()
     */
    public function getChildren()
    {
        return $this->current();
    }

    /**
     * implements Iterator::valid()
     *
     * @return bool check if there's a current element
     */
    public function valid()
    {
        if ($this->_countParts === null) {
            $this->countParts();
        }
        return $this->_iterationPos && $this->_iterationPos <= $this->_countParts;
    }

    /**
     * implements Iterator::next()
     *
     * @return null
     */
    public function next()
    {
        ++$this->_iterationPos;
    }

    /**
     * implements Iterator::key()
     *
     * @return string key/number of current part
     */
    public function key()
    {
        return $this->_iterationPos;
    }

    /**
     * implements Iterator::current()
     *
     * @return Mail_Part current part
     */
    public function current()
    {
        return $this->getPart($this->_iterationPos);
    }

    /**
     * implements Iterator::rewind()
     *
     * @return null
     */
    public function rewind()
    {
        $this->countParts();
        $this->_iterationPos = 1;
    }
}
