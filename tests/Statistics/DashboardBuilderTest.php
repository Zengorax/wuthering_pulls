<?php

namespace App\Tests\Statistics;

use App\Entity\BannerTarget;
use App\Entity\MajorVersion;
use App\Entity\ResourceBreakdown;
use App\Entity\Version;
use App\Enum\Element;
use App\Statistics\DashboardBuilder;
use PHPUnit\Framework\TestCase;

final class DashboardBuilderTest extends TestCase
{
    public function testEmptyDashboardHasNoStatistics(): void
    {
        $dashboard = (new DashboardBuilder())->build([]);

        self::assertFalse($dashboard->hasVersions);
        self::assertSame(0, $dashboard->totalPulls);
        self::assertNull($dashboard->characterAverage);
        self::assertNull($dashboard->weaponAverage);
        self::assertNull($dashboard->fiftyFiftyRate());
        self::assertNull($dashboard->luckPercent);
        self::assertNull($dashboard->projection);
        self::assertSame(0, $dashboard->estimatedShare());
    }

    public function testTotalsSeparateRealFromEstimatedPulls(): void
    {
        $version = $this->version('3.0', $this->period('3.X'));
        $this->target($version, 'Lynae', 100, 40);
        $this->target($version, 'Aemeath', 50, 10, estimate: true);

        $dashboard = (new DashboardBuilder())->build([$version->getMajorVersion()]);

        self::assertSame(200, $dashboard->totalPulls);
        self::assertSame(140, $dashboard->realPulls);
        self::assertSame(60, $dashboard->estimatedPulls);
        self::assertSame(30, $dashboard->estimatedShare());
        self::assertSame(32000, $dashboard->totalAstrites());
    }

    public function testAveragesIgnoreEstimatesZerosAndFourStars(): void
    {
        $version = $this->version('3.0', $this->period('3.X'));
        $this->target($version, 'Lynae', 100, 60);
        $this->target($version, 'Aemeath', 80, 0);
        $this->target($version, 'Luci', 25, 0, rarity: 4);
        $this->target($version, 'Estimé', 10, 10, estimate: true);

        $dashboard = (new DashboardBuilder())->build([$version->getMajorVersion()]);

        self::assertSame(90.0, $dashboard->characterAverage);
        self::assertSame(2, $dashboard->characterCount);
        self::assertSame(60.0, $dashboard->weaponAverage);
        self::assertSame(1, $dashboard->weaponCount);
    }

    public function testFiftyFiftyRateAndLuck(): void
    {
        $version = $this->version('3.0', $this->period('3.X'));
        $this->target($version, 'A', 60, 0, result: BannerTarget::FIFTY_FIFTY_WON);
        $this->target($version, 'B', 60, 0, result: BannerTarget::FIFTY_FIFTY_WON);
        $this->target($version, 'C', 140, 0, result: BannerTarget::FIFTY_FIFTY_LOST);
        $this->target($version, 'D', 50, 0);

        $dashboard = (new DashboardBuilder())->build([$version->getMajorVersion()]);

        self::assertSame(2, $dashboard->fiftyFiftyWon);
        self::assertSame(1, $dashboard->fiftyFiftyLost);
        self::assertSame(67, $dashboard->fiftyFiftyRate());
        // Moyenne de 77,5 pulls contre ≈ 93,75 attendus : plus chanceux que la moyenne.
        self::assertGreaterThan(0, $dashboard->luckPercent);
    }

    public function testDistributionBucketsFiveStarPulls(): void
    {
        $version = $this->version('3.0', $this->period('3.X'));
        foreach ([1, 20, 21, 79, 80, 81, 160, 175] as $index => $pulls) {
            $this->target($version, 'T'.$index, $pulls, 0);
        }

        $counts = array_column((new DashboardBuilder())->build([$version->getMajorVersion()])->distribution, 'count');

        self::assertSame([2, 1, 0, 2, 1, 0, 0, 2], $counts);
    }

    public function testPeriodsAndVersionsAreOrderedNaturallyFromNewestToOldest(): void
    {
        $older = $this->period('2.X');
        $newer = $this->period('3.X');
        $this->version('3.9', $newer);
        $this->version('3.10', $newer);
        $this->version('3.2', $newer);
        $this->version('2.0', $older);

        $dashboard = (new DashboardBuilder())->build([$older, $newer]);

        self::assertSame(['3.X', '2.X'], array_map(static fn ($p) => $p->period->getName(), $dashboard->periods));
        self::assertSame(['3.10', '3.9', '3.2'], array_map(static fn ($v) => $v->version->getName(), $dashboard->periods[0]->versions));
        // Le graphique est chronologique.
        self::assertSame(['2.0', '3.2', '3.9', '3.10'], $dashboard->chart['labels']);
    }

