<?php

namespace App\Entity;

class ViagogoUser
{
    private $username;
    private $password;

    function __construct($username, $password)
    {
        $this->username = isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : null;
        $this->password = isset($password) ? htmlspecialchars($password, ENT_QUOTES, 'UTF-8') : null;
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
}
