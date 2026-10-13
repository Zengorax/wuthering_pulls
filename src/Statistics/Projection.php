<?php

namespace App\Statistics;

/**
 * Projection du stock de la prochaine version à partir de l'historique saisi.
 */
final readonly class Projection
{
    public function __construct(
        public string $fromVersion,
        public float $startPulls,
        public float $averageIncome,
        public float $projectedPulls,
        public int $guaranteedFeatured,
        public float $expectedFeatured,
    ) {
    }
}
