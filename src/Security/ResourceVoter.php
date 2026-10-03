<?php

namespace Base\Classroom\Security;

use Base\Classroom\Entity\Resource;
use Base\Classroom\Enum\Visibility;
use Base\Classroom\Repository\EntitlementRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may download a resource: anyone for a free one, the members
 * (classroom.members_role) for a members' one, whoever holds an Entitlement
 * for a paid one - and the administrators always.
 */
final class ResourceVoter extends Voter
{
    public const DOWNLOAD = 'CLASSROOM_DOWNLOAD';

    public function __construct(
        private readonly Security $security,
        private readonly EntitlementRepository $entitlements,
        #[Autowire('%classroom.members_role%')] private readonly string $membersRole = 'ROLE_USER',
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::DOWNLOAD === $attribute && $subject instanceof Resource;
    }

    /** @param Resource $subject */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$subject->isPublished() && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        return match ($subject->getVisibility()) {
            Visibility::FREE => true,
            Visibility::MEMBERS => $this->security->isGranted($this->membersRole),
            Visibility::PAID => $this->security->isGranted('ROLE_ADMIN') || null !== $this->entitlements->findOne($token->getUser(), $subject),
        };
    }
}
