<?php

namespace Base\Classroom\Entity;

use Base\Classroom\Enum\Period;
use Base\Classroom\Repository\SequenceRepository;
use Base\Database\Attribute\DiscriminatorEntry;
use Base\Database\Attribute\Uploader;
use Base\Entity\Thread;
use Base\Service\Model\LinkableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A learning sequence: what it teaches (its subjects), to whom (its levels),
 * when in the year (its period), what it aims at (objectives, competences
 * of the programmes), what it needs (material), and its sessions - threads
 * of their own, children of this one, in order. Its text is the
 * presentation; its resources are the files to download.
 */
#[ORM\Entity(repositoryClass: SequenceRepository::class)]
#[ORM\Table(name: 'classroom_sequence')]
#[DiscriminatorEntry(value: 'classroom_sequence')]
class Sequence extends Thread implements LinkableInterface
{
    use ClassifiedTrait;

    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-book-open'];
    }

    public function __toLink(array $routeParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        return $this->getRouter()->generate('classroom_sequence', array_merge($routeParameters, ['slug' => $this->getSlug()]), $referenceType);
    }

    /**
     * A string, not a native enum (see Resource::$visibilityValue), and named
     * apart from its getter: Thread's magic __get answers Twig's `sequence.period`
     * with the raw property before getPeriod() is tried.
     */
    #[ORM\Column(name: 'period', type: 'string', length: 2, nullable: true)]
    protected ?string $periodValue = null;

    /** The whole sequence, in minutes; null: the sum of its sessions. */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $durationMinutes = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    protected array $objectives = [];

    /** @var list<string> the competences of the programmes it works on */
    #[ORM\Column(type: 'json')]
    protected array $competences = [];

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $materials = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '8MB', mime_types: ['image/*'])]
    protected $cover = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $featured = false;

    #[ORM\ManyToMany(targetEntity: Resource::class, inversedBy: 'sequences')]
    #[ORM\JoinTable(name: 'classroom_sequence_resource')]
    protected Collection $resources;

    public function __construct(?\Base\Entity\User $owner = null, ?Thread $parent = null, ?string $title = null, ?string $slug = null)
    {
        parent::__construct($owner, $parent, $title, $slug);
        $this->resources = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? '';
    }

    public function getPeriod(): ?Period { return $this->periodValue ? Period::tryFrom($this->periodValue) : null; }
    public function setPeriod(Period|string|null $period): self { $this->periodValue = $period instanceof Period ? $period->value : (Period::tryFrom((string) $period)?->value); return $this; }
    public function getPeriodValue(): ?string { return $this->periodValue; }
    public function setPeriodValue(?string $period): self { return $this->setPeriod($period); }

    public function getDurationMinutes(): ?int
    {
        if (null !== $this->durationMinutes) {
            return $this->durationMinutes;
        }
        $sum = array_sum(array_map(fn (Session $session) => $session->getDurationMinutes() ?? 0, $this->getSessions()));

        return $sum > 0 ? $sum : null;
    }
    public function setDurationMinutes(?int $minutes): self { $this->durationMinutes = $minutes ?: null; return $this; }

    /** @return list<string> */
    public function getObjectives(): array { return $this->objectives; }
    /** @param list<string>|string|null $objectives one per line when a string */
    public function setObjectives(array|string|null $objectives): self { $this->objectives = self::lines($objectives); return $this; }

    /** @return list<string> */
    public function getCompetences(): array { return $this->competences; }
    public function setCompetences(array|string|null $competences): self { $this->competences = self::lines($competences); return $this; }

    /** The objectives and competences one per line, for a textarea. */
    public function getObjectivesText(): string { return implode("\n", $this->objectives); }
    public function setObjectivesText(?string $text): self { return $this->setObjectives($text); }
    public function getCompetencesText(): string { return implode("\n", $this->competences); }
    public function setCompetencesText(?string $text): self { return $this->setCompetences($text); }

    public function getMaterials(): ?string { return $this->materials; }
    public function setMaterials(?string $materials): self { $this->materials = $materials ?: null; return $this; }

    public function getCover(): ?string { return Uploader::getPublic($this, 'cover'); }
    public function getCoverFile(): ?File { return Uploader::get($this, 'cover'); }
    public function setCover($cover): self { $this->cover = $cover; return $this; }

    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): self { $this->featured = $featured; return $this; }

    /** @return list<Session> in order */
    public function getSessions(): array
    {
        $sessions = array_values(array_filter($this->getChildren()->toArray(), fn ($child) => $child instanceof Session));
        usort($sessions, fn (Session $a, Session $b) => [$a->getPosition(), $a->getId()] <=> [$b->getPosition(), $b->getId()]);

        return $sessions;
    }

    /** @return Collection<int, Resource> */
    public function getResources(): Collection { return $this->resources; }
    public function addResource(Resource $resource): self { if (!$this->resources->contains($resource)) { $this->resources->add($resource); } return $this; }
    public function removeResource(Resource $resource): self { $this->resources->removeElement($resource); return $this; }

    /** @return list<string> */
    private static function lines(array|string|null $value): array
    {
        $lines = \is_array($value) ? $value : preg_split('/\R/', (string) $value);

        return array_values(array_filter(array_map(fn ($line) => trim((string) $line), $lines), fn ($line) => '' !== $line));
    }
}
