<?php
namespace Router;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class PsrRequest implements RequestInterface
{
    /**
     * @var Body
     */
    private $body;

    /**
     * @var string
     */
    private $method = '';

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $headersKeys = [];

    /**
     * @var array
     */
    private $serverParameters = [];

    /**
     * @var array
     */
    private $cookieParameters = [];

    /**
     * @var array
     */
    private $queryParameters = [];

    /**
     * @var array
     */
    private $bodyParameters = [];

    /**
     * @var array
     */
    private $fileParameters = [];

    /**
     * @var string
     */
    private $protocolVersion = '';

    /**
     * @var array
     */
    private $attributes = [];

    public function __construct()
    {
        $this->body = new Body();
    }

    /**
     * Gets the HTTP protocol version as a string.
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $protocolVersion
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param Body $body
     * @return $this
     */
    public function setBody(Body $body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Gets all message headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        $this->headersKeys = [];
    }

    /**
     * @return array
     */
    private function getHeaderLowercaseKeyMapping()
    {
        if (count($this->headers) && !count($this->headersKeys)) {
            foreach ($this->headers as $key => $values) {
                $this->headersKeys[strtolower($key)] = $key;
            }
        }
        return $this->headersKeys;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header
     * @return bool
     */
    public function hasHeader($header)
    {
        $header = strtolower($header);
        $headersKeys = $this->getHeaderLowercaseKeyMapping();
        return isset($headersKeys[$header]);
    }

    /**
     * Retrieve a header by the given case-insensitive name, as a string.
     *
     * @param string $header
     * @return string
     */
    public function getHeader($header)
    {
        if ($values = $this->getHeaderAsArray($header)) {
            return implode(", ", $values);
        }
        return null;
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $header Case-insensitive header name.
     * @return string[]
     */
    public function getHeaderAsArray($header)
    {
        $header = strtolower($header);
        $headersKeys = $this->getHeaderLowercaseKeyMapping();
        if (isset($headersKeys[$header])) {
            $header = $headersKeys[$header];
            return $this->headers[$header];
        }
        return null;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Retrieves the request URL.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Retrieve server parameters.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParameters;
    }

    /**
     * @param array $serverParameters
     */
    public function setServerParams(array $serverParameters)
    {
        $this->serverParameters = $serverParameters;
    }

    /**
     * Retrieve cookies.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParameters;
    }

    /**
     * @param array $cookieParameters
     */
    public function setCookieParams(array $cookieParameters)
    {
        $this->cookieParameters = $cookieParameters;
    }

    /**
     * Retrieve query string arguments.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParameters;
    }

    /**
     * @param array $queryParameters
     */
    public function setQueryParams(array $queryParameters)
    {
        $this->queryParameters = $queryParameters;
    }

    /**
     * Retrieve the upload file metadata.
     *
     * @return array Upload file(s) metadata, if any.
     */
    public function getFileParams()
    {
        return $this->fileParameters;
    }

    /**
     * @param array $fileParameters
     */
    public function setFileParams(array $fileParameters)
    {
        $this->fileParameters = $fileParameters;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * @return array
     */
    public function getBodyParams()
    {
        return $this->bodyParameters;
    }

    /**
     * @param array $bodyParameters
     */
    public function setBodyParams(array $bodyParameters)
    {
        $this->bodyParameters = $bodyParameters;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * @param string $attribute
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($attribute, $default = null)
    {
        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        }
        return $default;
    }

    /**
     * Set attributes derived from the request.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Set a single derived request attribute.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    public function withProtocolVersion($version)
    {

    }

    public function getHeaderLine($name)
    {

    }

    public function withHeader($name, $value)
    {

    }

    public function withAddedHeader($name, $value)
    {

    }

    public function withoutHeader($name)
    {

    }

    public function withBody(StreamInterface $body)
    {

    }

    public function getRequestTarget()
    {
    }

    public function withRequestTarget($requestTarget)
    {

    }

    public function withMethod($method)
    {

    }

    public function getUri()
    {

    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {

    }
}