    public function testChartSeriesFlagVersionsContainingEstimates(): void
    {
        $period = $this->period('3.X');
        $first = $this->version('3.0', $period);
        $second = $this->version('3.1', $period);
        $this->target($first, 'A', 70, 30);
        $this->target($second, 'B', 40, 20, estimate: true);

        $chart = (new DashboardBuilder())->build([$period])->chart;

        self::assertSame([70, 40], $chart['character']);
        self::assertSame([30, 20], $chart['weapon']);
        self::assertSame([false, true], $chart['estimated']);
    }

    public function testResourcesAreConvertedToPullsAndRanked(): void
    {
        $period = $this->period('3.X');
        $this->resource($period, 'Daily', 3200, ResourceBreakdown::UNIT_ASTRITES);
        $this->resource($period, 'Événement', 10, ResourceBreakdown::UNIT_PULLS);
        $this->resource($period, 'Marées', 5, ResourceBreakdown::UNIT_TIDES);

        $summary = (new DashboardBuilder())->build([$period])->periods[0];

        self::assertSame(35.0, $summary->resourcePulls);
        self::assertSame(['Daily', 'Événement', 'Marées'], array_map(static fn ($l) => $l->resource->getName(), $summary->resources));
        self::assertSame([100, 50, 25], array_map(static fn ($l) => $l->share, $summary->resources));
    }

    public function testProjectionUsesAverageImpliedIncome(): void
    {
        $period = $this->period('3.X');
        $first = $this->version('3.0', $period);
        $first->setStartPulls(20)->setEstimatedEndPulls(40);
        $this->target($first, 'A', 100, 0); // gain implicite : 40 - 20 + 100 = 120
        $second = $this->version('3.1', $period);
        $second->setStartAstrite(3200)->setEstimatedEndAstrite(8000); // 20 -> 50 pulls
        $this->target($second, 'B', 80, 0); // gain implicite : 50 - 20 + 80 = 110

        $projection = (new DashboardBuilder())->build([$period])->projection;

        self::assertNotNull($projection);
        self::assertSame('3.1', $projection->fromVersion);
        self::assertSame(50.0, $projection->startPulls);
        self::assertSame(115.0, $projection->averageIncome);
        self::assertSame(165.0, $projection->projectedPulls);
        self::assertSame(1, $projection->guaranteedFeatured);
    }

    public function testNoProjectionWithoutBothStocks(): void
    {
        $period = $this->period('3.X');
        $this->version('3.0', $period)->setStartPulls(20);

        self::assertNull((new DashboardBuilder())->build([$period])->projection);
    }

    public function testPityCapDoublesAfterALostFiftyFifty(): void
    {
        $target = (new BannerTarget())->setPullsCharacter(70);
        self::assertSame(80, $target->getCharacterPityCap());

        $target->setFiftyFiftyResult(BannerTarget::FIFTY_FIFTY_LOST);
        self::assertSame(160, $target->getCharacterPityCap());

        self::assertSame(160, (new BannerTarget())->setPullsCharacter(95)->getCharacterPityCap());
    }

    private function period(string $name): MajorVersion
    {
        return (new MajorVersion())->setName($name);
    }

    private function version(string $name, MajorVersion $period): Version
    {
        $version = (new Version())->setName($name);
        $period->addVersion($version);

        return $version;
    }

    private function target(Version $version, string $name, int $character, int $weapon, bool $estimate = false, int $rarity = 5, ?string $result = null): BannerTarget
    {
        $target = (new BannerTarget())
            ->setName($name)
            ->setPullsCharacter($character)
            ->setPullsWeapon($weapon)
            ->setIsEstimate($estimate)
            ->setRarity($rarity)
            ->setElement(Element::Havoc)
            ->setFiftyFiftyResult($result);
        $version->addBannerTarget($target);

        return $target;
    }

    private function resource(MajorVersion $period, string $name, int $amount, string $unit): void
    {
        $period->addResourceBreakdown((new ResourceBreakdown())->setName($name)->setAmount($amount)->setUnit($unit));
    }
}
