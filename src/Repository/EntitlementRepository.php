<?php

namespace Base\Classroom\Repository;

use App\Entity\User;
use Base\Classroom\Entity\Entitlement;
use Base\Classroom\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Entitlement> */
class EntitlementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entitlement::class);
    }

    /** @return list<Entitlement> what this person bought, newest first */
    public function findForUser(?object $user): array
    {
        return $user instanceof User ? $this->findBy(['user' => $user], ['createdAt' => 'DESC']) : [];
    }

    public function findOne(?object $user, Resource $resource): ?Entitlement
    {
        return $user instanceof User ? $this->findOneBy(['user' => $user, 'resource' => $resource]) : null;
    }

    /** Since the first of the month: how many downloads were bought. */
    public function countSince(\DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('e')->select('COUNT(e.id)')
            ->andWhere('e.createdAt >= :since')->setParameter('since', $since)
            ->getQuery()->getSingleScalarResult();
    }
}
