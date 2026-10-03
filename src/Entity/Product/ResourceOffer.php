<?php

namespace Base\Classroom\Entity\Product;

use Base\Classroom\Entity\Resource;
use Base\Database\Attribute\DiscriminatorEntry;
use Base\Marketplace\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

/**
 * A resource for sale: a marketplace product whose delivery is a download.
 * Paid, the order's EventSubscriber\OrderPaidSubscriber writes the buyer an
 * Entitlement to the resource. Nothing ships: checkout asks for no address.
 */
#[ORM\Entity]
#[DiscriminatorEntry(value: 'classroom_resource_offer')]
class ResourceOffer extends Product
{
    #[ORM\ManyToOne(targetEntity: Resource::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Resource $resource = null;

    public function isShippable(): bool
    {
        return false;
    }

    /** One of each: a download bought twice is still one download. */
    public function getMaxQuantity(): ?int
    {
        return 1;
    }

    public function getResource(): ?Resource { return $this->resource; }
    public function setResource(?Resource $resource): self { $this->resource = $resource; return $this; }
}
