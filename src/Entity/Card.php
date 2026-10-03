<?php

namespace Base\Classroom\Entity;

use Base\Classroom\Repository\CardRepository;
use Base\Database\Attribute\DiscriminatorEntry;
use Base\Database\Attribute\Uploader;
use Base\Entity\Thread;
use Base\Service\Model\LinkableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * An idea card, the ones a colleague picks from the fan on the home page:
 * a picture, a title, a line (the headline), a few paragraphs (the text),
 * one to three links - to a sequence, a resource, a chronicle, a site -,
 * and its levels and subjects.
 */
#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(name: 'classroom_card')]
#[DiscriminatorEntry(value: 'classroom_card')]
class Card extends Thread implements LinkableInterface
{
    use ClassifiedTrait;

    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-clone'];
    }

    public function __toLink(array $routeParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        return $this->getRouter()->generate('classroom_card', array_merge($routeParameters, ['slug' => $this->getSlug()]), $referenceType);
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '8MB', mime_types: ['image/*'])]
    protected $image = null;

    /** @var list<array{label: string, url: string}> */
    #[ORM\Column(type: 'json')]
    protected array $links = [];

    /** On the home page's fan. */
    #[ORM\Column(type: 'boolean')]
    protected bool $featured = false;

    /** Its place in the fan: lower first. */
    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    /** Its symbol: a Font Awesome class ("fa-solid fa-dice"), big on a card with no picture, a badge on one with. */
    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $icon = null;

    /** The card's colour (its back, its ribbon), as the host paints it. */
    #[ORM\Column(length: 24, nullable: true)]
    protected ?string $color = null;

    public function __toString(): string
    {
        return $this->getTitle() ?? '';
    }

    public function getImage(): ?string { return Uploader::getPublic($this, 'image'); }
    public function getImageFile(): ?File { return Uploader::get($this, 'image'); }
    public function setImage($image): self { $this->image = $image; return $this; }

    /** @return list<array{label: string, url: string}> */
    public function getLinks(): array { return $this->links; }

    /** @param list<array{label?: string, url?: string}>|string|null $links "label | url" per line when a string */
    public function setLinks(array|string|null $links): self
    {
        if (!\is_array($links)) {
            $links = array_map(function (string $line) {
                [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

                return $url ? ['label' => $label, 'url' => $url] : ['label' => $label, 'url' => $label];
            }, array_filter(preg_split('/\R/', (string) $links), fn ($l) => '' !== trim($l)));
        }
        $this->links = array_values(array_filter(array_map(fn ($link) => ['label' => trim((string) ($link['label'] ?? '')), 'url' => trim((string) ($link['url'] ?? ''))], $links), fn ($link) => '' !== $link['url']));

        return $this;
    }

    /** The links, one per line, for a textarea. */
    public function getLinksText(): string
    {
        return implode("\n", array_map(fn ($link) => $link['label'] && $link['label'] !== $link['url'] ? $link['label'].' | '.$link['url'] : $link['url'], $this->links));
    }
    public function setLinksText(?string $text): self { return $this->setLinks($text); }

    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): self { $this->featured = $featured; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }

    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(?string $icon): self { $this->icon = $icon ? trim($icon) : null; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): self { $this->color = $color ?: null; return $this; }
}
