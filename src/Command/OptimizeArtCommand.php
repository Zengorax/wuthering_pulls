<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Convertit les arts PNG de `assets/characters/art/` en WebP léger :
 *  - `art/<nom>.webp`   : grande version pour l'animation de pull ;
 *  - `thumb/<nom>.webp` : vignette pour les cartes d'historique.
 */
#[AsCommand(name: 'app:art:optimize', description: 'Convertit les arts de personnages en WebP (grande version et vignette)')]
final class OptimizeArtCommand extends Command
{
    private const FULL_HEIGHT = 960;
    private const FULL_QUALITY = 82;
    private const THUMB_HEIGHT = 400;
    private const THUMB_QUALITY = 80;

    public function __construct(
        #[Autowire('%kernel.project_dir%/assets/characters')]
        private readonly string $charactersDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Dossier des personnages (contient art/)', $this->charactersDir)
            ->addOption('remove-originals', null, InputOption::VALUE_NONE, 'Supprime les PNG une fois convertis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!\function_exists('imagewebp')) {
            $io->error("L'extension GD avec le support WebP est requise.");

            return Command::FAILURE;
        }

        $root = (string) $input->getOption('path');
        $artDir = $root.'/art';
        if (!is_dir($artDir)) {
            $io->warning(sprintf('Aucun dossier %s : rien à convertir.', $artDir));

            return Command::SUCCESS;
        }

        $this->filesystem->mkdir($root.'/thumb');
        $converted = 0;
        $before = 0;
        $after = 0;

        foreach ((new Finder())->files()->in($artDir)->name('*.png') as $file) {
            $slug = $file->getBasename('.png');
            $source = @imagecreatefrompng($file->getPathname());
            if (false === $source) {
                $io->warning(sprintf('%s : PNG illisible, ignoré.', $file->getFilename()));
                continue;
            }
            imagepalettetotruecolor($source);

            $full = $this->scaled($source, self::FULL_HEIGHT);
            $thumb = $this->scaled($source, self::THUMB_HEIGHT);
            $fullPath = sprintf('%s/%s.webp', $artDir, $slug);
            $thumbPath = sprintf('%s/thumb/%s.webp', $root, $slug);
            imagewebp($full, $fullPath, self::FULL_QUALITY);
            imagewebp($thumb, $thumbPath, self::THUMB_QUALITY);

            $before += (int) $file->getSize();
            $after += (int) filesize($fullPath) + (int) filesize($thumbPath);
            ++$converted;

            if ($input->getOption('remove-originals')) {
                $this->filesystem->remove($file->getPathname());
            }
        }

        $io->success(sprintf('%d art(s) converti(s) : %.1f Mo de PNG → %.1f Mo de WebP.', $converted, $before / 1048576, $after / 1048576));

        return Command::SUCCESS;
    }

    private function scaled(\GdImage $source, int $maxHeight): \GdImage
    {
        $height = imagesy($source);
        $image = $height > $maxHeight ? imagescale($source, (int) round(imagesx($source) * $maxHeight / $height), $maxHeight) : $source;
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }
}
