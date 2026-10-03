<?php

namespace Base\Classroom\Entity;

use App\Entity\User;
use Base\Classroom\Enum\Visibility;
use Base\Classroom\Repository\ResourceRepository;
use Base\Database\Attribute\Slugify;
use Base\Database\Attribute\Timestamp;
use Base\Database\Attribute\Uploader;
use Base\Traits\BaseTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A file to download - a PDF of cards, a game's spreadsheet, a set of
 * flashcards zipped -, with a title, a description, a preview picture, its
 * levels and subjects (taxa, like the threads), and who it is for: anyone,
 * the members, or whoever bought it (a Product\ResourceOffer sells it). The
 * file stays out of public/ (the local.classroom storage) and is served
 * through signed links (glitchr/omnibase's Base\Service\DownloadLinks); each download is counted.
 */
#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'classroom_resource')]
class Resource
{
    use BaseTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Slugify(reference: 'title')]
    protected ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    /** The file itself, on the private storage. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(storage: 'local.classroom', max_size: '64MB', mime_types: ['application/pdf', 'application/zip', 'application/x-zip-compressed', 'application/vnd.openxmlformats-officedocument.*', 'application/vnd.ms-excel', 'application/vnd.oasis.opendocument.*', 'application/msword', 'text/csv', 'text/plain', 'image/*', 'audio/*', 'video/*'])]
    protected $file = null;

    /** What it is called when saved: "kangourou-ce1.xlsx". */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $filename = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $mimeType = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    protected ?int $size = null;

    /** A picture of it, on the public storage: the first page, a photo. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '8MB', mime_types: ['image/*'])]
    protected $preview = null;

    /**
     * A string, not a native enum, named apart from its getter: omnibase's Uploader rebuilds the entity's
     * previous state from raw column values on update (AbstractAttribute::getOldEntity),
     * which cannot assign a string to an enum-typed property.
     */
    #[ORM\Column(name: 'visibility', type: 'string', length: 16)]
    protected string $visibilityValue = 'free';

    #[ORM\Column(type: 'boolean')]
    protected bool $published = true;

    #[ORM\ManyToMany(targetEntity: \Base\Entity\Thread\Taxon::class)]
    #[ORM\JoinTable(name: 'classroom_resource_taxon')]
    protected Collection $taxa;

    #[ORM\ManyToMany(targetEntity: Sequence::class, mappedBy: 'resources')]
    protected Collection $sequences;

    #[ORM\Column(type: 'integer')]
    protected int $downloads = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\Column(type: 'datetime')]
    #[Timestamp(on: 'create')]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Timestamp(on: ['create', 'update'])]
    protected ?\DateTimeInterface $updatedAt = null;

    public function __construct(?User $owner = null, ?string $title = null)
    {
        $this->owner = $owner;
        $this->title = $title;
        $this->taxa = new ArrayCollection();
        $this->sequences = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): self { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description ?: null; return $this; }

    public function getFile(): ?File
    {
        $file = Uploader::get($this, 'file');

        return \is_array($file) ? ($file[0] ?? null) : $file;
    }
    public function setFile($file): self { $this->file = $file; return $this; }
    public function hasFile(): bool { return !empty($this->file); }

    public function getFilename(): ?string
    {
        return $this->filename ?: ($this->slug ? $this->slug.'.'.($this->getExtension() ?? 'bin') : null);
    }
    public function setFilename(?string $filename): self { $this->filename = $filename ?: null; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): self { $this->mimeType = $mimeType; return $this; }

    public function getSize(): ?int { return $this->size; }
    public function setSize(?int $size): self { $this->size = $size; return $this; }

    /** "pdf", "xlsx", "csv", "zip"… from the saved name, else from the type. */
    public function getExtension(): ?string
    {
        if ($this->filename && str_contains($this->filename, '.')) {
            return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
        }

        return match ($this->mimeType) {
            'application/pdf' => 'pdf',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
            'text/csv' => 'csv',
            default => null,
        };
    }

    public function isSpreadsheet(): bool
    {
        return \in_array($this->getExtension(), ['xlsx', 'xls', 'xlsm', 'csv', 'ods'], true);
    }

    public function getPreview(): ?string { return Uploader::getPublic($this, 'preview'); }
    public function getPreviewFile(): ?File { return Uploader::get($this, 'preview'); }
    public function setPreview($preview): self { $this->preview = $preview; return $this; }

    public function getVisibility(): Visibility { return Visibility::tryFrom($this->visibilityValue) ?? Visibility::FREE; }
    public function setVisibility(Visibility|string $visibility): self { $this->visibilityValue = $visibility instanceof Visibility ? $visibility->value : (Visibility::tryFrom($visibility) ?? Visibility::FREE)->value; return $this; }
    /** The raw value, for the admin's select. */
    public function getVisibilityValue(): string { return $this->visibilityValue; }
    public function setVisibilityValue(?string $visibility): self { return $this->setVisibility((string) $visibility); }
    public function isFree(): bool { return Visibility::FREE === $this->getVisibility(); }
    public function isPaid(): bool { return Visibility::PAID === $this->getVisibility(); }

    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $published): self { $this->published = $published; return $this; }

    /** @return Collection<int, \Base\Entity\Thread\Taxon> */
    public function getTaxa(): Collection { return $this->taxa; }
    public function addTaxon(\Base\Entity\Thread\Taxon $taxon): self { if (!$this->taxa->contains($taxon)) { $this->taxa->add($taxon); } return $this; }
    public function removeTaxon(\Base\Entity\Thread\Taxon $taxon): self { $this->taxa->removeElement($taxon); return $this; }

    /** @return list<Level> */
    public function getLevels(): array
    {
        $levels = array_values(array_filter($this->taxa->toArray(), fn ($t) => $t instanceof Level));
        usort($levels, fn (Level $a, Level $b) => $a->getPosition() <=> $b->getPosition());

        return $levels;
    }
    /** @return list<Subject> */
    public function getSubjects(): array { return array_values(array_filter($this->taxa->toArray(), fn ($t) => $t instanceof Subject)); }
    public function getLevelsLabel(): string { return implode(' · ', array_map(fn (Level $l) => $l->getShort(), $this->getLevels())); }

    /** @return Collection<int, Sequence> */
    public function getSequences(): Collection { return $this->sequences; }

    public function getDownloads(): int { return $this->downloads; }
    public function countDownload(): self { ++$this->downloads; return $this; }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): self { $this->owner = $owner; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}
