<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Sequence;
use Base\Enum\ThreadState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Sequence> */
class SequenceRepository extends ServiceEntityRepository
{
    use ClassifiedRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sequence::class);
    }

    /** @return list<Sequence> newest first, filtered by the taxa slugs given and the period */
    public function findPublishedPage(int $page = 1, int $perPage = 24, array $taxa = [], ?string $period = null): array
    {
        $query = $this->classified($this->published(), $taxa);
        if ($period) {
            $query->andWhere('s.periodValue = :period')->setParameter('period', $period);
        }

        return $query->orderBy('s.publishedAt', 'DESC')->addOrderBy('s.id', 'DESC')
            ->setFirstResult(max(0, $page - 1) * $perPage)->setMaxResults($perPage)
            ->getQuery()->getResult();
    }

    public function countPublished(array $taxa = [], ?string $period = null): int
    {
        $query = $this->classified($this->published(), $taxa)->select('COUNT(DISTINCT s.id)');
        if ($period) {
            $query->andWhere('s.periodValue = :period')->setParameter('period', $period);
        }

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    /** @return list<Sequence> */
    public function findLatest(int $limit = 3): array
    {
        return $this->published()->orderBy('s.featured', 'DESC')->addOrderBy('s.publishedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findOnePublished(string $slug): ?Sequence
    {
        return $this->published()->andWhere('s.slug = :slug')->setParameter('slug', $slug)->getQuery()->getOneOrNullResult();
    }

    /** @return list<Sequence> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->published()->innerJoin('s.translations', 'tr')
            ->andWhere('tr.title LIKE :term OR tr.excerpt LIKE :term OR tr.content LIKE :term')->setParameter('term', '%'.$term.'%')
            ->setMaxResults($limit)->getQuery()->getResult();
    }

    public function countDrafts(): int
    {
        return $this->count(['state' => ThreadState::DRAFT]);
    }

    private function published(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.state = :published')->setParameter('published', ThreadState::PUBLISH)
            ->andWhere('s.publishedAt IS NULL OR s.publishedAt <= CURRENT_TIMESTAMP()');
    }
}
