<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Utility;

/**
 * Class HttpResponse
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Utility
 */
class HttpResponse
{
    const CLASS_NAME = __CLASS__;

    /**
     * HTTP status code.
     *
     * @var int
     */
    private $status;
    /**
     * Response body.
     *
     * @var string
     */
    private $body;
    /**
     * Response headers list where key is header name and value is header value.
     *
     * @var array
     */
    private $headers;

    /**
     * HttpResponse constructor.
     *
     * @param int $status Response HTTP status code.
     * @param array|null $headers Response headers list.
     * @param string $body Response body.
     */
    public function __construct($status, $headers, $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Gets response HTTP status code
     *
     * @return int
     *   HTTP response status code. For example 200 for "200 OK".
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Return response body.
     *
     * @return string
     *   Response payload without any decoding.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Gets response headers.
     *
     * [
     *   "content-type" => "application/json; charset=utf-8",
     *   "connection" => "keep-alive"
     * ]
     *
     * @return array
     *   Response headers list where key is header name and value is header value.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if request was successful or not.
     *
     * @return bool
     *   On success returns true, otherwise false.
     */
    public function isSuccessful()
    {
        return $this->getStatus() >= 200 && $this->getStatus() < 300;
    }
}
