<?php

namespace Base\Classroom\Twig;

use Base\Classroom\Entity\Resource;
use Base\Classroom\Repository\CardRepository;
use Base\Classroom\Repository\ResourceRepository;
use Base\Classroom\Repository\SequenceRepository;
use Base\Classroom\Service\Taxa;
use Base\Classroom\Security\ResourceVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/** What a host page asks the classroom: the taxa for its menus, the cards for its fan, the latest sequences and resources. */
final class ClassroomExtension extends AbstractExtension
{
    public function __construct(
        private readonly Taxa $taxa,
        private readonly CardRepository $cards,
        private readonly SequenceRepository $sequences,
        private readonly ResourceRepository $resources,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('classroom_cycles', fn (): array => $this->taxa->cycles()),
            new TwigFunction('classroom_levels', fn (): array => $this->taxa->levels()),
            new TwigFunction('classroom_subjects', fn (): array => $this->taxa->subjects()),
            new TwigFunction('classroom_purposes', fn (): array => $this->taxa->purposes()),
            new TwigFunction('classroom_cards', fn (int $limit = 9): array => $this->cards->findFeatured($limit)),
            new TwigFunction('classroom_sequences', fn (int $limit = 3): array => $this->sequences->findLatest($limit)),
            new TwigFunction('classroom_resources', fn (int $limit = 6): array => $this->resources->findPublished([], $limit)),
            new TwigFunction('classroom_may_download', fn (Resource $resource): bool => $this->security->isGranted(ResourceVoter::DOWNLOAD, $resource)),
        ];
    }

    public function getFilters(): array
    {
        return [
            // "2,4 Mo"
            new TwigFilter('classroom_size', function (?int $bytes): string {
                if (null === $bytes) {
                    return '';
                }
                foreach (['o', 'Ko', 'Mo', 'Go'] as $i => $unit) {
                    if ($bytes < 1024 ** ($i + 1) || 'Go' === $unit) {
                        return ($i ? number_format($bytes / 1024 ** $i, 1, ',', ' ') : $bytes).' '.$unit;
                    }
                }

                return (string) $bytes;
            }),
            // "1 h 30" / "45 min"
            new TwigFilter('classroom_minutes', fn (?int $minutes): string => null === $minutes ? '' : ($minutes >= 60 ? sprintf('%d h%s', intdiv($minutes, 60), $minutes % 60 ? sprintf(' %02d', $minutes % 60) : '') : $minutes.' min')),
        ];
    }
}
