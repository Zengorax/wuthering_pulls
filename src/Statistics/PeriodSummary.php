<?php

namespace App\Statistics;

use App\Entity\MajorVersion;

final readonly class PeriodSummary
{
    /**
     * @param list<VersionSummary> $versions     versions de la plus récente à la plus ancienne
     * @param list<ResourceLine>   $resources    origines des ressources, de la plus grosse à la plus petite
     * @param float                $resourcePulls total des origines saisies, converti en pulls
     */
    public function __construct(
        public MajorVersion $period,
        public array $versions,
        public array $resources,
        public float $resourcePulls,
    ) {
    }
}
