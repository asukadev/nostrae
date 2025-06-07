<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d’utilisateur est déjà utilisé.')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profileImage = null;

    #[Vich\UploadableField(mapping: 'user_profile_image', fileNameProperty: 'profileImage')]
    #[Ignore]
    private ?File $profileImageFile = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'createdBy', orphanRemoval: true)]
    private Collection $events;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $bookings;

    /**
     * @var Collection<int, OrganizerRequest>
     */
    #[ORM\OneToMany(targetEntity: OrganizerRequest::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $organizerRequests;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->organizerRequests = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string { return (string) $this->username; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string { return $this->password; }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getEvents(): Collection { return $this->events; }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setCreatedBy($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getCreatedBy() === $this) {
                $event->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getBookings(): Collection { return $this->bookings; }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getUser() === $this) {
                $booking->setUser(null);
            }
        }

        return $this;
    }

    public function getProfileImage(): ?string { return $this->profileImage; }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;
        return $this;
    }

    public function setProfileImageFile(?File $profileImageFile = null): void
    {
        $this->profileImageFile = $profileImageFile;
        if ($profileImageFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getProfileImageFile(): ?File { return $this->profileImageFile; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, OrganizerRequest>
     */
    public function getOrganizerRequests(): Collection
    {
        return $this->organizerRequests;
    }

    public function addOrganizerRequest(OrganizerRequest $organizerRequest): static
    {
        if (!$this->organizerRequests->contains($organizerRequest)) {
            $this->organizerRequests->add($organizerRequest);
            $organizerRequest->setUser($this);
        }

        return $this;
    }

    public function removeOrganizerRequest(OrganizerRequest $organizerRequest): static
    {
        if ($this->organizerRequests->removeElement($organizerRequest)) {
            // set the owning side to null (unless already changed)
            if ($organizerRequest->getUser() === $this) {
                $organizerRequest->setUser(null);
            }
        }

        return $this;
    }
}
