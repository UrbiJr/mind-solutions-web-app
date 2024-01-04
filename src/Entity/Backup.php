<?php

namespace App\Entity;

use App\Repository\BackupRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use App\Entity\User;

/**
 * Release
 */
#[ORM\Table(name: 'Backups')]
#[ORM\Index(name: 'fk_captcha_provider_id', columns: ['captcha_provider_id'])]
#[ORM\Index(name: 'user_fk', columns: ['user'])]
#[ORM\Entity(repositoryClass: BackupRepository::class)]
class Backup
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'User')]
    private $user;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'timestamp', type: 'datetime', nullable: true)]
    private $timestamp;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'first_name', type: 'string', length: 256, nullable: true)]
    private $firstName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'last_name', type: 'string', length: 256, nullable: true)]
    private $lastName;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'connections', type: 'json', nullable: true)]
    private $connections;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'about', type: 'string', length: 160, nullable: true)]
    private $about;

    /**
     * @var string
     */
    #[ORM\Column(name: 'currency', type: 'string', length: 10, nullable: false, options: ['default' => 'EUR'])]
    private $currency = 'EUR';

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'captcha_provider_api_key', type: 'string', length: 256, nullable: true)]
    private $captchaProviderApiKey;

    /**
     * @var \CaptchaProvider
     */
    #[ORM\JoinColumn(name: 'captcha_provider_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'CaptchaProvider')]
    private $captchaProvider;


    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of user
     */ 
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */ 
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of timestamp
     */ 
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp
     *
     * @return  self
     */ 
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of firstName
     */ 
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the value of firstName
     *
     * @return  self
     */ 
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the value of lastName
     */ 
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set the value of lastName
     *
     * @return  self
     */ 
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get the value of connections
     */ 
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Set the value of connections
     *
     * @return  self
     */ 
    public function setConnections($connections)
    {
        $this->connections = $connections;

        return $this;
    }

    /**
     * Get the value of about
     */ 
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * Set the value of about
     *
     * @return  self
     */ 
    public function setAbout($about)
    {
        $this->about = $about;

        return $this;
    }

    /**
     * Get the value of currency
     */ 
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @return  self
     */ 
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of captchaProviderApiKey
     */ 
    public function getCaptchaProviderApiKey()
    {
        return $this->captchaProviderApiKey;
    }

    /**
     * Set the value of captchaProviderApiKey
     *
     * @return  self
     */ 
    public function setCaptchaProviderApiKey($captchaProviderApiKey)
    {
        $this->captchaProviderApiKey = $captchaProviderApiKey;

        return $this;
    }

    /**
     * Get the value of captchaProvider
     */ 
    public function getCaptchaProvider()
    {
        return $this->captchaProvider;
    }

    /**
     * Set the value of captchaProvider
     *
     * @return  self
     */ 
    public function setCaptchaProvider($captchaProvider)
    {
        $this->captchaProvider = $captchaProvider;

        return $this;
    }
}

    