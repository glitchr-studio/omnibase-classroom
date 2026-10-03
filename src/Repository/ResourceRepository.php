<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Resource> */
class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    /** @return list<Resource> the published ones, newest first, of the taxa given */
    public function findPublished(array $taxa = [], int $limit = 200): array
    {
        $query = $this->published();
        foreach (array_values(array_filter($taxa)) as $i => $slug) {
            $query->innerJoin('r.taxa', 'x'.$i)->andWhere('x'.$i.'.slug = :taxon'.$i)->setParameter('taxon'.$i, $slug);
        }

        return $query->orderBy('r.createdAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findOnePublished(string $slug): ?Resource
    {
        return $this->published()->andWhere('r.slug = :slug')->setParameter('slug', $slug)->getQuery()->getOneOrNullResult();
    }

    /** @return list<Resource> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->published()->andWhere('r.title LIKE :term OR r.description LIKE :term')->setParameter('term', '%'.$term.'%')
            ->setMaxResults($limit)->getQuery()->getResult();
    }

    public function sumDownloads(): int
    {
        return (int) $this->createQueryBuilder('r')->select('COALESCE(SUM(r.downloads), 0)')->getQuery()->getSingleScalarResult();
    }

    private function published(): QueryBuilder
    {
        return $this->createQueryBuilder('r')->andWhere('r.published = true');
    }
}
