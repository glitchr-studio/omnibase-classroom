<?php

namespace Base\Classroom\Repository;

use Doctrine\ORM\QueryBuilder;

/** Filtering a thread query by taxa slugs: each slug given must be among the thread's taxa. */
trait ClassifiedRepositoryTrait
{
    /** @param list<string> $taxa slugs (a level, a subject, a purpose...) all required */
    private function classified(QueryBuilder $query, array $taxa): QueryBuilder
    {
        $alias = $query->getRootAliases()[0];
        foreach (array_values(array_filter($taxa)) as $i => $slug) {
            $query->innerJoin($alias.'.taxa', 'x'.$i)->andWhere('x'.$i.'.slug = :taxon'.$i)->setParameter('taxon'.$i, $slug);
        }

        return $query;
    }
}
