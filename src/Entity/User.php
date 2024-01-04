<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User
 */
#[ORM\Table(name: 'Users')]
#[ORM\Index(name: 'fk_captcha_provider_id', columns: ['captcha_provider_id'])]
#[ORM\UniqueConstraint(name: 'username', columns: ['username'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Serializable
{

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_verified', type: 'boolean', length: 256, nullable: false, options: ['default' => false])]
    private $isVerified = false;

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
     * @var string
     */
    #[ORM\Column(name: 'username', type: 'string', length: 256, nullable: false)]
    private $username;

    /**
     * @var string
     */
    #[ORM\Column(name: 'password', type: 'string', length: 256, nullable: false)]
    private $password;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'secret_code', type: 'string', length: 256, nullable: true)]
    private $secretCode;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'license_key', type: 'string', length: 64, nullable: true)]
    private $licenseKey;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private $createdAt;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'discord_id', type: 'string', length: 20, nullable: true)]
    private $discordId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'discord_username', type: 'string', length: 32, nullable: true)]
    private $discordUsername;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'whop_manage_url', type: 'string', length: 256, nullable: true)]
    private $whopManageUrl;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'image_url', type: 'string', length: 256, nullable: true)]
    private $imageUrl;

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
     * @var array
     */
    #[ORM\Column(name: 'roles', type: 'json')]
    private $roles = [];

    public function __construct()
    {
        $this->connections = array();
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSecretCode(): ?string
    {
        return $this->secretCode;
    }

    public function setSecretCode(?string $secretCode): static
    {
        $this->secretCode = $secretCode;

        return $this;
    }

    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }

    public function setLicenseKey(?string $licenseKey): static
    {
        if (!is_string($licenseKey)) {
            throw new \InvalidArgumentException('License key must be a string.');
        }

        $this->licenseKey = $licenseKey;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(?string $discordId): static
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    public function setDiscordUsername(?string $discordUsername): static
    {
        $this->discordUsername = $discordUsername;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getConnections(): ?array
    {
        return $this->connections;
    }

    public function setConnections(?array $connections): static
    {
        $this->connections = $connections;

        return $this;
    }

    public function getConnection(string $key): ?array
    {
        if (isset($this->connections[$key])) {
            return $this->connections[$key];
        }

        return null;
    }

    public function setConnection(string $key, array $connection): static
    {
        $this->connections[$key] = $connection;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCaptchaProviderApiKey(): ?string
    {
        return $this->captchaProviderApiKey;
    }

    public function setCaptchaProviderApiKey(?string $captchaProviderApiKey): static
    {
        $this->captchaProviderApiKey = $captchaProviderApiKey;

        return $this;
    }

    public function getCaptchaProvider(): ?CaptchaProvider
    {
        return $this->captchaProvider;
    }

    public function setCaptchaProvider(?CaptchaProvider $captchaProvider): static
    {
        $this->captchaProvider = $captchaProvider;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * Adds a role to the user.
     *
     * @param string $role
     * @return $this
     */
    public function addRole($role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * Removes a role from the user.
     *
     * @param string $role
     * @return $this
     */
    public function removeRole(string $role): self
    {
        $this->roles = array_diff($this->roles, [$role]);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Ensure ROLE_USER is present
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getSalt(): ?string
    {
        // bcrypt and Argon2i don't require a separate salt.
        // If you're using a different algorithm, adjust this method accordingly.
        return null;
    }

    public function eraseCredentials(): void
    {
        // This can be used to remove any plain-text password after authentication.
        // Currently, we don't need to do anything here.
    }

    /**
     * Serializes the user.
     * 
     * @return string Serialized representation of the user.
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            $this->licenseKey
            // Tipically, only id, username & password are needed for authentication
        ]);
    }

    /**
     * Unserializes the user.
     * 
     * @param string $serialized Serialized representation of the user.
     */
    public function unserialize($serialized): void
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $this->licenseKey
            // Tipically, only id, username & password are needed for authentication
        ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * Get the value of isVerified
     */
    public function getIsVerified()
    {
        return $this->isVerified;
    }

    /**
     * Set the value of isVerified
     *
     * @return  self
     */
    public function setIsVerified(bool $isVerified)
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    /**
     * Get the value of whopManageUrl
     */
    public function getWhopManageUrl()
    {
        return $this->whopManageUrl;
    }

    /**
     * Set the value of whopManageUrl
     *
     * @return  self
     */
    public function setWhopManageUrl($whopManageUrl)
    {
        $this->whopManageUrl = $whopManageUrl;

        return $this;
    }


    /**
     * Get the value of about
     */ 
    public function getAbout()
    {
        return $this->about;
    }

    public function setAbout(?string $about): static
    {
        $this->about = $about;

        return $this;
    }
}
