<?php

namespace App\Entity;

use App\Repository\EventTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
class EventType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'eventType')]
    private Collection $events;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'type')]
    private Collection $relatedEvents;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->relatedEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setEventType($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getEventType() === $this) {
                $event->setEventType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getRelatedEvents(): Collection
    {
        return $this->relatedEvents;
    }

    public function addRelatedEvent(Event $relatedEvent): static
    {
        if (!$this->relatedEvents->contains($relatedEvent)) {
            $this->relatedEvents->add($relatedEvent);
            $relatedEvent->setType($this);
        }

        return $this;
    }

    public function removeRelatedEvent(Event $relatedEvent): static
    {
        if ($this->relatedEvents->removeElement($relatedEvent)) {
            // set the owning side to null (unless already changed)
            if ($relatedEvent->getType() === $this) {
                $relatedEvent->setType(null);
            }
        }

        return $this;
    }
}
