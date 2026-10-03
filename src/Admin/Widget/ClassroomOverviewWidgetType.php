<?php

namespace Base\Classroom\Admin\Widget;

use Base\Admin\Config\Menu\MenuItem;
use Base\Admin\Widget\DashboardWidgetTypeInterface;
use Base\Classroom\Entity\Card;
use Base\Classroom\Entity\Resource;
use Base\Classroom\Repository\EntitlementRepository;
use Base\Classroom\Repository\ResourceRepository;
use Base\Classroom\Repository\SequenceRepository;
use Base\Enum\ThreadState;
use Doctrine\ORM\EntityManagerInterface;

/** The classroom at a glance: sequences online and in draft, cards, resources, downloads, what was bought this month. */
final class ClassroomOverviewWidgetType implements DashboardWidgetTypeInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SequenceRepository $sequences,
        private readonly ResourceRepository $resources,
        private readonly EntitlementRepository $entitlements,
    ) {
    }

    public static function getName(): string
    {
        return 'classroom_overview';
    }

    public function getTemplate(): string
    {
        return '@Classroom/admin/widget/overview.html.twig';
    }

    public function getTemplateVars(MenuItem $widget): array
    {
        $month = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0);

        return ['tiles' => [
            'sequences' => ['value' => $this->sequences->count(['state' => ThreadState::PUBLISH]), 'crud' => 'sequences'],
            'drafts' => ['value' => $this->sequences->countDrafts(), 'crud' => 'sequences', 'alert' => true],
            'cards' => ['value' => $this->entityManager->getRepository(Card::class)->count(['state' => ThreadState::PUBLISH]), 'crud' => 'cards'],
            'resources' => ['value' => $this->entityManager->getRepository(Resource::class)->count(['published' => true]), 'crud' => 'resources'],
            'downloads' => ['value' => $this->resources->sumDownloads(), 'crud' => 'resources'],
            'bought' => ['value' => $this->entitlements->countSince($month), 'crud' => 'resource_offers'],
        ]];
    }
}
