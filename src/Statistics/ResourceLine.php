<?php

namespace App\Statistics;

use App\Entity\ResourceBreakdown;

final readonly class ResourceLine
{
    /**
     * @param float $pulls equivalent en pulls de la source
     * @param int   $share part (0-100) par rapport à la plus grosse source de la période
     */
    public function __construct(
        public ResourceBreakdown $resource,
        public float $pulls,
        public int $share,
    ) {
    }
}
