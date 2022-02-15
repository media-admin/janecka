<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

class AuthInfo
{
    /**
     * @var string
     */
    protected $accessToken;
    /**
     * @var int
     */
    protected $accessTokenDuration;
    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * AuthInfo constructor.
     *
     * @param string $accessToken
     * @param int $accessTokenDuration
     * @param string $refreshToken
     */
    public function __construct($accessToken, $accessTokenDuration, $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->accessTokenDuration = $accessTokenDuration;
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return int
     */
    public function getAccessTokenDuration()
    {
        return $this->accessTokenDuration;
    }

    /**
     * @param int $accessTokenDuration
     */
    public function setAccessTokenDuration($accessTokenDuration)
    {
        $this->accessTokenDuration = $accessTokenDuration;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

}