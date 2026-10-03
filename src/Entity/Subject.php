<?php

namespace Base\Classroom\Entity;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread\Taxon;
use Doctrine\ORM\Mapping as ORM;

/**
 * A discipline and its domains: Français › Lecture, Écriture, Grammaire…;
 * Allemand › Vocabulaire, Culture…; Mathématiques, Questionner le monde,
 * EMC, Arts, EPS. An omnibase taxon.
 */
#[ORM\Entity]
#[DiscriminatorEntry(value: 'classroom_subject')]
class Subject extends Taxon
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-shapes'];
    }

    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    #[ORM\Column(length: 9, nullable: true)]
    protected ?string $color = null;

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
    public function getColor(): ?string { return $this->color ?? $this->getParent()?->getColor(); }
    public function setColor(?string $color): self { $this->color = $color ?: null; return $this; }
}
