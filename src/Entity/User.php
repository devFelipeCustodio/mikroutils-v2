<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $allowedHostIds = [];

    /**
     * @var Collection<int, ClientSearch>
     */
    #[ORM\OneToMany(targetEntity: ClientSearch::class, mappedBy: 'user')]
    private Collection $clientSearches;

    /**
     * @var Collection<int, LogSearch>
     */
    #[ORM\OneToMany(targetEntity: LogSearch::class, mappedBy: 'user')]
    private Collection $logSearches;

    /**
     * @var Collection<int, ClientExport>
     */
    #[ORM\OneToMany(targetEntity: ClientExport::class, mappedBy: 'user')]
    private Collection $clientExports;

    /**
     * @var Collection<int, UserSession>
     */
    #[ORM\OneToMany(targetEntity: UserSession::class, mappedBy: 'user_id')]
    private Collection $userSessions;

    public function __construct()
    {
        $this->clientSearches = new ArrayCollection();
        $this->logSearches = new ArrayCollection();
        $this->clientExports = new ArrayCollection();
        $this->userSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getAllowedHostIds(): array
    {
        return $this->allowedHostIds;
    }

    /**
     * @param array<int,mixed> $allowedHostIds
     */
    public function setAllowedHostIds(array $allowedHostIds): User
    {
        $this->allowedHostIds = $allowedHostIds;

        return $this;
    }

    /**
     * @return Collection<int, ClientSearch>
     */
    public function getClientSearches(): Collection
    {
        return $this->clientSearches;
    }

    public function addClientSearch(ClientSearch $clientSearch): static
    {
        if (!$this->clientSearches->contains($clientSearch)) {
            $this->clientSearches->add($clientSearch);
            $clientSearch->setUser($this);
        }

        return $this;
    }

    public function removeClientSearch(ClientSearch $clientSearch): static
    {
        if ($this->clientSearches->removeElement($clientSearch)) {
            // set the owning side to null (unless already changed)
            if ($clientSearch->getUser() === $this) {
                $clientSearch->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogSearch>
     */
    public function getLogSearches(): Collection
    {
        return $this->logSearches;
    }

    public function addLogSearch(LogSearch $logSearch): static
    {
        if (!$this->logSearches->contains($logSearch)) {
            $this->logSearches->add($logSearch);
            $logSearch->setUser($this);
        }

        return $this;
    }

    public function removeLogSearch(LogSearch $logSearch): static
    {
        if ($this->logSearches->removeElement($logSearch)) {
            // set the owning side to null (unless already changed)
            if ($logSearch->getUser() === $this) {
                $logSearch->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClientExport>
     */
    public function getClientExports(): Collection
    {
        return $this->clientExports;
    }

    public function addClientExport(ClientExport $clientExport): static
    {
        if (!$this->clientExports->contains($clientExport)) {
            $this->clientExports->add($clientExport);
            $clientExport->setUser($this);
        }

        return $this;
    }

    public function removeClientExport(ClientExport $clientExport): static
    {
        if ($this->clientExports->removeElement($clientExport)) {
            // set the owning side to null (unless already changed)
            if ($clientExport->getUser() === $this) {
                $clientExport->setUser(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /**
     * @return Collection<int, UserSession>
     */
    public function getUserSessions(): Collection
    {
        return $this->userSessions;
    }

    public function addUserSession(UserSession $userSession): static
    {
        if (!$this->userSessions->contains($userSession)) {
            $this->userSessions->add($userSession);
            $userSession->setUser($this);
        }

        return $this;
    }

    public function removeUserSession(UserSession $userSession): static
    {
        if ($this->userSessions->removeElement($userSession)) {
            // set the owning side to null (unless already changed)
            if ($userSession->getUser() === $this) {
                $userSession->setUser(null);
            }
        }

        return $this;
    }
}
