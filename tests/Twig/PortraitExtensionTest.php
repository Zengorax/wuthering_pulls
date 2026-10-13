<?php

namespace App\Tests\Twig;

use App\Twig\PortraitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;

final class PortraitExtensionTest extends TestCase
{
    public function testSlugIsLowercaseAsciiWithDashes(): void
    {
        self::assertSame('xiangli-yao', PortraitExtension::slug('Xiangli Yao'));
        self::assertSame('luuk-herssen', PortraitExtension::slug('  Luuk   Herssen '));
        self::assertSame('zhezhi', PortraitExtension::slug('Zhézhi'));
    }

    public function testArtAndIconAreLookedUpInTheirOwnFolders(): void
    {
        $assetMapper = $this->createStub(AssetMapperInterface::class);
        $assetMapper->method('getPublicPath')->willReturnCallback(
            static fn (string $logicalPath): ?string => match ($logicalPath) {
                'characters/art/lynae.png' => '/assets/characters/art/lynae-hash.png',
                'characters/youhu.png' => '/assets/characters/youhu-hash.png',
                default => null,
            },
        );

        $extension = new PortraitExtension($assetMapper);

        self::assertSame('/assets/characters/art/lynae-hash.png', $extension->art('Lynae'));
        self::assertNull($extension->portrait('Lynae'));
        self::assertNull($extension->art('Youhu'), "Un personnage sans art garde seulement son icône.");
        self::assertSame('/assets/characters/youhu-hash.png', $extension->portrait('Youhu'));
    }

    public function testFindsTheFirstExistingImageForACharacter(): void
    {
        $assetMapper = $this->createStub(AssetMapperInterface::class);
        $assetMapper->method('getPublicPath')->willReturnCallback(
            static fn (string $logicalPath): ?string => 'characters/lynae.png' === $logicalPath ? '/assets/characters/lynae-abc.png' : null,
        );

        $extension = new PortraitExtension($assetMapper);

        self::assertSame('/assets/characters/lynae-abc.png', $extension->portrait('Lynae'));
        self::assertNull($extension->portrait('Inconnu'));
        self::assertNull($extension->portrait(null));
        self::assertNull($extension->portrait('   '));
    }
}
