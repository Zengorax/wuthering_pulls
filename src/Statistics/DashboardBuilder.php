<?php

namespace App\Statistics;

use App\Entity\BannerTarget;
use App\Entity\MajorVersion;
use App\Entity\ResourceBreakdown;
use App\Entity\Version;

/**
 * Calcule les totaux, moyennes, distributions et projections affichés sur le tableau de bord.
 */
final class DashboardBuilder
{
    /**
     * @param iterable<MajorVersion> $majorVersions
     */
    public function build(iterable $majorVersions): Dashboard
    {
        $periods = [...$majorVersions];
        usort($periods, static fn (MajorVersion $a, MajorVersion $b): int => strnatcmp($b->getName() ?? '', $a->getName() ?? ''));

        $summaries = [];
        $allVersions = [];
        $total = $real = 0;
        $characterPulls = $characterCount = $weaponPulls = $weaponCount = 0;
        $won = $lost = 0;
        $fiveStarPulls = [];

        foreach ($periods as $period) {
            $versionSummaries = [];
            $versions = $period->getVersions()->toArray();
            usort($versions, static fn (Version $a, Version $b): int => strnatcmp($b->getName() ?? '', $a->getName() ?? ''));

            foreach ($versions as $version) {
                $targets = $version->getBannerTargets()->toArray();
                usort($targets, static fn (BannerTarget $a, BannerTarget $b): int => $a->getId() <=> $b->getId());
                $versionSummaries[] = new VersionSummary($version, $targets);
                $allVersions[] = $version;

                $total += $version->getTotalPulls();
                $real += $version->getRealPulls();

                foreach ($targets as $target) {
                    if ($target->isEstimate()) {
                        continue;
                    }

                    if ($target->isFiveStar() && $target->getPullsCharacter() > 0) {
                        $characterPulls += $target->getPullsCharacter();
                        ++$characterCount;
                        $fiveStarPulls[] = $target->getPullsCharacter();
                    }

                    if ($target->getPullsWeapon() > 0) {
                        $weaponPulls += $target->getPullsWeapon();
                        ++$weaponCount;
                    }

                    if ($target->isFiveStar()) {
                        match ($target->getFiftyFiftyResult()) {
                            BannerTarget::FIFTY_FIFTY_WON => ++$won,
                            BannerTarget::FIFTY_FIFTY_LOST => ++$lost,
                            default => null,
                        };
                    }
                }
            }

            [$lines, $resourcePulls] = $this->resourceLines($period);
            $summaries[] = new PeriodSummary($period, $versionSummaries, $lines, $resourcePulls);
        }

        $characterAverage = $characterCount > 0 ? $characterPulls / $characterCount : null;
        $expected = PullRules::expectedPullsForFeaturedCharacter();

        return new Dashboard(
            periods: $summaries,
            hasVersions: [] !== $allVersions,
            totalPulls: $total,
            realPulls: $real,
            estimatedPulls: $total - $real,
            characterAverage: $characterAverage,
            characterCount: $characterCount,
            weaponAverage: $weaponCount > 0 ? $weaponPulls / $weaponCount : null,
            weaponCount: $weaponCount,
            fiftyFiftyWon: $won,
            fiftyFiftyLost: $lost,
            luckPercent: null !== $characterAverage ? ($expected - $characterAverage) / $expected * 100 : null,
            distribution: $this->distribution($fiveStarPulls),
            chart: $this->chart($allVersions),
            projection: $this->projection($allVersions),
        );
    }

    /**
     * @return array{0: list<ResourceLine>, 1: float}
     */
    private function resourceLines(MajorVersion $period): array
    {
        $resources = $period->getResourceBreakdowns()->toArray();
        $pulls = array_map(static fn (ResourceBreakdown $r): float => match ($r->getUnit()) {
            ResourceBreakdown::UNIT_ASTRITES => PullRules::astritesToPulls($r->getAmount()),
            default => (float) $r->getAmount(),
        }, $resources);

        $max = [] === $pulls ? 0.0 : max($pulls);
        $lines = [];
        foreach ($resources as $index => $resource) {
            $lines[] = new ResourceLine($resource, $pulls[$index], $max > 0 ? (int) round($pulls[$index] / $max * 100) : 0);
        }
        usort($lines, static fn (ResourceLine $a, ResourceLine $b): int => $b->pulls <=> $a->pulls);

        return [$lines, array_sum($pulls)];
    }

    /**
     * @param list<int> $fiveStarPulls
     *
     * @return list<array{label: string, count: int}>
     */
    private function distribution(array $fiveStarPulls): array
    {
        $step = PullRules::DISTRIBUTION_STEP;
        $buckets = (int) (2 * PullRules::HARD_PITY / $step);
        $counts = array_fill(0, $buckets, 0);
        foreach ($fiveStarPulls as $pulls) {
            ++$counts[min($buckets - 1, intdiv(max(1, $pulls) - 1, $step))];
        }

        $distribution = [];
        foreach ($counts as $index => $count) {
            $from = $index * $step + 1;
            $to = ($index + 1) * $step;
            $distribution[] = ['label' => $index === $buckets - 1 ? sprintf('%d+', $from) : sprintf('%d-%d', $from, $to), 'count' => $count];
        }

        return $distribution;
    }

    /**
     * @param list<Version> $versions
     *
     * @return array<string, mixed>
     */
    private function chart(array $versions): array
    {
        usort($versions, static fn (Version $a, Version $b): int => strnatcmp($a->getName() ?? '', $b->getName() ?? ''));

        $chart = ['labels' => [], 'character' => [], 'weapon' => [], 'estimated' => []];
        foreach ($versions as $version) {
            $character = $weapon = 0;
            foreach ($version->getBannerTargets() as $target) {
                $character += $target->getPullsCharacter();
                $weapon += $target->getPullsWeapon();
            }
            $chart['labels'][] = $version->getName();
            $chart['character'][] = $character;
            $chart['weapon'][] = $weapon;
            $chart['estimated'][] = $version->getEstimatedPulls() > 0;
        }

        return $chart;
    }

    /**
     * @param list<Version> $versions
     */
    private function projection(array $versions): ?Projection
    {
        usort($versions, static fn (Version $a, Version $b): int => strnatcmp($b->getName() ?? '', $a->getName() ?? ''));

        $incomes = [];
        foreach ($versions as $version) {
            if (null !== ($income = $version->getImpliedIncomePulls())) {
                $incomes[] = $income;
            }
        }

        $latest = null;
        foreach ($versions as $version) {
            if (null !== $version->getEstimatedEndEquivalentPulls()) {
                $latest = $version;
                break;
            }
        }

        if ([] === $incomes || null === $latest) {
            return null;
        }

        $start = $latest->getEstimatedEndEquivalentPulls() ?? 0.0;
        $average = array_sum($incomes) / count($incomes);
        $projected = max(0.0, $start + $average);

        return new Projection(
            fromVersion: (string) $latest->getName(),
            startPulls: $start,
            averageIncome: $average,
            projectedPulls: $projected,
            guaranteedFeatured: (int) floor($projected / (2 * PullRules::HARD_PITY)),
            expectedFeatured: $projected / PullRules::expectedPullsForFeaturedCharacter(),
        );
    }
}
