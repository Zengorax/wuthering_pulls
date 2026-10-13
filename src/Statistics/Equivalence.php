<?php

namespace App\Statistics;

/**
 * Équivalence de fin de version, saisie sous la forme « S9R8 ».
 */
final readonly class Equivalence implements \Stringable
{
    public const PATTERN = '/^S(\d{1,2})R(\d{1,2})$/i';

    public function __construct(
        public int $sets,
        public int $refines,
    ) {
    }

    public static function tryFromString(?string $value): ?self
    {
        if (null === $value || 1 !== preg_match(self::PATTERN, trim($value), $matches)) {
            return null;
        }

        return new self((int) $matches[1], (int) $matches[2]);
    }

    public function __toString(): string
    {
        return sprintf('S%dR%d', $this->sets, $this->refines);
    }
}
