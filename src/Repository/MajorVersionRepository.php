<?php

namespace App\Repository;

use App\Entity\MajorVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MajorVersion>
 */
class MajorVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MajorVersion::class);
    }

    /**
     * Charge toutes les périodes avec leurs versions, cibles et origines de ressources en 2 requêtes.
     *
     * @return list<MajorVersion>
     */
    public function findAllWithHistory(): array
    {
        $periods = $this->createQueryBuilder('m')
            ->addSelect('v', 't')
            ->leftJoin('m.versions', 'v')
            ->leftJoin('v.bannerTargets', 't')
            ->getQuery()
            ->getResult();

        // Les périodes sont déjà gérées par l'EntityManager : cette requête hydrate seulement leurs ressources.
        $this->createQueryBuilder('m')
            ->addSelect('r')
            ->leftJoin('m.resourceBreakdowns', 'r')
            ->getQuery()
            ->getResult();

        return $periods;
    }
}
