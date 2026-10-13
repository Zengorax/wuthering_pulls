<?php

namespace App\Repository;

use App\Entity\BannerTarget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BannerTarget>
 */
class BannerTargetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BannerTarget::class);
    }

    /**
     * Noms déjà saisis, pour l'autocomplétion.
     *
     * @return list<string>
     */
    public function findKnownNames(): array
    {
        return array_column(
            $this->createQueryBuilder('t')
                ->select('DISTINCT t.name AS name')
                ->orderBy('t.name')
                ->getQuery()
                ->getArrayResult(),
            'name',
        );
    }
}
