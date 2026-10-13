<?php

namespace App\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class OptimizeArtCommandTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/wp-art-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/art', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testConvertsPngToFullAndThumbnailWebp(): void
    {
        $image = imagecreatetruecolor(300, 1200);
        imagepng($image, $this->dir.'/art/lynae.png');

        $tester = new CommandTester(new \App\Command\OptimizeArtCommand($this->dir));
        $status = $tester->execute(['--path' => $this->dir, '--remove-originals' => true]);

        self::assertSame(0, $status);
        self::assertFileExists($this->dir.'/art/lynae.webp');
        self::assertFileExists($this->dir.'/thumb/lynae.webp');
        self::assertFileDoesNotExist($this->dir.'/art/lynae.png');
        self::assertSame(960, getimagesize($this->dir.'/art/lynae.webp')[1]);
        self::assertSame(400, getimagesize($this->dir.'/thumb/lynae.webp')[1]);
        self::assertStringContainsString('1 art(s) converti(s)', $tester->getDisplay());
    }

    public function testKeepsOriginalsByDefault(): void
    {
        imagepng(imagecreatetruecolor(100, 100), $this->dir.'/art/zani.png');

        (new CommandTester(new \App\Command\OptimizeArtCommand($this->dir)))->execute(['--path' => $this->dir]);

        self::assertFileExists($this->dir.'/art/zani.png');
        self::assertFileExists($this->dir.'/art/zani.webp');
    }
}
