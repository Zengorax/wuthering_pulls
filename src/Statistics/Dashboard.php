<?php

namespace App\Statistics;

final readonly class Dashboard
{
    /**
     * @param list<PeriodSummary>                   $periods
     * @param list<array{label: string, count: int}> $distribution répartition des pulls par personnage 5★ réel
     * @param array<string, mixed>                  $chart         données du graphique (labels et séries)
     */
    public function __construct(
        public array $periods,
        public bool $hasVersions,
        public int $totalPulls,
        public int $realPulls,
        public int $estimatedPulls,
        public ?float $characterAverage,
        public int $characterCount,
        public ?float $weaponAverage,
        public int $weaponCount,
        public int $fiftyFiftyWon,
        public int $fiftyFiftyLost,
        public ?float $luckPercent,
        public array $distribution,
        public array $chart,
        public ?Projection $projection,
    ) {
    }

    public function totalAstrites(): int
    {
        return $this->totalPulls * PullRules::ASTRITES_PER_PULL;
    }

    public function expectedPulls(): float
    {
        return PullRules::expectedPullsForFeaturedCharacter();
    }

    public function estimatedShare(): int
    {
        return $this->totalPulls > 0 ? (int) round($this->estimatedPulls / $this->totalPulls * 100) : 0;
    }

    public function fiftyFiftyTotal(): int
    {
        return $this->fiftyFiftyWon + $this->fiftyFiftyLost;
    }

    public function fiftyFiftyRate(): ?int
    {
        $total = $this->fiftyFiftyTotal();

        return $total > 0 ? (int) round($this->fiftyFiftyWon / $total * 100) : null;
    }
}
