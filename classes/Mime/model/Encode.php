<?php

class Mime_Encode
{
    protected $_boundary;
    protected static $makeUnique = 0;


    /**
     * Check if the given string is "printable"
     *
     * Checks that a string contains no unprintable characters. If this returns
     * false, encode the string for secure delivery.
     *
     * @param string $str
     * @return boolean
     */
    public static function isPrintable($str)
    {
        return (strcspn($str, Mime_Const::$qpKeysString) == strlen($str));
    }

    /**
     * Encode a given string with the QUOTED_PRINTABLE mechanism and wrap the lines.
     *
     * @param string $str
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param int $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeQuotedPrintable($str,
        $lineLength = Mime_Const::LINELENGTH,
        $lineEnd = Mime_Const::LINEEND)
    {
        $out = '';
        $str = self::_encodeQuotedPrintable($str);

        // Split encoded text into separate lines
        while ($str) {
            $ptr = strlen($str);
            if ($ptr > $lineLength) {
                $ptr = $lineLength;
            }

            // Ensure we are not splitting across an encoded character
            $pos = strrpos(substr($str, 0, $ptr), '=');
            if ($pos !== false && $pos >= $ptr - 2) {
                $ptr = $pos;
            }

            // Check if there is a space at the end of the line and rewind
            if ($ptr > 0 && $str[$ptr - 1] == ' ') {
                --$ptr;
            }

            // Add string and continue
            $out .= substr($str, 0, $ptr) . '=' . $lineEnd;
            $str = substr($str, $ptr);
        }

        $out = rtrim($out, $lineEnd);
        $out = rtrim($out, '=');
        return $out;
    }

    /**
     * Converts a string into quoted printable format.
     *
     * @param  string $str
     * @return string
     */
    private static function _encodeQuotedPrintable($str)
    {
        $str = str_replace('=', '=3D', $str);
        $str = str_replace(Mime_Const::$qpKeys, Mime_Const::$qpReplaceValues, $str);
        $str = rtrim($str);
        return $str;
    }

    /**
     * Encode a given string with the QUOTED_PRINTABLE mechanism for Mail Headers.
     *
     * Mail headers depend on an extended quoted printable algorithm otherwise
     * a range of bugs can occur.
     *
     * @param string $str
     * @param string $charset
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param int $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeQuotedPrintableHeader($str, $charset,
        $lineLength = Mime_Const::LINELENGTH,
        $lineEnd = Mime_Const::LINEEND)
    {
        // Reduce line-length by the length of the required delimiter, charsets and encoding
        $prefix = sprintf('=?%s?Q?', $charset);
        $lineLength = $lineLength-strlen($prefix)-3;

        $str = self::_encodeQuotedPrintable($str);

        // Mail-Header required chars have to be encoded also:
        $str = str_replace(array('?', ' ', '_'), array('=3F', '=20', '=5F'), $str);

        // initialize first line, we need it anyways
        $lines = array(0 => "");

        // Split encoded text into separate lines
        $tmp = "";
        while(strlen($str) > 0) {
            $currentLine = max(count($lines)-1, 0);
            $token       = self::getNextQuotedPrintableToken($str);
            $str         = substr($str, strlen($token));

            $tmp .= $token;
            if($token == '=20') {
                // only if we have a single char token or space, we can append the
                // tempstring it to the current line or start a new line if necessary.
                if(strlen($lines[$currentLine].$tmp) > $lineLength) {
                    $lines[$currentLine+1] = $tmp;
                } else {
                    $lines[$currentLine] .= $tmp;
                }
                $tmp = "";
            }
            // don't forget to append the rest to the last line
            if(strlen($str) == 0) {
                $lines[$currentLine] .= $tmp;
            }
        }

        // assemble the lines together by pre- and appending delimiters, charset, encoding.
        for($i = 0; $i < count($lines); $i++) {
            $lines[$i] = " ".$prefix.$lines[$i]."?=";
        }
        $str = trim(implode($lineEnd, $lines));
        return $str;
    }

    /**
     * Retrieves the first token from a quoted printable string.
     *
     * @param  string $str
     * @return string
     */
    private static function getNextQuotedPrintableToken($str)
    {
        $token = '';
        if(substr($str, 0, 1) == "=") {
            $token = substr($str, 0, 3);
        } else {
            $token = substr($str, 0, 1);
        }
        return $token;
    }

    /**
     * Encode a given string in mail header compatible base64 encoding.
     *
     * @param string $str
     * @param string $charset
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param int $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeBase64Header($str,
        $charset,
        $lineLength = Mime_Const::LINELENGTH,
        $lineEnd = Mime_Const::LINEEND)
    {
        $prefix = '=?' . $charset . '?B?';
        $suffix = '?=';
        $remainingLength = $lineLength - strlen($prefix) - strlen($suffix);

        $encodedValue = self::encodeBase64($str, $remainingLength, $lineEnd);
        $encodedValue = str_replace($lineEnd, $suffix . $lineEnd . ' ' . $prefix, $encodedValue);
        $encodedValue = $prefix . $encodedValue . $suffix;
        return $encodedValue;
    }

    /**
     * Encode a given string in base64 encoding and break lines
     * according to the maximum linelength.
     *
     * @param string $str
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param int $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeBase64($str,
        $lineLength = Mime_Const::LINELENGTH,
        $lineEnd = Mime_Const::LINEEND)
    {
        return rtrim(chunk_split(base64_encode($str), $lineLength, $lineEnd));
    }

    /**
     * Constructor
     *
     * @param null|string $boundary
     * @access public
     * @return void
     */
    public function __construct($boundary = null)
    {
        // This string needs to be somewhat unique
        if ($boundary === null) {
            $this->_boundary = '=_' . md5(microtime(1) . self::$makeUnique++);
        } else {
            $this->_boundary = $boundary;
        }
    }

    /**
     * Encode the given string with the given encoding.
     *
     * @param string $str
     * @param string $encoding
     * @param string $EOL EOL string; defaults to {@link Mime_Const::LINEEND}
     * @return string
     */
    public static function encode($str, $encoding, $EOL = Mime_Const::LINEEND)
    {
        switch ($encoding) {
            case Mime_Const::ENCODING_BASE64:
                return self::encodeBase64($str, Mime_Const::LINELENGTH, $EOL);

            case Mime_Const::ENCODING_QUOTEDPRINTABLE:
                return self::encodeQuotedPrintable($str, Mime_Const::LINELENGTH, $EOL);

            default:
                /**
                 * @todo 7Bit and 8Bit is currently handled the same way.
                 */
                return $str;
        }
    }

    /**
     * Return a MIME boundary
     *
     * @access public
     * @return string
     */
    public function boundary()
    {
        return $this->_boundary;
    }

    /**
     * Return a MIME boundary line
     *
     * @param mixed $EOL Defaults to {@link LINEEND}
     * @access public
     * @return string
     */
    public function boundaryLine($EOL = Mime_Const::LINEEND)
    {
        return $EOL . '--' . $this->_boundary . $EOL;
    }

    /**
     * Return MIME ending
     *
     * @access public
     * @return string
     */
    public function mimeEnd($EOL = Mime_Const::LINEEND)
    {
        return $EOL . '--' . $this->_boundary . '--' . $EOL;
    }
}
