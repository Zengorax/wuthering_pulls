<?php

namespace App\Statistics;

use App\Entity\BannerTarget;
use App\Entity\Version;

final readonly class VersionSummary
{
    /**
     * @param list<BannerTarget> $targets cibles triées par ordre de saisie
     */
    public function __construct(
        public Version $version,
        public array $targets,
    ) {
    }
}
