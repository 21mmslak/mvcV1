<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var Collection<int, Scoreboard>
     */
    #[ORM\OneToMany(targetEntity: Scoreboard::class, mappedBy: 'user')]
    private Collection $scoreboards;

    public function __construct()
    {
        $this->scoreboards = new ArrayCollection();
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

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        return $roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, Scoreboard>
     */
    public function getScoreboards(): Collection
    {
        return $this->scoreboards;
    }

    public function addScoreboard(Scoreboard $scoreboard): static
    {
        if (!$this->scoreboards->contains($scoreboard)) {
            $this->scoreboards->add($scoreboard);
            $scoreboard->setUser($this);
        }

        return $this;
    }

    public function removeScoreboard(Scoreboard $scoreboard): static
    {
        if ($this->scoreboards->removeElement($scoreboard)) {
            // set the owning side to null (unless already changed)
            if ($scoreboard->getUser() === $this) {
                $scoreboard->setUser(null);
            }
        }

        return $this;
    }
}