<?php

namespace App\Twig;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Attribute\AsTwigFunction;

/**
 * Images des personnages, trouvées par le nom (« Xiangli Yao » → `xiangli-yao`) :
 *  - `assets/characters/art/<nom>.<ext>` : art haute définition (animation de pull) ;
 *  - `assets/characters/thumb/<nom>.<ext>` : vignette légère de l'art (cartes d'historique) ;
 *  - `assets/characters/<nom>.<ext>` : petite icône, utilisée en secours quand il n'y a pas d'art.
 * Sans image, l'interface garde son décor par défaut.
 */
final class PortraitExtension
{
    private const EXTENSIONS = ['webp', 'avif', 'png', 'jpg', 'jpeg'];

    /** @var array<string, ?string> */
    private array $cache = [];

    public function __construct(private readonly AssetMapperInterface $assetMapper)
    {
    }

    public static function slug(string $name): string
    {
        return (string) (new AsciiSlugger())->slug($name)->lower();
    }

    /** Petite icône du personnage. */
    #[AsTwigFunction('portrait')]
    public function portrait(?string $name): ?string
    {
        return $this->lookup($name, 'characters/%s.%s');
    }

    /** Vignette légère de l'art du personnage (repli : l'art complet). */
    #[AsTwigFunction('art_thumb')]
    public function artThumb(?string $name): ?string
    {
        return $this->lookup($name, 'characters/thumb/%s.%s') ?? $this->art($name);
    }

    /** Art haute définition du personnage. */
    #[AsTwigFunction('art')]
    public function art(?string $name): ?string
    {
        return $this->lookup($name, 'characters/art/%s.%s');
    }

    private function lookup(?string $name, string $pattern): ?string
    {
        if (null === $name || '' === trim($name)) {
            return null;
        }

        $slug = self::slug($name);
        $key = $pattern.'|'.$slug;

        return $this->cache[$key] ??= $this->find($slug, $pattern);
    }

    private function find(string $slug, string $pattern): ?string
    {
        if ('' === $slug) {
            return null;
        }

        foreach (self::EXTENSIONS as $extension) {
            if (null !== $path = $this->assetMapper->getPublicPath(sprintf($pattern, $slug, $extension))) {
                return $path;
            }
        }

        return null;
    }
}
