<?php

namespace Base\Classroom\Entity;

/**
 * What the classroom's threads share: their levels, subjects and purposes
 * are their omnibase taxa, read back by kind.
 */
trait ClassifiedTrait
{
    /**
     * What the teacher made elsewhere and shows here, framed: a Canva design,
     * a Padlet board, a video, an exercise (glitchr/omnibase's embed_url() says which).
     *
     * @var list<array{url: string, label: string}>
     */
    #[\Doctrine\ORM\Mapping\Column(type: 'json')]
    protected array $embeds = [];

    /** @return list<array{url: string, label: string}> */
    public function getEmbeds(): array
    {
        return $this->embeds;
    }

    /** @param list<array{url?: string, label?: string}>|string|null $embeds "Label | https://…" per line when a string */
    public function setEmbeds(array|string|null $embeds): self
    {
        if (!\is_array($embeds)) {
            $embeds = array_map(function (string $line) {
                [$a, $b] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

                return $b ? ['label' => $a, 'url' => $b] : ['label' => '', 'url' => $a];
            }, array_filter(preg_split('/\R/', (string) $embeds), fn ($l) => '' !== trim($l)));
        }
        $this->embeds = array_values(array_filter(array_map(fn ($e) => ['label' => trim((string) ($e['label'] ?? '')), 'url' => trim((string) ($e['url'] ?? ''))], $embeds), fn ($e) => (bool) preg_match('#^https?://#i', $e['url'])));

        return $this;
    }

    /** The embeds, one per line, for a textarea. */
    public function getEmbedsText(): string
    {
        return implode("\n", array_map(fn ($e) => '' !== $e['label'] ? $e['label'].' | '.$e['url'] : $e['url'], $this->embeds));
    }

    public function setEmbedsText(?string $text): self
    {
        return $this->setEmbeds($text);
    }

    /** @return list<Level> */
    public function getLevels(): array
    {
        $levels = array_values(array_filter($this->getTaxa()->toArray(), fn ($taxon) => $taxon instanceof Level));
        usort($levels, fn (Level $a, Level $b) => $a->getPosition() <=> $b->getPosition());

        return $levels;
    }

    /** @return list<Subject> */
    public function getSubjects(): array
    {
        return array_values(array_filter($this->getTaxa()->toArray(), fn ($taxon) => $taxon instanceof Subject));
    }

    /** @return list<Purpose> */
    public function getPurposes(): array
    {
        return array_values(array_filter($this->getTaxa()->toArray(), fn ($taxon) => $taxon instanceof Purpose));
    }

    /** Several levels at once: "multi-niveaux". */
    public function isMultiLevel(): bool
    {
        return \count($this->getLevels()) > 1;
    }

    /** "CP · CE1" */
    public function getLevelsLabel(): string
    {
        return implode(' · ', array_map(fn (Level $level) => $level->getShort(), $this->getLevels()));
    }
}
