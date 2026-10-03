<?php

namespace Base\Classroom\Entity;

use Base\Classroom\Repository\SessionRepository;
use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread;
use Base\Service\Model\LinkableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * One session of a sequence - a thread whose parent is the sequence: its
 * title, its place, its duration, its aim (the headline) and its course
 * (the text, EditorJS). Read on the sequence's page, folded one under the
 * other; published with it.
 */
#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\Table(name: 'classroom_session')]
#[DiscriminatorEntry(value: 'classroom_session')]
class Session extends Thread implements LinkableInterface
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-list-ol'];
    }

    public function __toLink(array $routeParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        $sequence = $this->getSequence();

        return $sequence ? $this->getRouter()->generate('classroom_sequence', array_merge($routeParameters, ['slug' => $sequence->getSlug(), '_fragment' => 'seance-'.$this->getPosition()]), $referenceType) : null;
    }

    #[ORM\Column(type: 'integer')]
    protected int $position = 1;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $durationMinutes = null;

    /** @var list<string> what the pupils need for this one */
    #[ORM\Column(type: 'json')]
    protected array $materials = [];

    public function __toString(): string
    {
        return sprintf('%d. %s', $this->position, $this->getTitle() ?? '');
    }

    public function getSequence(): ?Sequence
    {
        $parent = $this->getParent();

        return $parent instanceof Sequence ? $parent : null;
    }

    public function setSequence(?Sequence $sequence): self { $this->setParent($sequence); return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = max(1, $position); return $this; }

    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function setDurationMinutes(?int $minutes): self { $this->durationMinutes = $minutes ?: null; return $this; }

    /** @return list<string> */
    public function getMaterials(): array { return $this->materials; }
    public function getMaterialsText(): string { return implode("\n", $this->materials); }
    public function setMaterialsText(?string $text): self { return $this->setMaterials($text); }
    public function setMaterials(array|string|null $materials): self
    {
        $lines = \is_array($materials) ? $materials : preg_split('/\R/', (string) $materials);
        $this->materials = array_values(array_filter(array_map(fn ($l) => trim((string) $l), $lines), fn ($l) => '' !== $l));

        return $this;
    }
}
