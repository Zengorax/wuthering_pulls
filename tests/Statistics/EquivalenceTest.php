<?php

namespace App\Tests\Statistics;

use App\Entity\Version;
use App\Statistics\Equivalence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EquivalenceTest extends TestCase
{
    public function testParsesTheSxRxNotation(): void
    {
        $equivalence = Equivalence::tryFromString(' s9r8 ');

        self::assertNotNull($equivalence);
        self::assertSame(9, $equivalence->sets);
        self::assertSame(8, $equivalence->refines);
        self::assertSame('S9R8', (string) $equivalence);
    }

    #[DataProvider('invalidValues')]
    public function testRejectsAnythingElse(?string $value): void
    {
        self::assertNull(Equivalence::tryFromString($value));
    }

    /**
     * @return iterable<string, array{0: ?string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'null' => [null];
        yield 'vide' => [''];
        yield 'texte libre' => ['environ 9 sets'];
        yield 'ordre inversé' => ['R8S9'];
        yield 'nombre manquant' => ['S9R'];
    }

    public function testVersionNormalisesTheEquivalence(): void
    {
        $version = (new Version())->setEquivalence(' s9r8 ');
        self::assertSame('S9R8', $version->getEquivalence());
        self::assertSame(8, $version->getEquivalenceValue()?->refines);

        self::assertNull($version->setEquivalence('   ')->getEquivalence());
    }

    public function testVersionStockIsConvertedToEquivalentPulls(): void
    {
        $version = new Version();
        self::assertNull($version->getStartEquivalentPulls());
        self::assertNull($version->getImpliedIncomePulls());

        $version->setStartLuminite(160)->setStartAstrite(320)->setStartPulls(10);
        self::assertSame(13.0, $version->getStartEquivalentPulls());
    }
}
