<?php

namespace Base\Classroom\Entity;

use App\Entity\User;
use Base\Database\Attribute\Timestamp;
use Base\Traits\BaseTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * The right to download a paid resource: written when the order holding its
 * offer is paid (EventSubscriber\OrderPaidSubscriber), one per resource and
 * buyer, remembering the order. Never expires; the file may be fetched
 * again after a lost download.
 */
#[ORM\Entity(repositoryClass: \Base\Classroom\Repository\EntitlementRepository::class)]
#[ORM\Table(name: 'classroom_entitlement')]
#[ORM\UniqueConstraint(name: 'classroom_entitlement_unique', columns: ['user_id', 'resource_id'])]
class Entitlement
{
    use BaseTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Resource::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Resource $resource = null;

    /** The order's reference, for the receipt. */
    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $orderReference = null;

    #[ORM\Column(type: 'integer')]
    protected int $downloads = 0;

    #[ORM\Column(type: 'datetime')]
    #[Timestamp(on: 'create')]
    protected ?\DateTimeInterface $createdAt = null;

    public function __construct(?User $user = null, ?Resource $resource = null, ?string $orderReference = null)
    {
        $this->user = $user;
        $this->resource = $resource;
        $this->orderReference = $orderReference;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function getResource(): ?Resource { return $this->resource; }
    public function getOrderReference(): ?string { return $this->orderReference; }
    public function getDownloads(): int { return $this->downloads; }
    public function countDownload(): self { ++$this->downloads; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
