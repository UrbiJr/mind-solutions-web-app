<?php

namespace App\Entity;

class ViagogoUser
{
    private $username;
    private $password;
    private $wsu2Cookie;
    private $rvtCookie;

    function __construct($username, $password, $wsu2Cookie, $rvtCookie)
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setWsu2Cookie($wsu2Cookie);
        $this->setRvtCookie($rvtCookie);
    }

    /**
     * Get the value of username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the value of username
     *
     * @return  self
     */
    public function setUsername($username)
    {
        $this->username = isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */
    public function setPassword($password)
    {
        $this->password = isset($password) ? htmlspecialchars($password, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of wsu2Cookie
     */
    public function getWsu2Cookie()
    {
        return $this->wsu2Cookie;
    }

    /**
     * Set the value of wsu2Cookie
     *
     * @return  self
     */
    public function setWsu2Cookie($wsu2Cookie)
    {
        $this->wsu2Cookie = isset($wsu2Cookie) ? htmlspecialchars($wsu2Cookie, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of rvtCookie
     */
    public function getRvtCookie()
    {
        return $this->rvtCookie;
    }

    /**
     * Set the value of rvtCookie
     *
     * @return  self
     */
    public function setRvtCookie($rvtCookie)
    {
        $this->rvtCookie = isset($rvtCookie) ? htmlspecialchars($rvtCookie, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }
}
