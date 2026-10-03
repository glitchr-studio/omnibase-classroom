<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Card;
use Base\Enum\ThreadState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Card> */
class CardRepository extends ServiceEntityRepository
{
    use ClassifiedRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /** The fan: the featured cards in order, then the newest, up to $limit. @return list<Card> */
    public function findFeatured(int $limit = 9): array
    {
        return $this->published()->orderBy('c.featured', 'DESC')->addOrderBy('c.position', 'ASC')->addOrderBy('c.publishedAt', 'DESC')
            ->setMaxResults($limit)->getQuery()->getResult();
    }

    /** @return list<Card> */
    public function findPublished(array $taxa = [], int $limit = 200): array
    {
        return $this->classified($this->published(), $taxa)->orderBy('c.position', 'ASC')->addOrderBy('c.publishedAt', 'DESC')
            ->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findOnePublished(string $slug): ?Card
    {
        return $this->published()->andWhere('c.slug = :slug')->setParameter('slug', $slug)->getQuery()->getOneOrNullResult();
    }

    /** @return list<Card> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->published()->innerJoin('c.translations', 'tr')
            ->andWhere('tr.title LIKE :term OR tr.headline LIKE :term OR tr.content LIKE :term')->setParameter('term', '%'.$term.'%')
            ->setMaxResults($limit)->getQuery()->getResult();
    }

    private function published(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.state = :published')->setParameter('published', ThreadState::PUBLISH)
            ->andWhere('c.publishedAt IS NULL OR c.publishedAt <= CURRENT_TIMESTAMP()');
    }
}
