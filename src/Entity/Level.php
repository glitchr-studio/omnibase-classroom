<?php

namespace Base\Classroom\Entity;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread\Taxon;
use Doctrine\ORM\Mapping as ORM;

/**
 * A class level, in a tree of cycles: Cycle 1 › PS, MS, GS; Cycle 2 › CP,
 * CE1, CE2; Cycle 3 › CM1, CM2. An omnibase taxon: label, description and
 * icon come with it. A thread carrying several levels is "multi-niveaux".
 */
#[ORM\Entity]
#[DiscriminatorEntry(value: 'classroom_level')]
class Level extends Taxon
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-stairs'];
    }

    /** The order of the levels, youngest first. */
    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    /** The short name on the pills: "CP", "CE1". */
    #[ORM\Column(length: 12, nullable: true)]
    protected ?string $short = null;

    /** A colour for its pills, when the site paints levels. */
    #[ORM\Column(length: 9, nullable: true)]
    protected ?string $color = null;

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
    public function getShort(): ?string { return $this->short ?: $this->getLabel(); }
    public function setShort(?string $short): self { $this->short = $short ?: null; return $this; }
    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): self { $this->color = $color ?: null; return $this; }

    /** A cycle holds levels; a level holds none. */
    public function isCycle(): bool
    {
        return null === $this->getParent();
    }
}
