<?php

namespace Base\Classroom\Entity;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread\Taxon;
use Doctrine\ORM\Mapping as ORM;

/**
 * "Pour la classe": what a thing is for rather than what it teaches -
 * rituals, displays, autonomy, games, methods, organisation. An omnibase taxon.
 */
#[ORM\Entity]
#[DiscriminatorEntry(value: 'classroom_purpose')]
class Purpose extends Taxon
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-thumbtack'];
    }

    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
