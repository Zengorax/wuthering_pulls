<?php

namespace App\Statistics;

/**
 * Règles de la bannière utilisées par les statistiques.
 *
 * Les valeurs de chance sont des approximations de la communauté, à ajuster si le jeu évolue.
 */
final class PullRules
{
    public const ASTRITES_PER_PULL = 160;

    /** Pulls maximum pour obtenir un 5★ (pitié garantie). */
    public const HARD_PITY = 80;

    /** Début approximatif de la pitié douce (taux qui grimpe). */
    public const SOFT_PITY = 66;

    /** Nombre moyen approximatif de pulls pour un 5★ quelconque. */
    public const AVERAGE_PULLS_PER_FIVE_STAR = 62.5;

    /** Gain moyen de 5★ par cible : 50 % de chances de gagner le 50/50, sinon un 2e 5★ garanti. */
    public const FIVE_STARS_PER_FEATURED = 1.5;

    /** Taille des classes de l'histogramme de distribution. */
    public const DISTRIBUTION_STEP = 20;

    private function __construct()
    {
    }

    public static function expectedPullsForFeaturedCharacter(): float
    {
        return self::AVERAGE_PULLS_PER_FIVE_STAR * self::FIVE_STARS_PER_FEATURED;
    }

    public static function astritesToPulls(int|float $astrites): float
    {
        return $astrites / self::ASTRITES_PER_PULL;
    }
}
