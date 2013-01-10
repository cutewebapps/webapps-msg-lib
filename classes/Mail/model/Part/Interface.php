<?php

interface Mail_Part_Interface extends RecursiveIterator
{
    /**
     * Check if part is a multipart message
     *
     * @return bool if part is multipart
     */
    public function isMultipart();


    /**
     * Body of part
     *
     * If part is multipart the raw content of this part with all sub parts is returned
     *
     * @return string body
     * @throws Mail_Exception
     */
    public function getContent();

    /**
     * Return size of part
     *
     * @return int size
     */
    public function getSize();

    /**
     * Get part of multipart message
     *
     * @param  int $num number of part starting with 1 for first part
     * @return Mail_Part wanted part
     * @throws Mail_Exception
     */
    public function getPart($num);

    /**
     * Count parts of a multipart part
     *
     * @return int number of sub-parts
     */
    public function countParts();


    /**
     * Get all headers
     *
     * The returned headers are as saved internally. All names are lowercased. The value is a string or an array
     * if a header with the same name occurs more than once.
     *
     * @return array headers as array(name => value)
     */
    public function getHeaders();

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
    public function getHeader($name, $format = null);

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
     * @throws Exception, Mail_Exception
     */
    public function getHeaderField($name, $wantedPart = 0, $firstName = 0);


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
    public function __get($name);

    /**
     * magic method to get content of part
     *
     * @return string content
     */
    public function __toString();
}