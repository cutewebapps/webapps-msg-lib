<?php

class Mime_Part {

    public $type = Mime_Const::TYPE_OCTETSTREAM;
    public $encoding = Mime_Const::ENCODING_8BIT;
    public $id;
    public $disposition;
    public $filename;
    public $description;
    public $charset;
    public $boundary;
    public $location;
    public $language;
    protected $_content;
    protected $_isStream = false;


    /**
     * create a new Mime Part.
     * The (unencoded) content of the Part as passed
     * as a string or stream
     *
     * @param mixed $content  String or Stream containing the content
     */
    public function __construct($content)
    {
        $this->_content = $content;
        if (is_resource($content)) {
            $this->_isStream = true;
        }
    }

    /**
     * @todo setters/getters
     * @todo error checking for setting $type
     * @todo error checking for setting $encoding
     */

    /**
     * check if this part can be read as a stream.
     * if true, getEncodedStream can be called, otherwise
     * only getContent can be used to fetch the encoded
     * content of the part
     *
     * @return bool
     */
    public function isStream()
    {
      return $this->_isStream;
    }

    /**
     * if this was created with a stream, return a filtered stream for
     * reading the content. very useful for large file attachments.
     *
     * @return stream
     * @throws Mime_Exception if not a stream or unable to append filter
     */
    public function getEncodedStream()
    {
        if (!$this->_isStream) {
            throw new Mime_Exception('Attempt to get a stream from a string part');
        }

        //stream_filter_remove(); // ??? is that right?
        switch ($this->encoding) {
            case Mime_Const::ENCODING_QUOTEDPRINTABLE:
                $filter = stream_filter_append(
                    $this->_content,
                    'convert.quoted-printable-encode',
                    STREAM_FILTER_READ,
                    array(
                        'line-length'      => 76,
                        'line-break-chars' => Mime_Const::LINEEND
                    )
                );
                if (!is_resource($filter)) {
                    throw new Mime_Exception('Failed to append quoted-printable filter');
                }
                break;
            case Mime_Const::ENCODING_BASE64:
                $filter = stream_filter_append(
                    $this->_content,
                    'convert.base64-encode',
                    STREAM_FILTER_READ,
                    array(
                        'line-length'      => 76,
                        'line-break-chars' => Mime_Const::LINEEND
                    )
                );
                if (!is_resource($filter)) {
                    throw new Mime_Exception('Failed to append base64 filter');
                }
                break;
            default:
        }
        return $this->_content;
    }

    /**
     * Get the Content of the current Mime Part in the given encoding.
     *
     * @return String
     */
    public function getContent($EOL = Mime_Const::LINEEND)
    {
        if ($this->_isStream) {
            return stream_get_contents($this->getEncodedStream());
        } else {
            return Mime_Encode::encode($this->_content, $this->encoding, $EOL);
        }
    }

    /**
     * Get the Content of the current Mime Part in the given encoding.
     *
     * @return String
     */
    public function getRawContent()
    {
        if ($this->_isStream) {
            return stream_get_contents($this->_content);
        } else {
            return $this->_content;
        }
    }

    /**
     * Create and return the array of headers for this MIME part
     *
     * @access public
     * @return array
     */
    public function getHeadersArray($EOL = Mime_Const::LINEEND)
    {
        $headers = array();

        $contentType = $this->type;
        if ($this->charset) {
            $contentType .= '; charset=' . $this->charset;
        }

        if ($this->boundary) {
            $contentType .= ';' . $EOL
                          . " boundary=\"" . $this->boundary . '"';
        }

        $headers[] = array('Content-Type', $contentType);

        if ($this->encoding) {
            $headers[] = array('Content-Transfer-Encoding', $this->encoding);
        }

        if ($this->id) {
            $headers[]  = array('Content-ID', '<' . $this->id . '>');
        }

        if ($this->disposition) {
            $disposition = $this->disposition;
            if ($this->filename) {
                $disposition .= '; filename="' . $this->filename . '"';
            }
            $headers[] = array('Content-Disposition', $disposition);
        }

        if ($this->description) {
            $headers[] = array('Content-Description', $this->description);
        }

        if ($this->location) {
            $headers[] = array('Content-Location', $this->location);
        }

        if ($this->language){
            $headers[] = array('Content-Language', $this->language);
        }

        return $headers;
    }

    /**
     * Return the headers for this part as a string
     *
     * @return String
     */
    public function getHeaders($EOL = Mime_Const::LINEEND)
    {
        $res = '';
        foreach ($this->getHeadersArray($EOL) as $header) {
            $res .= $header[0] . ': ' . $header[1] . $EOL;
        }

        return $res;
    }
}
